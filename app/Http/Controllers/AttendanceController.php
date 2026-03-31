<?php

namespace App\Http\Controllers;

use App\Helper\NotificationHelper;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $today = now()->toDateString();

        // ── Personal check-in status ────────────────────────────────
        $myAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $myOnLeaveToday = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->exists();

        // ── Team / all-company stats (requires module attendance) ───
        $attendanceUsers = collect();
        $counts          = [];
        $canSeeStats     = $user->can('module attendance');

        if ($canSeeStats) {
            $scopedUserIds = $this->_scopedUserIds($user);

            $todayAttendances = Attendance::whereDate('date', $today)
                ->whereIn('user_id', $scopedUserIds)
                ->get()
                ->keyBy('user_id');

            $onLeaveIds = LeaveRequest::where('status', 'approved')
                ->whereDate('start_at', '<=', $today)
                ->whereDate('end_at', '>=', $today)
                ->whereIn('user_id', $scopedUserIds)
                ->pluck('user_id')
                ->flip();

            $attendanceUsers = User::whereIn('id', $scopedUserIds)
                ->orderBy('name')
                ->get()
                ->map(function ($u) use ($todayAttendances, $onLeaveIds) {
                    $att = $todayAttendances->get($u->id);

                    if ($att) {
                        $category = match (true) {
                            $att->status === 'approved' && $att->type === 'on_site' => 'on_site',
                            $att->status === 'approved' && $att->type === 'wfh'     => 'wfh',
                            $att->status === 'pending'                               => 'wfh_pending',
                            default                                                  => 'not_checked_in',
                        };
                    } elseif ($onLeaveIds->has($u->id)) {
                        $category = 'on_leave';
                    } else {
                        $category = 'not_checked_in';
                    }

                    return [
                        'id'              => $u->id,
                        'name'            => $u->name,
                        'position'        => $u->position,
                        'profile_picture' => $u->profile_picture,
                        'category'        => $category,
                        'att_type'        => $att?->type,
                        'att_id'          => $att?->id,
                        'can_approve'     => $att && $att->status === 'pending'
                                            ? $this->_canApprove(auth()->user(), $att)
                                            : false,
                    ];
                });

            $counts = [
                'all'           => $attendanceUsers->count(),
                'on_site'       => $attendanceUsers->where('category', 'on_site')->count(),
                'wfh'           => $attendanceUsers->where('category', 'wfh')->count(),
                'on_leave'      => $attendanceUsers->where('category', 'on_leave')->count(),
                'wfh_pending'   => $attendanceUsers->where('category', 'wfh_pending')->count(),
                'not_checked_in'=> $attendanceUsers->where('category', 'not_checked_in')->count(),
            ];
        }

        $officeLat      = AppSetting::get('office_latitude');
        $officeLng      = AppSetting::get('office_longitude');
        $officeRadiusKm = AppSetting::get('office_radius_km', 2);

        return view('attendance.index', compact(
            'myAttendance', 'myOnLeaveToday',
            'attendanceUsers', 'counts', 'canSeeStats', 'today',
            'officeLat', 'officeLng', 'officeRadiusKm'
        ));
    }

    public function store(Request $request)
    {
        $user  = auth()->user();
        $today = now()->toDateString();

        // Block if already checked in
        if (Attendance::where('user_id', $user->id)->whereDate('date', $today)->exists()) {
            return back()->with('error', 'You have already checked in today.');
        }

        $type = $request->input('type');

        if ($request->type === 'on_site') {
            $savedIps = AppSetting::get('office_ips', '');

            if (!empty(trim($savedIps))) {
                $allowedIps = array_map('trim', explode(',', $savedIps));
                $clientIp   = $_SERVER['HTTP_CF_CONNECTING_IP'];

                if (!in_array($clientIp, $allowedIps)) {
                    return back()->withErrors([
                        'attendance' => 'On-Site check-in is only available on the company Wi-Fi. '
                            . 'Your current IP (' . $clientIp . ') is not recognised. '
                            . 'Please connect to the office network or use WFH instead.',
                    ]);
                }
            }

            Attendance::create([
                'user_id'     => $user->id,
                'date'        => $today,
                'type'        => 'on_site',
                'status'      => 'approved',
                'hours'       => 8,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return back()->with('success', 'On-Site attendance recorded.');
        }

        // WFH
        $request->validate([
            'hours'  => 'required|numeric|min:0.5|max:24',
            'reason' => 'required|string|max:500',
        ]);

        // Auto-approve if flagged
        $autoApprove = $user->wfh_without_approval;

        $attendance = Attendance::create([
            'user_id'     => $user->id,
            'date'        => $today,
            'type'        => 'wfh',
            'status'      => $autoApprove ? 'approved' : 'pending',
            'hours'       => $request->hours,
            'reason'      => $request->reason,
            'approved_by' => $autoApprove ? $user->id : null,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        if (!$autoApprove) {
            $approvers = $this->_findApprovers($user);
            foreach ($approvers as $approver) {
                NotificationHelper::send(
                    receivingUser: $approver,
                    title: 'WFH Request — ' . $user->name,
                    description: $user->name . ' submitted a WFH request for ' . now()->format('d/m/Y')
                        . ' (' . $request->hours . 'h): ' . \Str::limit($request->reason, 60),
                    url: route('attendance.index'),
                    incomingUser: $user,
                );
            }
            return back()->with('success', 'WFH request submitted and pending approval.');
        }

        return back()->with('success', 'WFH attendance recorded.');
    }

    public function approve(Attendance $attendance)
    {
        if (!$this->_canApprove(auth()->user(), $attendance)) abort(403);

        $attendance->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        NotificationHelper::send(
            receivingUser: $attendance->user,
            title: 'WFH Approved',
            description: 'Your WFH request for ' . $attendance->date->format('d/m/Y') . ' has been approved.',
            url: route('attendance.index'),
            incomingUser: auth()->user(),
        );

        return back()->with('success', 'WFH request approved.');
    }

    public function reject(Request $request, Attendance $attendance)
    {
        if (!$this->_canApprove(auth()->user(), $attendance)) abort(403);

        $request->validate(['reject_reason' => 'required|string|max:300']);

        $attendance->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reject_reason,
        ]);

        NotificationHelper::send(
            receivingUser: $attendance->user,
            title: 'WFH Rejected',
            description: 'Your WFH request for ' . $attendance->date->format('d/m/Y')
                . ' was rejected. Reason: ' . $request->reject_reason,
            url: route('attendance.index'),
            incomingUser: auth()->user(),
        );

        return back()->with('success', 'WFH request rejected.');
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Find who should receive the WFH notification for $user.
     * Priority: team leaders → supervisors → nobody.
     */
    private function _findApprovers(User $user): \Illuminate\Support\Collection
    {
        // Leaders of teams the user belongs to (where user is NOT the leader)
        $leaderIds = DB::table('team_user as tu_member')
            ->join('team_user as tu_leader', 'tu_member.team_id', '=', 'tu_leader.team_id')
            ->where('tu_member.user_id', $user->id)
            ->where('tu_member.is_leader', false)
            ->where('tu_leader.is_leader', true)
            ->where('tu_leader.user_id', '!=', $user->id)
            ->pluck('tu_leader.user_id')
            ->unique();

        if ($leaderIds->isNotEmpty()) {
            return User::whereIn('id', $leaderIds)->get();
        }

        // Fall back to supervisors
        return $user->supervisors()->get();
    }

    /**
     * Returns the IDs of users this viewer can see.
     * view all attendance → everyone active
     * else               → team-mates + subordinates + self
     */
    private function _scopedUserIds(User $user): \Illuminate\Support\Collection
    {
        if ($user->can('view all attendance')) {
            return User::where('is_active', true)->pluck('id');
        }

        $ids = collect([$user->id]);
        $ids = $ids->merge($user->teamMembers()->pluck('id'));
        $ids = $ids->merge($user->subordinates()->pluck('users.id'));

        return User::whereIn('id', $ids->unique()->toArray())
            ->where('is_active', true)
            ->pluck('id');
    }

    /**
     * Check whether $actor can approve/reject $attendance.
     */
    private function _canApprove(User $actor, Attendance $attendance): bool
    {
        if ($actor->can('approve attendance')) {
            return true;
        }

        // Is a supervisor of the requester?
        if ($actor->subordinates()->where('users.id', $attendance->user_id)->exists()) {
            return true;
        }

        // Is a team leader of the requester?
        return $actor->teams()
            ->wherePivot('is_leader', true)
            ->whereHas('users', fn($q) => $q->where('users.id', $attendance->user_id))
            ->exists();
    }
}

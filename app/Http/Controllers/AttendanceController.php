<?php

namespace App\Http\Controllers;

use App\Helper\NotificationHelper;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Team;
use App\Models\User;
use App\Models\AppSetting;
use Carbon\Carbon;
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
                'user_id'        => $user->id,
                'date'           => $today,
                'type'           => 'on_site',
                'check_in_time'  => now()->format('H:i:s'),
                'status'         => 'approved',
                'hours'          => 8,
                'approved_by'    => $user->id,
                'approved_at'    => now(),
                'created_by'     => $user->id,
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
            'user_id'        => $user->id,
            'date'           => $today,
            'type'           => 'wfh',
            'check_in_time'  => now()->format('H:i:s'),
            'status'         => $autoApprove ? 'approved' : 'pending',
            'hours'          => $request->hours,
            'reason'         => $request->reason,
            'approved_by'    => $autoApprove ? $user->id : null,
            'approved_at'    => $autoApprove ? now() : null,
            'created_by'     => $user->id,
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

    public function checkOut(Request $request)
    {
        $user  = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return back()->with('error', 'Không tìm thấy dữ liệu chấm công hoặc bạn đã check-out rồi.');
        }

        if ($attendance->status !== 'approved') {
            return back()->with('error', 'Không thể check-out khi WFH chưa được phê duyệt.');
        }

        $checkOutTime = now()->format('H:i:s');

        // ── Calculate actual work hours ──────────────────────────────────
        $lunchStart = AppSetting::get('lunch_break_start', '12:00');
        $lunchEnd   = AppSetting::get('lunch_break_end',   '13:00');

        $checkInMins  = $this->_timeToMinutes($attendance->check_in_time ?: $attendance->created_at->format('H:i:s'));
        $checkOutMins = $this->_timeToMinutes($checkOutTime);
        $lunchSMins   = $this->_timeToMinutes($lunchStart);
        $lunchEMins   = $this->_timeToMinutes($lunchEnd);

        $totalMins    = max(0, $checkOutMins - $checkInMins);
        $overlapStart = max($checkInMins, $lunchSMins);
        $overlapEnd   = min($checkOutMins, $lunchEMins);
        $lunchMins    = max(0, $overlapEnd - $overlapStart);
        $actualHours  = round(($totalMins - $lunchMins) / 60, 2);

        $attendance->update([
            'check_out_time'    => $checkOutTime,
            'actual_work_hours' => max(0, $actualHours),
        ]);

        return back()->with('success', 'Đã check-out. Giờ làm thực tế: ' . max(0, $actualHours) . 'h');
    }

    public function list(Request $request)
    {
        $user = auth()->user();

        if (!$user->can('module attendance')) abort(403);

        // ── Month ────────────────────────────────────────────────────────
        $monthStr = $request->input('month', now()->format('Y-m'));
        try {
            $month = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
            $monthStr = $month->format('Y-m');
        }
        $monthEnd = $month->copy()->endOfMonth();

        // ── Scope users ──────────────────────────────────────────────────
        $scopedUserIds = $this->_scopedUserIds($user);

        // Teams available to this viewer
        $teams = Team::whereHas('users', fn($q) => $q->whereIn('users.id', $scopedUserIds->toArray()))
            ->orderBy('name')->get();

        $selectedTeamId = $request->input('team_id');

        if ($selectedTeamId) {
            $memberIds = Team::findOrFail($selectedTeamId)
                ->users()
                ->whereIn('users.id', $scopedUserIds->toArray())
                ->pluck('users.id');
        } else {
            $memberIds = $scopedUserIds;
        }

        $members = User::whereIn('id', $memberIds)->orderBy('name')->get();

        // ── Attendances ──────────────────────────────────────────────────
        // Key: "{user_id}_{Y-m-d}"
        $attendances = Attendance::whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()])
            ->whereIn('user_id', $memberIds)
            ->get()
            ->keyBy(fn($a) => $a->user_id . '_' . $a->date->format('Y-m-d'));

        // ── Approved leaves ──────────────────────────────────────────────
        $leaveRows = LeaveRequest::where('status', 'approved')
            ->whereIn('user_id', $memberIds)
            ->where('start_at', '<=', $monthEnd->toDateTimeString())
            ->where('end_at', '>=', $month->toDateTimeString())
            ->get();

        $leavesByDay = [];
        foreach ($leaveRows as $leave) {
            $cursor = Carbon::parse($leave->start_at)->startOfDay();
            $end    = Carbon::parse($leave->end_at)->startOfDay();
            while ($cursor->lte($end)) {
                $dk = $cursor->format('Y-m-d');
                if ($dk >= $month->toDateString() && $dk <= $monthEnd->toDateString()) {
                    $leavesByDay[$leave->user_id . '_' . $dk] = $leave;
                }
                $cursor->addDay();
            }
        }

        // ── Holidays ─────────────────────────────────────────────────────
        $holidayDates = PublicHoliday::getHolidayDates($month, $monthEnd);

        // ── Misc ─────────────────────────────────────────────────────────
        $today                = now()->toDateString();
        $daysInMonth          = (int) $month->daysInMonth;
        $canCheckinForOther   = $user->can('checkin for other user');
        $allUsers             = User::whereIn('id', $scopedUserIds)->orderBy('name')->get();

        return view('attendance.list', compact(
            'members', 'attendances', 'leavesByDay', 'holidayDates',
            'month', 'monthEnd', 'daysInMonth', 'today',
            'teams', 'selectedTeamId', 'monthStr',
            'canCheckinForOther', 'allUsers'
        ));
    }

    public function checkinForUser(Request $request)
    {
        if (!auth()->user()->can('checkin for other user')) abort(403);

        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'type'          => 'required|in:on_site,wfh',
            'date'          => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
        ]);

        if (Attendance::where('user_id', $request->user_id)
                      ->whereDate('date', $request->date)
                      ->exists()) {
            return back()->with('error', 'Người dùng này đã có dữ liệu chấm công trong ngày đó.');
        }

        Attendance::create([
            'user_id'       => $request->user_id,
            'date'          => $request->date,
            'type'          => $request->type,
            'check_in_time' => $request->check_in_time . ':00',
            'status'        => 'approved',
            'hours'         => 8,
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
            'created_by'    => auth()->id(),
        ]);

        return back()->with('success', 'Đã ghi nhận chấm công thành công.');
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

    /** Convert "HH:MM" or "HH:MM:SS" string to minutes from midnight. */
    private function _timeToMinutes(string $timeStr): int
    {
        $parts = explode(':', $timeStr);
        return (int) ($parts[0] ?? 0) * 60 + (int) ($parts[1] ?? 0);
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

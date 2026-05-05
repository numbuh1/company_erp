<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\LeaveBalanceLog;
use App\Helper\Helper;
use App\Helper\NotificationHelper;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = LeaveRequest::with('user', 'approver');

        if ($user->can('view all leaves')) {
            // no scope
        } elseif ($user->can('view team leaves')) {
            $query->whereIn('user_id', $user->teamMembers()->pluck('id'));
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $leaveRequests = $query->latest()->paginate(20)->withQueryString();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to',   '');
        $status   = $request->input('status',   'all');

        return view('leave_requests.index', compact('leaveRequests', 'dateFrom', 'dateTo', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        if ($user->can('edit all leaves')) {
            $users = User::orderBy('name')->get();
        } elseif ($user->can('edit team leaves')) {
            $ledTeamIds = $user->teams()->wherePivot('is_leader', true)->pluck('teams.id');
            if ($ledTeamIds->isEmpty()) {
                // Has permission but leads no team — can only create for self
                $users = collect([$user]);
            } else {
                $memberIds = \App\Models\Team::whereIn('id', $ledTeamIds)
                    ->with('users')
                    ->get()
                    ->flatMap->users
                    ->pluck('id')
                    ->unique();
                $users = User::whereIn('id', $memberIds)->orderBy('name')->get();
            }
        } elseif ($user->can('edit own leaves')) {
            $users = collect([$user]);
        } else {
            abort(403);
        }

        return view('leave_requests.form', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->can('edit all leaves') && !$user->can('edit team leaves') && !$user->can('edit own leaves')) {
            abort(403);
        }

        $request->validate([
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after_or_equal:start_at',
            'type'        => 'required|string',
            'description' => 'nullable|string',
        ]);

        $requestedUserId = $request->user_id;

        if ($user->can('edit all leaves')) {
            // Any user_id allowed — keep as-is
        } elseif ($user->can('edit team leaves') && $requestedUserId && $requestedUserId != $user->id) {
            // Creating for someone else — must be their team leader
            $targetUser = User::findOrFail($requestedUserId);
            if (!Helper::checkLeadOfTeamMate($targetUser)) {
                $request->merge(['user_id' => $user->id]);
            }
        } else {
            $request->merge(['user_id' => $user->id]);
        }

        LeaveRequest::create($request->all());

        return redirect()->route('leave-requests.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('view all leaves', 'view team leaves', $leaveRequest);

        $user_show = User::where('id', $leaveRequest->user_id)->get();

        return view('leave_requests.form', [
            'leave' => $leaveRequest,
            'readonly' => true,
            'users' => $user_show
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        if (in_array($leaveRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot edit an approved or rejected leave request.');
        }

        if ($user->can('edit all leaves')) {
            // allowed
        } elseif ($user->can('edit team leaves')) {
            if ($leaveRequest->user_id !== $user->id) {
                if (!Helper::checkLeadOfTeamMate($leaveRequest->user)) abort(403);
            }
        } elseif ($user->can('edit own leaves')) {
            if ($leaveRequest->user_id !== $user->id) abort(403);
        } else {
            abort(403);
        }

        return view('leave_requests.form', [
            'leave' => $leaveRequest,
            'users' => collect([$leaveRequest->user]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();

        if ($user->can('edit all leaves')) {
            // allowed
        } elseif ($user->can('edit team leaves')) {
            if ($leaveRequest->user_id !== $user->id) {
                if (!Helper::checkLeadOfTeamMate($leaveRequest->user)) abort(403);
            }
        } elseif ($user->can('edit own leaves')) {
            if ($leaveRequest->user_id !== $user->id) abort(403);
        } else {
            abort(403);
        }

        $data = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'type'        => 'required|string',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'hours'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $leaveRequest->update($data);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        if (in_array($leaveRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot delete an approved or rejected leave request.');
        }

    }

    /**
     * Approve Leave Request
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all leaves', 'approve team leaves', $leaveRequest);

        // If annual leave => Deduct leave balance and log it
        if ($leaveRequest->type == 'annual') {
            $leaveUser = $leaveRequest->user;
            $old = $leaveUser->leave_balance;
            $new = $old - $leaveRequest->hours;
            $leaveUser->update(['leave_balance' => $new]);

            LeaveBalanceLog::create([
                'user_id'       => $leaveUser->id,
                'changed_by'    => auth()->id(),
                'change_hours'  => -$leaveRequest->hours,
                'balance_after' => $new,
                'reason'        => 'Leave approved: #' . $leaveRequest->id,
            ]);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approver_by' => auth()->id(),
            'reject_reason' => null
        ]);

        NotificationHelper::sendRequestApprovalNotification($leaveRequest, 'leave');

        // NotificationHelper::send(
        //     receivingUser: $leaveRequest->user,
        //     title: 'Leave Request Approved',
        //     description: 'Your ' . $leaveRequest->type . ' leave request (' .
        //         $leaveRequest->start_at->format('d/m/Y') . ' – ' .
        //         $leaveRequest->end_at->format('d/m/Y') . ') has been approved.',
        //     url: route('leave-requests.index'),
        //     incomingUser: auth()->user(),
        // );

        return back()->with('success', 'Leave approved.');
    }


    /**
     * Reject Leave Request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all leaves', 'approve team leaves', $leaveRequest);

        $data = $request->validate([
            'reject_reason' => 'required|string|max:500'
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approver_by' => auth()->id(),
            'reject_reason' => $data['reject_reason']
        ]);

        // NotificationHelper::send(
        //     receivingUser: $leaveRequest->user,
        //     title: 'Leave Request Rejected',
        //     description: 'Your ' . $leaveRequest->type . ' leave request (' .
        //         $leaveRequest->start_at->format('d/m/Y') . ' – ' .
        //         $leaveRequest->end_at->format('d/m/Y') . ') has been rejected.',
        //     url: route('leave-requests.index'),
        //     incomingUser: auth()->user(),
        // );

        NotificationHelper::sendRequestApprovalNotification($leaveRequest, 'leave');

        return back()->with('success', 'Leave rejected.');
    }

    // public function authorize(string $all_permission, string $team_permission, $leaveRequest) {
    //     $user = auth()->user();

    //     if (!$user->can($all_permission)) {
    //         if(!$user->can($team_permission)) {
    //             return abort(403);
    //         }

    //         $check_leader = Helper::checkLeadOfTeamMate($leaveRequest->user);
    //         if(!$check_leader) {
    //             return abort(403);
    //         }
    //     }

    //     return true;
    // }
}

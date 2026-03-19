<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\LeaveBalanceLog;
use Helper\Helper;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $query = LeaveRequest::with('user', 'approver');

        // 👇 View team leaves
        if ($user->can('view all leaves')) {
            // No condition
        } else if ($user->can('view team leaves')) {
            $teamUserIds = $user->teamMembers()->pluck('id');
            $query->whereIn('user_id', $teamUserIds);
        } else {
            // Only own requests
            $query->where('user_id', $user->id);
        }

        $leaveRequests = $query->latest()->paginate(10);

        return view('leave_requests.index', compact('leaveRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        $query = User::query();

        if ($user->can('view all leaves')) {
           // No filter
        } elseif ($user->can('view team leaves')) {
            $teamUserIds = $user->teamMembers()->pluck('id');
            $query->whereIn('id', $teamUserIds);
        } else {
            $query->where('id', $user->id);
        }

        $users = $query->get();

        return view('leave_requests.form', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'start_at' => 'required|date',
            'end_at' => 'required|date|after_or_equal:start_at',
            'type' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $user = auth()->user();

        // 👇 If no permission, can only create own
        if (!$user->can('edit team leaves') && !$user->can('edit all leaves')) {
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
        $this->authorize('view all leaves', 'view team leaves');

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
        if (in_array($leaveRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot edit an approved or rejected leave request.');
        }

        $this->authorize('edit all leaves', 'edit team leaves');

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
        $this->authorize('edit all leaves', 'edit team leaves');

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'hours' => 'required|numeric|min:0',
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
        $this->authorize('approve all leaves', 'approve team leaves');

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

        return back()->with('success', 'Leave approved.');
    }


    /**
     * Reject Leave Request
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $this->authorize('approve all leaves', 'approve team leaves');

        $data = $request->validate([
            'reject_reason' => 'required|string|max:500'
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'approver_by' => auth()->id(),
            'reject_reason' => $data['reject_reason']
        ]);

        return back()->with('success', 'Leave rejected.');
    }

    public function authorize(string $all_permission, string $team_permission) {
        $user = auth()->user();

        if (!$user->can($all_permission)) {
            if(!$user->can($team_permission)) {
                return abort(403);
            }

            $check_leader = Helper::checkLeadOfTeamMate($leaveRequest->user);
            if(!$check_leader) {
                return abort(403);
            }
        }

        return true;
    }
}

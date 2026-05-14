<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Helper\Helper;
use App\Helper\NotificationHelper;
use Illuminate\Http\Request;

class OvertimeRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = OvertimeRequest::with('user', 'approver', 'project', 'task');

        if ($user->can('view all ot')) {
            // no filter
        } elseif ($user->can('view team ot')) {
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

        $otRequests = $query->latest()->paginate(20)->withQueryString();

        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to',   '');
        $status   = $request->input('status',   'all');

        return view('overtime_requests.index', compact('otRequests', 'dateFrom', 'dateTo', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user  = auth()->user();
        $query = User::query();

        if ($user->can('edit all ot')) {
            // no filter
        } elseif ($user->can('edit team ot')) {
            $query->whereIn('id', $user->teamMembers()->pluck('id'));
        } else {
            $query->where('id', $user->id);
        }

        $users = $query->get();
        ['projects' => $projects, 'tasks' => $tasks] = $this->_getProjectsAndTasksFor($user);

        return view('overtime_requests.form', compact('users', 'projects', 'tasks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after_or_equal:start_at',
            'hours'       => 'required|numeric|min:0',
            'type'        => 'required|string',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'task_id'     => 'nullable|exists:tasks,id',
        ]);

        $user = auth()->user();
        if (!$user->can('edit team ot') && !$user->can('edit all ot')) {
            $request->merge(['user_id' => $user->id]);
        }

        OvertimeRequest::create($request->only([
            'user_id', 'project_id', 'task_id',
            'start_at', 'end_at', 'hours', 'type', 'description',
        ]));

        return redirect()->route('requests.index', ['type' => 'ot'])->with('success', 'Tạo yêu cầu tăng ca thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OvertimeRequest $overtimeRequest)
    {
        Helper::authorizeRequest('view all ot', 'view team ot', $overtimeRequest);
        $overtimeRequest->load('project', 'task');

        return view('overtime_requests.form', [
            'ot'       => $overtimeRequest,
            'readonly' => true,
            'users'    => collect([$overtimeRequest->user]),
            'projects' => collect(),
            'tasks'    => collect(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OvertimeRequest $overtimeRequest)
    {
        if (in_array($overtimeRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot edit an approved or rejected OT request.');
        }

        Helper::authorizeRequest('edit all ot', 'edit team ot', $overtimeRequest);
        $overtimeRequest->load('user');

        ['projects' => $projects, 'tasks' => $tasks] = $this->_getProjectsAndTasksFor($overtimeRequest->user);

        return view('overtime_requests.form', [
            'ot'       => $overtimeRequest,
            'users'    => collect([$overtimeRequest->user]),
            'projects' => $projects,
            'tasks'    => $tasks,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeRequest $overtimeRequest)
    {
        Helper::authorizeRequest('edit all ot', 'edit team ot', $overtimeRequest);

        $data = $request->validate([
            'user_id'     => 'required|exists:users,id',
            'type'        => 'required|string',
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'hours'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'task_id'     => 'nullable|exists:tasks,id',
        ]);

        $overtimeRequest->update($data);

        return redirect()->route('requests.index', ['type' => 'ot'])->with('success', 'Cập nhật yêu cầu tăng ca thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OvertimeRequest $overtimeRequest)
    {
        if (in_array($overtimeRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot delete an approved or rejected OT request.');
        }

        Helper::authorizeRequest('delete all ot', 'delete team ot', $overtimeRequest);

        $overtimeRequest->delete();
        return back()->with('success', 'OT request deleted.');
    }

    /**
     * Approve a pending Overtime Request
     */
    public function approve(OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all ot', 'approve team ot', $overtimeRequest);

        $overtimeRequest->update([
            'status'        => 'approved',
            'approved_by'   => $user->id,
            'reject_reason' => null,
        ]);

        NotificationHelper::sendRequestApprovalNotification($overtimeRequest, 'ot');

        return back()->with('success', 'OT request approved.');
    }

    /**
     * Reject a pending Overtime Request (With reason)
     */
    public function reject(Request $request, OvertimeRequest $overtimeRequest)
    {
        $user = auth()->user();
        Helper::authorizeRequest('approve all ot', 'approve team ot', $overtimeRequest);

        $data = $request->validate(['reject_reason' => 'required|string|max:500']);

        $overtimeRequest->update([
            'status'        => 'rejected',
            'approved_by'   => $user->id,
            'reject_reason' => $data['reject_reason'],
        ]);

        NotificationHelper::sendRequestApprovalNotification($overtimeRequest, 'ot');

        return back()->with('success', 'OT request rejected.');
    }

    /**
     * Return projects and tasks assigned to the given user.
     */
    private function _getProjectsAndTasksFor(User $user): array
    {
        $userId = $user->id;

        $projects = Project::where(function ($q) use ($userId) {
            $q->whereHas('users', fn($q2) => $q2->where('users.id', $userId))
              ->orWhereHas('teams', fn($q2) => $q2->whereHas('users', fn($q3) => $q3->where('users.id', $userId)));
        })->orderBy('name')->get(['id', 'name']);

        $tasks = Task::whereHas('assignees', fn($q) => $q->where('users.id', $userId))
            ->orderBy('name')
            ->get(['id', 'name', 'project_id']);

        return compact('projects', 'tasks');
    }
}

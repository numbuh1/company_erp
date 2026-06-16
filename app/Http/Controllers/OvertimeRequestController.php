<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Models\PublicHoliday;
use App\Models\Task;
use App\Models\User;
use App\Helper\Helper;
use App\Helper\NotificationHelper;
use Carbon\Carbon;
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

        $users        = $query->get();
        ['projects' => $projects, 'tasks' => $tasks] = $this->_getProjectsAndTasksFor($user);
        $holidayDates = $this->_holidayDateRange();

        return view('overtime_requests.form', compact('users', 'projects', 'tasks', 'holidayDates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'ot_date'     => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'hours'       => 'required|numeric|min:0.25',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'task_id'     => 'nullable|exists:tasks,id',
        ]);

        if ($this->_timeToMins($data['end_time']) <= $this->_timeToMins($data['start_time'])) {
            return back()->withErrors(['end_time' => 'Giờ kết thúc phải sau giờ bắt đầu.'])->withInput();
        }

        $user   = auth()->user();
        $userId = ($user->can('edit team ot') || $user->can('edit all ot'))
            ? ($request->user_id ?: $user->id)
            : $user->id;

        $otRequest = OvertimeRequest::create([
            'user_id'     => $userId,
            'project_id'  => $data['project_id'] ?: null,
            'task_id'     => $data['task_id']     ?: null,
            'start_at'    => $data['ot_date'] . ' ' . $data['start_time'],
            'end_at'      => $data['ot_date'] . ' ' . $data['end_time'],
            'hours'       => $data['hours'],
            'type'        => $this->_determineOtType($data['ot_date']),
            'description' => $data['description'] ?? null,
        ]);
        NotificationHelper::sendNewRequestNotification($otRequest, 'ot');

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
            'ot'           => $overtimeRequest,
            'readonly'     => true,
            'users'        => collect([$overtimeRequest->user]),
            'projects'     => collect(),
            'tasks'        => collect(),
            'holidayDates' => [],
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
            'ot'           => $overtimeRequest,
            'users'        => collect([$overtimeRequest->user]),
            'projects'     => $projects,
            'tasks'        => $tasks,
            'holidayDates' => $this->_holidayDateRange(),
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
            'ot_date'     => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'hours'       => 'required|numeric|min:0.25',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'task_id'     => 'nullable|exists:tasks,id',
        ]);

        if ($this->_timeToMins($data['end_time']) <= $this->_timeToMins($data['start_time'])) {
            return back()->withErrors(['end_time' => 'Giờ kết thúc phải sau giờ bắt đầu.'])->withInput();
        }

        $overtimeRequest->update([
            'user_id'     => $data['user_id'],
            'project_id'  => $data['project_id'] ?: null,
            'task_id'     => $data['task_id']     ?: null,
            'start_at'    => $data['ot_date'] . ' ' . $data['start_time'],
            'end_at'      => $data['ot_date'] . ' ' . $data['end_time'],
            'hours'       => $data['hours'],
            'type'        => $this->_determineOtType($data['ot_date']),
            'description' => $data['description'] ?? null,
        ]);

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
     * Determine OT type based on date: holiday → x3, Sunday → x2, else → x1.5
     */
    private function _determineOtType(string $date): string
    {
        $carbon   = Carbon::parse($date);
        $holidays = PublicHoliday::getHolidayDates($carbon->copy(), $carbon->copy());
        if (!empty($holidays)) return 'OT x3';
        if ($carbon->isSunday()) return 'OT x2';
        return 'OT x1.5';
    }

    /**
     * Convert "HH:MM" to total minutes.
     */
    private function _timeToMins(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return $h * 60 + $m;
    }

    /**
     * Holiday dates for a ~3-year window (past year → next 2 years).
     */
    private function _holidayDateRange(): array
    {
        return PublicHoliday::getHolidayDates(
            Carbon::now()->subYear(),
            Carbon::now()->addYears(2)
        );
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
        })->orderBy('name')->get(['id', 'name', 'project_code']);

        $tasks = Task::whereHas('assignees', fn($q) => $q->where('users.id', $userId))
            ->orderBy('name')
            ->get(['id', 'name', 'project_id', 'task_code']);

        return compact('projects', 'tasks');
    }
}

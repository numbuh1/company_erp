<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Helper\NotificationHelper;
use App\Models\User;
use App\Models\WfhRequest;
use App\Support\Assignments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WfhRequestController extends Controller
{
    public function index()
    {
        return redirect()->route('requests.index', ['type' => 'wfh']);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->canAny(['edit own wfh', 'edit team wfh', 'edit all wfh'])) abort(403);

        $data   = $this->_validate($request);
        $userId = $this->_targetUserId($request, $user);

        [$start, $end, $hours] = $this->_resolveSpan($data);
        $this->_assertNoOverlap($userId, $start, $end);

        $wfh = WfhRequest::create([
            'user_id'     => $userId,
            'project_id'  => $data['project_id'] ?? null,
            'task_id'     => $data['task_id'] ?? null,
            'start_at'    => $start,
            'end_at'      => $end,
            'hours'       => $hours,
            'description' => $data['description'] ?? null,
        ]);
        NotificationHelper::sendNewRequestNotification($wfh->load('project', 'task'), 'wfh');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $wfh->id]);
        }

        return redirect()->route('requests.index', ['type' => 'wfh'])->with('success', __('WFH request created.'));
    }

    public function show(WfhRequest $wfhRequest)
    {
        $this->_authorize($wfhRequest, 'view all wfh', 'view team wfh', 'view own wfh');
        $wfhRequest->load('user', 'approver', 'project', 'task');
        ['projects' => $projects, 'tasks' => $tasks] = Assignments::projectsAndTasksFor($wfhRequest->user);

        $user     = auth()->user();
        $isFinal  = in_array($wfhRequest->status, ['approved', 'rejected']);
        $canEdit  = !$isFinal && ($user->canAny(['edit all wfh', 'edit team wfh'])
            || ($user->can('edit own wfh') && $wfhRequest->user_id === $user->id));
        $canApprove = $wfhRequest->status === 'pending' && $user->canAny(['approve all wfh', 'approve team wfh']);

        return response()->json([
            'wfh' => [
                'id'            => $wfhRequest->id,
                'user_id'       => $wfhRequest->user_id,
                'user_name'     => $wfhRequest->user->name,
                'status'        => $wfhRequest->status,
                'hours'         => $wfhRequest->hours,
                'description'   => $wfhRequest->description,
                'reject_reason' => $wfhRequest->reject_reason,
                'approver_name' => $wfhRequest->approver?->name,
                'start_at_input' => $wfhRequest->start_at->format('Y-m-d\TH:i'),
                'end_at_input'   => $wfhRequest->end_at->format('Y-m-d\TH:i'),
                'start_at_text'  => $wfhRequest->start_at->translatedFormat('D, d/m/y H:i'),
                'end_at_text'    => $wfhRequest->end_at->translatedFormat('D, d/m/y H:i'),
                'is_multi_day'   => $wfhRequest->isMultiDay(),
                'project_id'     => $wfhRequest->project_id,
                'task_id'        => $wfhRequest->task_id,
                'project_text'   => $wfhRequest->project ? $wfhRequest->project->project_code . ' · ' . $wfhRequest->project->name : null,
                'task_text'      => $wfhRequest->task ? $wfhRequest->task->task_code . ' · ' . $wfhRequest->task->name : null,
            ],
            'projects'    => $projects->map(fn ($p) => ['id' => $p->id, 'text' => $p->project_code . ' · ' . $p->name]),
            'tasks'       => $tasks->map(fn ($t) => ['id' => $t->id, 'text' => $t->task_code . ' · ' . $t->name, 'project_id' => $t->project_id]),
            ...$this->totalsFor($wfhRequest->user_id),
            'can_edit'    => $canEdit,
            'can_approve' => $canApprove,
        ]);
    }

    public function update(Request $request, WfhRequest $wfhRequest)
    {
        if (in_array($wfhRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot edit an approved or rejected WFH request.');
        }
        $this->_authorize($wfhRequest, 'edit all wfh', 'edit team wfh', 'edit own wfh');

        $data = $this->_validate($request);
        [$start, $end, $hours] = $this->_resolveSpan($data);
        $this->_assertNoOverlap($wfhRequest->user_id, $start, $end, $wfhRequest->id);

        $wfhRequest->update([
            'project_id'  => $data['project_id'] ?? null,
            'task_id'     => $data['task_id'] ?? null,
            'start_at'    => $start,
            'end_at'      => $end,
            'hours'       => $hours,
            'description' => $data['description'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('requests.index', ['type' => 'wfh'])->with('success', __('WFH request updated.'));
    }

    public function destroy(WfhRequest $wfhRequest)
    {
        if (in_array($wfhRequest->status, ['approved', 'rejected'])) {
            abort(403, 'Cannot delete an approved or rejected WFH request.');
        }
        $this->_authorize($wfhRequest, 'delete all wfh', 'delete team wfh', 'delete own wfh');

        $wfhRequest->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('WFH request deleted.'));
    }

    public function approve(WfhRequest $wfhRequest)
    {
        Helper::authorizeRequest('approve all wfh', 'approve team wfh', $wfhRequest);
        $this->_assertPending($wfhRequest);

        // Approved WFH counts as work time: record it as time logs together with the approval
        DB::transaction(function () use ($wfhRequest) {
            $wfhRequest->update([
                'status'        => 'approved',
                'approved_by'   => auth()->id(),
                'reject_reason' => null,
            ]);
            $wfhRequest->logWorkTime();
        });

        NotificationHelper::sendRequestApprovalNotification($wfhRequest, 'wfh');

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('WFH request approved.'));
    }

    public function reject(Request $request, WfhRequest $wfhRequest)
    {
        Helper::authorizeRequest('approve all wfh', 'approve team wfh', $wfhRequest);
        $this->_assertPending($wfhRequest);

        $data = $request->validate(['reject_reason' => 'required|string|max:500']);

        $wfhRequest->update([
            'status'        => 'rejected',
            'approved_by'   => auth()->id(),
            'reject_reason' => $data['reject_reason'],
        ]);

        NotificationHelper::sendRequestApprovalNotification($wfhRequest, 'wfh');

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('WFH request rejected.'));
    }

    /** Approved WFH hours for the current month and year. */
    public static function totalsFor(int $userId): array
    {
        $base = WfhRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_at', now()->year);

        return [
            'wfh_year_total'  => (float) (clone $base)->sum('hours'),
            'wfh_month_total' => (float) (clone $base)->whereMonth('start_at', now()->month)->sum('hours'),
        ];
    }

    private function _validate(Request $request): array
    {
        return $request->validate([
            'start_at'    => 'required|date',
            'end_at'      => 'required|date|after:start_at',
            'hours'       => 'nullable|numeric|min:0.25|max:24',
            'project_id'  => 'nullable|integer|exists:projects,id',
            'task_id'     => 'nullable|integer|exists:tasks,id',
            'description' => 'nullable|string',
        ]);
    }

    private function _assertPending(WfhRequest $wfh): void
    {
        if ($wfh->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('This WFH request has already been processed.')]);
        }
    }

    /** Own requests need the "own" permission; others go through the team/all approval-style check. */
    private function _authorize(WfhRequest $wfh, string $all, string $team, string $own): void
    {
        $user = auth()->user();
        if ($wfh->user_id === $user->id && $user->can($own)) return;
        Helper::authorizeRequest($all, $team, $wfh);
    }

    private function _targetUserId(Request $request, User $user): int
    {
        $requested = (int) ($request->input('user_id') ?: $user->id);
        if ($requested === $user->id || $user->can('edit all wfh')) return $requested;

        if ($user->can('edit team wfh') && $user->teamMembers()->whereKey($requested)->exists()) {
            return $requested;
        }

        abort(403);
    }

    /**
     * Single day: span minus lunch, or the hand-entered hours (capped at the span).
     * Multi-day: always the per-day breakdown total (see WfhRequest::breakdown).
     */
    private function _resolveSpan(array $data): array
    {
        $start = Carbon::parse($data['start_at'])->seconds(0);
        $end   = Carbon::parse($data['end_at'])->seconds(0);

        if ($start->diffInDays($end) > 31) {
            throw ValidationException::withMessages(['end_at' => __('A WFH request cannot span more than 31 days.')]);
        }

        $hours = array_sum(WfhRequest::breakdown($start, $end));

        if ($start->isSameDay($end) && isset($data['hours']) && $data['hours'] !== '') {
            $hours = (float) $data['hours'];
            if ($hours > $start->diffInMinutes($end) / 60) {
                throw ValidationException::withMessages(['hours' => __('WFH hours cannot exceed the time range.')]);
            }
        }

        if ($hours <= 0) {
            throw ValidationException::withMessages(['end_at' => __('This range has no working hours.')]);
        }

        return [$start, $end, round($hours, 2)];
    }

    private function _assertNoOverlap(int $userId, Carbon $start, Carbon $end, ?int $ignoreId = null): void
    {
        $overlaps = WfhRequest::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages(['start_at' => __('This time overlaps another WFH request.')]);
        }
    }
}

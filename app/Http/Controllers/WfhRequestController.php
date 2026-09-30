<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Helper\NotificationHelper;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\WfhRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            'start_at'    => $start,
            'end_at'      => $end,
            'hours'       => $hours,
            'description' => $data['description'] ?? null,
        ]);
        NotificationHelper::sendNewRequestNotification($wfh, 'wfh');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $wfh->id]);
        }

        return redirect()->route('requests.index', ['type' => 'wfh'])->with('success', __('WFH request created.'));
    }

    public function show(WfhRequest $wfhRequest)
    {
        $this->_authorize($wfhRequest, 'view all wfh', 'view team wfh', 'view own wfh');
        $wfhRequest->load('user', 'approver');

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
                'wfh_date'      => $wfhRequest->start_at->format('Y-m-d'),
                'start_time'    => $wfhRequest->start_at->format('H:i'),
                'end_time'      => $wfhRequest->end_at->format('H:i'),
            ],
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

        $wfhRequest->update([
            'status'        => 'approved',
            'approved_by'   => auth()->id(),
            'reject_reason' => null,
        ]);

        NotificationHelper::sendRequestApprovalNotification($wfhRequest, 'wfh');

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('WFH request approved.'));
    }

    public function reject(Request $request, WfhRequest $wfhRequest)
    {
        Helper::authorizeRequest('approve all wfh', 'approve team wfh', $wfhRequest);

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
            'wfh_date'    => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'hours'       => 'nullable|numeric|min:0.25|max:24',
            'description' => 'nullable|string',
        ]);
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

    /** Hours = span minus the lunch break overlap, unless the user entered hours manually (capped at the span). */
    private function _resolveSpan(array $data): array
    {
        $start = Carbon::parse($data['wfh_date'] . ' ' . $data['start_time']);
        $end   = Carbon::parse($data['wfh_date'] . ' ' . $data['end_time']);

        $toMins     = fn (string $hm) => (int) substr($hm, 0, 2) * 60 + (int) substr($hm, 3, 2);
        $lunchStart = $toMins(AppSetting::get('lunch_break_start', '12:00'));
        $lunchEnd   = $toMins(AppSetting::get('lunch_break_end', '13:00'));
        $fromM      = $toMins($data['start_time']);
        $toM        = $toMins($data['end_time']);
        $spanHours  = ($toM - $fromM) / 60;
        $lunchHours = max(0, min($toM, $lunchEnd) - max($fromM, $lunchStart)) / 60;

        $hours = isset($data['hours']) && $data['hours'] !== ''
            ? (float) $data['hours']
            : $spanHours - $lunchHours;

        if ($hours > $spanHours) {
            throw ValidationException::withMessages(['hours' => __('WFH hours cannot exceed the time range.')]);
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
            throw ValidationException::withMessages(['wfh_date' => __('This time overlaps another WFH request.')]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\WfhRequest;
use Illuminate\Http\Request;

class PendingApprovalsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $leaveQuery = null;
        if ($user->can('approve all leaves')) {
            $leaveQuery = LeaveRequest::query();
        } elseif ($user->can('approve team leaves')) {
            $leaveQuery = LeaveRequest::whereIn('user_id', $user->teamMembers()->pluck('id'));
        }

        $otQuery = null;
        if ($user->can('approve all ot')) {
            $otQuery = OvertimeRequest::query();
        } elseif ($user->can('approve team ot')) {
            $otQuery = OvertimeRequest::whereIn('user_id', $user->teamMembers()->pluck('id'));
        }

        $wfhQuery = null;
        if ($user->can('approve all wfh')) {
            $wfhQuery = WfhRequest::query();
        } elseif ($user->can('approve team wfh')) {
            $wfhQuery = WfhRequest::whereIn('user_id', $user->teamMembers()->pluck('id'));
        }

        $leaves = $leaveQuery
            ? $leaveQuery->with('user')->where('status', 'pending')->latest()->get()->map(fn($lr) => [
                'id'          => $lr->id,
                'type_key'    => 'leave',
                'user'        => [
                    'id'       => $lr->user->id,
                    'name'     => $lr->user->name,
                    'position' => $lr->user->position,
                    'grade'    => $lr->user->grade,
                    'avatar'   => $lr->user->profile_picture
                        ? asset('storage/profile_pictures/' . $lr->user->profile_picture)
                        : null,
                    'initials' => mb_strtoupper(mb_substr($lr->user->name, 0, 1)),
                    'url'      => route('users.show', $lr->user),
                ],
                'leave_type'    => $lr->type,
                'leave_label'   => ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'unpaid' => 'Nghỉ không lương'][$lr->type] ?? $lr->type,
                'hours'         => $lr->hours,
                'start_at_text' => $lr->start_at->translatedFormat('D, d/m/y H:i'),
                'end_at_text'   => $lr->end_at->translatedFormat('D, d/m/y H:i'),
                'description'   => $lr->description,
                'created_at'    => $lr->created_at->format('d/m/y H:i'),
                'approve_url'   => route('leave-requests.approve', $lr->id),
                'reject_url'    => route('leave-requests.reject', $lr->id),
            ])
            : collect();

        $ots = $otQuery
            ? $otQuery->with('user', 'project', 'task')->where('status', 'pending')->latest()->get()->map(fn($ot) => [
                'id'          => $ot->id,
                'type_key'    => 'ot',
                'user'        => [
                    'id'       => $ot->user->id,
                    'name'     => $ot->user->name,
                    'position' => $ot->user->position,
                    'grade'    => $ot->user->grade,
                    'avatar'   => $ot->user->profile_picture
                        ? asset('storage/profile_pictures/' . $ot->user->profile_picture)
                        : null,
                    'initials' => mb_strtoupper(mb_substr($ot->user->name, 0, 1)),
                    'url'      => route('users.show', $ot->user),
                ],
                'ot_type'       => $ot->type,
                'hours'         => $ot->hours,
                'start_at_text' => $ot->start_at->translatedFormat('D, d/m/y H:i'),
                'end_at_text'   => $ot->end_at->translatedFormat('D, d/m/y H:i'),
                'project_code'  => $ot->project?->project_code,
                'project_name'  => $ot->project?->name,
                'task_code'     => $ot->task?->task_code,
                'task_name'     => $ot->task?->name,
                'description'   => $ot->description,
                'created_at'    => $ot->created_at->format('d/m/y H:i'),
                'approve_url'   => route('overtime-requests.approve', $ot->id),
                'reject_url'    => route('overtime-requests.reject', $ot->id),
            ])
            : collect();

        $wfhs = $wfhQuery
            ? $wfhQuery->with('user')->where('status', 'pending')->latest()->get()->map(fn($w) => [
                'id'            => $w->id,
                'type_key'      => 'wfh',
                'user'          => [
                    'id'       => $w->user->id,
                    'name'     => $w->user->name,
                    'position' => $w->user->position,
                    'grade'    => $w->user->grade,
                    'avatar'   => $w->user->profile_picture
                        ? asset('storage/profile_pictures/' . $w->user->profile_picture)
                        : null,
                    'initials' => mb_strtoupper(mb_substr($w->user->name, 0, 1)),
                    'url'      => route('users.show', $w->user),
                ],
                'hours'         => $w->hours,
                'start_at_text' => $w->start_at->translatedFormat('D, d/m/y H:i'),
                'end_at_text'   => $w->end_at->format('H:i'),
                'description'   => $w->description,
                'created_at'    => $w->created_at->format('d/m/y H:i'),
                'approve_url'   => route('wfh-requests.approve', $w->id),
                'reject_url'    => route('wfh-requests.reject', $w->id),
            ])
            : collect();

        return response()->json([
            'leaves' => $leaves->values(),
            'ots'    => $ots->values(),
            'wfhs'   => $wfhs->values(),
            'total'  => $leaves->count() + $ots->count() + $wfhs->count(),
        ]);
    }
}

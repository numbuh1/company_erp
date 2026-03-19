<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Task;
use App\Models\TimeLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ── Announcements ──────────────────────────────────────────
        $latestAnnouncement    = Announcement::latest()->first();
        $previousAnnouncements = Announcement::latest()
            ->when($latestAnnouncement, fn($q) => $q->where('id', '!=', $latestAnnouncement->id))
            ->limit(5)
            ->get();

        // ── User stats ─────────────────────────────────────────────
        $weekStart  = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd    = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $weekTimeLogs = TimeLog::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('time_spent');

        $monthTimeLogs = TimeLog::where('user_id', $user->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('time_spent');

        $monthOTHours = OvertimeRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereMonth('start_at', now()->month)
            ->whereYear('start_at', now()->year)
            ->sum('hours');

        // ── Upcoming approved leaves (now → +2 weeks) ─────────────
        $leaveWindowStart = Carbon::now()->startOfDay();
        $leaveWindowEnd   = Carbon::now()->addWeeks(2)->endOfDay();

        $leaveQuery = LeaveRequest::with('user')
            ->where('status', 'approved')
            ->where('start_at', '<=', $leaveWindowEnd)
            ->where('end_at',   '>=', $leaveWindowStart)
            ->orderBy('start_at');

        if ($user->can('edit all user')) {
            // show all users' leaves
        } else {
            $teamUserIds = $user->teamMembers()->pluck('id')->toArray();
            $leaveQuery->whereIn('user_id', array_unique(array_merge([$user->id], $teamUserIds)));
        }

        $upcomingLeaves = $leaveQuery->get();

        // ── In Progress tasks nearing deadline (≤ 5 days) ─────────
        $deadlineQuery = Task::with(['project', 'assignees'])
            ->whereNot('status', 'Done')
            ->whereNotNull('expected_end_date')
            ->where('expected_end_date', '>=', now()->toDateString())
            ->where('expected_end_date', '<=', now()->addDays(5)->toDateString())
            ->orderBy('expected_end_date');

        if (!$user->can('view all tasks')) {
            $deadlineQuery->whereHas('assignees', fn($q) => $q->where('users.id', $user->id));
        }

        $deadlineTasks = $deadlineQuery->limit(5)->get();

        // ── Pending request counts (approvers only) ────────────────
        $pendingLeavesCount = null;
        $pendingOTCount     = null;

        if ($user->can('approve all leaves') || $user->can('approve team leaves')) {
            $pendingLeavesCount = LeaveRequest::where('status', 'pending')->count();
        }
        if ($user->can('approve all ot') || $user->can('approve team ot')) {
            $pendingOTCount = OvertimeRequest::where('status', 'pending')->count();
        }

        return view('dashboard', compact(
            'latestAnnouncement', 'previousAnnouncements',
            'weekTimeLogs', 'monthTimeLogs', 'monthOTHours',
            'upcomingLeaves', 'deadlineTasks',
            'pendingLeavesCount', 'pendingOTCount'
        ));
    }
}

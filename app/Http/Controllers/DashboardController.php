<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\Event;
use App\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ── Announcements ──────────────────────────────────────────
        $announcementQuery = \App\Models\Announcement::with('teams')->latest();

        // Scope to announcements visible to this user
        if (!$user->can('edit announcements') && !$user->can('edit all user') && !$user->can('view all user')) {
            $userTeamIds = $user->teams()->pluck('teams.id');
            $announcementQuery->where(function ($q) use ($userTeamIds) {
                $q->whereDoesntHave('teams')
                  ->orWhereHas('teams', fn($tq) => $tq->whereIn('teams.id', $userTeamIds));
            });
        }

        $visibleAnnouncements  = $announcementQuery->limit(6)->get();
        $latestAnnouncement    = $visibleAnnouncements->first();
        $previousAnnouncements = $visibleAnnouncements->skip(1)->values();

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
            ->whereNot('status', 'Đã xong')
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

        // ── Birthdays this month ───────────────────────────────────
        $birthdayUsers = User::whereNotNull('birthday')
            ->whereRaw('MONTH(birthday) = ?', [now()->month])
            ->when(!$user->can('edit all user'), fn($q) => $q->where('id', $user->id))
            ->orderByRaw('DAY(birthday)')
            ->get(['id', 'name', 'birthday']);

        $upcomingBirthdays = $birthdayUsers->map(function ($u) {
            $thisYear = Carbon::create(now()->year, $u->birthday->month, $u->birthday->day);
            return (object)[
                'name'      => $u->name,
                'date'      => $thisYear,
                'day'       => $thisYear->day,
                'is_today'  => $thisYear->isToday(),
                'days_left' => (int) now()->startOfDay()->diffInDays($thisYear->copy()->startOfDay(), false),
            ];
        })->sortBy('days_left')->values();

        // ── Contract expiries this month ──────────────────────────
        $contractExpiryUsers = User::whereNotNull('contract_expiry')
            ->whereMonth('contract_expiry', now()->month)
            ->whereYear('contract_expiry', now()->year)
            ->when(!$user->can('edit all user'), fn($q) => $q->where('id', $user->id))
            ->orderByRaw('DAY(contract_expiry)')
            ->get(['id', 'name', 'position', 'contract_expiry']);

        // ── Onboarding widgets (Team Leads, or anyone with "view teams") ─────
        $canViewOnboarding = $user->can('view teams') || $user->teams()->wherePivot('is_leader', true)->exists();

        $onboardedUsers       = collect();
        $probationEndingUsers = collect();

        if ($canViewOnboarding) {
            $onboardQuery   = User::whereNotNull('recruitment_applicant_id')->with('recruitmentApplicant.position');
            $probationQuery = User::whereNotNull('probation_end_date');

            if (!$user->can('view teams')) {
                $teamUserIds = $user->teamMembers()->pluck('id')->toArray();
                $onboardQuery->whereIn('id', $teamUserIds);
                $probationQuery->whereIn('id', $teamUserIds);
            }

            $onboardedUsers = $onboardQuery
                ->whereNotNull('probation_start_date')
                ->whereMonth('probation_start_date', now()->month)
                ->whereYear('probation_start_date', now()->year)
                ->orderBy('probation_start_date')
                ->get(['id', 'name', 'position', 'recruitment_applicant_id', 'probation_start_date']);

            $probationEndingUsers = $probationQuery
                ->where(function ($q) {
                    $q->where('probation_end_date', '<', now()->toDateString())
                      ->orWhere(function ($q2) {
                          $q2->whereMonth('probation_end_date', now()->month)
                             ->whereYear('probation_end_date', now()->year);
                      });
                })
                ->orderBy('probation_end_date')
                ->get(['id', 'name', 'position', 'probation_end_date']);
        }

        // ── Public holidays this month ─────────────────────────────
        $monthHolidays = PublicHoliday::getHolidaysForRange(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        // ── Today & this week events ─────────────────────────────
        $todayEvents = collect();
        $weekEvents  = collect();

        if ($user->can('module calendar')) {
            $baseQuery = fn() => Event::with('attendants')
                ->where(function ($q) use ($user) {
                    $q->whereHas('attendants', fn($sq) => $sq->where('users.id', $user->id))
                      ->orWhere('created_by', $user->id);
                });

            $todayEvents = $baseQuery()
                ->whereBetween('start_at', [now()->startOfDay(), now()->endOfDay()])
                ->orderBy('start_at')
                ->get();

            $weekEvents = $baseQuery()
                ->where('start_at', '>', now()->endOfDay())
                ->where('start_at', '<=', now()->endOfWeek(Carbon::SUNDAY)->endOfDay())
                ->orderBy('start_at')
                ->get();
        }

        return view('dashboard', compact(
            'latestAnnouncement', 'previousAnnouncements',
            'weekTimeLogs', 'monthTimeLogs', 'monthOTHours',
            'upcomingLeaves', 'deadlineTasks',
            'pendingLeavesCount', 'pendingOTCount',
            'todayEvents', 'weekEvents',
            'upcomingBirthdays', 'monthHolidays',
            'contractExpiryUsers',
            'canViewOnboarding', 'onboardedUsers', 'probationEndingUsers'
        ));
    }
}

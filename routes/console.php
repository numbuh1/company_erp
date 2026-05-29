<?php

use App\Mail\WeeklyApprovalReminderMail;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Weekly approval reminder — runs every weekday at 15:00.
 * Only actually sends emails on the last non-holiday working day of the week
 * (Friday, or Thursday/Wednesday if Friday/Thursday is a holiday).
 */
Schedule::call(function () {
    $today = Carbon::today();

    // Walk back from Friday to find the last non-holiday weekday this week
    $friday      = $today->copy()->endOfWeek(Carbon::FRIDAY);
    $lastWorkDay = $friday->copy();
    for ($i = 0; $i < 4; $i++) {
        $holidays = PublicHoliday::getHolidayDates($lastWorkDay->copy(), $lastWorkDay->copy());
        if (empty($holidays)) break;
        $lastWorkDay->subDay();
        // Stay within the current work week (Mon–Fri)
        if ($lastWorkDay->lt($today->copy()->startOfWeek(Carbon::MONDAY))) break;
    }

    // Only proceed if today IS that last working day
    if (!$today->isSameDay($lastWorkDay)) return;

    // Find all team leaders with their leader-only teams (eager-loaded with members)
    $leads = User::whereHas('teams', fn ($q) => $q->where('team_user.is_leader', true))
        ->with(['teams' => fn ($q) => $q->where('team_user.is_leader', true)->with('users')])
        ->get();

    foreach ($leads as $lead) {
        // Collect all member IDs across every team this person leads
        $memberIds = $lead->teams
            ->flatMap(fn ($t) => $t->users->pluck('id'))
            ->unique()
            ->values()
            ->toArray();

        if (empty($memberIds)) continue;

        $pendingLeaves = LeaveRequest::whereIn('user_id', $memberIds)
            ->where('status', 'pending')
            ->with('user')
            ->get();

        $pendingOts = OvertimeRequest::whereIn('user_id', $memberIds)
            ->where('status', 'pending')
            ->with('user')
            ->get();

        if ($pendingLeaves->isEmpty() && $pendingOts->isEmpty()) continue;

        try {
            Mail::to($lead->email)->send(
                new WeeklyApprovalReminderMail($lead, $pendingLeaves, $pendingOts)
            );
        } catch (\Throwable $e) {
            logger()->error("Weekly reminder failed for user {$lead->id}: " . $e->getMessage());
        }
    }
})->weekdays()->at('15:00')->name('weekly-approval-reminder')->withoutOverlapping();

<?php

namespace Database\Seeders;

use App\Models\LeaveBalanceLog;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\PublicHoliday;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds time logs, approved leave requests, and approved OT requests for every
 * user from June 1st through today. ~90% of (user, weekday) days net exactly
 * 8 hours (via work, full-day leave, or a mix); ~10% deviate (less or more)
 * to exercise the over/under indicators in the timesheet views.
 */
class JuneTimesheetSeeder extends Seeder
{
    private const RANGE_START = '2026-06-01';

    private array $workDescs = [
        'Feature development and implementation',
        'Code review and refactoring',
        'Bug fixes and testing',
        'Sprint planning and grooming',
        'Documentation updates',
        'Client meeting and follow-up',
        'Integration testing and QA',
        'Deployment and monitoring',
        'Performance optimisation',
        'UI/UX improvements',
    ];

    private array $freeDescs = [
        'Internal team sync',
        'Admin and email',
        'Training and research',
        'General support tasks',
        'Cross-team coordination',
    ];

    private array $leaveDescs = [
        'Personal time off',
        'Family matters',
        'Rest day',
        'Medical appointment',
    ];

    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping JuneTimesheetSeeder.');
            return;
        }

        $approver = User::where('name', 'Admin')->first() ?? $users->first();

        $start = Carbon::parse(self::RANGE_START);
        $end   = Carbon::today();

        $holidayDates = PublicHoliday::getHolidayDates($start->copy(), $end->copy());

        $weekdays = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (!$d->isWeekend() && !in_array($d->toDateString(), $holidayDates, true)) {
                $weekdays[] = $d->copy();
            }
        }

        if (empty($weekdays)) {
            $this->command->warn('No working days in range. Skipping JuneTimesheetSeeder.');
            return;
        }

        $tasksByUser = [];
        foreach ($users as $u) {
            $tasksByUser[$u->id] = Task::whereHas('assignees', fn ($q) => $q->where('users.id', $u->id))
                ->whereNotNull('project_id')
                ->get(['id', 'project_id']);
        }

        // ── Pass 1: plan each user's days, so we know how much annual leave
        //    balance we need before writing anything ────────────────────────
        $plans = [];
        foreach ($users as $user) {
            $plan = [];
            $annualNeeded = 0.0;

            foreach ($weekdays as $day) {
                $entry = $this->planDay();
                if ($entry['kind'] === 'leave' && $entry['leave_type'] === 'annual') {
                    $annualNeeded += $entry['hours'];
                }
                $plan[] = ['day' => $day, 'entry' => $entry];
            }

            $plans[$user->id] = $plan;

            // Top up balance if this user's planned annual leave would go negative.
            if ($annualNeeded > $user->leave_balance) {
                $old = (float) $user->leave_balance;
                $new = $annualNeeded + 16; // small buffer above what's needed
                $user->update(['leave_balance' => $new]);
                LeaveBalanceLog::create([
                    'user_id'       => $user->id,
                    'changed_by'    => $approver->id,
                    'change_hours'  => $new - $old,
                    'balance_after' => $new,
                    'reason'        => 'Demo data top-up (JuneTimesheetSeeder)',
                ]);
            }
        }

        // ── Pass 2: write the rows ──────────────────────────────────────────
        $timeLogCount = 0;
        $leaveCount   = 0;
        $otCount      = 0;

        foreach ($users as $user) {
            $userTasks  = $tasksByUser[$user->id];
            $workDays   = []; // days this user worked (eligible for an independent OT request)

            foreach ($plans[$user->id] as $row) {
                $day   = $row['day'];
                $entry = $row['entry'];

                switch ($entry['kind']) {
                    case 'leave':
                        $this->createLeave($user, $day, $entry['hours'], $entry['leave_type'], $approver);
                        $leaveCount++;
                        break;

                    case 'work':
                        $timeLogCount += $this->createWorkLogs($user, $day, $entry['hours'], $userTasks);
                        $workDays[] = $day;
                        break;
                }
            }

            // Sprinkle a handful of OT requests on random work days, independent
            // of that day's logged-hours total — just to have some OT data to test.
            shuffle($workDays);
            foreach (array_slice($workDays, 0, rand(2, 3)) as $otDay) {
                $otHours = [1.0, 1.5, 2.0, 2.5, 3.0][array_rand([1.0, 1.5, 2.0, 2.5, 3.0])];
                $this->createOt($user, $otDay, $otHours, $userTasks, $approver);
                $otCount++;
            }
        }

        $this->command->info("JuneTimesheetSeeder: {$timeLogCount} time log entries, {$leaveCount} approved leave requests, {$otCount} approved OT requests created across " . count($weekdays) . " weekdays for {$users->count()} users.");
    }

    /**
     * Decide what a single (user, day) looks like.
     * 90% => total logged hours (work + leave) exactly 8h.
     * 10% => total deviates from 8h (under via partial work/leave, or over via extra work hours).
     */
    private function planDay(): array
    {
        $isAnomaly = rand(1, 100) <= 10;

        if (!$isAnomaly) {
            if (rand(1, 100) <= 12) {
                return ['kind' => 'leave', 'hours' => 8.0, 'leave_type' => $this->randomLeaveType()];
            }
            return ['kind' => 'work', 'hours' => 8.0];
        }

        // Anomaly bucket: 50/50 less vs more.
        if (rand(0, 1) === 0) {
            $delta = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5][array_rand([0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5])];
            $hours = round(8.0 - $delta, 2);

            // Half of the "less" days come from a short leave instead of under-logged work.
            if (rand(0, 1) === 0) {
                return ['kind' => 'leave', 'hours' => $hours, 'leave_type' => $this->randomLeaveType()];
            }
            return ['kind' => 'work', 'hours' => $hours];
        }

        $extra = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0][array_rand([0.5, 1.0, 1.5, 2.0, 2.5, 3.0])];
        return ['kind' => 'work', 'hours' => round(8.0 + $extra, 2)];
    }

    private function randomLeaveType(): string
    {
        $roll = rand(1, 100);
        if ($roll <= 50) return 'annual';
        if ($roll <= 80) return 'sick';
        return 'unpaid';
    }

    /**
     * Create 1–3 TimeLog entries for the given total hours, split across
     * the user's assigned tasks (~65%) or logged as free-form work (~35%).
     */
    private function createWorkLogs(User $user, Carbon $day, float $totalHours, $userTasks): int
    {
        if ($totalHours <= 0) return 0;

        $created = 0;
        foreach ($this->splitHours($totalHours) as $chunkHours) {
            $useTask = $userTasks->isNotEmpty() && rand(1, 100) <= 65;
            $task    = $useTask ? $userTasks->random() : null;

            TimeLog::create([
                'user_id'     => $user->id,
                'project_id'  => $task?->project_id,
                'task_id'     => $task?->id,
                'description' => $task ? $this->workDescs[array_rand($this->workDescs)] : $this->freeDescs[array_rand($this->freeDescs)],
                'date'        => $day->toDateString(),
                'time_spent'  => $chunkHours,
            ]);
            $created++;
        }

        return $created;
    }

    private function createLeave(User $user, Carbon $day, float $hours, string $type, User $approver): void
    {
        $startAt = $day->copy()->setTime(9, 0);
        $endAt   = $startAt->copy()->addMinutes((int) round($hours * 60));

        $leave = LeaveRequest::create([
            'user_id'     => $user->id,
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'hours'       => $hours,
            'type'        => $type,
            'description' => $this->leaveDescs[array_rand($this->leaveDescs)],
            'status'      => 'approved',
            'approved_by' => $approver->id,
        ]);

        if ($type === 'annual') {
            $old = (float) $user->leave_balance;
            $new = $old - $hours;
            $user->update(['leave_balance' => $new]);

            LeaveBalanceLog::create([
                'user_id'       => $user->id,
                'changed_by'    => $approver->id,
                'change_hours'  => -$hours,
                'balance_after' => $new,
                'reason'        => 'Leave approved: #' . $leave->id,
            ]);
        }
    }

    private function createOt(User $user, Carbon $day, float $hours, $userTasks, User $approver): void
    {
        $startAt = $day->copy()->setTime(18, 0);
        $endAt   = $startAt->copy()->addMinutes((int) round($hours * 60));
        $task    = $userTasks->isNotEmpty() && rand(1, 100) <= 65 ? $userTasks->random() : null;

        OvertimeRequest::create([
            'user_id'     => $user->id,
            'project_id'  => $task?->project_id,
            'task_id'     => $task?->id,
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'hours'       => $hours,
            'type'        => 'OT x1.5', // weekday OT
            'description' => 'Overtime to finish up work for the day.',
            'status'      => 'approved',
            'approved_by' => $approver->id,
        ]);
    }

    /**
     * Split a total number of hours (multiple of 0.5) into 1–3 chunks,
     * each a multiple of 0.5h and at least 0.5h, summing back to the total.
     */
    private function splitHours(float $totalHours): array
    {
        $totalMinutes = (int) round($totalHours * 60);
        $step         = 30; // 0.5h
        $maxChunks     = min(3, intdiv($totalMinutes, $step));
        $numChunks     = rand(1, max(1, $maxChunks));

        if ($numChunks === 1) {
            return [round($totalMinutes / 60, 2)];
        }

        $chunks    = [];
        $remaining = $totalMinutes;

        for ($i = 0; $i < $numChunks - 1; $i++) {
            $minChunk = $step;
            $maxChunk = $remaining - $step * ($numChunks - $i - 1);
            $steps    = intdiv($maxChunk - $minChunk, $step);
            $chunk    = $minChunk + rand(0, max(0, $steps)) * $step;
            $chunks[] = $chunk;
            $remaining -= $chunk;
        }

        $chunks[] = $remaining;

        return array_map(fn ($m) => round($m / 60, 2), $chunks);
    }
}

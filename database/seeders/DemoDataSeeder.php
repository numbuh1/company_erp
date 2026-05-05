<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping.');
            return;
        }

        // Use the first admin-ish user as the approver for all requests
        $approver = $users->first();

        // Collect working days (Mon–Fri) for the past 14 days up to yesterday
        $workDays = [];
        for ($i = 14; $i >= 1; $i--) {
            $day = Carbon::today()->subDays($i);
            if (!$day->isWeekend()) {
                $workDays[] = $day;
            }
        }

        $leaveTypes = ['Annual Leave', 'Sick Leave', 'Personal Leave'];
        $otTypes    = ['Project Overtime', 'Client Request', 'Urgent Delivery'];
        $workDescs  = [
            'Working on project deliverables',
            'Client meetings and coordination',
            'Code review and development',
            'Documentation and reporting',
            'Testing and quality assurance',
            'Planning and sprint grooming',
            'Bug fixes and hotfixes',
        ];

        foreach ($users as $user) {
            foreach ($workDays as $day) {
                // 75% work day, 25% leave day
                if (rand(1, 100) <= 75) {
                    // --- Time logs: split 6–7.5 h into 1–3 entries ---
                    $totalMinutes = rand(12, 15) * 30; // 360–450 min (6–7.5 h)
                    foreach ($this->splitMinutes($totalMinutes) as $minutes) {
                        TimeLog::create([
                            'user_id'     => $user->id,
                            'project_id'  => null,
                            'task_id'     => null,
                            'description' => $workDescs[array_rand($workDescs)],
                            'date'        => $day->toDateString(),
                            'time_spent'  => round($minutes / 60, 2),
                        ]);
                    }

                    // --- OT: ~25% of work days, not every user ---
                    if ($user->id % 2 === 0 && rand(1, 100) <= 25) {
                        $otMinutes = [60, 90, 120, 150, 180][rand(0, 4)];
                        $startAt   = $day->copy()->setTime(17, 0);
                        $endAt     = $startAt->copy()->addMinutes($otMinutes);

                        OvertimeRequest::create([
                            'user_id'     => $user->id,
                            'start_at'    => $startAt,
                            'end_at'      => $endAt,
                            'hours'       => $otMinutes / 60,
                            'type'        => $otTypes[array_rand($otTypes)],
                            'description' => 'Overtime approved for project completion.',
                            'status'      => 'approved',
                            'approved_by' => $approver->id,
                        ]);
                    }
                } else {
                    // --- Leave day: full day (8 h) ---
                    LeaveRequest::create([
                        'user_id'     => $user->id,
                        'start_at'    => $day->copy()->setTime(9, 0),
                        'end_at'      => $day->copy()->setTime(18, 0),
                        'hours'       => 8,
                        'type'        => $leaveTypes[array_rand($leaveTypes)],
                        'description' => 'Approved leave.',
                        'status'      => 'approved',
                        'approved_by' => $approver->id,
                    ]);
                }
            }
        }

        $this->command->info('Demo data seeded: time logs, leave requests, and OT requests created.');
    }

    /**
     * Split a total number of minutes into 1–3 chunks,
     * each a multiple of 30 min and at least 30 min.
     */
    private function splitMinutes(int $total): array
    {
        $numChunks = rand(1, min(3, intdiv($total, 30)));

        if ($numChunks === 1) {
            return [$total];
        }

        $chunks    = [];
        $remaining = $total;

        for ($i = 0; $i < $numChunks - 1; $i++) {
            $minChunk = 30;
            $maxChunk = $remaining - 30 * ($numChunks - $i - 1);
            $steps    = intdiv($maxChunk - $minChunk, 30);
            $chunk    = $minChunk + rand(0, max(0, $steps)) * 30;
            $chunks[]  = $chunk;
            $remaining -= $chunk;
        }

        $chunks[] = $remaining;

        return $chunks;
    }
}
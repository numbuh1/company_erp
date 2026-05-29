<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeLogMaySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping TimeLogMaySeeder.');
            return;
        }

        // Only use tasks that belong to a project
        $tasks = Task::whereNotNull('project_id')->get();

        if ($tasks->isEmpty()) {
            $this->command->warn('No tasks with projects found. Skipping TimeLogMaySeeder.');
            return;
        }

        // Work week: Mon 25 May → Fri 29 May 2026
        $days = [
            Carbon::create(2026, 5, 25), // Monday
            Carbon::create(2026, 5, 26), // Tuesday
            Carbon::create(2026, 5, 27), // Wednesday
            Carbon::create(2026, 5, 28), // Thursday
            Carbon::create(2026, 5, 29), // Friday
        ];

        $descriptions = [
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

        $created = 0;

        foreach ($users as $user) {
            foreach ($days as $day) {
                // Split exactly 8 h (480 min) into 2–3 log entries
                foreach ($this->split480() as $minutes) {
                    /** @var Task $task */
                    $task = $tasks->random();

                    TimeLog::create([
                        'user_id'     => $user->id,
                        'project_id'  => $task->project_id,
                        'task_id'     => $task->id,
                        'description' => $descriptions[array_rand($descriptions)],
                        'date'        => $day->toDateString(),
                        'time_spent'  => round($minutes / 60, 2),
                    ]);
                    $created++;
                }
            }
        }

        $this->command->info("TimeLogMaySeeder: {$created} entries created ({$users->count()} users × 5 days × 8 h).");
    }

    /**
     * Split 480 minutes into 2 or 3 chunks.
     * Each chunk is a multiple of 30 min, minimum 60 min.
     * The chunks always sum to exactly 480.
     */
    private function split480(): array
    {
        $total     = 480;
        $numChunks = rand(2, 3);
        $chunks    = [];
        $remaining = $total;

        for ($i = 0; $i < $numChunks - 1; $i++) {
            $minChunk  = 60;
            $maxChunk  = $remaining - 60 * ($numChunks - $i - 1); // leave room for remaining chunks
            $maxChunk  = max($minChunk, $maxChunk);
            $steps     = intdiv($maxChunk - $minChunk, 30);
            $chunk     = $minChunk + rand(0, $steps) * 30;
            $chunks[]  = $chunk;
            $remaining -= $chunk;
        }

        $chunks[] = $remaining; // last chunk takes whatever is left
        return $chunks;
    }
}

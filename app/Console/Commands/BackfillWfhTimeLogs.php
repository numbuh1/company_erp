<?php

namespace App\Console\Commands;

use App\Models\TimeLog;
use App\Models\WfhRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * WFH requests approved before approval started creating time logs only exist as a WFH label,
 * so their days look short in the timesheets. This records their hours as work time, using the
 * same rule as approval (each day filled up to 8h after approved leave and work already logged).
 */
class BackfillWfhTimeLogs extends Command
{
    protected $signature   = 'wfh:log-work-time {--dry-run : Show what would be logged without saving anything}';
    protected $description = 'Create time logs for approved WFH requests that have none yet';

    public function handle(): int
    {
        $requests = WfhRequest::with('user')
            ->where('status', 'approved')
            ->whereDoesntHave('timeLogs')
            ->orderBy('start_at')
            ->get();

        if ($requests->isEmpty()) {
            $this->info('Every approved WFH request already has its time logs.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows   = [];

        DB::beginTransaction();
        try {
            foreach ($requests as $wfh) {
                $created = $wfh->logWorkTime();
                $rows[]  = [
                    $wfh->id,
                    $wfh->user?->name,
                    $wfh->start_at->format('d/m/Y H:i') . ' → ' . $wfh->end_at->format('d/m/Y H:i'),
                    $wfh->hours . 'h',
                    $created,
                    TimeLog::where('wfh_request_id', $wfh->id)->sum('time_spent') . 'h',
                ];
            }
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['WFH #', 'User', 'Period', 'WFH hours', 'Logs', 'Hours logged'], $rows);
        $this->info($dryRun
            ? 'Dry run — nothing was saved. Run again without --dry-run to create these logs.'
            : count($rows) . ' WFH request(s) processed.');

        return self::SUCCESS;
    }
}

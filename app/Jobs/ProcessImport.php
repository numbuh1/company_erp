<?php

namespace App\Jobs;

use App\Imports\LeaveBalanceImport;
use App\Imports\LeaveRequestsImport;
use App\Imports\OvertimeRequestsImport;
use App\Imports\RequestsImport;
use App\Imports\TeamsImport;
use App\Imports\UsersImport;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function __construct(
        private int    $logId,
        private string $type,
        private string $tempPath,
    ) {}

    public function handle(): void
    {
        $log = ImportLog::findOrFail($this->logId);

        $import = match($this->type) {
            'users'          => new UsersImport,
            'teams'          => new TeamsImport,
            'leave-requests' => new LeaveRequestsImport,
            'ot-requests'    => new OvertimeRequestsImport,
            'leave-balance'  => new LeaveBalanceImport,
            'requests'       => new RequestsImport,
            default          => throw new \InvalidArgumentException("Unknown type: {$this->type}"),
        };

        $import->setLog($log);

        try {
            Excel::import($import, Storage::path($this->tempPath));

            $log->update([
                'status'         => 'done',
                'created_count'  => $import->created,
                'updated_count'  => $import->updated ?? 0,
                'skipped_count'  => $import->skipped,
                'rows'           => $import->rows,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
                'created_count' => $import->created,
                'updated_count' => $import->updated ?? 0,
                'skipped_count' => $import->skipped,
                'rows'          => $import->rows,
            ]);
        } finally {
            Storage::delete($this->tempPath);
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportLog::where('id', $this->logId)->update([
            'status'        => 'error',
            'error_message' => $e->getMessage(),
        ]);
        Storage::delete($this->tempPath);
    }
}

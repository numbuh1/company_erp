<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Models\LeaveBalanceLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeaveBalanceImport implements ToCollection, WithHeadingRow
{
    public int   $created = 0;
    public int   $updated = 0;
    public int   $skipped = 0;
    public array $errors  = [];
    public array $rows    = [];

    private ?ImportLog $log = null;

    public function __construct(private bool $dryRun = false) {}

    public function setLog(ImportLog $log): void
    {
        $this->log = $log;
    }

    public function collection(Collection $rows): void
    {
        if ($this->log) {
            $this->log->update(['total_rows' => $rows->count()]);
        }

        foreach ($rows as $i => $row) {
            $this->processRow(array_map('trim', $row->toArray()), $i + 2);
            $this->tickProgress();
        }
    }

    private function processRow(array $data, int $rowNum): void
    {
        $userRaw = $data['user']   ?? '';
        $action  = strtolower($data['action'] ?? 'set');
        $hours   = $data['hours']  ?? '';
        $reason  = $data['reason'] ?? '';

        if (!$userRaw) {
            $this->_skip($rowNum, "row {$rowNum}", 'user is required.');
            return;
        }

        $userId = $this->resolveUser($userRaw);
        if (!$userId) {
            $this->_skip($rowNum, $userRaw, "user '{$userRaw}' not found.");
            return;
        }

        $user = User::find($userId);
        $userName = $user->name;

        if (!is_numeric($hours)) {
            $this->_skip($rowNum, $userName, 'hours must be a number.');
            return;
        }

        $hoursVal = (float) $hours;

        if (!in_array($action, ['set', 'add', 'subtract'], true)) {
            $this->_skip($rowNum, $userName, "action must be: set, add, or subtract.");
            return;
        }

        $oldBalance = (float) $user->leave_balance;

        $newBalance = match ($action) {
            'set'      => $hoursVal,
            'add'      => $oldBalance + $hoursVal,
            'subtract' => $oldBalance - $hoursVal,
        };

        $changeHours = $newBalance - $oldBalance;

        try {
            if (!$this->dryRun) {
                $user->update(['leave_balance' => $newBalance]);

                LeaveBalanceLog::create([
                    'user_id'       => $userId,
                    'changed_by'    => auth()->id(),
                    'change_hours'  => $changeHours,
                    'balance_after' => $newBalance,
                    'reason'        => $reason ?: 'Bulk import',
                ]);
            }

            $this->rows[] = [
                'row'        => $rowNum,
                'action'     => 'updated',
                'identifier' => $userName,
                'changes'    => [
                    'action'  => $action,
                    'hours'   => $hoursVal,
                    'balance' => ['from' => $oldBalance, 'to' => $newBalance],
                    'reason'  => $reason,
                ],
            ];
            $this->updated++;
        } catch (\Throwable $e) {
            $this->_skip($rowNum, $userName, $e->getMessage());
        }
    }

    private function tickProgress(): void
    {
        if (!$this->dryRun) {
            sleep(1);
        }
        if ($this->log) {
            $this->log->increment('processed_rows');
        }
    }

    private function resolveUser(string $val): ?int
    {
        $val = trim($val);
        if ($val === '') return null;
        if (is_numeric($val)) return (int) $val;
        if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $val)->value('id');
        }
        return User::where('name', $val)->value('id');
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = ['row' => $rowNum, 'action' => 'skipped', 'identifier' => $identifier, 'error' => $message];
        $this->skipped++;
    }
}

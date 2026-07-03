<?php

namespace App\Imports;

use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeaveRequestsImport implements ToCollection, WithHeadings
{
    public int   $created = 0;
    public int   $skipped = 0;
    public array $errors  = [];
    public array $rows    = [];

    private const VALID_TYPES    = ['annual', 'sick', 'unpaid'];
    private const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct(private bool $dryRun = false) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $data   = array_map('trim', $row->toArray());

            $userRaw      = $data['user']          ?? '';
            $type         = strtolower($data['type'] ?? '');
            $startRaw     = $data['start_at']      ?? '';
            $endRaw       = $data['end_at']        ?? '';
            $hours        = $data['hours']         ?? '';
            $description  = $data['description']   ?: null;
            $status       = strtolower($data['status'] ?? 'pending');
            $approverRaw  = $data['approved_by']   ?? '';
            $rejectReason = $data['reject_reason'] ?: null;

            if (!$userRaw) { $this->_skip($rowNum, "row {$rowNum}", 'user is required.'); continue; }

            $userId = $this->resolveUser($userRaw);
            if (!$userId) { $this->_skip($rowNum, $userRaw, "user '{$userRaw}' not found."); continue; }
            $userName = User::find($userId)?->name ?? $userRaw;

            if (!in_array($type, self::VALID_TYPES, true)) {
                $this->_skip($rowNum, $userName, 'type must be: annual, sick, or unpaid.'); continue;
            }

            $startAt = $this->parseDate($startRaw);
            $endAt   = $this->parseDate($endRaw);
            if (!$startAt || !$endAt) { $this->_skip($rowNum, $userName, 'invalid start_at or end_at.'); continue; }

            if (!is_numeric($hours) || (float) $hours <= 0) {
                $this->_skip($rowNum, $userName, 'hours must be a positive number.'); continue;
            }

            if (!in_array($status, self::VALID_STATUSES, true)) $status = 'pending';

            $approverId   = $approverRaw !== '' ? $this->resolveUser($approverRaw) : null;
            $approverName = $approverId ? (User::find($approverId)?->name ?? $approverRaw) : null;

            try {
                if (!$this->dryRun) {
                    LeaveRequest::create([
                        'user_id'       => $userId,
                        'type'          => $type,
                        'start_at'      => $startAt,
                        'end_at'        => $endAt,
                        'hours'         => (float) $hours,
                        'description'   => $description,
                        'status'        => $status,
                        'approved_by'   => $approverId,
                        'reject_reason' => $rejectReason,
                    ]);
                }

                $this->rows[] = [
                    'row'        => $rowNum,
                    'action'     => 'created',
                    'identifier' => $userName,
                    'changes'    => array_filter([
                        'user'        => $userName,
                        'type'        => $type,
                        'start_at'    => $startAt->format('d/m/Y H:i'),
                        'end_at'      => $endAt->format('d/m/Y H:i'),
                        'hours'       => (float) $hours,
                        'status'      => $status,
                        'approved_by' => $approverName,
                        'description' => $description,
                    ], fn ($v) => $v !== null && $v !== ''),
                ];
                $this->created++;
            } catch (\Throwable $e) {
                $this->_skip($rowNum, $userName, $e->getMessage());
            }
        }
    }

    public function headings(): array
    {
        return ['user', 'type', 'start_at', 'end_at', 'hours', 'description', 'status', 'approved_by', 'reject_reason'];
    }

    private function resolveUser(string $val): ?int
    {
        $val = trim($val);
        if ($val === '') return null;
        if (is_numeric($val)) return (int) $val;
        return User::where('name', $val)->value('id');
    }

    private function parseDate(string $val): ?Carbon
    {
        if ($val === '') return null;
        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $val); } catch (\Throwable) {}
        }
        try { return Carbon::parse($val); } catch (\Throwable) { return null; }
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = ['row' => $rowNum, 'action' => 'skipped', 'identifier' => $identifier, 'error' => $message];
        $this->skipped++;
    }
}

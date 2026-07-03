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

    private const VALID_TYPES   = ['annual', 'sick', 'unpaid'];
    private const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $row    = $row->toArray();

            $userRaw     = trim($row['user']         ?? '');
            $type        = strtolower(trim($row['type']        ?? ''));
            $startRaw    = trim($row['start_at']     ?? '');
            $endRaw      = trim($row['end_at']       ?? '');
            $hours       = trim($row['hours']        ?? '');
            $description = trim($row['description']  ?? '') ?: null;
            $status      = strtolower(trim($row['status'] ?? 'pending'));
            $approverRaw = trim($row['approved_by']  ?? '');
            $rejectReason= trim($row['reject_reason'] ?? '') ?: null;

            if (!$userRaw) {
                $this->errors[] = "Row {$rowNum}: user is required.";
                $this->skipped++;
                continue;
            }

            $userId = $this->resolveUser($userRaw);
            if (!$userId) {
                $this->errors[] = "Row {$rowNum}: user '{$userRaw}' not found.";
                $this->skipped++;
                continue;
            }

            if (!in_array($type, self::VALID_TYPES, true)) {
                $this->errors[] = "Row {$rowNum}: type must be one of: " . implode(', ', self::VALID_TYPES) . ".";
                $this->skipped++;
                continue;
            }

            $startAt = $this->parseDate($startRaw);
            $endAt   = $this->parseDate($endRaw);

            if (!$startAt || !$endAt) {
                $this->errors[] = "Row {$rowNum}: invalid start_at or end_at (use d/m/Y H:i).";
                $this->skipped++;
                continue;
            }

            if (!is_numeric($hours) || (float) $hours <= 0) {
                $this->errors[] = "Row {$rowNum}: hours must be a positive number.";
                $this->skipped++;
                continue;
            }

            if (!in_array($status, self::VALID_STATUSES, true)) {
                $status = 'pending';
            }

            $approverId = $approverRaw !== '' ? $this->resolveUser($approverRaw) : null;

            try {
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

                $this->created++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
                $this->skipped++;
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
            try {
                return Carbon::createFromFormat($fmt, $val);
            } catch (\Throwable) {}
        }
        try {
            return Carbon::parse($val);
        } catch (\Throwable) {
            return null;
        }
    }
}

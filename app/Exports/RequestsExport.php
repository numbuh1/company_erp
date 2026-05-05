<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RequestsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected string  $type,
        protected ?string $dateFrom,
        protected ?string $dateTo,
        protected string  $status,
        protected         $user,
    ) {}

    public function collection(): Collection
    {
        $rows = collect();

        if (in_array($this->type, ['all', 'leave'])) {
            $q = LeaveRequest::with('user', 'approver');
            $this->applyScope($q, 'leave');
            $this->applyDateFilter($q);
            $this->applyStatusFilter($q);
            foreach ($q->orderBy('start_at')->get() as $r) {
                $rows->push([
                    'Leave',
                    $r->user?->name ?? '-',
                    $r->start_at->format('d/m/Y H:i'),
                    $r->end_at->format('d/m/Y H:i'),
                    $r->hours,
                    ucfirst($r->type),
                    $r->description ?? '',
                    ucfirst($r->status),
                    $r->approver?->name ?? '-',
                    $r->reject_reason ?? '',
                    $r->created_at->format('d/m/Y H:i'),
                ]);
            }
        }

        if (in_array($this->type, ['all', 'ot'])) {
            $q = OvertimeRequest::with('user', 'approver');
            $this->applyScope($q, 'ot');
            $this->applyDateFilter($q);
            $this->applyStatusFilter($q);
            foreach ($q->orderBy('start_at')->get() as $r) {
                $rows->push([
                    'OT',
                    $r->user?->name ?? '-',
                    $r->start_at->format('d/m/Y H:i'),
                    $r->end_at->format('d/m/Y H:i'),
                    $r->hours,
                    ucfirst($r->type),
                    $r->description ?? '',
                    ucfirst($r->status),
                    $r->approver?->name ?? '-',
                    $r->reject_reason ?? '',
                    $r->created_at->format('d/m/Y H:i'),
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Request Type', 'User', 'Period Start', 'Period End',
            'Hours', 'Sub-type', 'Description', 'Status',
            'Approver', 'Reject Reason', 'Submitted At',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    protected function applyScope($query, string $module): void
    {
        $allPerm  = $module === 'leave' ? 'view all leaves' : 'view all ot';
        $teamPerm = $module === 'leave' ? 'view team leaves' : 'view team ot';

        if ($this->user->can($allPerm)) {
            // no scope
        } elseif ($this->user->can($teamPerm)) {
            $query->whereIn('user_id', $this->user->teamMembers()->pluck('id'));
        } else {
            $query->where('user_id', $this->user->id);
        }
    }

    protected function applyDateFilter($query): void
    {
        if ($this->dateFrom) $query->whereDate('start_at', '>=', $this->dateFrom);
        if ($this->dateTo)   $query->whereDate('start_at', '<=', $this->dateTo);
    }

    protected function applyStatusFilter($query): void
    {
        if ($this->status && $this->status !== 'all') {
            $query->where('status', $this->status);
        }
    }
}
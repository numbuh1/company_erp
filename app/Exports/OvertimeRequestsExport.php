<?php

namespace App\Exports;

use App\Models\OvertimeRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimeRequestsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return OvertimeRequest::with(['user', 'approver', 'project', 'task'])
            ->orderBy('start_at')
            ->get()
            ->map(fn (OvertimeRequest $r) => [
                $r->id,
                $r->user_id,
                $r->user?->name ?? '',
                $r->type ?? '',
                $r->start_at->format('d/m/Y H:i'),
                $r->end_at->format('d/m/Y H:i'),
                $r->hours,
                $r->project_id ?? '',
                $r->project?->name ?? '',
                $r->task_id ?? '',
                $r->task?->name ?? '',
                $r->description ?? '',
                $r->status,
                $r->approved_by ?? '',
                $r->approver?->name ?? '',
                $r->reject_reason ?? '',
            ]);
    }

    public function headings(): array
    {
        return [
            'id', 'user_id', 'user_name',
            'type', 'start_at', 'end_at', 'hours',
            'project_id', 'project_name',
            'task_id', 'task_name',
            'description', 'status',
            'approved_by_id', 'approved_by_name', 'reject_reason',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
            ],
        ];
    }
}

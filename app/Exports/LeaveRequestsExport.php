<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveRequestsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return LeaveRequest::with(['user', 'approver'])->orderBy('start_at')->get()->map(fn (LeaveRequest $r) => [
            $r->id,
            $r->user_id,
            $r->user?->name ?? '',
            $r->type,
            $r->start_at->format('d/m/Y H:i'),
            $r->end_at->format('d/m/Y H:i'),
            $r->hours,
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
            'description', 'status',
            'approved_by_id', 'approved_by_name', 'reject_reason',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F6F0']],
            ],
        ];
    }
}

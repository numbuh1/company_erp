<?php

namespace App\Exports;

use App\Models\OvertimeRequest;
use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// ─────────────────────────────────────────────────────────────
// Main export — two sheets: Time Logs + OT Requests
// ─────────────────────────────────────────────────────────────
class TimeLogExport implements WithMultipleSheets
{
    /**
     * @param  array|null $viewableIds  null = unrestricted, array = allowed user IDs
     * @param  array      $filters      optional query filters (date_from, date_to,
     *                                  user_id, project_id, task_id, user_ids[])
     */
    public function __construct(
        protected ?array $viewableIds,
        protected array  $filters = [],
    ) {}

    public function sheets(): array
    {
        return [
            new TimeLogDataSheet($this->viewableIds, $this->filters),
            new OvertimeRequestDataSheet($this->viewableIds, $this->filters),
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 1 — Time Logs
// ─────────────────────────────────────────────────────────────
class TimeLogDataSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?array $viewableIds,
        protected array  $filters,
    ) {}

    public function title(): string
    {
        return 'Time Logs';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Người dùng',
            'Ngày',
            'Số giờ',
            'Dự án',
            'Công việc',
            'Mô tả',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function collection(): Collection
    {
        $q = TimeLog::with(['user', 'project', 'task'])
            ->orderBy('date')
            ->orderBy('id');

        if ($this->viewableIds !== null) {
            $q->whereIn('user_id', $this->viewableIds);
        }

        $this->applyFilters($q, 'date');

        return $q->get()->map(fn (TimeLog $log) => [
            $log->id,
            $log->user?->name ?? '—',
            $log->date->format('d/m/Y'),
            $log->time_spent,
            $log->project ? 'PJ-' . $log->project_id . ' ' . $log->project->name : '—',
            $log->task    ? 'TK-' . $log->task_id    . ' ' . $log->task->name    : '—',
            $log->description ?? '',
            $log->created_at?->format('d/m/Y H:i') ?? '',
            $log->updated_at?->format('d/m/Y H:i') ?? '',
        ]);
    }

    protected function applyFilters($q, string $dateColumn): void
    {
        $f = $this->filters;
        if (!empty($f['date_from']))  $q->whereDate($dateColumn, '>=', $f['date_from']);
        if (!empty($f['date_to']))    $q->whereDate($dateColumn, '<=', $f['date_to']);
        if (!empty($f['user_ids']))   $q->whereIn('user_id', (array) $f['user_ids']);
        elseif (!empty($f['user_id'])) $q->where('user_id', $f['user_id']);
        if (!empty($f['project_id'])) $q->where('project_id', $f['project_id']);
        if (!empty($f['task_id']))    $q->where('task_id', $f['task_id']);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'E8F0FE']],
            ],
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 2 — Overtime Requests
// ─────────────────────────────────────────────────────────────
class OvertimeRequestDataSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected ?array $viewableIds,
        protected array  $filters,
    ) {}

    public function title(): string
    {
        return 'OT Requests';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Người dùng',
            'Bắt đầu',
            'Kết thúc',
            'Số giờ',
            'Loại OT',
            'Dự án',
            'Công việc',
            'Mô tả',
            'Trạng thái',
            'Người duyệt',
            'Lý do từ chối',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function collection(): Collection
    {
        $q = OvertimeRequest::with(['user', 'approver', 'project', 'task'])
            ->orderBy('start_at')
            ->orderBy('id');

        if ($this->viewableIds !== null) {
            $q->whereIn('user_id', $this->viewableIds);
        }

        $this->applyFilters($q, 'start_at');

        return $q->get()->map(fn (OvertimeRequest $ot) => [
            $ot->id,
            $ot->user?->name ?? '—',
            $ot->start_at->format('d/m/Y H:i'),
            $ot->end_at->format('d/m/Y H:i'),
            $ot->hours,
            $ot->type,
            $ot->project ? 'PJ-' . $ot->project_id . ' ' . $ot->project->name : '—',
            $ot->task    ? 'TK-' . $ot->task_id    . ' ' . $ot->task->name    : '—',
            $ot->description ?? '',
            match ($ot->status) {
                'approved' => 'Đã duyệt',
                'rejected' => 'Đã từ chối',
                default    => 'Đang chờ',
            },
            $ot->approver?->name ?? '—',
            $ot->reject_reason ?? '',
            $ot->created_at?->format('d/m/Y H:i') ?? '',
            $ot->updated_at?->format('d/m/Y H:i') ?? '',
        ]);
    }

    protected function applyFilters($q, string $dateColumn): void
    {
        $f = $this->filters;
        if (!empty($f['date_from']))   $q->whereDate($dateColumn, '>=', $f['date_from']);
        if (!empty($f['date_to']))     $q->whereDate($dateColumn, '<=', $f['date_to']);
        if (!empty($f['user_ids']))    $q->whereIn('user_id', (array) $f['user_ids']);
        elseif (!empty($f['user_id'])) $q->where('user_id', $f['user_id']);
        if (!empty($f['project_id']))  $q->where('project_id', $f['project_id']);
        if (!empty($f['task_id']))     $q->where('task_id', $f['task_id']);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                           'startColor' => ['rgb' => 'FFF3E0']],
            ],
        ];
    }
}

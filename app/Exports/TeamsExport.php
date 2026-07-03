<?php

namespace App\Exports;

use App\Models\Team;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeamsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return Team::with('users')->get()->map(fn (Team $t) => [
            $t->id,
            $t->name,
            $t->users->where('pivot.is_leader', true)->map(fn ($u) => $u->id . ':' . $u->name)->join('|'),
            $t->users->map(fn ($u) =>
                $u->id . ':' . $u->name . ($u->pivot->is_leader ? ' (Leader)' : '')
            )->join('|'),
        ]);
    }

    public function headings(): array
    {
        return ['id', 'name', 'leaders', 'members'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F0FE']],
            ],
        ];
    }
}

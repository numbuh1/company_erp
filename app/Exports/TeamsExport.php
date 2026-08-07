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
        $rows = collect();
        Team::with('users')->orderBy('name')->get()->each(function (Team $t) use ($rows) {
            foreach ($t->users->sortBy('name') as $u) {
                $rows->push([
                    $u->email,
                    $t->name,
                    $u->pivot->is_leader ? '1' : '0',
                ]);
            }
        });
        return $rows;
    }

    public function headings(): array
    {
        return ['user_email', 'team_name', 'is_leader'];
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

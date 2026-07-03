<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function collection(): Collection
    {
        return User::with(['roles', 'teams'])->get()->map(fn (User $u) => [
            $u->id,
            $u->name,
            $u->email,
            $u->position ?? '',
            $u->grade    ?? '',
            (float) ($u->leave_balance ?? 0),
            $u->roles->pluck('name')->join('|'),
            $u->teams->map(fn ($t) =>
                $t->id . ':' . $t->name . ($t->pivot->is_leader ? ' (Leader)' : '')
            )->join('|'),
        ]);
    }

    public function headings(): array
    {
        return ['id', 'name', 'email', 'position', 'grade', 'leave_balance', 'roles', 'teams'];
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

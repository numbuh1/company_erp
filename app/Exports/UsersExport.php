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
            $u->full_name              ?? '',
            $u->contact_email          ?? '',
            $u->position               ?? '',
            $u->grade                  ?? '',
            $u->phone_number           ?? '',
            $u->citizen_id             ?? '',
            $u->tax_code               ?? '',
            $u->social_insurance_id    ?? '',
            $u->home_address           ?? '',
            $u->birthday?->format('d/m/Y')               ?? '',
            $u->contract_expiry?->format('d/m/Y')        ?? '',
            $u->probation_start_date?->format('d/m/Y')   ?? '',
            $u->probation_end_date?->format('d/m/Y')     ?? '',
            $u->employment_status      ?? '',
            $u->is_active              ? '1' : '0',
            $u->wfh_without_approval   ? '1' : '0',
            (float) ($u->leave_balance ?? 0),
            $u->salary                 ?? '',
            $u->salary_type            ?? '',
            $u->roles->pluck('name')->join('|'),
            $u->teams->map(fn ($t) =>
                $t->id . ':' . $t->name . ($t->pivot->is_leader ? ' (Leader)' : '')
            )->join('|'),
        ]);
    }

    public function headings(): array
    {
        return [
            'id', 'name', 'email',
            'full_name', 'contact_email', 'position', 'grade',
            'phone_number', 'citizen_id', 'tax_code', 'social_insurance_id',
            'home_address', 'birthday', 'contract_expiry',
            'probation_start_date', 'probation_end_date',
            'employment_status', 'is_active', 'wfh_without_approval',
            'leave_balance', 'salary', 'salary_type',
            'roles', 'teams',
        ];
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

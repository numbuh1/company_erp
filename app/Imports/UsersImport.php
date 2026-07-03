<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadings
{
    public int   $created = 0;
    public int   $updated = 0;
    public int   $skipped = 0;
    public array $errors  = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $row    = $row->toArray();

            $name         = trim($row['name']          ?? '');
            $email        = strtolower(trim($row['email'] ?? ''));
            $password     = trim($row['password']      ?? '');
            $position     = trim($row['position']      ?? '') ?: null;
            $grade        = trim($row['grade']         ?? '') ?: null;
            $leaveBalance = trim($row['leave_balance'] ?? '');
            $rolesRaw     = trim($row['roles']         ?? '');

            if (!$name || !$email) {
                $this->errors[] = "Row {$rowNum}: name and email are required.";
                $this->skipped++;
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNum}: '{$email}' is not a valid email.";
                $this->skipped++;
                continue;
            }

            $roleNames = $rolesRaw !== ''
                ? array_filter(array_map('trim', explode('|', $rolesRaw)))
                : [];

            $validRoles = $roleNames
                ? Role::whereIn('name', $roleNames)->pluck('name')->toArray()
                : [];

            try {
                $existing = User::withTrashed()->where('email', $email)->first();

                if ($existing) {
                    $updates = array_filter([
                        'name'     => $name,
                        'position' => $position,
                        'grade'    => $grade,
                    ], fn ($v) => $v !== null);

                    if ($leaveBalance !== '') {
                        $updates['leave_balance'] = (float) $leaveBalance;
                    }

                    if ($password !== '') {
                        $updates['password'] = bcrypt($password);
                    }

                    $existing->update($updates);

                    if (!empty($validRoles)) {
                        $existing->syncRoles($validRoles);
                    }

                    $this->updated++;
                } else {
                    $plainPassword = $password ?: Str::random(12);

                    $user = User::create([
                        'name'          => $name,
                        'email'         => $email,
                        'password'      => bcrypt($plainPassword),
                        'position'      => $position,
                        'grade'         => $grade,
                        'leave_balance' => $leaveBalance !== '' ? (float) $leaveBalance : 0,
                    ]);

                    if (!empty($validRoles)) {
                        $user->syncRoles($validRoles);
                    }

                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum} ({$email}): " . $e->getMessage();
                $this->skipped++;
            }
        }
    }

    public function headings(): array
    {
        return ['name', 'email', 'password', 'position', 'grade', 'leave_balance', 'roles'];
    }
}

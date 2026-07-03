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
    public array $rows    = [];  // per-row log entries

    /** Scalar columns directly writable; excludes password, roles, handled separately */
    private const SCALAR_COLUMNS = [
        'name', 'full_name', 'contact_email',
        'position', 'grade', 'phone_number',
        'citizen_id', 'tax_code', 'social_insurance_id',
        'home_address', 'employment_status',
        'salary', 'salary_type',
    ];

    private const DATE_COLUMNS = [
        'birthday', 'contract_expiry', 'probation_start_date', 'probation_end_date',
    ];

    private const BOOL_COLUMNS = ['is_active', 'wfh_without_approval'];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $data   = array_map('trim', $row->toArray());

            $email = strtolower($data['email'] ?? '');
            $name  = $data['name'] ?? '';

            // ── Validate required ──────────────────────────────
            if (!$name || !$email) {
                $this->_skip($rowNum, $email ?: "row {$rowNum}", 'name and email are required.');
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->_skip($rowNum, $email, "'{$email}' is not a valid email.");
                continue;
            }

            // ── Build scalar updates ───────────────────────────
            $updates = [];
            foreach (self::SCALAR_COLUMNS as $col) {
                if (($data[$col] ?? '') !== '') {
                    $updates[$col] = $data[$col];
                }
            }
            // email always comes from the row (it's the lookup key)
            $updates['name'] = $name;

            // Dates
            foreach (self::DATE_COLUMNS as $col) {
                if (($data[$col] ?? '') !== '') {
                    $parsed = $this->parseDate($data[$col]);
                    if ($parsed) {
                        $updates[$col] = $parsed->toDateString();
                    }
                }
            }

            // Booleans
            foreach (self::BOOL_COLUMNS as $col) {
                if (($data[$col] ?? '') !== '') {
                    $updates[$col] = in_array(strtolower($data[$col]), ['1', 'true', 'yes'], true) ? 1 : 0;
                }
            }

            // leave_balance
            if (($data['leave_balance'] ?? '') !== '') {
                $updates['leave_balance'] = (float) $data['leave_balance'];
            }

            // salary
            if (($data['salary'] ?? '') !== '') {
                $updates['salary']      = (float) $data['salary'];
                $updates['salary_type'] = $data['salary_type'] ?? 'monthly';
            }

            // Roles
            $rolesRaw  = $data['roles'] ?? '';
            $roleNames = $rolesRaw !== ''
                ? array_filter(array_map('trim', explode('|', $rolesRaw)))
                : [];
            $validRoles = $roleNames
                ? Role::whereIn('name', $roleNames)->pluck('name')->toArray()
                : [];

            // Password
            $plainPassword = trim($data['password'] ?? '');

            try {
                $existing = User::withTrashed()->where('email', $email)->first();

                if ($existing) {
                    // ── UPDATE ─────────────────────────────────
                    $before = $existing->only(array_keys($updates));

                    if ($plainPassword !== '') {
                        $updates['password'] = bcrypt($plainPassword);
                    }

                    $existing->update($updates);

                    // Roles diff
                    $rolesBefore = $existing->roles->pluck('name')->sort()->values()->all();
                    if (!empty($validRoles)) {
                        $existing->syncRoles($validRoles);
                        $existing->refresh();
                    }
                    $rolesAfter = $existing->roles->pluck('name')->sort()->values()->all();

                    $diff = [];
                    foreach ($updates as $key => $newVal) {
                        if ($key === 'password') continue;
                        $oldVal = $before[$key] ?? null;
                        // Cast both to string for comparison to handle type differences
                        if ((string) $oldVal !== (string) $newVal) {
                            $diff[$key] = ['from' => $oldVal, 'to' => $newVal];
                        }
                    }
                    if ($rolesBefore !== $rolesAfter) {
                        $diff['roles'] = [
                            'from' => implode('|', $rolesBefore),
                            'to'   => implode('|', $rolesAfter),
                        ];
                    }

                    $this->rows[] = [
                        'row'        => $rowNum,
                        'action'     => 'updated',
                        'identifier' => $email,
                        'changes'    => $diff,
                    ];
                    $this->updated++;
                } else {
                    // ── CREATE ─────────────────────────────────
                    $password = $plainPassword ?: Str::random(12);
                    $user = User::create(array_merge($updates, [
                        'email'    => $email,
                        'password' => bcrypt($password),
                    ]));

                    if (!empty($validRoles)) {
                        $user->syncRoles($validRoles);
                    }

                    // Log all non-null, non-password fields that were set
                    $created = array_diff_key($updates, ['password' => true]);
                    $created['email'] = $email;
                    if (!empty($validRoles)) $created['roles'] = implode('|', $validRoles);

                    $this->rows[] = [
                        'row'        => $rowNum,
                        'action'     => 'created',
                        'identifier' => $email,
                        'changes'    => $created,
                    ];
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->_skip($rowNum, $email, $e->getMessage());
            }
        }
    }

    public function headings(): array
    {
        return [
            'name', 'email', 'password',
            'full_name', 'contact_email', 'position', 'grade',
            'phone_number', 'citizen_id', 'tax_code', 'social_insurance_id',
            'home_address', 'birthday', 'contract_expiry',
            'probation_start_date', 'probation_end_date',
            'employment_status', 'is_active', 'wfh_without_approval',
            'leave_balance', 'salary', 'salary_type',
            'roles',
        ];
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = [
            'row'        => $rowNum,
            'action'     => 'skipped',
            'identifier' => $identifier,
            'error'      => $message,
        ];
        $this->skipped++;
    }

    private function parseDate(string $val): ?Carbon
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $val); } catch (\Throwable) {}
        }
        try { return Carbon::parse($val); } catch (\Throwable) { return null; }
    }
}

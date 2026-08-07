<?php

namespace App\Imports;

use App\Models\ImportLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow
{
    public int   $created = 0;
    public int   $updated = 0;
    public int   $skipped = 0;
    public array $errors  = [];
    public array $rows    = [];

    private ?ImportLog $log = null;

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

    public function __construct(private bool $dryRun = false) {}

    public function setLog(ImportLog $log): void
    {
        $this->log = $log;
    }

    public function collection(Collection $rows): void
    {
        if ($this->log) {
            $this->log->update(['total_rows' => $rows->count()]);
        }

        foreach ($rows as $i => $row) {
            $this->processRow(array_map('trim', $row->toArray()), $i + 2);
            $this->tickProgress();
        }
    }

    private function processRow(array $data, int $rowNum): void
    {
        $email = strtolower($data['email'] ?? '');
        $name  = $data['name'] ?? '';

        if (!$name || !$email) {
            $this->_skip($rowNum, $email ?: "row {$rowNum}", 'name and email are required.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_skip($rowNum, $email, "'{$email}' is not a valid email.");
            return;
        }

        $updates = ['name' => $name];
        foreach (self::SCALAR_COLUMNS as $col) {
            if (($data[$col] ?? '') !== '') $updates[$col] = $data[$col];
        }
        foreach (self::DATE_COLUMNS as $col) {
            if (($data[$col] ?? '') !== '') {
                $parsed = $this->parseDate($data[$col]);
                if ($parsed) $updates[$col] = $parsed->toDateString();
            }
        }
        foreach (self::BOOL_COLUMNS as $col) {
            if (($data[$col] ?? '') !== '') {
                $updates[$col] = in_array(strtolower($data[$col]), ['1', 'true', 'yes'], true) ? 1 : 0;
            }
        }
        if (($data['leave_balance'] ?? '') !== '') {
            $updates['leave_balance'] = (float) $data['leave_balance'];
        }
        if (($data['salary'] ?? '') !== '') {
            $updates['salary']      = (float) $data['salary'];
            $updates['salary_type'] = $data['salary_type'] ?? 'monthly';
        }

        $rolesRaw      = $data['roles'] ?? '';
        $roleNames     = $rolesRaw !== '' ? array_filter(array_map('trim', explode('|', $rolesRaw))) : [];
        $validRoles    = $roleNames ? Role::whereIn('name', $roleNames)->pluck('name')->toArray() : [];
        $plainPassword = trim($data['password'] ?? '');

        try {
            $existing = User::withTrashed()->where('email', $email)->first();

            if ($existing) {
                $before      = $existing->only(array_keys($updates));
                $rolesBefore = $existing->roles->pluck('name')->sort()->values()->all();
                $rolesAfter  = !empty($validRoles)
                    ? collect($validRoles)->sort()->values()->all()
                    : $rolesBefore;

                if (!$this->dryRun) {
                    if ($plainPassword !== '') $updates['password'] = bcrypt($plainPassword);
                    $existing->update($updates);
                    if (!empty($validRoles)) {
                        $existing->syncRoles($validRoles);
                        $existing->refresh();
                    }
                    $rolesAfter = $existing->roles->pluck('name')->sort()->values()->all();
                }

                $diff = [];
                foreach ($updates as $key => $newVal) {
                    if ($key === 'password') continue;
                    $oldVal = $before[$key] ?? null;
                    if ((string) $oldVal !== (string) $newVal) {
                        $diff[$key] = ['from' => $oldVal, 'to' => $newVal];
                    }
                }
                if ($plainPassword !== '') {
                    $diff['password'] = ['from' => '***', 'to' => '(set)'];
                }
                if ($rolesBefore !== $rolesAfter) {
                    $diff['roles'] = [
                        'from' => implode('|', $rolesBefore),
                        'to'   => implode('|', $rolesAfter),
                    ];
                }

                $this->rows[] = ['row' => $rowNum, 'action' => 'updated', 'identifier' => $email, 'changes' => $diff];
                $this->updated++;
            } else {
                if (!$this->dryRun) {
                    $password = $plainPassword ?: Str::random(12);
                    $user = User::create(array_merge($updates, [
                        'email'    => $email,
                        'password' => bcrypt($password),
                    ]));
                    if (!empty($validRoles)) $user->syncRoles($validRoles);
                }

                $logged          = array_diff_key($updates, ['password' => true]);
                $logged['email'] = $email;
                if (!empty($validRoles)) $logged['roles'] = implode('|', $validRoles);

                $this->rows[] = ['row' => $rowNum, 'action' => 'created', 'identifier' => $email, 'changes' => $logged];
                $this->created++;
            }
        } catch (\Throwable $e) {
            $this->_skip($rowNum, $email, $e->getMessage());
        }
    }

    private function tickProgress(): void
    {
        if (!$this->dryRun) {
            sleep(1); // testing delay — remove for production
        }
        if ($this->log) {
            $this->log->increment('processed_rows');
        }
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = ['row' => $rowNum, 'action' => 'skipped', 'identifier' => $identifier, 'error' => $message];
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

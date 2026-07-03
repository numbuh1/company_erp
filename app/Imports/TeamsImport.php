<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TeamsImport implements ToCollection, WithHeadingRow
{
    public int   $created = 0;
    public int   $updated = 0;
    public int   $skipped = 0;
    public array $errors  = [];
    public array $rows    = [];

    public function __construct(private bool $dryRun = false) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $data   = $row->toArray();

            $name       = trim($data['name']    ?? '');
            $leadersRaw = trim($data['leaders'] ?? '');
            $membersRaw = trim($data['members'] ?? '');

            if (!$name) {
                $this->_skip($rowNum, "row {$rowNum}", 'team name is required.');
                continue;
            }

            try {
                // Read-only check: does the team already exist?
                $existing = Team::where('name', $name)->first();
                $isNew    = $existing === null;

                if (!$this->dryRun) {
                    $team = $existing ?? Team::create(['name' => $name]);
                } else {
                    $team = $existing; // may be null in dry-run create case
                }

                // Build pivot data
                $pivot = [];
                if ($membersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $membersRaw))) as $entry) {
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => false];
                    }
                }
                if ($leadersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $leadersRaw))) as $entry) {
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => true];
                    }
                }

                // Capture existing members for diff
                $membersBefore = [];
                if (!$isNew && $team) {
                    $team->load('users');
                    foreach ($team->users as $u) {
                        $membersBefore[$u->id] = (bool) $u->pivot->is_leader;
                    }
                }

                if (!$this->dryRun && $team && !empty($pivot)) {
                    $team->users()->sync($pivot);
                }

                // Build change log
                if ($isNew) {
                    $memberNames = [];
                    foreach ($pivot as $uid => $p) {
                        $uname = User::find($uid)?->name ?? "ID:{$uid}";
                        $memberNames[] = $uname . ($p['is_leader'] ? ' (Leader)' : '');
                    }
                    $this->rows[] = [
                        'row'        => $rowNum,
                        'action'     => 'created',
                        'identifier' => $name,
                        'changes'    => ['name' => $name, 'members' => implode(', ', $memberNames)],
                    ];
                    $this->created++;
                } else {
                    $diff = [];
                    foreach ($pivot as $uid => $p) {
                        $wasLeader = $membersBefore[$uid] ?? null;
                        $uname     = User::find($uid)?->name ?? "ID:{$uid}";
                        if ($wasLeader === null) {
                            $diff["member:{$uid}"] = ['from' => null, 'to' => $uname . ($p['is_leader'] ? ' (Leader)' : '')];
                        } elseif ($wasLeader !== $p['is_leader']) {
                            $diff["member:{$uid}"] = [
                                'from' => $uname . ($wasLeader ? ' (Leader)' : ''),
                                'to'   => $uname . ($p['is_leader'] ? ' (Leader)' : ''),
                            ];
                        }
                    }
                    foreach (array_keys($membersBefore) as $uid) {
                        if (!isset($pivot[$uid])) {
                            $uname = User::find($uid)?->name ?? "ID:{$uid}";
                            $diff["member:{$uid}"] = ['from' => $uname, 'to' => null];
                        }
                    }
                    $this->rows[] = [
                        'row'        => $rowNum,
                        'action'     => 'updated',
                        'identifier' => $name,
                        'changes'    => $diff,
                    ];
                    $this->updated++;
                }
            } catch (\Throwable $e) {
                $this->_skip($rowNum, $name, $e->getMessage());
            }
        }
    }

    private function resolveUser(string $val): ?int
    {
        $val = trim($val);
        if ($val === '') return null;
        if (str_contains($val, ':')) {
            $id = (int) explode(':', $val)[0];
            if ($id > 0) return $id;
        }
        if (is_numeric($val)) return (int) $val;
        return User::where('name', $val)->value('id');
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = ['row' => $rowNum, 'action' => 'skipped', 'identifier' => $identifier, 'error' => $message];
        $this->skipped++;
    }
}

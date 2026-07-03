<?php

namespace App\Imports;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeamsImport implements ToCollection, WithHeadings
{
    public int   $created = 0;
    public int   $updated = 0;
    public int   $skipped = 0;
    public array $errors  = [];
    public array $rows    = [];

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
                $team  = Team::firstOrCreate(['name' => $name]);
                $isNew = $team->wasRecentlyCreated;

                // Build pivot data from members column first
                $pivot = [];
                if ($membersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $membersRaw))) as $entry) {
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => false];
                    }
                }

                // Override is_leader from leaders column
                if ($leadersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $leadersRaw))) as $entry) {
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => true];
                    }
                }

                // Capture member state before sync for diff
                $membersBefore = [];
                if (!$isNew) {
                    $team->load('users');
                    foreach ($team->users as $u) {
                        $membersBefore[$u->id] = $u->pivot->is_leader;
                    }
                }

                if (!empty($pivot)) {
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
                        'changes'    => [
                            'name'    => $name,
                            'members' => implode(', ', $memberNames),
                        ],
                    ];
                    $this->created++;
                } else {
                    $diff = [];
                    foreach ($pivot as $uid => $p) {
                        $wasLeader = $membersBefore[$uid] ?? null;
                        if ($wasLeader === null) {
                            $uname = User::find($uid)?->name ?? "ID:{$uid}";
                            $diff["member:{$uid}"] = ['from' => null, 'to' => $uname . ($p['is_leader'] ? ' (Leader)' : '')];
                        } elseif ((bool) $wasLeader !== $p['is_leader']) {
                            $uname = User::find($uid)?->name ?? "ID:{$uid}";
                            $diff["member:{$uid}"] = [
                                'from' => $uname . ($wasLeader ? ' (Leader)' : ''),
                                'to'   => $uname . ($p['is_leader'] ? ' (Leader)' : ''),
                            ];
                        }
                    }
                    // Members removed (in before but not in new pivot)
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

    public function headings(): array
    {
        return ['name', 'leaders', 'members'];
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
        $this->rows[]   = [
            'row'        => $rowNum,
            'action'     => 'skipped',
            'identifier' => $identifier,
            'error'      => $message,
        ];
        $this->skipped++;
    }
}

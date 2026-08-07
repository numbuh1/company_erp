<?php

namespace App\Imports;

use App\Models\ImportLog;
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

    private ?ImportLog $log = null;

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
            $this->processRow($row->toArray(), $i + 2);
            if ($this->log) {
                $this->log->increment('processed_rows');
            }
        }
    }

    private function processRow(array $data, int $rowNum): void
    {
        $email    = trim($data['user_email'] ?? '');
        $teamName = trim($data['team_name']  ?? '');
        $isLeader = $this->parseBool($data['is_leader'] ?? '0');

        if (!$email) {
            $this->_skip($rowNum, "row {$rowNum}", 'user_email is required.');
            return;
        }
        if (!$teamName) {
            $this->_skip($rowNum, $email, 'team_name is required.');
            return;
        }

        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->_skip($rowNum, $email, "No user found with email \"{$email}\".");
                return;
            }

            $existingTeam = Team::where('name', $teamName)->first();
            $teamIsNew    = $existingTeam === null;

            if (!$this->dryRun) {
                $team = $existingTeam ?? Team::create(['name' => $teamName]);
            } else {
                $team = $existingTeam;
            }

            $wasMember = false;
            $wasLeader = false;
            if ($team) {
                $pivot     = $team->users()->where('user_id', $user->id)->first();
                $wasMember = $pivot !== null;
                $wasLeader = $wasMember && (bool) $pivot->pivot->is_leader;
            }

            if (!$this->dryRun && $team) {
                $team->users()->syncWithoutDetaching([$user->id => ['is_leader' => $isLeader]]);
            }

            $identifier = "{$user->name} → {$teamName}";

            if (!$wasMember) {
                $this->rows[] = [
                    'row'        => $rowNum,
                    'action'     => 'created',
                    'identifier' => $identifier,
                    'changes'    => [
                        'team'      => $teamIsNew ? "{$teamName} (new)" : $teamName,
                        'is_leader' => $isLeader ? 'yes' : 'no',
                    ],
                ];
                $this->created++;
            } elseif ($wasLeader !== $isLeader) {
                $this->rows[] = [
                    'row'        => $rowNum,
                    'action'     => 'updated',
                    'identifier' => $identifier,
                    'changes'    => ['is_leader' => ['from' => $wasLeader ? 'yes' : 'no', 'to' => $isLeader ? 'yes' : 'no']],
                ];
                $this->updated++;
            } else {
                $this->rows[] = [
                    'row'        => $rowNum,
                    'action'     => 'updated',
                    'identifier' => $identifier,
                    'changes'    => [],
                ];
                $this->updated++;
            }
        } catch (\Throwable $e) {
            $this->_skip($rowNum, $email, $e->getMessage());
        }
    }

    private function parseBool(mixed $val): bool
    {
        return in_array(strtolower(trim((string) $val)), ['1', 'true', 'yes'], true);
    }

    private function _skip(int $rowNum, string $identifier, string $message): void
    {
        $this->errors[] = "Row {$rowNum}: {$message}";
        $this->rows[]   = ['row' => $rowNum, 'action' => 'skipped', 'identifier' => $identifier, 'error' => $message];
        $this->skipped++;
    }
}

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

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;
            $row    = $row->toArray();

            $name       = trim($row['name']    ?? '');
            $leadersRaw = trim($row['leaders'] ?? '');
            $membersRaw = trim($row['members'] ?? '');

            if (!$name) {
                $this->errors[] = "Row {$rowNum}: team name is required.";
                $this->skipped++;
                continue;
            }

            try {
                $team = Team::firstOrCreate(['name' => $name]);
                $isNew = $team->wasRecentlyCreated;

                // Build pivot data: userId => [is_leader => bool]
                $pivot = [];

                // Parse members column first (includes everyone including leaders)
                if ($membersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $membersRaw))) as $entry) {
                        // Strip trailing label like " (Leader)"
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => false];
                    }
                }

                // Override is_leader for explicit leaders column
                if ($leadersRaw !== '') {
                    foreach (array_filter(array_map('trim', explode('|', $leadersRaw))) as $entry) {
                        $entry = preg_replace('/\s*\(Leader\)\s*$/i', '', $entry);
                        $uid   = $this->resolveUser($entry);
                        if ($uid) $pivot[$uid] = ['is_leader' => true];
                    }
                }

                if (!empty($pivot)) {
                    $team->users()->sync($pivot);
                }

                $isNew ? $this->created++ : $this->updated++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum} ({$name}): " . $e->getMessage();
                $this->skipped++;
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

        // "id:name" format exported by TeamsExport
        if (str_contains($val, ':')) {
            $id = (int) explode(':', $val)[0];
            if ($id > 0) return $id;
        }

        if (is_numeric($val)) return (int) $val;

        return User::where('name', $val)->value('id');
    }
}

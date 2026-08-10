<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SyncTranslations extends Command
{
    protected $signature   = 'translations:sync {--locale= : Only sync a specific locale (en or vi)}';
    protected $description = 'Seed the translations table from JSON language files (skips keys already in DB)';

    public function handle(): int
    {
        $onlyLocale = $this->option('locale');
        $locales    = $onlyLocale ? [$onlyLocale] : ['vi', 'en'];

        foreach ($locales as $locale) {
            $path = lang_path("{$locale}.json");

            if (!File::exists($path)) {
                $this->warn("Skipping {$locale}: {$path} not found.");
                continue;
            }

            $entries = json_decode(File::get($path), true);

            if (!is_array($entries)) {
                $this->error("Could not parse {$path}.");
                continue;
            }

            $inserted = 0;
            $skipped  = 0;

            foreach ($entries as $key => $value) {
                $exists = Translation::where('locale', $locale)
                    ->where('key', $key)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Translation::create(['locale' => $locale, 'key' => $key, 'value' => $value]);
                $inserted++;
            }

            Cache::forget("translations.{$locale}");

            $this->info("[{$locale}] {$inserted} inserted, {$skipped} already in DB.");
        }

        return self::SUCCESS;
    }
}

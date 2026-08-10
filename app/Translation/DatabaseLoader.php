<?php

namespace App\Translation;

use App\Models\Translation;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Cache;

class DatabaseLoader implements Loader
{
    public function __construct(private Loader $fileLoader) {}

    public function load($locale, $group, $namespace = null): array
    {
        $lines = $this->fileLoader->load($locale, $group, $namespace);

        // Only overlay DB values on top of JSON translations (group='*')
        if ($group !== '*') {
            return $lines;
        }

        $dbLines = Cache::remember("translations.{$locale}", 3600, function () use ($locale) {
            return Translation::where('locale', $locale)
                ->pluck('value', 'key')
                ->all();
        });

        // Priority: DB → JSON file → key itself (Laravel's built-in fallback)
        return array_merge($lines, $dbLines);
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->fileLoader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->fileLoader->addJsonPath($path);
    }

    public function namespaces(): array
    {
        return $this->fileLoader->namespaces();
    }
}

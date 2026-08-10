<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TranslationsSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('translations:sync', [], $this->command->getOutput());
    }
}

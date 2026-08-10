<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    protected $fillable = ['locale', 'key', 'value'];

    protected static function booted(): void
    {
        $flush = fn (self $m) => Cache::forget("translations.{$m->locale}");

        static::saved($flush);
        static::deleted($flush);
    }
}

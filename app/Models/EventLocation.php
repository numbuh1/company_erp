<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLocation extends Model
{
    protected $fillable = ['name'];

    public static function resolveOrCreate(string $value): string
    {
        $loc = static::firstOrCreate(['name' => trim($value)]);
        return $loc->name;
    }
}

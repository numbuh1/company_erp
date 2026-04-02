<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentTag extends Model
{
    protected $fillable = ['name', 'type'];

    public static function resolveIds(array $values, string $type): array
    {
        $ids = [];
        foreach ($values as $val) {
            if (is_numeric($val)) {
                $ids[] = (int) $val;
            } else {
                $tag = static::firstOrCreate(
                    ['name' => trim($val), 'type' => $type],
                );
                $ids[] = $tag->id;
            }
        }
        return $ids;
    }
}

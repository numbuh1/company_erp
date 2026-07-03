<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'filename',
        'created_count', 'updated_count', 'skipped_count',
        'rows',
    ];

    protected $casts = [
        'rows' => 'array',
    ];

    public static array $typeLabels = [
        'users'          => 'Người dùng',
        'teams'          => 'Nhóm',
        'leave-requests' => 'Nghỉ phép',
        'ot-requests'    => 'Tăng ca',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function typeLabel(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }
}

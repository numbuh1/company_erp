<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'filename', 'status', 'error_message',
        'total_rows', 'processed_rows',
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
        'leave-balance'  => 'Số dư phép',
        'requests'       => 'Yêu cầu',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function typeLabel(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'in_progress' => 'Đang xử lý',
            'done'        => 'Hoàn thành',
            'error'       => 'Lỗi',
            default       => $this->status,
        };
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            'done'        => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
            'error'       => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            default       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    }
}

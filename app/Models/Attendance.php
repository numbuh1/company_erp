<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'type',
        'status',
        'hours',
        'reason',
        'approved_by',
        'approved_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

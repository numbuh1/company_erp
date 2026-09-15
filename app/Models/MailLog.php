<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'subject',
        'to',
        'cc',
        'bcc',
        'from',
        'body',
        'mailable_class',
        'status',
    ];

    protected $casts = [
        'to'  => 'array',
        'cc'  => 'array',
        'bcc' => 'array',
    ];
}

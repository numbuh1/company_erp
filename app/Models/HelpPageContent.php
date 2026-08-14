<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpPageContent extends Model
{
    protected $fillable = ['help_page_id', 'locale', 'content'];

    public function helpPage(): BelongsTo
    {
        return $this->belongsTo(HelpPage::class);
    }
}

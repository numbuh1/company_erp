<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpPageContent extends Model
{
    protected $fillable = ['help_page_id', 'help_page_component_id', 'locale', 'content'];

    public function helpPage(): BelongsTo
    {
        return $this->belongsTo(HelpPage::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(HelpPageComponent::class, 'help_page_component_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpPageComponent extends Model
{
    protected $fillable = ['help_page_id', 'type', 'sort_order', 'image'];

    public function helpPage(): BelongsTo
    {
        return $this->belongsTo(HelpPage::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(HelpPageContent::class);
    }

    public function getContent(string $locale): ?string
    {
        $exact = $this->contents->firstWhere('locale', $locale);
        if ($exact && filled($exact->content)) {
            return $exact->content;
        }

        $any = $this->contents->first(fn ($c) => filled($c->content));
        return $any?->content;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpPage extends Model
{
    use SoftDeletes;

    protected $fillable = ['route', 'title', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function contents(): HasMany
    {
        return $this->hasMany(HelpPageContent::class);
    }

    /**
     * Return content for the given locale, falling back to any available language.
     */
    public function getContent(string $locale): ?string
    {
        $exact = $this->contents->firstWhere('locale', $locale);
        if ($exact && filled($exact->content)) {
            return $exact->content;
        }

        $any = $this->contents->first(fn($c) => filled($c->content));
        return $any?->content;
    }

    /**
     * Find an active help page for the given route name, with contents eager-loaded.
     */
    public static function forRoute(string $routeName): ?self
    {
        return static::where('route', $routeName)
            ->where('is_active', true)
            ->with('contents')
            ->first();
    }
}

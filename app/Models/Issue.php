<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Issue extends Model
{
    protected $fillable = ['volume', 'number', 'year', 'title', 'cover_path', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugAttribute(): string
    {
        return 'vol'.$this->volume.'-no'.$this->number.'-'.($this->year ?? now()->year);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $slug = (string) $value;
        if (preg_match('/^vol(\d+)-no(\d+)-(\d{4})$/', $slug, $m)) {
            return $this->where('volume', $m[1])->where('number', $m[2])->first();
        }

        return $this->whereKey($value)->first();
    }

    public function publications(): HasMany
    {
        return $this->hasMany(Publication::class)->orderBy('position');
    }

    public function publishedPublications(): HasMany
    {
        return $this->publications()->whereNotNull('publications.published_at')->whereNull('publications.deleted_at');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->orderByDesc('volume')->orderByDesc('number');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function label(): string
    {
        return 'Vol. '.$this->volume.' No. '.$this->number.' ('.$this->year.')';
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? asset('storage/'.$this->cover_path) : null;
    }
}

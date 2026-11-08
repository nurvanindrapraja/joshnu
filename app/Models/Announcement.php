<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title', 'body', 'poster_path', 'type', 'starts_at', 'ends_at', 'is_active', 'by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->starts_at->lte(now())
            && ($this->ends_at === null || $this->ends_at->gt(now()));
    }

    public function getPosterUrlAttribute(): ?string
    {
        return $this->poster_path ? asset('storage/'.$this->poster_path) : null;
    }
}

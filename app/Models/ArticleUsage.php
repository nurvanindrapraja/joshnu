<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleUsage extends Model
{
    protected $table = 'article_usage';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected $fillable = [
        'publication_id', 'type', 'ip_hash', 'session_hash', 'country', 'user_agent', 'created_at',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip.'|'.config('app.key', 'joshnu'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationMetadata extends Model
{
    protected $fillable = ['publication_id', 'lang', 'title', 'abstract', 'keywords'];

    protected function casts(): array
    {
        return ['keywords' => 'array'];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }
}

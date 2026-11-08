<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Galley extends Model
{
    protected $fillable = [
        'publication_id', 'type', 'label', 'path', 'mime_type', 'size_bytes', 'is_version_of_record',
    ];

    protected function casts(): array
    {
        return ['is_version_of_record' => 'boolean'];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class);
    }

    public function humanSize(): string
    {
        $bytes = (float) $this->size_bytes;
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1).' '.$unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' TB';
    }
}

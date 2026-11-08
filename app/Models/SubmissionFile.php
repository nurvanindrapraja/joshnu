<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SubmissionFile extends Model
{
    protected $fillable = [
        'submission_id', 'revision_id', 'type', 'original_name',
        'path', 'mime_type', 'size_bytes', 'uploaded_by',
    ];

    public const WHITELIST = ['pdf', 'doc', 'docx', 'odt', 'tex', 'zip', 'png', 'jpg', 'jpeg', 'gif', 'xlsx'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(SubmissionRevision::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function storagePath(): string
    {
        return $this->path;
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

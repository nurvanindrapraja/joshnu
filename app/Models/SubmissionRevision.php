<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionRevision extends Model
{
    protected $fillable = ['submission_id', 'version', 'note'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }
}

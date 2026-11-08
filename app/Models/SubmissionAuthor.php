<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAuthor extends Model
{
    protected $fillable = [
        'submission_id', 'user_id', 'name', 'email', 'orcid',
        'institution', 'is_corresponding', 'display_order',
    ];

    protected function casts(): array
    {
        return ['is_corresponding' => 'boolean'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

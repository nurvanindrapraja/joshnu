<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Decision extends Model
{
    protected $fillable = [
        'submission_id', 'editor_id', 'round', 'type', 'comment_to_author',
        'internal_note', 'revision_due_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_due_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public const TYPE_LABELS = [
        'accept' => 'Diterima (Accept)',
        'minor_revise' => 'Revisi Minor (Minor Revise)',
        'major_revise' => 'Revisi Mayor (Major Revise)',
        'reject' => 'Ditolak (Reject)',
        'continue_review' => 'Lanjut Review (Continue)',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}

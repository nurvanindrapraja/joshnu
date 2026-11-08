<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'submission_id', 'reviewer_id', 'round', 'status', 'invite_token', 'invite_email',
        'decline_reason', 'recommended_decision', 'rating_novelty', 'rating_methodology',
        'rating_clarity', 'rating_contribution', 'comments_to_author', 'comments_to_editor',
        'due_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** File form review (SubmissionFile type=review_form, di-upload oleh reviewer). */
    public function reviewFiles()
    {
        return $this->submission->files()->where('type', 'review_form')->where('uploaded_by', $this->reviewer_id);
    }

    public function displayReviewerName(): string
    {
        return $this->reviewer?->name ?? $this->invite_email ?? 'Reviewer diundang';
    }

    /** Judul naskah sesuai kebijakan blind review. */
    public function blindTitle(): string
    {
        $setting = \App\Models\JournalSetting::current();
        $title = $this->submission->title;

        if ($setting->review_mode === 'single_blind') {
            return $title.' — '.$this->submission->authors->pluck('name')->implode(', ');
        }

        return $title; // double-blind / open: tanpa nama penulis
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['invited', 'accepted', 'in_progress', 'overdue'], true)
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function daysLeft(): ?int
    {
        return $this->due_at ? (int) $this->due_at->diffInDays(now(), false) : null;
    }
}

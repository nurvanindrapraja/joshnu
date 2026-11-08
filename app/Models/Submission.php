<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Submission extends Model
{
    use SoftDeletes;
    public function getRouteKeyName(): string
    {
        return 'reference_no';
    }

    /**
     * State machine: stage -> daftar stage tujuan yang diizinkan.
     */
    public const TRANSITIONS = [
        'submitted' => ['under_review', 'decision', 'rejected', 'withdrawn'],
        'under_review' => ['decision', 'revised', 'rejected', 'withdrawn'],
        'decision' => ['under_review', 'accepted', 'revised', 'rejected', 'withdrawn'],
        'revised' => ['under_review', 'decision', 'rejected', 'withdrawn'],
        'accepted' => ['scheduled', 'withdrawn'],
        'scheduled' => ['accepted', 'published'],
        'rejected' => [],
        'published' => [],
        'withdrawn' => [],
    ];

    public const STAGE_LABELS = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'decision' => 'Editorial Decision',
        'revised' => 'Revised',
        'rejected' => 'Rejected',
        'accepted' => 'Accepted',
        'scheduled' => 'Scheduled',
        'published' => 'Published',
        'withdrawn' => 'Withdrawn',
    ];

    /** Pipeline display order for author timeline. */
    public const PIPELINE = ['submitted', 'under_review', 'decision', 'revised', 'accepted', 'scheduled', 'published'];

    protected $fillable = [
        'reference_no', 'author_id', 'section_id', 'stage', 'current_round',
        'title', 'cover_letter', 'conflict_of_interest', 'suggested_reviewers',
        'metadata', 'submitted_at', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'suggested_reviewers' => 'array',
            'metadata' => 'array',
            'submitted_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /* ---------------- Relations ---------------- */

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SubmissionRevision::class)->orderBy('version');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(SubmissionAuthor::class)->orderBy('display_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(Decision::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SubmissionEvent::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(Publication::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /* ---------------- Helpers ---------------- */

    public static function nextReferenceNo(): string
    {
        $year = now()->year;
        $last = static::withTrashed()
            ->where('reference_no', 'like', "JOSHNU-{$year}-%")
            ->orderByDesc('id')
            ->value('reference_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('JOSHNU-%d-%04d', $year, $seq);
    }

    public static function nextInvoiceNo(): string
    {
        $year = now()->year;
        $last = Payment::where('invoice_no', 'like', "INV-{$year}-%")->orderByDesc('id')->value('invoice_no');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('INV-%d-%04d', $year, $seq);
    }

    public function stageLabel(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? $this->stage;
    }

    public function canTransitionTo(string $stage): bool
    {
        return in_array($stage, self::TRANSITIONS[$this->stage] ?? [], true);
    }

    public function daysInStage(): int
    {
        $lastStageChange = $this->events()
            ->where('event', 'stage_changed')
            ->latest('created_at')
            ->value('created_at');

        $since = $lastStageChange ?? $this->submitted_at;

        return (int) now()->startOfDay()->diffInDays($since, false);
    }

    public function metadataFor(string $lang): array
    {
        $meta = $this->metadata ?? [];

        return [
            'title' => $meta[$lang.'_title'] ?? ($meta['id_title'] ?? $this->title),
            'abstract' => $meta[$lang.'_abstract'] ?? '',
            'keywords' => $meta['keywords'] ?? [],
        ];
    }

    public function primaryAuthor(): ?SubmissionAuthor
    {
        return $this->authors->first();
    }

    public function latestRevision(): ?SubmissionRevision
    {
        return $this->revisions()->latest('version')->first();
    }

    public function canWithdraw(): bool
    {
        return in_array($this->stage, ['submitted', 'under_review', 'decision', 'revised'], true);
    }

    public function scopeStage($query, string $stage): Builder
    {
        return $query->where('stage', $stage);
    }
}

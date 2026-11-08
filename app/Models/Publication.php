<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Publication extends Model
{
    protected $fillable = [
        'submission_id', 'issue_id', 'section_id', 'slug', 'published_at',
        'is_online_first', 'position', 'doi', 'doi_status', 'doi_response',
        'article_status', 'correction_note', 'retraction_reason', 'views', 'downloads',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_online_first' => 'boolean',
            'doi_response' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(PublicationMetadata::class);
    }

    public function galleys(): HasMany
    {
        return $this->hasMany(Galley::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(ArticleUsage::class);
    }

    public function versionOfRecord(): ?Galley
    {
        return $this->galleys()->where('is_version_of_record', true)->first()
            ?? $this->galleys()->where('type', 'article_pdf')->first();
    }

    public function authors()
    {
        // Publication ─(submission_id)→ Submission ─(id)→ SubmissionAuthor
        return $this->hasManyThrough(
            SubmissionAuthor::class,
            Submission::class,
            'id',              // key pada tabel through (submissions) yang merujuk parent
            'submission_id',   // key pada tabel related (submission_authors) yang merujuk through
            'submission_id',   // key pada parent (publications) yang merujuk through
            'id'
        )->orderBy('display_order');
    }

    public function metadataFor(string $lang): ?PublicationMetadata
    {
        $m = $this->metadata->firstWhere('lang', $lang);
        if ($m) {
            return $m;
        }

        return $this->metadata->first();
    }

    public function titleFor(string $lang): string
    {
        return $this->metadataFor($lang)?->title ?? $this->submission->title;
    }

    public function abstractFor(string $lang): string
    {
        return $this->metadataFor($lang)?->abstract ?? '';
    }

    public function keywordsFor(string $lang): array
    {
        return $this->metadataFor($lang)?->keywords ?? [];
    }

    public function authorString(): string
    {
        $names = $this->authors->pluck('name');
        if ($names->count() <= 3) {
            return $names->implode(', ');
        }

        return $names->take(3)->implode(', ').' et al.';
    }

    public function doiUrl(): ?string
    {
        return $this->doi ? 'https://doi.org/'.$this->doi : null;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->whereNull('deleted_at');
    }
}

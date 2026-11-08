<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['submission_id', 'event', 'from_stage', 'to_stage', 'actor_id', 'meta', 'created_at'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function log(Submission $submission, string $event, ?array $meta = null, ?int $actorId = null, ?string $fromStage = null, ?string $toStage = null): void
    {
        static::create([
            'submission_id' => $submission->id,
            'event' => $event,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'actor_id' => $actorId,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}

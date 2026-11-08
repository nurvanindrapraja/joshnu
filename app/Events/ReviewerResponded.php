<?php

namespace App\Events;

class ReviewerResponded extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Review $review, public readonly bool $accepted, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

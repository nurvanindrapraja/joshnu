<?php

namespace App\Events;

class ReviewSubmitted extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Review $review, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

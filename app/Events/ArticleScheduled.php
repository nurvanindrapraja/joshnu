<?php

namespace App\Events;

class ArticleScheduled extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Publication $publication, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

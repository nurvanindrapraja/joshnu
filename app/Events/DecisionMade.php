<?php

namespace App\Events;

class DecisionMade extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Decision $decision, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

<?php

namespace App\Events;

class RevisionSubmitted extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\SubmissionRevision $revision, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

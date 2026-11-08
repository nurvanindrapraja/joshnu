<?php

namespace App\Events;

class PublicationStatusChanged extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Publication $publication, public readonly string $newStatus, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

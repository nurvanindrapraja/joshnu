<?php

namespace App\Events;

class StageChanged extends PipelineEvent
{
    public function __construct(
        \App\Models\Submission $submission,
        public readonly string $fromStage,
        public readonly string $toStage,
        ?int $actorId = null,
        array $payload = [],
    ) {
        parent::__construct($submission, $actorId, $payload);
    }
}

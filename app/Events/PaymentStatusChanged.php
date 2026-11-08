<?php

namespace App\Events;

class PaymentStatusChanged extends PipelineEvent
{
    public function __construct(\App\Models\Submission $submission, public readonly \App\Models\Payment $payment, public readonly string $newStatus, ?int $actorId = null, array $payload = [])
    {
        parent::__construct($submission, $actorId, $payload);
    }
}

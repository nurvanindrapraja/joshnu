<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dasar pipeline. Dipancarkan (dispatch) oleh SubmissionWorkflow /
 * service terkait sebagai titik ekstensi (plugin dapat subscribe event ini).
 */
abstract class PipelineEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Submission $submission,
        public readonly ?int $actorId = null,
        public readonly array $payload = [],
    ) {
    }

    public function referenceNo(): string
    {
        return $this->submission->reference_no;
    }
}

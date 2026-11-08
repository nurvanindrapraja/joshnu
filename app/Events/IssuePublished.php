<?php

namespace App\Events;

use App\Models\Issue;

class IssuePublished
{
    use \Illuminate\Foundation\Events\Dispatchable;

    public function __construct(
        public readonly Issue $issue,
        public readonly ?int $actorId = null,
    ) {
    }
}

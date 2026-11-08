<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Services\CrossrefService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DepositDoi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(public Publication $publication)
    {
    }

    public function handle(CrossrefService $crossref): void
    {
        if ($this->publication->doi_status === 'registered') {
            return;
        }

        $crossref->depositDoi($this->publication);
    }
}

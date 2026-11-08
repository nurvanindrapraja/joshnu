<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateArticlePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Publication $publication, public bool $isProof = false)
    {
    }

    public function handle(PdfService $pdf): void
    {
        $pdf->generateArticlePdf($this->publication, $this->isProof);
    }
}

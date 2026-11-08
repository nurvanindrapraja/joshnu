<?php

namespace App\Jobs;

use App\Models\Review;
use App\Services\MailNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Tandai review overdue + notifikasi reviewer dan editor (jadwal harian). */
class MarkOverdueReviews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MailNotifier $mailer): void
    {
        $overdue = Review::query()
            ->whereIn('status', ['invited', 'accepted', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with('submission')
            ->get();

        foreach ($overdue as $review) {
            $review->update(['status' => 'overdue']);

            $mailer->notify('review_overdue', [
                'ref' => $review->submission->reference_no,
                'title' => $review->blindTitle(),
                'due_at' => $review->due_at->format('d M Y'),
                'email' => $review->reviewer?->email ?? $review->invite_email,
                'user_id' => $review->reviewer_id,
                'url' => route('review.show', $review->submission->reference_no),
            ]);
        }
    }
}

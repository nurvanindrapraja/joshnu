<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\User;
use App\Services\MailNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reminder otomatis review: dikirim reminder_before_days sebelum due date
 * (jadwal harian via scheduler).
 */
class SendReviewReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MailNotifier $mailer): void
    {
        $setting = \App\Models\JournalSetting::current();
        $days = $setting->reminder_before_days;

        $reviews = Review::query()
            ->whereIn('status', ['invited', 'accepted', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addDays($days))
            ->with('submission')
            ->get();

        foreach ($reviews as $review) {
            $ctx = [
                'ref' => $review->submission->reference_no,
                'title' => $review->blindTitle(),
                'due_at' => $review->due_at->format('d M Y'),
                'url' => route('review.show', $review->submission->reference_no),
                'email' => $review->reviewer?->email ?? $review->invite_email,
                'user_id' => $review->reviewer_id,
            ];
            $mailer->notify('review_reminder', $ctx);
        }
    }
}

<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /** Author hanya melihat naskah sendiri; editorial melihat semua. */
    public function view(User $user, Submission $submission): bool
    {
        return $user->isEditorial()
            || $user->isProduction()
            || $submission->author_id === $user->id
            || $submission->reviews()->whereIn('status', ['invited', 'accepted', 'in_progress', 'submitted', 'overdue'])
                ->where(fn ($q) => $q->where('reviewer_id', $user->id)
                    ->orWhere('invite_email', $user->email))
                ->exists();
    }

    public function editMetadata(User $user, Submission $submission): bool
    {
        return $submission->author_id === $user->id
            && in_array($submission->stage, ['submitted', 'revised'], true);
    }

    public function withdraw(User $user, Submission $submission): bool
    {
        return $submission->author_id === $user->id && $submission->canWithdraw();
    }

    public function uploadFile(User $user, Submission $submission): bool
    {
        return $submission->author_id === $user->id
            && in_array($submission->stage, ['submitted', 'revised'], true);
    }

    public function makeDecision(User $user, Submission $submission): bool
    {
        return $user->isEditorial();
    }

    public function manageReviewers(User $user, Submission $submission): bool
    {
        return $user->isEditorial();
    }
}

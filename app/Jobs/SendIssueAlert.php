<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Services\MailNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Email alert ke subscribers saat issue terbit. */
class SendIssueAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Issue $issue)
    {
    }

    public function handle(MailNotifier $mailer): void
    {
        $articles = $this->issue->publishedPublications()->with('metadata')->get();

        $mailer->notify('issue_published', [
            'issue' => $this->issue->label().($this->issue->title ? ' — "'.$this->issue->title.'"' : ''),
            'articles' => $articles->map(fn ($p) => [
                'title' => $p->titleFor('id'),
                'authors' => $p->authorString(),
            ])->all(),
            'url' => route('issues.show', $this->issue->slug),
        ]);
    }
}

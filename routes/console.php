<?php

use App\Jobs\MarkOverdueReviews;
use App\Jobs\SendReviewReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendReviewReminders)->dailyAt('08:00')->withoutOverlapping();
Schedule::job(new MarkOverdueReviews)->dailyAt('08:05')->withoutOverlapping();

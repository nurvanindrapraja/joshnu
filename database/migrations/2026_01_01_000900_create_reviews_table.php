<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('round')->default(1);
            $table->enum('status', ['invited', 'accepted', 'declined', 'in_progress', 'submitted', 'overdue', 'cancelled'])
                ->default('invited');
            $table->char('invite_token', 64)->nullable()->unique();
            $table->string('invite_email', 190)->nullable();
            $table->text('decline_reason')->nullable();
            $table->enum('recommended_decision', ['accept', 'minor_revise', 'major_revise', 'reject'])->nullable();
            $table->tinyInteger('rating_novelty')->nullable();
            $table->tinyInteger('rating_methodology')->nullable();
            $table->tinyInteger('rating_clarity')->nullable();
            $table->tinyInteger('rating_contribution')->nullable();
            $table->text('comments_to_author')->nullable();
            $table->text('comments_to_editor')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'reviewer_id', 'round'], 'reviews_sub_rev_round_unique');
            $table->index('status');
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users');
            $table->tinyInteger('round');
            $table->enum('type', ['accept', 'minor_revise', 'major_revise', 'reject', 'continue_review']);
            $table->text('comment_to_author');
            $table->text('internal_note')->nullable();
            $table->dateTime('revision_due_at')->nullable();
            $table->dateTime('decided_at');
            $table->timestamps();
            $table->index(['submission_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};

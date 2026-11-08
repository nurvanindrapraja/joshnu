<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 25)->unique();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('section_id')->constrained('sections');
            $table->enum('stage', [
                'submitted', 'under_review', 'decision', 'revised', 'rejected',
                'accepted', 'scheduled', 'published', 'withdrawn',
            ])->default('submitted');
            $table->tinyInteger('current_round')->default(1);
            $table->string('title', 500);
            $table->text('cover_letter')->nullable();
            $table->text('conflict_of_interest')->nullable();
            $table->json('suggested_reviewers')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('stage');
            $table->index('submitted_at');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('submissions', function (Blueprint $table) {
                $table->fulltext(['title'], 'ft_title');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};

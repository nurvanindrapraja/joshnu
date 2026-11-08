<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('section_id')->constrained('sections');
            $table->string('slug', 200)->unique();
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_online_first')->default(false);
            $table->smallInteger('position')->default(0);
            $table->string('doi', 64)->nullable()->unique();
            $table->enum('doi_status', ['pending', 'deposited', 'registered', 'failed'])->default('pending');
            $table->json('doi_response')->nullable();
            $table->enum('article_status', ['active', 'corrected', 'retracted'])->default('active');
            $table->text('correction_note')->nullable();
            $table->text('retraction_reason')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['issue_id', 'position']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publications');
    }
};

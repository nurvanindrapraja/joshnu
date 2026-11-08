<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->nullable()->constrained('submission_revisions')->nullOnDelete();
            $table->enum('type', [
                'manuscript', 'cover_letter', 'figure', 'table',
                'supplementary', 'review_response', 'review_form', 'proof',
            ]);
            $table->string('original_name');
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['submission_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['article_pdf', 'proof', 'supplementary', 'poster', 'jats_xml']);
            $table->string('label', 150)->nullable();
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->boolean('is_version_of_record')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['publication_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galleys');
    }
};

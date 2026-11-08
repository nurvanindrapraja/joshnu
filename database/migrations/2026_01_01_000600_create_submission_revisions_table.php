<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('version');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_revisions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('orcid', 19)->nullable();
            $table->string('institution')->nullable();
            $table->boolean('is_corresponding')->default(false);
            $table->smallInteger('display_order');
            $table->timestamps();
            $table->unique(['submission_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_authors');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 50);
            $table->string('tagline')->nullable();
            $table->string('issn_print', 20)->nullable();
            $table->string('issn_online', 20)->nullable();
            $table->text('scope')->nullable();
            $table->longText('description')->nullable();
            $table->longText('ethics_policy')->nullable();
            $table->longText('oa_policy')->nullable();
            $table->longText('apc_policy')->nullable();
            $table->json('editorial_board')->nullable();
            $table->boolean('apc_enabled')->default(false);
            $table->decimal('apc_amount', 12, 2)->nullable();
            $table->char('apc_currency', 3)->default('IDR');
            $table->json('apc_bank')->nullable();
            $table->enum('review_mode', ['single_blind', 'double_blind', 'open'])->default('double_blind');
            $table->tinyInteger('max_review_rounds')->default(3);
            $table->tinyInteger('review_deadline_days')->default(30);
            $table->tinyInteger('reminder_before_days')->default(7);
            $table->boolean('online_first_enabled')->default(true);
            $table->string('doi_prefix', 40)->nullable();
            $table->string('doi_suffix_pattern', 60)->nullable();
            $table->json('theme')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->json('social')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_settings');
    }
};

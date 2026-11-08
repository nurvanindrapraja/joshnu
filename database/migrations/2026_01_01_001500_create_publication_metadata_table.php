<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->char('lang', 2);
            $table->string('title', 500);
            $table->text('abstract');
            $table->json('keywords');
            $table->timestamps();
            $table->unique(['publication_id', 'lang']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('publication_metadata', function (Blueprint $table) {
                $table->fulltext(['title', 'abstract'], 'ft_pub_meta');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_metadata');
    }
};

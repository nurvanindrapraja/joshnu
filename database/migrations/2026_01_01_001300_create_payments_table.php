<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 30)->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('IDR');
            $table->enum('status', ['pending', 'proof_uploaded', 'verified', 'rejected', 'paid', 'refunded', 'cancelled'])
                ->default('pending');
            $table->dateTime('due_at')->nullable();
            $table->string('payer_name', 150)->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_payment_id', 100)->nullable();
            $table->json('webhook_log')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

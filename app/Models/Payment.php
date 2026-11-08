<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'submission_id', 'invoice_no', 'amount', 'currency', 'status', 'due_at',
        'payer_name', 'proof_path', 'rejected_reason', 'verified_by', 'verified_at',
        'gateway', 'gateway_payment_id', 'webhook_log',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_at' => 'datetime',
            'verified_at' => 'datetime',
            'webhook_log' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function formatAmount(): string
    {
        $n = (float) $this->amount;
        return $this->currency.' '.number_format($n, 0, ',', '.');
    }
}

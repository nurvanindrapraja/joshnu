<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'type', 'data', 'read_at', 'created_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function send(int $userId, string $type, array $data = []): void
    {
        static::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    public function label(): string
    {
        $s = $this->data['submission_ref'] ?? '';
        $title = $this->data['title'] ?? '';

        return match ($this->type) {
            'new_submission' => "Naskah baru: {$s}",
            'submission_received' => "Naskah diterima: {$s}",
            'review_started' => "Masuk tahap review: {$s}",
            'with_editor' => "Sedang dinilai redaksi: {$s}",
            'review_assigned' => "Undangan review: {$s}",
            'review_submitted' => "Review masuk: {$s}",
            'decision_made' => "Keputusan editorial: {$s}",
            'revision_received' => "Revisi diterima: {$s}",
            'article_accepted' => "Naskah diterima: {$title}",
            'article_scheduled' => "Naskah dijadwalkan: {$s}",
            'article_published' => "Artikel terbit: {$title}",
            'payment_proof' => "Bukti pembayaran: {$s}",
            'payment_verified' => "Pembayaran terverifikasi: {$s}",
            'payment_rejected' => "Pembayaran ditolak: {$s}",
            'issue_published' => "Issue baru terbit",
            'editor_message' => "Pesan dari redaksi: {$s}",
            'reviewer_accepted' => "Reviewer menerima undangan: {$s}",
            'reviewer_declined' => "Reviewer menolak undangan: {$s}",
            default => ucfirst(str_replace('_', ' ', $this->type)).($title ? ": {$title}" : ''),
        };
    }
}

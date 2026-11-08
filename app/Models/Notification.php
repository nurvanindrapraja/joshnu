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

    /**
     * URL tujuan notifikasi, ditulisi ulang ke host aplikasi saat ini.
     *
     * URL disimpan sebagai absolut saat notifikasi dibuat (mis. dari lingkungan
     * dev → "http://127.0.0.1:8000/..."). Agar link tetap benar ketika aplikasi
     * dipindah ke hosting, hanya path + query yang diambil lalu digabung dengan
     * skema + host permintaan berjalan (fallback: APP_URL).
     */
    public function getUrlAttribute(): ?string
    {
        $url = $this->data['url'] ?? null;
        if (! is_string($url) || $url === '') {
            return $url;
        }

        // Sudah path relatif → gunakan apa adanya.
        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! isset($parts['path'])) {
            return $url;
        }

        $path = $parts['path'];
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        $base = $this->currentBaseUrl();

        return $base.$path.$query.$fragment;
    }

    private function currentBaseUrl(): string
    {
        $request = app('request');

        // Konteks web: gunakan skema + host yang sedang diakses user.
        // Konteks console/queue: request sintetis berbasis APP_URL.
        if ($request && $request->getHost()) {
            return $request->getSchemeAndHttpHost();
        }

        return rtrim((string) config('app.url'), '/');
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

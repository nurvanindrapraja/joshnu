<?php

namespace App\Services;

use App\Models\ArticleUsage;
use App\Models\Publication;

/**
 * Hitung view/download dengan dedup: 1 IP+session terhitung 1x per 24 jam.
 */
class UsageTracker
{
    public function record(Publication $p, string $type): void
    {
        $ipHash = ArticleUsage::hashIp(request()->ip() ?? '0.0.0.0');
        $sessionHash = request()->hasSession() ? hash('sha256', session()->getId()) : null;

        $recent = ArticleUsage::query()
            ->where('publication_id', $p->id)
            ->where('type', $type)
            ->where('ip_hash', $ipHash)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($recent) {
            return; // dedup 24 jam per IP
        }

        ArticleUsage::create([
            'publication_id' => $p->id,
            'type' => $type,
            'ip_hash' => $ipHash,
            'session_hash' => $sessionHash,
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);

        $p->increment($type === 'view' ? 'views' : 'downloads');
    }
}

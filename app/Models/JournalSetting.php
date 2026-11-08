<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class JournalSetting extends Model
{
    protected $table = 'journal_settings';

    protected $fillable = [
        'name', 'short_name', 'tagline', 'issn_print', 'issn_online', 'scope', 'scope_en',
        'description', 'description_en', 'ethics_policy', 'ethics_policy_en',
        'oa_policy', 'oa_policy_en', 'apc_policy', 'apc_policy_en',
        'editorial_board',
        'apc_enabled', 'apc_amount', 'apc_currency', 'apc_bank', 'review_mode',
        'max_review_rounds', 'review_deadline_days', 'reminder_before_days',
        'online_first_enabled', 'doi_prefix', 'doi_suffix_pattern', 'theme',
        'contact_email', 'address', 'social',
    ];

    protected function casts(): array
    {
        return [
            'editorial_board' => 'array',
            'apc_bank' => 'array',
            'apc_enabled' => 'boolean',
            'online_first_enabled' => 'boolean',
            'apc_amount' => 'decimal:2',
            'theme' => 'array',
            'social' => 'array',
        ];
    }

    /**
     * Ambil (dan cache) satu baris konfigurasi jurnal aktif.
     */
    public static function current(): static
    {
        return Cache::remember('journal.current', now()->addDay(), function () {
            return static::firstOrCreate(
                ['id' => 1],
                ['name' => 'JOSHNU', 'short_name' => 'JOSHNU']
            );
        });
    }

    public static function refreshCache(): void
    {
        cache()->forget('journal.current');
    }

    public function primaryColor(): string
    {
        return $this->theme['colors']['primary'] ?? '#1b6e5a';
    }

    /**
     * Ambil konten sesuai locale aktif (EN → kolom _en, fallback ke ID).
     */
    public function text(string $field): ?string
    {
        $base = $this->getAttribute($field);
        $en = $this->getAttribute($field . '_en');
        if (app()->getLocale() === 'en' && filled($en)) {
            return $en;
        }
        return filled($base) ? $base : $en;
    }
}

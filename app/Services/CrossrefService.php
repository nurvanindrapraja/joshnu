<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\JournalSetting;
use App\Models\Publication;
use Illuminate\Support\Facades\Http;

/**
 * Deposit DOI + metadata ke Crossref.
 * Bila API key belum dikonfigurasi, deposit disimulasikan (simpan respons
 * "simulated") agar alur produksi tetap berjalan di dev/UAT.
 */
class CrossrefService
{
    public function depositDoi(Publication $p, ?int $actorId = null): array
    {
        $setting = JournalSetting::current();
        $apiKey = config('services.crossref.api_key');
        $doi = $p->doi;

        if (! $doi) {
            return $this->storeResult($p, 'failed', ['error' => 'DOI belum tersedia']);
        }

        $payload = $this->buildPayload($p);

        if ($apiKey === null || $apiKey === '') {
            // Simulasi mode dev: tandai deposited tanpa panggilan HTTP.
            $result = $this->storeResult($p, 'deposited', [
                'simulated' => true,
                'message' => 'Crossref API key belum diset; deposit disimulasikan.',
                'payload' => $payload,
            ]);
            AuditLog::log($actorId, 'doi.deposit_simulated', 'Publication', $p->id, ['doi' => $doi]);

            return $result;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.crossref.org/works/'.$doi, $payload);

            $status = $response->successful() ? 'registered' : 'failed';
            $result = $this->storeResult($p, $status, [
                'http_status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            AuditLog::log($actorId, 'doi.deposit', 'Publication', $p->id, [
                'doi' => $doi, 'status' => $status, 'http' => $response->status(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $result = $this->storeResult($p, 'failed', ['error' => $e->getMessage()]);
            AuditLog::log($actorId, 'doi.deposit_failed', 'Publication', $p->id, ['doi' => $doi]);

            return $result;
        }
    }

    private function storeResult(Publication $p, string $status, array $response): array
    {
        $p->update([
            'doi_status' => $status,
            'doi_response' => array_merge($response, ['at' => now()->toIso8601String()]),
        ]);

        return ['doi' => $p->doi, 'status' => $status, 'response' => $response];
    }

    private function buildPayload(Publication $p): array
    {
        $meta = $p->metadataFor('en') ?? $p->metadataFor('id');
        $issue = $p->issue;

        return [
            'DOI' => $p->doi,
            'Title' => [$meta->title],
            'author' => $p->authors->map(fn ($a) => [
                'given' => $a->name,
                'family' => $a->name,
                'ORCID' => $a->orcid,
                'affiliation' => [['name' => $a->institution]],
            ])->all(),
            'abstract' => $meta->abstract,
            'journal-title' => [$p->section?->name ?? 'JOSHNU'],
            'journal-volume' => $issue?->volume,
            'issue' => $issue?->number,
            'published' => [
                'date-parts' => [[optional($p->published_at)->format('Y'), optional($p->published_at)->format('m'), optional($p->published_at)->format('d')]],
            ],
            'URL' => route('articles.show', $p->slug, absolute: true),
            'license' => ['URL' => 'https://creativecommons.org/licenses/by/4.0/'],
        ];
    }
}

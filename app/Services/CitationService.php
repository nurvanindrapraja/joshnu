<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Support\Str;

class CitationService
{
    public function format(Publication $p, string $format, string $lang = 'id'): string
    {
        $meta = $p->metadataFor($lang);
        $year = optional($p->published_at)->format('Y') ?? now()->year;
        $issue = $p->issue;
        $volNo = $issue ? "Vol. {$issue->volume}, No. {$issue->number}" : 'Online First';
        $setting = \App\Models\JournalSetting::current();
        $journal = $setting->name;
        $doi = $p->doi ?? '';

        $names = $p->authors->pluck('name');
        $lastFirst = $names->map(function ($name) {
            $parts = preg_split('/\s+/', trim($name));
            $last = array_pop($parts);
            $initials = collect($parts)->map(fn ($x) => mb_strtoupper(mb_substr($x, 0, 1)))->implode(' ');

            return $last.($initials ? ', '.$initials : '');
        });

        $all = $names->implode(', ');
        $first = $lastFirst->first();
        $etAl = $names->count() > 1 ? ', et al.' : '';

        return match ($format) {
            'apa' => "{$first}{$etAl} ({$year}). {$meta->title}. {$journal}, {$volNo}. https://doi.org/{$doi}",
            'mla' => "{$names->first()}. \"{$meta->title}.\" {$journal}, vol. {$issue?->volume}, no. {$issue?->number}, {$year}.",
            'chicago' => "{$first}. \"{$meta->title}.\" {$journal}, vol. {$issue?->volume}, no. {$issue?->number} ({$year}).",
            'bibtex' => $this->bibtex($p, $meta, $year, $journal, $doi),
            'ris' => $this->ris($p, $meta, $year, $journal, $doi),
            'endnote' => $this->endnote($p, $meta, $year, $journal, $doi),
            default => "{$first}{$etAl} ({$year}). {$meta->title}. {$journal}, {$volNo}.",
        };
    }

    private function citeKey(Publication $p): string
    {
        $first = $p->authors->first()?->name ?? 'joshnu';
        $key = Str::slug(preg_replace('/[^A-Za-z]/', '', explode(' ', $first)[0]), '_');

        return strtolower($key).'_'.optional($p->published_at)->format('Y');
    }

    private function bibtex(Publication $p, $meta, int $year, string $journal, string $doi): string
    {
        $authors = $p->authors->pluck('name')->implode(' and ');
        $key = $this->citeKey($p);

        return "@article{$key},\n"
            ."  title   = {{$meta->title}},\n"
            ."  author  = {{$authors}},\n"
            ."  journal = {{$journal}},\n"
            ."  year    = {{$year}},\n"
            .($p->issue ? "  volume  = {{$p->issue->volume}},\n  number  = {{$p->issue->number}},\n" : '')
            .($doi ? "  doi     = {{$doi}},\n" : '')
            ."  publisher = {JOSHNU}\n"
            ."}\n";
    }

    private function ris(Publication $p, $meta, int $year, string $journal, string $doi): string
    {
        $lines = [
            'TY  - JOUR',
            'TI  - '.$meta->title,
            'JO  - '.$journal,
            'PY  - '.$year,
            'VL  - '.($p->issue?->volume ?? ''),
            'IS  - '.($p->issue?->number ?? ''),
            'KW  - '.implode("\nKW  - ", $meta->keywords ?? []),
            'DO  - '.$doi,
            'UR  - '.($p->doiUrl() ?? url('/article/'.$p->slug)),
        ];
        foreach ($p->authors as $a) {
            $lines[] = 'AU  - '.$a->name;
        }
        $lines[] = 'ER  - ';

        return implode("\n", $lines)."\n";
    }

    private function endnote(Publication $p, $meta, int $year, string $journal, string $doi): string
    {
        $authors = $p->authors->pluck('name')->implode(' ');

        return "%0 Journal Article\n"
            ."%T {$meta->title}\n"
            ."%A {$authors}\n"
            ."%J {$journal}\n"
            ."%D JOSHNU\n"
            ."%V ".($p->issue?->volume ?? '')."\n"
            ."%N ".($p->issue?->number ?? '')."\n"
            ."%U ".($p->doiUrl() ?? '')."\n"
            ."%X {$meta->abstract}\n"
            ."%K ".implode(', ', $meta->keywords ?? [" "])."\n"
            ."%D {$year}\n";
    }
}

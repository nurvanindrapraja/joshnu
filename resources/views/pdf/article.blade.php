<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $p->titleFor('id') }}</title>
<style>
    @page { size: A4; margin: 22mm 18mm; @bottom-center { content: counter(page) " / " counter(pages); font-size: 8px; color: #9ca3af; } }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111827; line-height: 1.55; }
    .masthead { border-bottom: 3px solid #1b6e5a; padding-bottom: 8px; margin-bottom: 18px; }
    .masthead .journal { font-size: 13pt; font-weight: bold; color: #1b6e5a; }
    .masthead .issn { font-size: 8pt; color: #6b7280; }
    h1.title { font-size: 14pt; line-height: 1.3; margin: 0 0 10px; }
    .authors { font-size: 10pt; margin-bottom: 2px; }
    .affil { font-size: 8.5pt; color: #4b5563; }
    .meta { font-size: 8.5pt; color: #6b7280; margin: 10px 0 16px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    h2 { font-size: 11pt; text-transform: uppercase; letter-spacing: 0.05em; color: #1b6e5a; margin: 18px 0 6px; }
    .abstract { font-size: 9.5pt; text-align: justify; }
    .keywords { font-size: 9pt; margin-top: 8px; }
    .doi { font-family: monospace; font-size: 9pt; }
    .proof-watermark { position: absolute; top: 40%; left: 10%; font-size: 60pt; color: rgba(220,38,38,0.15); transform: rotate(-30deg); font-weight: bold; }
    .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 8pt; color: #9ca3af; }
</style>
</head>
<body>
@if($isProof)<div class="proof-watermark">PROOF</div>@endif

@php($logoPath = storage_path('app/public/'.(\App\Models\JournalSetting::current()->theme['logo'] ?? '')))
<div class="masthead">
    <div class="journal">@if(file_exists($logoPath))<img src="{{ $logoPath }}" style="height: 24pt; vertical-align: -7pt; margin-right: 6pt;">@endif{{ \App\Models\JournalSetting::current()->name }}</div>
    <div class="issn">
        @if(\App\Models\JournalSetting::current()->issn_online)ISSN e-{{ \App\Models\JournalSetting::current()->issn_online }}@endif
        @if($p->issue) — Vol. {{ $p->issue->volume }} No. {{ $p->issue->number }} ({{ $p->issue->year }})@else — Online First @endif
    </div>
</div>

<h1 class="title">{{ $p->titleFor('id') }}</h1>

<p class="authors">
    @foreach($p->authors as $i => $a){{ $a->name }}<sup>{{ $i + 1 }}@if($a->is_corresponding)*@endif</sup> @if(!$loop->last), @endif
@endforeach
</p>
@foreach($p->authors->map(fn ($a) => $a->institution)->filter()->unique()->values() as $i => $inst)
    <p class="affil">{{ $i + 1 }}. {{ $inst }}</p>
@endforeach
@php($corr = $p->authors->firstWhere('is_corresponding', true))
@if($corr?->email)<p class="affil">* Korespondensi: {{ $corr->email }}</p>@endif

<div class="meta">
    Received {{ optional($p->submission->submitted_at)->format('d M Y') }}
    @php($acceptDate = $p->submission->decisions()->where('type', 'accept')->max('decided_at'))
    @if($acceptDate) • Accepted {{ \Carbon\Carbon::parse($acceptDate)->format('d M Y') }} @endif
    @if($p->published_at) • Published {{ $p->published_at->format('d M Y') }} @endif
    @if($p->doi)<br>DOI: <span class="doi">{{ $p->doi }}</span>@endif
</div>

@if($p->metadataFor('id'))
    <h2>Abstrak</h2>
    <div class="abstract">{{ $p->abstractFor('id') }}</div>
    @if($p->keywordsFor('id'))
        <p class="keywords"><strong>Kata kunci:</strong> {{ implode('; ', $p->keywordsFor('id')) }}</p>
    @endif
@endif
@if($p->metadataFor('en'))
    <h2>Abstract</h2>
    <div class="abstract">{{ $p->abstractFor('en') }}</div>
    @if($p->keywordsFor('en'))
        <p class="keywords"><strong>Keywords:</strong> {{ implode('; ', $p->keywordsFor('en')) }}</p>
    @endif
@endif

@php($m = $p->submission->metadata ?? [])
@if($m['funding'] ?? null)
    <h2>Pendanaan</h2>
    <p>{{ $m['funding'] }}</p>
@endif
@if($m['acknowledgments'] ?? null)
    <h2>Ucapan Terima Kasih</h2>
    <p>{{ $m['acknowledgments'] }}</p>
@endif

<div class="footer">
    © {{ now()->year }} {{ $p->authorString() }}. Dilisensikan di bawah <strong>CC BY 4.0</strong> (https://creativecommons.org/licenses/by/4.0/).
    Hak cipta milik penulis; jurnal memiliki hak terbit pertama.
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Issue {{ $issue->label() }}</title>
<style>
    @page { size: A4; margin: 22mm 18mm; @bottom-center { content: counter(page) " / " counter(pages); font-size: 8px; color: #9ca3af; } }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #111827; line-height: 1.5; }
    .masthead { border-bottom: 3px solid #1b6e5a; padding-bottom: 8px; margin-bottom: 20px; }
    .journal { font-size: 13pt; font-weight: bold; color: #1b6e5a; }
    .issn { font-size: 8pt; color: #6b7280; }
    h1 { font-size: 14pt; margin: 0 0 4px; }
    .sub { font-size: 9pt; color: #6b7280; margin-bottom: 20px; }
    .article { border-top: 1px solid #e5e7eb; padding: 14px 0; page-break-inside: avoid; }
    .article h2 { font-size: 11pt; margin: 0 0 4px; }
    .article .authors { font-size: 9pt; color: #374151; margin: 0 0 6px; }
    .article .abs { font-size: 8.5pt; color: #4b5563; text-align: justify; }
    .article .doi { font-family: monospace; font-size: 8pt; color: #1b6e5a; margin-top: 6px; }
</style>
</head>
<body>
@php($logoPath = storage_path('app/public/'.(\App\Models\JournalSetting::current()->theme['logo'] ?? '')))
<div class="masthead">
    <div class="journal">@if(file_exists($logoPath))<img src="{{ $logoPath }}" style="height: 24pt; vertical-align: -7pt; margin-right: 6pt;">@endif{{ \App\Models\JournalSetting::current()->name }}</div>
    <div class="issn">@if(\App\Models\JournalSetting::current()->issn_online)ISSN e-{{ \App\Models\JournalSetting::current()->issn_online }}@endif</div>
</div>

<h1>Vol. {{ $issue->volume }} No. {{ $issue->number }} ({{ $issue->year }})</h1>
@if($issue->title)<p class="sub">Tema: {{ $issue->title }}</p>@endif
<p class="sub">Terbit {{ $issue->published_at?->format('d M Y') }} — Daftar Artikel</p>

@foreach($issue->publishedPublications as $p)
    <div class="article">
        <h2>{{ $p->titleFor('id') }}</h2>
        <p class="authors">{{ $p->authorString() }}</p>
        <p class="abs">{{ \Illuminate\Support\Str::limit($p->abstractFor('id'), 400) }}</p>
        @if($p->doi)<p class="doi">DOI: {{ $p->doi }}</p>@endif
    </div>
@endforeach
</body>
</html>

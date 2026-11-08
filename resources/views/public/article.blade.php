@extends('layouts.app')
@section('title', $publication->titleFor('id').' — '.\App\Models\JournalSetting::current()->name)
@section('meta_description', Str::limit($publication->abstractFor('id'), 155))
@section('content')
@php($lang = app()->isLocale('en') ? 'en' : 'id')
@php($meta = $publication->metadataFor($lang))
@php($setting = \App\Models\JournalSetting::current())
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    @if($publication->issue)
        <nav class="text-xs text-gray-500 mb-4">
            <a href="{{ route('issues.index') }}" class="hover:text-primary">Archive</a> /
            <a href="{{ route('issues.show', $publication->issue->slug) }}" class="hover:text-primary">{{ $publication->issue->label() }}</a>
        </nav>
    @endif

    @if($publication->article_status === 'retracted')
        <div class="mb-4 rounded-lg border-2 border-red-600 bg-red-50 p-4">
            <p class="font-bold text-red-700">RETRACTED — Artikel ini telah dicabut</p>
            <p class="text-sm text-red-700 mt-1">{{ $publication->retraction_reason }}</p>
        </div>
    @elseif($publication->article_status === 'corrected')
        <div class="mb-4 rounded-lg border border-amber-500 bg-amber-50 p-4">
            <p class="font-bold text-amber-800">CORRECTED — Terdapat pembetulan</p>
            <p class="text-sm text-amber-800 mt-1">{{ $publication->correction_note }}</p>
        </div>
    @endif

    <header class="mb-6">
        <div class="flex flex-wrap gap-2 mb-3">
            @if($publication->is_online_first)
                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-teal-50 text-teal-700">{{ __('article.online_first') }}</span>
            @endif
            <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $publication->section?->name }}</span>
        </div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 leading-tight">{{ $meta->title }}</h1>

        <div class="mt-4">
            @php($names = $publication->authors)
            <p class="text-sm text-gray-800">
                @foreach($names as $i => $a)
                    <span class="font-medium">{{ $a->name }}<sup>{{ $i + 1 }}@if($a->is_corresponding)*@endif</sup></span>
                    @if(!$loop->last), @endif
                @endforeach
            </p>
            @php($insts = $names->map(fn ($a) => $a->institution)->filter()->unique()->values())
            @foreach($insts as $i => $inst)
                <p class="text-xs text-gray-500">{{ $i + 1 }}. {{ $inst }}</p>
            @endforeach
            @php($corr = $names->firstWhere('is_corresponding', true))
            @if($corr?->email)<p class="text-xs text-gray-500 mt-1">* Korespondensi: <a href="mailto:{{ $corr->email }}" class="text-primary hover:underline">{{ $corr->email }}</a></p>@endif
        </div>

        <dl class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500">
            <div><dt class="font-medium text-gray-700">{{ __('article.received') }}</dt><dd>{{ optional($publication->submission->submitted_at)->format('d M Y') }}</dd></div>
            @php($lastReview = $publication->submission->reviews()->whereNotNull('submitted_at')->max('submitted_at'))
            <div><dt class="font-medium text-gray-700">{{ __('article.reviewed') }}</dt><dd>{{ optional(\Carbon\Carbon::parse($lastReview))->format('d M Y') ?? '—' }}</dd></div>
            @php($acceptDate = $publication->submission->decisions()->where('type', 'accept')->max('decided_at'))
            <div><dt class="font-medium text-gray-700">{{ __('article.accepted') }}</dt><dd>{{ optional(\Carbon\Carbon::parse($acceptDate))->format('d M Y') ?? '—' }}</dd></div>
            <div><dt class="font-medium text-gray-700">{{ __('article.published') }}</dt><dd>{{ optional($publication->published_at)->format('d M Y') }}</dd></div>
        </dl>

        @if($publication->doi)
            <p class="mt-3 text-sm">
                <span class="inline-flex items-center gap-1.5 rounded bg-blue-50 text-blue-700 font-mono px-2 py-1">
                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm1 15h-2v-6h2v6Zm0-8h-2V7h2v2Z"/></svg>
                    DOI: {{ $publication->doi }}
                </span>
                <a href="{{ $publication->doiUrl() }}" target="_blank" class="ml-2 text-xs text-primary hover:underline">doi.org</a>
            </p>
        @endif
    </header>

    <section class="rounded-xl border border-gray-200 bg-white p-6 mb-6">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3">{{ __('article.abstract') }} <span class="normal-case font-normal text-gray-400">({{ strtoupper($lang) }})</span></h2>
        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $meta->abstract }}</p>
        @if(!empty($meta->keywords))
            <p class="mt-4 text-sm"><span class="font-semibold text-gray-700">{{ __('article.keywords') }}:</span>
                @foreach($meta->keywords as $i => $kw)
                    <span class="inline-block bg-primary-light text-gray-700 text-xs rounded-full px-2.5 py-0.5 mt-1">{{ $kw }}</span>
                @endforeach
            </p>
        @endif
    </section>

    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-4">{{ __('article.galleys') }}</h2>
            <ul class="divide-y divide-gray-100">
                @forelse($publication->galleys as $g)
                    <li class="py-3 flex items-center gap-3">
                        <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625A2.625 2.625 0 0 0 3 8.25v13.5A2.625 2.625 0 0 0 5.625 24h12.75a2.625 2.625 0 0 0 2.625-2.625V15.375a2.625 2.625 0 0 0-2.625-2.625H15" /></svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $g->label ?? ucfirst($g->type) }}</p>
                            <p class="text-xs text-gray-400">{{ $g->humanSize() }}</p>
                        </div>
                        <a href="{{ route('articles.download', [$publication->slug, $g->id]) }}" class="inline-flex items-center gap-1.5 text-sm font-medium {{ $g->is_version_of_record ? 'bg-primary text-white' : 'border border-gray-300 text-gray-700 hover:border-primary hover:text-primary' }} rounded-lg px-3 py-1.5">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ $g->is_version_of_record ? 'Version of Record' : 'Unduh' }}
                        </a>
                    </li>
                @empty
                    <li class="py-2 text-sm text-gray-500">Belum ada dokumen.</li>
                @endforelse
            </ul>

            <div class="mt-4 pt-4 border-t border-gray-100 flex gap-6 text-sm text-gray-500">
                <span class="flex items-center gap-1.5">👁 {{ __('article.views') }}: {{ number_format($publication->views) }}</span>
                <span class="flex items-center gap-1.5">⬇ {{ __('article.downloads') }}: {{ number_format($publication->downloads) }}</span>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                {{ __('article.license') }}:
                <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" class="text-primary hover:underline">CC BY 4.0</a>
            </p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6" x-data="{ format: 'apa', citation: null, loading: false,
            load: async function () {
                this.loading = true;
                const res = await fetch('{{ route('articles.citations', $publication->slug) }}?format=' + this.format, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                const json = await res.json();
                this.citation = json.data.citation;
                this.loading = false;
            },
            copy: async function () { if (this.citation) { await navigator.clipboard.writeText(this.citation); } }
        }" x-init="load()">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500 mb-3">{{ __('article.citation') }}</h2>
            <select x-model="format" @change="load()" class="w-full rounded-lg border-gray-300 text-sm p-2 border mb-3">
                <option value="apa">APA</option>
                <option value="mla">MLA</option>
                <option value="chicago">Chicago</option>
                <option value="bibtex">BibTeX</option>
                <option value="ris">RIS</option>
                <option value="endnote">EndNote</option>
            </select>
            <pre class="whitespace-pre-wrap rounded-lg bg-gray-50 border border-gray-100 p-3 text-xs font-mono text-gray-700 min-h-[80px]" x-text="citation || '…'"></pre>
            <div class="mt-3 flex flex-wrap gap-2">
                <button @click="copy()" class="text-xs border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5">{{ __('article.copy') }}</button>
                <button @click="window.open('{{ route('articles.show', $publication->slug) }}?share=1', 'share', 'width=600,height=400')" class="text-xs border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5">{{ __('article.share') }}</button>
            </div>
            <div class="mt-4">
                <p class="text-xs text-gray-400 mb-2">Share:</p>
                <div class="flex gap-2 text-xs">
                    <a target="_blank" rel="noopener" class="border border-gray-300 rounded-lg px-2.5 py-1.5 hover:border-primary hover:text-primary" href="https://twitter.com/intent/tweet?url={{ route('articles.show', $publication->slug, absolute: true) }}&text={{ urlencode($meta->title) }}">X</a>
                    <a target="_blank" rel="noopener" class="border border-gray-300 rounded-lg px-2.5 py-1.5 hover:border-primary hover:text-primary" href="https://www.facebook.com/sharer/sharer.php?u={{ route('articles.show', $publication->slug, absolute: true) }}">FB</a>
                    <a target="_blank" rel="noopener" class="border border-gray-300 rounded-lg px-2.5 py-1.5 hover:border-primary hover:text-primary" href="https://wa.me/?text={{ urlencode($meta->title.' '.$publication->doiUrl()) }}">WA</a>
                    <a class="border border-gray-300 rounded-lg px-2.5 py-1.5 hover:border-primary hover:text-primary" href="mailto:?subject={{ urlencode($meta->title) }}&body={{ urlencode(route('articles.show', $publication->slug, absolute: true)) }}">Email</a>
                </div>
            </div>
        </section>
    </div>

    @if($related->count())
        <section class="mt-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">{{ __('article.related') }}</h2>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach($related as $p)
                    @include('partials.article-card', ['publication' => $p])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

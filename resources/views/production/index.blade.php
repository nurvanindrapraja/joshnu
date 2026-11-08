@extends('layouts.workspace')
@section('title', __('production.queue'))
@section('content')
<div class="max-w-6xl mx-auto space-y-4">
    @forelse($publications as $p)
        @php($s = $p->submission)
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex flex-wrap items-start gap-3">
                <div class="flex-1 min-w-[260px]">
                    <p class="text-xs font-mono text-gray-400">#{{ $s->reference_no }} @if($p->doi)• <span class="font-mono">{{ $p->doi }} ({{ $p->doi_status }})</span>@endif</p>
                    <h3 class="font-bold text-gray-900 mt-0.5">{{ $p->titleFor('id') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Stage naskah: <strong>{{ $s->stageLabel() }}</strong>
                        • Issue: {{ $p->issue?->label() ?? ($p->is_online_first ? 'Online First' : 'belum dijadwalkan') }}
                        @if($p->isPublished()) • Terbit {{ $p->published_at->format('d M Y') }}@endif
                        @if($p->article_status !== 'active') • <span class="text-red-600">{{ strtoupper($p->article_status) }}</span>@endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if(in_array($s->stage, ['accepted', 'scheduled']))
                        <a href="{{ route('editor.submissions.show', $s) }}#schedule-panel" class="text-xs border border-teal-500 text-teal-600 hover:bg-teal-50 rounded-lg px-3 py-1.5">Jadwalkan</a>
                    @endif
                    @if($s->stage === 'scheduled')
                        <button type="button" x-on:click="$dispatch('open-modal', 'confirm-publish-article-{{ $p->id }}')" class="text-xs bg-primary text-white rounded-lg px-3 py-1.5 font-medium">Publish</button>
                    @endif
                    @if($p->isPublished())
                        <a href="{{ route('articles.show', $p->slug) }}" target="_blank" class="text-xs border border-gray-300 text-gray-600 hover:text-primary hover:border-primary rounded-lg px-3 py-1.5">Lihat Publik</a>
                        @if(auth()->user()->hasRole('admin', 'eic'))
                            <button type="button" x-on:click="$dispatch('open-modal', 'confirm-unpublish-article-{{ $p->id }}')" class="text-xs text-red-500 hover:underline px-2 py-1.5">Unpublish</button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="mt-4 grid md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">
                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Galley</p>
                    @foreach($p->galleys as $g)
                        <p class="text-xs text-gray-600">• {{ $g->label ?? $g->type }} ({{ $g->humanSize() }})</p>
                    @endforeach
                    @if($p->galleys->isEmpty())<p class="text-xs text-gray-400">Belum ada.</p>@endif
                </div>
                <div class="rounded-lg bg-gray-50 p-3 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Typesetting</p>
                    <form method="POST" action="{{ route('production.publications.pdf', $p) }}">@csrf<button class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-1">Generate PDF of Record</button></form>
                    <form method="POST" action="{{ route('production.publications.jats', $p) }}">@csrf<button class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-1">Generate JATS XML</button></form>
                    <form method="POST" action="{{ route('production.proof', $s) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input type="file" name="file" accept=".pdf" class="text-xs flex-1">
                        <button class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-1">Proof</button>
                    </form>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase">DOI / Crossref</p>
                    <p class="text-xs text-gray-600 font-mono break-all">{{ $p->doi ?? '(belum ada DOI — jadwalkan dulu)' }}</p>
                    <p class="text-xs text-gray-500">Status: <strong>{{ $p->doi_status }}</strong></p>
                    <form method="POST" action="{{ route('production.publications.doi', $p) }}">
                        @csrf
                        <button class="text-xs border border-primary text-primary hover:bg-primary-light rounded-lg px-3 py-1">Deposit ke Crossref</button>
                    </form>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 space-y-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Metadata & Status</p>
                    <details>
                        <summary class="text-xs text-primary cursor-pointer">Edit metadata final</summary>
                        <form method="POST" action="{{ route('production.publications.metadata', $p) }}" class="mt-2 space-y-2">
                            @csrf @method('PUT')
                            <input type="text" name="id_title" value="{{ old('id_title', $p->titleFor('id')) }}" required class="w-full rounded-lg border-gray-300 text-xs border">
                            <textarea name="id_abstract" rows="2" required class="w-full rounded-lg border-gray-300 text-xs border">{{ old('id_abstract', $p->abstractFor('id')) }}</textarea>
                            <input type="text" name="id_keywords" value="{{ old('id_keywords', implode(',', $p->keywordsFor('id'))) }} class="w-full rounded-lg border-gray-300 text-xs border">
                            <button class="w-full bg-primary text-white rounded-lg px-3 py-1.5 text-xs">Simpan Metadata</button>
                        </form>
                    </details>
                    @if(auth()->user()->hasRole('admin', 'eic') && $p->isPublished())
                        <div class="border-t border-gray-200 pt-2 flex gap-2">
                            <form method="POST" action="{{ route('production.publications.correct', $p) }}" onsubmit="var n = prompt('Teks pembetulan (erratum):'); if (!n) return false; this.note.value = n;">
                                @csrf
                                <input type="hidden" name="note">
                                <button class="text-xs text-amber-600 hover:underline">Correction</button>
                            </form>
                            <form method="POST" action="{{ route('production.publications.retract', $p) }}" onsubmit="var r = prompt('Alasan retraction (publik, permanen):'); if (!r) return false; this.reason.value = r;">
                                @csrf
                                <input type="hidden" name="reason">
                                <button class="text-xs text-red-600 hover:underline">Retraction</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Modal konfirmasi publish artikel --}}
            @if($s->stage === 'scheduled')
                <x-modal name="confirm-publish-article-{{ $p->id }}" maxWidth="sm" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">Publikasikan artikel?</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Artikel <strong>{{ $p->titleFor('id') }}</strong> ({{ $s->reference_no }}) akan dipublikasikan sekarang
                            @if($p->doi) dengan DOI <span class="font-mono text-xs">{{ $p->doi }}</span>@endif.
                        </p>
                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-publish-article-{{ $p->id }}')">Batal</x-secondary-button>
                            <form method="POST" action="{{ route('production.publications.publish', $p) }}">
                                @csrf
                                <x-danger-button class="!bg-primary hover:!bg-primary-dark">Publish</x-danger-button>
                            </form>
                        </div>
                    </div>
                </x-modal>
            @endif

            {{-- Modal konfirmasi unpublish artikel --}}
            @if($p->isPublished() && auth()->user()->hasRole('admin', 'eic'))
                <x-modal name="confirm-unpublish-article-{{ $p->id }}" maxWidth="sm" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">Unpublish artikel?</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Artikel <strong>{{ $p->titleFor('id') }}</strong> ({{ $s->reference_no }}) akan ditarik dari terbitan publik dan kembali ke tahap terjadwal.
                        </p>
                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-unpublish-article-{{ $p->id }}')">Batal</x-secondary-button>
                            <form method="POST" action="{{ route('production.publications.unpublish', $p) }}">
                                @csrf
                                <x-danger-button>Unpublish</x-danger-button>
                            </form>
                        </div>
                    </div>
                </x-modal>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
            Tidak ada artikel pada tahap accepted/scheduled/published.
        </div>
    @endforelse
</div>
@endsection

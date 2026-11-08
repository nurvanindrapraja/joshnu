@extends('layouts.workspace')
@section('title', $submission ? 'Edit Naskah' : __('account.new_submission'))
@section('content')
@php($meta = $submission?->metadata ?? [])
@php($oldAuthors = $submission?->authors->filter(fn ($a) => $a->display_order > 1)->values()->all() ?? [])
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-2 text-xs font-medium text-gray-500 mb-5">
        <span class="h-6 w-6 rounded-full bg-primary text-white flex items-center justify-center font-bold">1</span> Metadata
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="h-6 w-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold">2</span> File
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="h-6 w-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold">3</span> Konfirmasi
    </div>

    <form method="POST" action="{{ $submission ? route('account.submissions.update', $submission) : route('account.submissions.store') }}" class="space-y-6">
        @csrf
        @if($submission)@method('PUT')@endif

        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
            <h2 class="font-bold text-gray-900">Metadata Naskah</h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="section_id">Section / Kategori</x-input-label>
                    <select id="section_id" name="section_id" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border" required>
                        <option value="">— Pilih section —</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" @selected((int) old('section_id', $submission?->section_id) === (int) $sec->id)>{{ $sec->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('section_id')" />
                </div>
                <div>
                    <x-input-label for="license">Lisensi</x-input-label>
                    <select id="license" name="license" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border" required>
                        <option value="CC-BY-4.0" @selected(old('license', $meta['license'] ?? '') === 'CC-BY-4.0')>CC BY 4.0 (default)</option>
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('license')" />
                </div>
            </div>

            <div>
                <x-input-label for="id_title">Judul (Bahasa Indonesia)</x-input-label>
                <input id="id_title" type="text" name="id_title" value="{{ old('id_title', $submission?->title) }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border" required maxlength="500">
                <x-input-error class="mt-1" :messages="$errors->get('id_title')" />
            </div>
            <div>
                <x-input-label for="en_title">Judul (English) <span class="text-gray-400">— opsional</span></x-input-label>
                <input id="en_title" type="text" name="en_title" value="{{ old('en_title', $meta['en_title'] ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border" maxlength="500">
                <x-input-error class="mt-1" :messages="$errors->get('en_title')" />
            </div>

            <div>
                <x-input-label for="id_abstract">Abstrak (ID) — minimal 150 kata</x-input-label>
                <textarea id="id_abstract" name="id_abstract" rows="6" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('id_abstract', $meta['id_abstract'] ?? '') }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('id_abstract')" />
            </div>
            <div>
                <x-input-label for="en_abstract">Abstrak (EN) <span class="text-gray-400">— opsional</span></x-input-label>
                <textarea id="en_abstract" name="en_abstract" rows="6" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('en_abstract', $meta['en_abstract'] ?? '') }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('en_abstract')" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label>Kata kunci — Bahasa Indonesia <span class="text-gray-400">(3–6, wajib)</span></x-input-label>
                    <div x-data="{ kws: @js(old('keywords', $meta['keywords'] ?? [])), input: '', add() { const v = this.input.trim(); if (!v) return; if (this.kws.length >= 6) { this.input = ''; return; } if (this.kws.includes(v)) { this.input = ''; return; } this.kws.push(v); this.input = ''; } }" class="mt-1">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(kw, i) in kws" :key="i">
                                <span class="inline-flex items-center gap-1 bg-primary-light text-gray-800 text-xs rounded-full px-3 py-1">
                                    <span x-text="kw"></span>
                                    <button type="button" @click="kws.splice(i,1)" class="text-gray-500 hover:text-red-600" title="Hapus">×</button>
                                    <input type="hidden" name="keywords[]" :value="kw">
                                </span>
                            </template>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <input type="text" x-model="input" @keydown.enter.prevent="add()" placeholder="mis. kesehatan perkotaan" maxlength="100" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm border">
                            <button type="button" @click="add()" :disabled="kws.length >= 6 || !input.trim()" class="shrink-0 border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5 text-sm disabled:opacity-50 disabled:cursor-not-allowed">+ Tambah</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-400" x-text="kws.length + ' / 6 kata kunci'"></p>
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('keywords')" />
                </div>
                <div>
                    <x-input-label>Keywords — English <span class="text-gray-400">(3–6, opsional; jika kosong memakai versi ID)</span></x-input-label>
                    <div x-data="{ kws: @js(old('en_keywords', $meta['en_keywords'] ?? $meta['keywords'] ?? [])), input: '', add() { const v = this.input.trim(); if (!v) return; if (this.kws.length >= 6) { this.input = ''; return; } if (this.kws.includes(v)) { this.input = ''; return; } this.kws.push(v); this.input = ''; } }" class="mt-1">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(kw, i) in kws" :key="i">
                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 text-xs rounded-full px-3 py-1">
                                    <span x-text="kw"></span>
                                    <button type="button" @click="kws.splice(i,1)" class="text-gray-500 hover:text-red-600" title="Hapus">×</button>
                                    <input type="hidden" name="en_keywords[]" :value="kw">
                                </span>
                            </template>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <input type="text" x-model="input" @keydown.enter.prevent="add()" placeholder="e.g. urban health" maxlength="100" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm border">
                            <button type="button" @click="add()" :disabled="kws.length >= 6 || !input.trim()" class="shrink-0 border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5 text-sm disabled:opacity-50 disabled:cursor-not-allowed">+ Tambah</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-400" x-text="kws.length + ' / 6 keywords'"></p>
                    </div>
                    <x-input-error class="mt-1" :messages="$errors->get('en_keywords')" />
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="funding">Pendanaan <span class="text-gray-400">— opsional</span></x-input-label>
                    <input id="funding" type="text" name="funding" value="{{ old('funding', $meta['funding'] ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <x-input-label for="conflict_of_interest">Konflik kepentingan</x-input-label>
                    <input id="conflict_of_interest" type="text" name="conflict_of_interest" value="{{ old('conflict_of_interest', $submission?->conflict_of_interest ?? 'Tidak ada') }}" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border" required>
                </div>
            </div>
            <div>
                <x-input-label for="acknowledgments">Ucapan terima kasih <span class="text-gray-400">— opsional</span></x-input-label>
                <textarea id="acknowledgments" name="acknowledgments" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('acknowledgments', $meta['acknowledgments'] ?? '') }}</textarea>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
            <h2 class="font-bold text-gray-900">Penulis (Co-Author)</h2>
            <p class="text-xs text-gray-500">Anda ({{ auth()->user()->name }}) otomatis menjadi penulis pertama (korespondensi).</p>
            @php($oldAuthors = $submission ? $submission->authors->filter(fn ($a) => $a->display_order > 1)->values()->all() : [])
            @php($rows = old('authors', $oldAuthors))
            <div id="authors-list">
                @foreach($rows as $i => $a)
                    <div class="author-row grid grid-cols-12 gap-2 mb-2">
                        <input type="text" name="authors[{{ $i }}][name]" value="{{ $a['name'] ?? '' }}" placeholder="Nama" class="col-span-4 rounded-lg border-gray-300 text-sm border" required>
                        <input type="email" name="authors[{{ $i }}][email]" value="{{ $a['email'] ?? '' }}" placeholder="Email" class="col-span-4 rounded-lg border-gray-300 text-sm border">
                        <input type="text" name="authors[{{ $i }}][orcid]" value="{{ $a['orcid'] ?? '' }}" placeholder="ORCID" class="col-span-2 rounded-lg border-gray-300 text-sm border font-mono text-xs">
                        <input type="text" name="authors[{{ $i }}][institution]" value="{{ $a['institution'] ?? '' }}" placeholder="Institusi" class="col-span-1 rounded-lg border-gray-300 text-sm border" title="Institusi">
                        <button type="button" class="col-span-1 text-red-500 hover:text-red-700 remove-author">×</button>
                    </div>
                @endforeach
            </div>
            <template id="author-row-template">
                <div class="author-row grid grid-cols-12 gap-2 mb-2">
                    <input type="text" name="authors[IDX][name]" placeholder="Nama" class="col-span-4 rounded-lg border-gray-300 text-sm border" required>
                    <input type="email" name="authors[IDX][email]" placeholder="Email" class="col-span-4 rounded-lg border-gray-300 text-sm border">
                    <input type="text" name="authors[IDX][orcid]" placeholder="ORCID" class="col-span-2 rounded-lg border-gray-300 text-sm border font-mono text-xs">
                    <input type="text" name="authors[IDX][institution]" placeholder="Institusi" class="col-span-1 rounded-lg border-gray-300 text-sm border" title="Institusi">
                    <button type="button" class="col-span-1 text-red-500 hover:text-red-700 remove-author">×</button>
                </div>
            </template>
            <button type="button" id="add-author" class="text-sm text-primary hover:underline">+ Tambah Penulis</button>
            <x-input-error class="mt-1" :messages="$errors->get('authors')" />
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const list = document.getElementById('authors-list');
                const tpl = document.getElementById('author-row-template');
                const addBtn = document.getElementById('add-author');
                if (!list || !tpl || !addBtn) return;
                addBtn.addEventListener('click', function () {
                    const idx = list.querySelectorAll('.author-row').length;
                    const clone = tpl.innerHTML.replaceAll('IDX', idx);
                    list.insertAdjacentHTML('beforeend', clone);
                });
                list.addEventListener('click', function (e) {
                    if (e.target.classList.contains('remove-author')) {
                        e.target.closest('.author-row').remove();
                    }
                });
            });
        </script>

        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
            <h2 class="font-bold text-gray-900">Rekomendasi Reviewer <span class="text-xs font-normal text-gray-400">(opsional, maks 3)</span></h2>
            @php($oldSR = array_values(old('suggested_reviewers', $submission?->suggested_reviewers ?? [])))
            <div x-data="{ list: @json(array_slice($oldSR, 0, 3)), add() { if (this.list.length >= 3) return; this.list.push({ name: '', email: '', institution: '', reason: '' }); } }">
                <template x-for="(r, i) in list" :key="i">
                    <div class="grid grid-cols-12 gap-2 mb-2">
                        <input type="text" :name="`suggested_reviewers[${i}][name]`" :value="r.name || ''" placeholder="Nama" class="col-span-3 rounded-lg border-gray-300 text-sm border">
                        <input type="email" :name="`suggested_reviewers[${i}][email]`" :value="r.email || ''" placeholder="Email" class="col-span-3 rounded-lg border-gray-300 text-sm border">
                        <input type="text" :name="`suggested_reviewers[${i}][institution]`" :value="r.institution || ''" placeholder="Institusi" class="col-span-3 rounded-lg border-gray-300 text-sm border">
                        <input type="text" :name="`suggested_reviewers[${i}][reason]`" :value="r.reason || ''" placeholder="Alasan" class="col-span-2 rounded-lg border-gray-300 text-sm border">
                        <button type="button" class="col-span-1 text-red-500 hover:text-red-700" @click="list.splice(i,1)">×</button>
                    </div>
                </template>
                <button type="button" @click="add()" class="text-sm text-primary hover:underline">+ Tambah</button>
            </div>
            <x-input-error class="mt-1" :messages="$errors->get('suggested_reviewers')" />
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-3">
            <h2 class="font-bold text-gray-900">Cover Letter</h2>
            <textarea name="cover_letter" rows="4" class="w-full rounded-lg border-gray-300 text-sm border" placeholder="Yth. Tim Redaksi, …">{{ old('cover_letter', $submission?->cover_letter) }}</textarea>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-2">
            <h2 class="font-bold text-gray-900">Ceklis Pernyataan (wajib)</h2>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="checklist_original" value="1" @checked(old('checklist_original', $meta['checklist']['original'] ?? false)) class="mt-0.5 rounded border-gray-300 text-primary">
                Naskah ini orisinal dan belum pernah dipublikasikan di mana pun.
            </label>
            <x-input-error class="mt-0" :messages="$errors->get('checklist_original')" />
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="checklist_not_published" value="1" @checked(old('checklist_not_published', $meta['checklist']['not_published'] ?? false)) class="mt-0.5 rounded border-gray-300 text-primary">
                Naskah tidak sedang diproses di jurnal lain.
            </label>
            <x-input-error class="mt-0" :messages="$errors->get('checklist_not_published')" />
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="checklist_coauthor_approved" value="1" @checked(old('checklist_coauthor_approved', $meta['checklist']['coauthor_approved'] ?? false)) class="mt-0.5 rounded border-gray-300 text-primary">
                Seluruh co-author menyetujui pengiriman naskah ini.
            </label>
            <x-input-error class="mt-0" :messages="$errors->get('checklist_coauthor_approved')" />
        </section>

        <div class="flex items-center justify-end gap-3 pb-8">
            @if($submission)
                <a href="{{ route('account.submissions.show', $submission) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 text-gray-600">Batal</a>
            @endif
            <button class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-6 py-2">
                {{ $submission ? 'Simpan Perubahan' : 'Lanjut ke Unggah File →' }}
            </button>
        </div>
    </form>
</div>
@endsection

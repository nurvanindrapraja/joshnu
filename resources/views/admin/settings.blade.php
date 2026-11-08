@extends('layouts.workspace')
@section('title', 'Journal Settings')
@section('content')
<div class="max-w-4xl mx-auto" x-data="{
    tab: 'general',
    saving: false,
    board: @js($setting->editorial_board ?? []),
    base: @js(route('admin.settings.edit')),
    csrf: document.querySelector('meta[name=csrf-token]').content,
    memberMode: null,
    memberIndex: null,
    memberForm: { name: '', title: '', institution: '', email: '', orcid: '' },
    memberPhoto: null,
    photoStatus: '',
    memberPreview: null,
    existingPhoto: null,
    assetBase: @js(rtrim(asset('storage'), '/') . '/'),
    deleteTarget: null,
    toasts: [],
    toastId: 0,
    notify(type, text, ttl = 3500) {
        const id = ++this.toastId;
        this.toasts.push({ id, type, text, leaving: false });
        setTimeout(() => this.markLeaving(id), ttl);
    },
    markLeaving(id) {
        const t = this.toasts.find(t => t.id === id);
        if (!t || t.leaving) return;
        t.leaving = true;
        setTimeout(() => {
            const i = this.toasts.findIndex(x => x.id === id);
            if (i !== -1) this.toasts.splice(i, 1);
        }, 300);
    },
    openAddMember() {
        this.memberMode = 'create';
        this.memberIndex = null;
        this.memberForm = { name: '', title: '', institution: '', email: '', orcid: '' };
        this.memberPhoto = null;
        this.existingPhoto = null;
        this.memberPreview = null;
        this.$dispatch('open-modal', 'board-member-modal');
    },
    openEditMember(i) {
        const m = this.board[i] || {};
        this.memberMode = 'edit';
        this.memberIndex = i;
        this.memberForm = { name: m.name || '', title: m.title || '', institution: m.institution || '', email: m.email || '', orcid: m.orcid || '' };
        this.memberPhoto = null;
        this.existingPhoto = m.photo ? this.assetBase + m.photo : null;
        this.memberPreview = this.existingPhoto;
        this.$dispatch('open-modal', 'board-member-modal');
    },
    async onPhotoChange(e) {
        const input = e.target;
        const f = input.files[0];
        if (f) {
            let target = f;
            if (window.mayCompressImage && window.mayCompressImage(f)) {
                this.photoStatus = 'Mengompres gambar…';
                try {
                    target = await window.processImageFile(input, f);
                    this.photoStatus = 'Gambar >2 MB dikompres menjadi ' + Math.round(target.size / 1024) + ' KB.';
                } catch (err) {
                    this.photoStatus = 'Kompresi gagal, file asli tetap dipakai.';
                }
            } else {
                this.photoStatus = '';
            }
            this.memberPhoto = target;
            if (this.memberPreview && this.memberPreview.startsWith('blob:')) URL.revokeObjectURL(this.memberPreview);
            this.memberPreview = URL.createObjectURL(target);
        } else {
            this.memberPhoto = null;
            this.memberPreview = this.existingPhoto;
            this.photoStatus = '';
        }
    },
    async saveMember() {
        this.saving = true;
        const isEdit = this.memberMode === 'edit';
        const url = isEdit ? this.base + '/board/' + this.memberIndex : this.base + '/board';
        const fd = new FormData();
        fd.append('_method', isEdit ? 'PUT' : 'POST');
        fd.append('name', this.memberForm.name);
        fd.append('title', this.memberForm.title);
        fd.append('institution', this.memberForm.institution);
        fd.append('email', this.memberForm.email);
        fd.append('orcid', this.memberForm.orcid);
        if (this.memberPhoto) fd.append('photo', this.memberPhoto);
        try {
            const res = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.board = data.board;
                this.notify('success', data.message || 'Anggota tersimpan.');
                this.$dispatch('close-modal', 'board-member-modal');
            } else {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menyimpan anggota.');
                this.notify('error', errs, 6000);
            }
        } catch (e) {
            this.notify('error', 'Gagal menyimpan. Periksa koneksi lalu coba lagi.', 6000);
        } finally {
            this.saving = false;
        }
    },
    askDeleteMember(i) {
        this.deleteTarget = { index: i, name: (this.board[i] || {}).name || '' };
        this.$dispatch('open-modal', 'board-delete-modal');
    },
    async confirmDeleteMember() {
        if (!this.deleteTarget) return;
        this.saving = true;
        try {
            const res = await fetch(this.base + '/board/' + this.deleteTarget.index, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ _method: 'DELETE' })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.board = data.board;
                this.notify('success', data.message || 'Anggota dihapus.');
                this.$dispatch('close-modal', 'board-delete-modal');
                this.deleteTarget = null;
            } else {
                this.notify('error', data.message || 'Gagal menghapus anggota.', 6000);
            }
        } catch (e) {
            this.notify('error', 'Gagal menghapus. Periksa koneksi lalu coba lagi.', 6000);
        } finally {
            this.saving = false;
        }
    },
    async submit(form) {
        this.saving = true;
        const fd = new FormData(form);
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.notify('success', data.message || 'Pengaturan jurnal tersimpan.');
            } else {
                const errs = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message || 'Terjadi kesalahan saat menyimpan.');
                this.notify('error', errs, 6000);
            }
        } catch (e) {
            this.notify('error', 'Gagal menyimpan. Periksa koneksi lalu coba lagi.', 6000);
        } finally {
            this.saving = false;
        }
    }
}">

    {{-- Toast notification: overlay pojok kanan atas (slideshow) --}}
    <div class="fixed top-4 right-4 z-50 w-80 max-w-[calc(100vw-2rem)] space-y-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg"
                 :class="[t.leaving ? 'toast-out' : 'toast-in', t.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800']">
                <svg x-show="t.type === 'success'" class="h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                <svg x-show="t.type === 'error'" class="h-5 w-5 shrink-0 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                <span class="flex-1 pt-0.5" x-text="t.text"></span>
                <button type="button" @click="markLeaving(t.id)" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Tutup">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Progres bar saat proses penyimpanan (AJAX) --}}
    <div x-cloak x-show="saving" class="fixed top-0 left-0 right-0 z-50 h-1 overflow-hidden bg-gray-200">
        <div class="h-full w-1/3 rounded-r bg-primary" style="animation: js-progress 1.1s ease-in-out infinite;"></div>
    </div>
    <style>
        @keyframes js-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(300%); } }
        @keyframes toast-in { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(24px); } }
        .toast-in { animation: toast-in 0.3s ease-out; }
        .toast-out { animation: toast-out 0.3s ease-in forwards; }
        [x-cloak] { display: none !important; }
    </style>

    <nav class="flex border-b border-gray-200 mb-5 text-sm overflow-x-auto">
        @foreach(['general' => 'General', 'board' => __('footer.board'), 'theme' => 'Masthead & Theme', 'apc' => 'APC', 'review' => 'Review Policy', 'integrasi' => 'Integrasi'] as $key => $label)
            <button @click="tab = '{{ $key }}'" class="px-4 py-3 border-b-2 whitespace-nowrap" :class="tab === '{{ $key }}' ? 'border-primary text-primary font-medium' : 'border-transparent text-gray-500'" :style="tab === '{{ $key }}' ? 'border-color: var(--primary); color: var(--primary)' : ''">{{ $label }}</button>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" @submit.prevent="submit($el)" class="space-y-5">
        @csrf @method('PUT')

        <div class="rounded-xl border border-gray-200 bg-white p-6 space-y-4" x-show="tab === 'general'">
            <h3 class="font-bold text-gray-900">General</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Nome jurnal</x-input-label><input type="text" name="name" value="{{ old('name', $setting->name) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border"><x-input-error :messages="$errors->get('name')" /></div>
                <div><x-input-label>Short name</x-input-label><input type="text" name="short_name" value="{{ old('short_name', $setting->short_name) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>Tagline</x-input-label><input type="text" name="tagline" value="{{ old('tagline', $setting->tagline) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>Email kontak</x-input-label><input type="email" name="contact_email" value="{{ old('contact_email', $setting->contact_email) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>ISSN print</x-input-label><input type="text" name="issn_print" value="{{ old('issn_print', $setting->issn_print) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono"></div>
                <div><x-input-label>ISSN online</x-input-label><input type="text" name="issn_online" value="{{ old('issn_online', $setting->issn_online) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono"></div>
            </div>
            <div>
                <x-input-label>Alamat kantor</x-input-label>
                <textarea name="address" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border" placeholder="Alamat redaksi/kantor jurnal — ditampilkan di halaman About dan footer situs">{{ old('address', $setting->address) }}</textarea>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Lingkup (scope)</x-input-label><textarea name="scope" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('scope', $setting->scope) }}</textarea></div>
                <div><x-input-label>Lingkup — versi Inggris (opsional)</x-input-label><textarea name="scope_en" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm border" placeholder="Dipakai saat pengunjung memilih bahasa EN.">{{ old('scope_en', $setting->scope_en) }}</textarea></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Deskripsi panjang</x-input-label><textarea name="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('description', $setting->description) }}</textarea></div>
                <div><x-input-label>Deskripsi — versi Inggris (opsional)</x-input-label><textarea name="description_en" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('description_en', $setting->description_en) }}</textarea></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Kebijakan etik</x-input-label><textarea name="ethics_policy" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('ethics_policy', $setting->ethics_policy) }}</textarea></div>
                <div><x-input-label>Kebijakan etik — versi Inggris (opsional)</x-input-label><textarea name="ethics_policy_en" rows="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('ethics_policy_en', $setting->ethics_policy_en) }}</textarea></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Kebijakan open access</x-input-label><textarea name="oa_policy" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('oa_policy', $setting->oa_policy) }}</textarea></div>
                <div><x-input-label>Kebijakan open access — versi Inggris (opsional)</x-input-label><textarea name="oa_policy_en" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('oa_policy_en', $setting->oa_policy_en) }}</textarea></div>
            </div>
        </div>

        <div x-cloak x-show="tab === 'board'" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-900">{{ __('footer.board') }}</h3>
                    <p class="text-xs text-gray-400">Terdapat di halaman About. Perubahan langsung tersimpan (AJAX, tanpa reload).</p>
                </div>
                <button type="button" @click="openAddMember()" :disabled="saving"
                        class="shrink-0 bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed">
                    + Tambah Anggota
                </button>
            </div>
            <template x-for="(m, i) in board" :key="i">
                <div class="rounded-lg border border-gray-200 p-4 flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="h-12 w-12 shrink-0 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                            <img x-cloak x-show="m.photo" :src="assetBase + m.photo" alt="" class="h-full w-full object-cover">
                            <svg x-cloak x-show="!m.photo" class="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-sm text-gray-900" x-text="m.name"></p>
                            <p class="text-xs text-gray-500" x-text="(m.title || '—') + (m.institution ? ' — ' + m.institution : '')"></p>
                            <p x-cloak x-show="m.email" class="text-xs text-gray-400" x-text="m.email"></p>
                            <p x-cloak x-show="m.orcid" class="text-xs font-mono text-gray-400" x-text="'ORCID: ' + m.orcid"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" @click="openEditMember(i)" :disabled="saving" :title="'Edit anggota'"
                                class="inline-flex items-center justify-center rounded-lg p-1.5 text-primary hover:bg-primary-light disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0 2.638 2.638M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </button>
                        <button type="button" @click="askDeleteMember(i)" :disabled="saving" :title="'Hapus anggota'"
                                class="inline-flex items-center justify-center rounded-lg p-1.5 text-red-600 hover:bg-red-50 disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </div>
                </div>
            </template>
            <p x-show="board.length === 0" class="text-sm text-gray-400">Belum ada anggota. Klik &ldquo;+ Tambah Anggota&rdquo; untuk menambahkan.</p>
        </div>

        <div x-cloak x-show="tab === 'theme'" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
            <h3 class="font-bold text-gray-900">Masthead & Theme</h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <x-input-label>Logo</x-input-label>
                    <input type="file" name="theme[logo]" accept=".png,.jpg,.jpeg,.svg" class="mt-1 w-full text-sm">
                </div>
                <div>
                    <x-input-label>Warna primer</x-input-label>
                    <input type="color" name="theme[colors][primary]" value="{{ old('theme.colors.primary', $setting->theme['colors']['primary'] ?? '#1b6e5a') }}" class="mt-1 w-full h-9 rounded-lg border-gray-300 border" required>
                </div>
                <div>
                    <x-input-label>Warna aksen</x-input-label>
                    <input type="color" name="theme[colors][accent]" value="{{ old('theme.colors.accent', $setting->theme['colors']['accent'] ?? '#f59e0b') }}" class="mt-1 w-full h-9 rounded-lg border-gray-300 border" required>
                </div>
            </div>
            @if($setting->theme['logo'] ?? null)
                <p class="text-xs text-gray-400">Logo saat ini: <img src="{{ asset('storage/'.$setting->theme['logo']) }}" class="inline h-8 ml-1 align-middle"></p>
            @endif
            <p class="text-xs text-gray-400">Tema disimpan sebagai token CSS; preview langsung di halaman publik setelah simpan. Tekan &ldquo;Simpan Perubahan&rdquo; di bawah untuk menyimpan.</p>
        </div>

        <div x-cloak x-show="tab === 'apc'" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
            <h3 class="font-bold text-gray-900">Article Processing Charge</h3>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="apc_enabled" value="1" @checked(old('apc_enabled', $setting->apc_enabled)) class="rounded border-gray-300 text-primary"> APC aktif</label>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Nominal</x-input-label><input type="number" step="0.01" name="apc_amount" value="{{ old('apc_amount', $setting->apc_amount) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>Mata uang</x-input-label><input type="text" name="apc_currency" value="{{ old('apc_currency', $setting->apc_currency) }}" maxlength="3" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>Kebijakan APC (teks publik)</x-input-label><textarea name="apc_policy" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('apc_policy', $setting->apc_policy) }}</textarea></div>
                <div><x-input-label>Kebijakan APC — versi Inggris (opsional)</x-input-label><textarea name="apc_policy_en" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('apc_policy_en', $setting->apc_policy_en) }}</textarea></div>
            </div>
            <div>
                <x-input-label>Rekening transfer (satu baris per rekening: bank | nomor | nama a.n.)</x-input-label>
                <textarea name="apc_bank_text" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono" placeholder="BCA | 1234567890 | JOSHNU">{{ old('apc_bank_text', collect($setting->apc_bank ?? [])->map(fn ($b) => ($b['bank'] ?? '').' | '.($b['no'] ?? '').' | '.($b['name'] ?? ''))->implode("\n")) }}</textarea>
            </div>
        </div>

        <div x-cloak x-show="tab === 'review'" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
            <h3 class="font-bold text-gray-900">Peer Review Policy</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label>Mode review</x-input-label>
                    <select name="review_mode" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                        <option value="double_blind" @selected(old('review_mode', $setting->review_mode) === 'double_blind')>Double-blind</option>
                        <option value="single_blind" @selected(old('review_mode', $setting->review_mode) === 'single_blind')>Single-blind</option>
                        <option value="open" @selected(old('review_mode', $setting->review_mode) === 'open')>Open review</option>
                    </select>
                </div>
                <div><x-input-label>Maks. ronde review</x-input-label><input type="number" name="max_review_rounds" value="{{ old('max_review_rounds', $setting->max_review_rounds) }}" min="1" max="10" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>Deadline review (hari)</x-input-label><input type="number" name="review_deadline_days" value="{{ old('review_deadline_days', $setting->review_deadline_days) }}" min="1" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
                <div><x-input-label>Reminder H- (hari sebelum due)</x-input-label><input type="number" name="reminder_before_days" value="{{ old('reminder_before_days', $setting->reminder_before_days) }}" min="0" max="30" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="online_first_enabled" value="1" @checked(old('online_first_enabled', $setting->online_first_enabled)) class="rounded border-gray-300 text-primary"> Online First diizinkan</label>
        </div>

        <div x-cloak x-show="tab === 'integrasi'" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
            <h3 class="font-bold text-gray-900">Integrasi (DOI / Crossref)</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><x-input-label>DOI prefix (mis. 10.56253)</x-input-label><input type="text" name="doi_prefix" value="{{ old('doi_prefix', $setting->doi_prefix) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono"></div>
                <div><x-input-label>API key Crossref (env: CROSSREF_API_KEY)</x-input-label>
                    <input type="text" disabled value="{{ config('services.crossref.api_key') ? 'terkonfigurasi ✓' : 'belum diset (deposit disimulasikan)' }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border bg-gray-50">
                </div>
            </div>
            <p class="text-xs text-gray-400">API key Crossref di-set via environment variable <code class="bg-gray-100 px-1 rounded">CROSSREF_API_KEY</code> pada server.</p>
        </div>

        {{-- Tombol simpan: di bawah cardview tab yang aktif, jarak mt-6 dari card --}}
        <div class="mt-6 pb-8 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
            <p x-cloak x-show="saving" class="text-sm text-gray-500">Menyimpan, mohon tunggu…</p>
            <button type="submit" :disabled="saving"
                    class="bg-primary hover:bg-primary-dark text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                <span x-text="saving ? 'Menyimpan…' : 'Simpan Perubahan'">Simpan Perubahan</span>
            </button>
        </div>
    </form>
    {{-- Modal form tambah/edit anggota dewan editorial --}}
    <x-modal name="board-member-modal" maxWidth="lg" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900" x-text="memberMode === 'edit' ? 'Edit Anggota Dewan Editorial' : 'Tambah Anggota Dewan Editorial'"></h3>
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 shrink-0 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                    <img x-cloak x-show="memberPreview" :src="memberPreview" alt="" class="h-full w-full object-cover">
                    <svg x-cloak x-show="!memberPreview" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <x-input-label>Foto (opsional — PNG/JPG/WEBP, maks 2MB)</x-input-label>
                    <input type="file" accept=".png,.jpg,.jpeg,.webp" @change="onPhotoChange($event)" class="mt-1 w-full text-sm">
                    <p class="text-xs text-gray-400 mt-1">Kosongkan untuk mempertahankan foto saat ini. Gambar >2 MB dikompres otomatis.</p>
                    <p class="text-xs text-amber-600 mt-0.5" x-show="photoStatus" x-text="photoStatus"></p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label>Nama lengkap</x-input-label>
                    <input type="text" x-model="memberForm.name" maxlength="150" placeholder="mis. Prof. Dr. dr. Siti Aminah, M.Kes."
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <x-input-label>Jabatan</x-input-label>
                    <input type="text" x-model="memberForm.title" maxlength="150" placeholder="mis. Editor-in-Chief"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <x-input-label>Institusi</x-input-label>
                    <input type="text" x-model="memberForm.institution" maxlength="150" placeholder="mis. Universitas Indonesia"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <x-input-label>Email</x-input-label>
                    <input type="email" x-model="memberForm.email" maxlength="190" placeholder="nama@instansi.ac.id"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
            </div>
            <div class="sm:w-1/2">
                <x-input-label>ORCID (opsional)</x-input-label>
                <input type="text" x-model="memberForm.orcid" maxlength="30" placeholder="0000-0000-0000-0000"
                       class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono">
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'board-member-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    Batal
                </button>
                <button type="button" @click="saveMember()" :disabled="saving"
                        class="bg-primary hover:bg-primary-dark text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menyimpan…' : 'Simpan Anggota'">Simpan Anggota</span>
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Modal konfirmasi hapus anggota dewan editorial --}}
    <x-modal name="board-delete-modal" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900" x-text="deleteTarget ? 'Hapus ' + deleteTarget.name + '?' : ''"></h3>
                    <p class="mt-2 text-sm text-gray-600">Anggota ini akan dihapus dari dewan editorial. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'board-delete-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    Batal
                </button>
                <button type="button" @click="confirmDeleteMember()" :disabled="saving"
                        class="rounded-lg bg-red-600 hover:bg-red-700 px-6 py-2.5 text-sm font-medium text-white transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menghapus…' : 'Hapus'">Hapus</span>
                </button>
            </div>
        </div>
    </x-modal>
</div>
@endsection

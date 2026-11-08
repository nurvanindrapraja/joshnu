@extends('layouts.workspace')
@section('title', __('nav.sections'))
@section('content')
<div class="max-w-5xl mx-auto" x-data="{
    saving: false,
    toasts: [],
    toastId: 0,
    editing: null,
    deleteTarget: null,
    label: @js(__('nav.sections')),
    form: { name: '', description: '', sort_order: 0, is_active: true },
    sections: @js($sections),
    base: @js(route('admin.sections.index')),
    csrf: document.querySelector('meta[name=csrf-token]').content,
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
    openCreate() {
        this.editing = 'new';
        this.form = { name: '', description: '', sort_order: 0, is_active: true };
        this.$dispatch('open-modal', 'section-form-modal');
    },
    openEdit(s) {
        this.editing = s.id;
        this.form = { name: s.name, description: s.description || '', sort_order: s.sort_order, is_active: s.is_active };
        this.$dispatch('open-modal', 'section-form-modal');
    },
    async save() {
        this.saving = true;
        const isEdit = this.editing !== 'new';
        const url = isEdit ? this.base + '/' + this.editing : this.base;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf
                },
                body: JSON.stringify(Object.assign({ _method: isEdit ? 'PUT' : 'POST' }, this.form))
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.notify('success', data.message || 'Section tersimpan.');
                this.sections = data.sections;
                this.$dispatch('close-modal', 'section-form-modal');
                this.editing = null;
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
    },
    remove(s) {
        if (s.publications_count > 0 || s.submissions_count > 0) {
            this.notify('error', 'Section tidak dapat dihapus karena sudah memiliki artikel/submission.', 6000);
            return;
        }
        this.deleteTarget = s;
        this.$dispatch('open-modal', 'section-delete-modal');
    },
    async confirmDelete() {
        if (!this.deleteTarget) return;
        const s = this.deleteTarget;
        this.saving = true;
        try {
            const res = await fetch(this.base + '/' + s.id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf
                },
                body: JSON.stringify({ _method: 'DELETE' })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.notify('success', data.message || 'Section dihapus.');
                this.sections = data.sections;
                this.$dispatch('close-modal', 'section-delete-modal');
                this.deleteTarget = null;
            } else {
                this.notify('error', data.message || 'Gagal menghapus section.', 6000);
            }
        } catch (e) {
            this.notify('error', 'Gagal menghapus. Periksa koneksi lalu coba lagi.', 6000);
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

    {{-- Progres bar saat proses (AJAX) --}}
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

    <div class="flex items-center justify-between mb-5 gap-3">
        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ __('nav.sections') }}</h2>
            <p class="text-sm text-gray-500">Master section/kategori artikel. Section yang sudah memiliki artikel tidak dapat dihapus.</p>
        </div>
        <button type="button" @click="openCreate()" :disabled="saving"
                class="shrink-0 bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
            + Tambah {{ __('nav.sections') }}
        </button>
    </div>

    {{-- Daftar section --}}
    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('nav.sections') }}</th>
                    <th class="px-4 py-3 font-semibold">Slug</th>
                    <th class="hidden md:table-cell px-4 py-3 font-semibold">Deskripsi</th>
                    <th class="px-4 py-3 font-semibold">Urutan</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-center">Artikel</th>
                    <th class="px-4 py-3 font-semibold text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="s in sections" :key="s.id">
                    <tr class="border-t border-gray-100 hover:bg-gray-50/60">
                        <td class="px-4 py-3 font-medium text-gray-900" x-text="s.name"></td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500" x-text="s.slug"></td>
                        <td class="hidden md:table-cell px-4 py-3 text-gray-600 max-w-xs truncate" x-text="s.description || '—'"></td>
                        <td class="px-4 py-3 text-gray-600" x-text="s.sort_order"></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                  :class="s.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                  x-text="s.is_active ? 'Aktif' : 'Nonaktif'"></span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700" x-text="s.publications_count"></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" @click="openEdit(s)" :disabled="saving"
                                    :title="'Edit ' + label"
                                    class="inline-flex items-center justify-center rounded-lg p-1.5 text-primary hover:bg-primary-light disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0 2.638 2.638M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </button>
                            <button type="button" @click="remove(s)" :disabled="saving || s.publications_count > 0 || s.submissions_count > 0"
                                    :title="s.publications_count > 0 || s.submissions_count > 0 ? 'Tidak dapat dihapus: sudah memiliki artikel/submission' : 'Hapus ' + label"
                                    class="inline-flex items-center justify-center rounded-lg p-1.5 text-red-600 hover:bg-red-50 disabled:opacity-40 disabled:cursor-not-allowed">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="sections.length === 0">
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400 border-t border-gray-100">Belum ada section. Klik &ldquo;+ Tambah {{ __('nav.sections') }}&rdquo; untuk membuat.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Modal form tambah/edit section --}}
    <x-modal name="section-form-modal" maxWidth="lg" focusable>
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-medium text-gray-900" x-text="editing === 'new' ? 'Tambah ' + label : 'Edit ' + label"></h3>
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <x-input-label>Nama {{ __('nav.sections') }}</x-input-label>
                    <input type="text" x-model="form.name" required maxlength="150" placeholder="mis. Riset Utama"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <x-input-label>Urutan</x-input-label>
                    <input type="number" x-model.number="form.sort_order" min="0" max="1000"
                           class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="form.is_active" class="rounded border-gray-300 text-primary">
                        Aktif
                    </label>
                </div>
            </div>
            <div>
                <x-input-label>Deskripsi (opsional)</x-input-label>
                <textarea x-model="form.description" rows="3" maxlength="2000"
                          class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></textarea>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'section-form-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed">
                    Batal
                </button>
                <button type="button" @click="save()" :disabled="saving"
                        class="bg-primary hover:bg-primary-dark text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menyimpan…' : 'Simpan'">Simpan</span>
                </button>
            </div>
        </div>
    </x-modal>
    {{-- Modal konfirmasi hapus section --}}
    <x-modal name="section-delete-modal" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900" x-text="deleteTarget ? 'Hapus ' + label + ' ' + deleteTarget.name + '?' : ''"></h3>
                    <p class="mt-2 text-sm text-gray-600">Kategori ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'section-delete-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    Batal
                </button>
                <button type="button" @click="confirmDelete()" :disabled="saving"
                        class="rounded-lg bg-red-600 hover:bg-red-700 px-6 py-2.5 text-sm font-medium text-white transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menghapus…' : 'Hapus'">Hapus</span>
                </button>
            </div>
        </div>
    </x-modal>
</div>
@endsection

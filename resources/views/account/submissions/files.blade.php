@extends('layouts.workspace')
@section('title', 'Unggah File — '.$submission->reference_no)
@section('content')
@php($hasManuscript = $submission->files->where('type', 'manuscript')->isNotEmpty())
@php($hasCover = $submission->files->where('type', 'cover_letter')->isNotEmpty())
<div class="max-w-3xl mx-auto" x-data="{
    saving: false,
    progress: 0,
    toasts: [],
    toastId: 0,
    fileInfo: null,
    previewUrl: null,
    previewIsPdf: false,
    deleteTarget: null,
    csrf: document.querySelector('meta[name=csrf-token]').content,
    formatSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    },
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
    onFileChange(e) {
        const f = e.target.files[0];
        this.clearPreview();
        if (!f) return;
        this.fileInfo = { name: f.name, size: f.size };
        this.previewIsPdf = (f.type === 'application/pdf' || /\.pdf$/i.test(f.name));
        this.previewUrl = URL.createObjectURL(f);
    },
    clearPreview() {
        if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
        this.previewUrl = null;
        this.fileInfo = null;
        this.previewIsPdf = false;
    },
    clearFileInput() {
        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        this.clearPreview();
    },
    updateSubmitArea(hasM, hasC) {
        const form = document.querySelector('[data-submit-form]');
        const hint = document.querySelector('[data-submit-hint]');
        const ok = hasM && hasC;
        if (form) form.style.display = ok ? '' : 'none';
        if (hint) hint.style.display = ok ? 'none' : '';
    },
    askDeleteFile(t) {
        this.deleteTarget = t;
        this.$dispatch('open-modal', 'file-delete-modal');
    },
    confirmDeleteFile() {
        const t = this.deleteTarget;
        if (!t) return;
        this.saving = true;
        fetch(t.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
            body: JSON.stringify({ _method: 'DELETE' })
        })
        .then(res => res.json().catch(() => ({})).then(data => ({ res, data })))
        .then(({ res, data }) => {
            if (res.ok) {
                const region = document.querySelector('[data-files-region]');
                if (region && data.files_card) {
                    const holder = document.createElement('div');
                    holder.innerHTML = data.files_card;
                    const node = holder.firstElementChild;
                    if (node) {
                        region.replaceWith(node);
                        if (window.Alpine) Alpine.initTree(node);
                    }
                }
                this.updateSubmitArea(!!data.has_manuscript, !!data.has_cover);
                this.notify('success', data.message || 'File dihapus.');
                this.$dispatch('close-modal', 'file-delete-modal');
                this.deleteTarget = null;
            } else {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menghapus file.');
                this.notify('error', errs, 6000);
            }
        })
        .catch(() => this.notify('error', 'Gagal menghapus. Periksa koneksi lalu coba lagi.', 6000))
        .finally(() => { this.saving = false; });
    },
    upload(e) {
        e.preventDefault();
        const form = e.target;
        if (!this.fileInfo) {
            this.notify('error', 'Pilih file terlebih dahulu.', 5000);
            return;
        }
        const fd = new FormData(form);
        this.saving = true;
        this.progress = 0;
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', this.csrf);
        xhr.upload.onprogress = (ev) => {
            if (ev.lengthComputable) this.progress = Math.round((ev.loaded / ev.total) * 100);
        };
        xhr.onload = () => {
            this.saving = false;
            let data = {};
            try { data = JSON.parse(xhr.responseText); } catch (_) { /* abaikan */ }
            if (xhr.status >= 200 && xhr.status < 300) {
                const region = document.querySelector('[data-files-region]');
                if (region && data.files_card) {
                    const holder = document.createElement('div');
                    holder.innerHTML = data.files_card;
                    const node = holder.firstElementChild;
                    if (node) {
                        region.replaceWith(node);
                        if (window.Alpine) Alpine.initTree(node);
                    }
                }
                this.updateSubmitArea(!!data.has_manuscript, !!data.has_cover);
                this.notify('success', data.message || 'File terunggah.');
                form.reset();
                this.clearFileInput();
                this.progress = 0;
            } else {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal mengunggah file.');
                this.notify('error', errs, 6000);
            }
        };
        xhr.onerror = () => {
            this.saving = false;
            this.notify('error', 'Gagal mengunggah. Periksa koneksi lalu coba lagi.', 6000);
        };
        xhr.send(fd);
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

    {{-- Progres bar upload (persentase riil dari XHR) --}}
    <div x-cloak x-show="saving" class="fixed top-0 left-0 right-0 z-50 h-1 bg-gray-200">
        <div class="h-full bg-primary transition-all duration-200" :style="'width:' + progress + '%'"></div>
    </div>
    <style>
        @keyframes toast-in { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(24px); } }
        .toast-in { animation: toast-in 0.3s ease-out; }
        .toast-out { animation: toast-out 0.3s ease-in forwards; }
        [x-cloak] { display: none !important; }
    </style>

    <div class="flex items-center gap-2 text-xs font-medium text-gray-500 mb-5">
        <span class="h-6 w-6 rounded-full bg-gray-300 text-white flex items-center justify-center font-bold">✓</span> Metadata
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="h-6 w-6 rounded-full bg-primary text-white flex items-center justify-center font-bold">2</span> File
        <span class="flex-1 h-px bg-gray-200"></span>
        <span class="h-6 w-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold">3</span> Konfirmasi
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 mb-4">
        <h2 class="font-bold text-gray-900 mb-1">{{ $submission->title }}</h2>
        <p class="text-xs font-mono text-gray-400">{{ $submission->reference_no }}</p>
    </div>

    {{-- Form unggah file (AJAX) --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 mb-4 space-y-5">
        <div>
            <h2 class="font-bold text-gray-900">Unggah File</h2>
            <p class="text-xs text-gray-400 mt-0.5">Maks 50 MB per file — PDF, DOCX, ODT, TEX, ZIP, PNG, JPG, XLSX</p>
        </div>

        <form method="POST" action="{{ route('account.submissions.files.store', $submission) }}" enctype="multipart/form-data" @submit.prevent="upload($event)" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe file</label>
                <select name="type" class="w-full sm:w-72 rounded-lg border-gray-300 text-sm border" required>
                    <option value="manuscript">Naskah utama (wajib)</option>
                    <option value="cover_letter">Cover letter (wajib)</option>
                    <option value="figure">Gambar (figure)</option>
                    <option value="table">Tabel</option>
                    <option value="supplementary">Supplementary</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">File</label>
                <label class="flex flex-col items-center justify-center gap-1.5 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary hover:bg-gray-50 px-4 py-8 cursor-pointer text-center">
                    <svg class="h-9 w-9 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" /></svg>
                    <span class="text-sm text-gray-600">Klik untuk memilih file</span>
                    <span class="text-xs text-gray-400" x-show="fileInfo" x-text="fileInfo ? fileInfo.name : ''"></span>
                    <input type="file" name="file" x-ref="fileInput" @change="onFileChange($event)" required class="sr-only">
                </label>
            </div>

            {{-- Preview file yang akan diunggah --}}
            <div x-cloak x-show="fileInfo" class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 shrink-0 rounded-lg bg-white border border-gray-200 flex items-center justify-center">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="fileInfo ? fileInfo.name : ''"></p>
                        <p class="text-xs text-gray-400" x-text="fileInfo ? formatSize(fileInfo.size) + (previewIsPdf ? ' • PDF' : '') : ''"></p>
                    </div>
                    <button type="button" @click="clearFileInput()" :disabled="saving" class="shrink-0 text-xs text-red-500 hover:underline disabled:opacity-50">Bersihkan</button>
                </div>
                <iframe x-cloak x-show="previewIsPdf" :src="previewUrl" title="Preview naskah (PDF)" class="w-full h-80 rounded-lg border border-gray-200 bg-white"></iframe>
                <p x-show="!previewIsPdf" class="text-xs text-gray-400">Preview tidak tersedia untuk tipe file ini — file akan diunggah apa adanya.</p>
            </div>

            <x-input-error :messages="$errors->get('file')" />
            <div class="flex items-center justify-end gap-3">
                <p x-cloak x-show="saving" class="text-sm text-gray-500" x-text="'Mengunggah… ' + progress + '%'"></p>
                <button type="submit" :disabled="saving || !fileInfo"
                        class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-5 py-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Mengunggah…' : 'Unggah File'">Unggah File</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar file terunggah (diperbarui via AJAX) --}}
    @include('account.submissions.partials.files-list', ['submission' => $submission])

    <div class="flex items-center justify-between pb-8">
        <a href="{{ route('account.submissions.show', $submission) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 text-gray-600">← Kembali</a>
        <form data-submit-form method="POST" action="{{ route('account.submissions.submit', $submission) }}" {{ (!($hasManuscript && $hasCover)) ? 'style="display:none"' : '' }}>
            @csrf
            <button class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-6 py-2">SUBMIT NASKAH →</button>
        </form>
        <span data-submit-hint class="text-xs text-gray-400" {{ ($hasManuscript && $hasCover) ? 'style="display:none"' : '' }}>Unggah naskah utama + cover letter untuk submit.</span>
    </div>
    {{-- Modal konfirmasi hapus file --}}
    <x-modal name="file-delete-modal" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900" x-text="deleteTarget ? 'Hapus ' + deleteTarget.name + '?' : ''"></h3>
                    <p class="mt-2 text-sm text-gray-600">File ini akan dihapus permanen dari naskah. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'file-delete-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    Batal
                </button>
                <button type="button" @click="confirmDeleteFile()" :disabled="saving"
                        class="rounded-lg bg-red-600 hover:bg-red-700 px-6 py-2.5 text-sm font-medium text-white transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menghapus…' : 'Hapus'">Hapus</span>
                </button>
            </div>
        </div>
    </x-modal>
</div>
@endsection

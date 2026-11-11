@php
$editorId = 'page-editor-'.(isset($page) && $page ? 'edit-'.$page->id : 'create');
@endphp
{{--
    Field "content" adalah <textarea> yang diambil alih TinyMCE (dibungkus jadi
    editor rich-text) saat modal dibuka. Komponen Alpine pageEditor (lihat
    resources/js/page-editor.js) menginisialisasi TinyMCE dan menyalin isi
    kembali ke textarea (sync()) sebelum form dikirim.
--}}
<div>
    <label for="{{ $editorId }}-name" class="block text-xs font-semibold text-gray-500 mb-1">Nama Page</label>
    {{-- Input tampilan: disabled agar (1) tidak bisa diketik, (2) dilewati
         autofokus modal & siklus tab — fokus langsung jatuh ke Judul.
         Nilai yang terkirim berasal dari input hidden di bawah. --}}
    <input id="{{ $editorId }}-name" type="text" value="{{ $page?->name ?? '' }}" x-ref="namePreview" readonly disabled maxlength="120"
           class="w-full rounded-lg border-gray-300 text-sm border bg-gray-50 text-gray-500 cursor-not-allowed">
    <input type="hidden" name="name" x-ref="nameHidden" value="{{ $page?->name ?? '' }}">
    <p class="mt-1 text-xs text-gray-400">Terisi otomatis dari Judul — menjadi URL: /page/{nama} (otomatis huruf kecil + dash).</p>
</div>
<div>
    <label for="{{ $editorId }}-title" class="block text-xs font-semibold text-gray-500 mb-1">Judul</label>
    <input id="{{ $editorId }}-title" type="text" name="title" value="{{ $page?->title ?? '' }}" required maxlength="200"
           placeholder="mis. Pedoman Penulis"
           class="w-full rounded-lg border-gray-300 text-sm border focus:border-primary focus:ring-primary">
</div>
<div x-data="{
        originalImage: @js($page?->image_url ?? null),
        imageUrl: @js($page?->image_url ?? null),
        imageName: '',
        imageStatus: '',
        async setImage(file) {
            if (this.imageUrl && this.imageUrl.startsWith('blob:')) URL.revokeObjectURL(this.imageUrl);
            if (!file) {
                this.imageUrl = this.originalImage;
                this.imageName = '';
                this.imageStatus = '';
                return;
            }
            let target = file;
            if (window.mayCompressImage && window.mayCompressImage(file)) {
                this.imageStatus = 'Mengompres gambar…';
                try {
                    target = await window.processImageFile(this.$refs.pageImageInput, file);
                    this.imageStatus = 'Gambar >2 MB dikompres menjadi ' + Math.round(target.size / 1024) + ' KB.';
                } catch (err) {
                    this.imageStatus = 'Kompresi gagal, file asli tetap dipakai.';
                }
            }
            this.imageUrl = URL.createObjectURL(target);
            this.imageName = target.name;
        },
     }">
    <label for="page-image{{ $editorId }}" class="block text-xs font-semibold text-gray-500 mb-1">Gambar (PNG/JPG, opsional)</label>
    <div class="flex items-start gap-4">
        <div class="h-24 w-36 shrink-0 overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50">
            <img x-show="imageUrl" :src="imageUrl" :alt="'Preview gambar' + (imageName ? ' ' + imageName : '')" class="h-full w-full object-cover">
            <div x-show="!imageUrl" class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-300">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                <span class="text-[10px] font-medium">Belum ada gambar</span>
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <input id="page-image{{ $editorId }}" x-ref="pageImageInput" type="file" name="image" accept=".png,.jpg,.jpeg,.webp"
                   x-on:change="setImage($event.target.files ? $event.target.files[0] : null)"
                   class="block w-full text-sm text-gray-500 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary-light file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/15">
            <p class="mt-1.5 truncate text-xs text-gray-500" x-show="imageName" x-text="imageName"></p>
            <p class="mt-1.5 text-xs text-gray-400" x-show="!imageName">Preview muncul langsung setelah memilih gambar. Gambar &gt;2 MB dikompres otomatis.</p>
            <p class="mt-1 text-xs text-amber-600" x-show="imageStatus" x-text="imageStatus"></p>
        </div>
    </div>
</div>
<div>
    <label for="{{ $editorId }}" class="block text-xs font-semibold text-gray-500 mb-1">Isi (HTML)</label>
    <textarea id="{{ $editorId }}" name="content" rows="12"
              class="page-html-editor w-full rounded-lg border border-gray-300 text-sm focus:border-primary focus:ring-primary">{{ $initialContent ?? '' }}</textarea>
    <p class="mt-1 text-xs text-gray-400">Editor TinyMCE: format teks, heading, daftar, tautan, tabel, dan sumber. Isi tersimpan otomatis saat form dikirim.</p>
</div>

@props(['announcement' => null, 'idSuffix' => ''])

@php($posterUrl = $announcement?->poster_url ?? null)

<div x-data="{
        originalPoster: @js($posterUrl),
        posterUrl: @js($posterUrl),
        posterName: '',
        posterStatus: '',
        async setPoster(file) {
            if (this.posterUrl && this.posterUrl.startsWith('blob:')) URL.revokeObjectURL(this.posterUrl);
            if (!file) {
                this.posterUrl = this.originalPoster;
                this.posterName = '';
                this.posterStatus = '';
                return;
            }
            let target = file;
            if (window.mayCompressImage && window.mayCompressImage(file)) {
                this.posterStatus = 'Mengompres gambar…';
                try {
                    target = await window.processImageFile(this.$refs.posterInput, file);
                    this.posterStatus = 'Gambar >2 MB dikompres menjadi ' + Math.round(target.size / 1024) + ' KB.';
                } catch (err) {
                    this.posterStatus = 'Kompresi gagal, file asli tetap dipakai.';
                }
            }
            this.posterUrl = URL.createObjectURL(target);
            this.posterName = target.name;
        }
     }" class="space-y-3">
    <div>
        <x-input-label for="title{{ $idSuffix }}">Judul</x-input-label>
        <input id="title{{ $idSuffix }}" type="text" name="title" value="{{ old('title', $announcement?->title) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
        <x-input-error :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="body{{ $idSuffix }}">Isi pengumuman</x-input-label>
        <textarea id="body{{ $idSuffix }}" name="body" rows="4" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">{{ old('body', $announcement?->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <x-input-label for="type{{ $idSuffix }}">Jenis</x-input-label>
            <select id="type{{ $idSuffix }}" name="type" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                <option value="news" @selected(old('type', $announcement?->type) === 'news')>News</option>
                <option value="cfp" @selected(old('type', $announcement?->type) === 'cfp')>Call for Paper</option>
                <option value="maintenance" @selected(old('type', $announcement?->type) === 'maintenance')>Maintenance</option>
                <option value="other" @selected(old('type', $announcement?->type) === 'other')>Other</option>
            </select>
            <x-input-error :messages="$errors->get('type')" />
        </div>
        <div>
            <x-input-label for="is_active{{ $idSuffix }}">Status</x-input-label>
            <select id="is_active{{ $idSuffix }}" name="is_active" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                <option value="1" @selected(old('is_active', $announcement?->is_active ?? true) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $announcement?->is_active ?? true) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <x-input-label for="starts_at{{ $idSuffix }}">Mulai</x-input-label>
            <input id="starts_at{{ $idSuffix }}" type="date" name="starts_at" value="{{ old('starts_at', $announcement?->starts_at?->format('Y-m-d') ?? now()->toDateString()) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
            <x-input-error :messages="$errors->get('starts_at')" />
        </div>
        <div>
            <x-input-label for="ends_at{{ $idSuffix }}">Berakhir (opsional)</x-input-label>
            <input id="ends_at{{ $idSuffix }}" type="date" name="ends_at" value="{{ old('ends_at', $announcement?->ends_at?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
            <x-input-error :messages="$errors->get('ends_at')" />
        </div>
    </div>

    <div>
        <x-input-label for="poster{{ $idSuffix }}">Poster (PNG/JPG, opsional)</x-input-label>
        <div class="mt-1 flex items-start gap-4">
            <div class="h-24 w-36 shrink-0 overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50">
                <img x-show="posterUrl" :src="posterUrl" :alt="'Preview poster' + (posterName ? ' ' + posterName : '')" class="h-full w-full object-cover">
                <div x-show="!posterUrl" class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                    <span class="text-[10px] font-medium">Belum ada poster</span>
                </div>
            </div>
            <div class="min-w-0 flex-1">
                <input id="poster{{ $idSuffix }}" x-ref="posterInput" type="file" name="poster" accept=".png,.jpg,.jpeg" x-on:change="setPoster($event.target.files ? $event.target.files[0] : null)" class="block w-full text-sm text-gray-500 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary-light file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/15">
                <p class="mt-1.5 truncate text-xs text-gray-500">
                    <span x-show="posterName" x-text="posterName"></span>
                    <span x-show="!posterName" class="text-gray-400">Pilih gambar untuk melihat preview.</span>
                </p>
                <p class="mt-0.5 text-xs text-amber-600" x-show="posterStatus" x-text="posterStatus"></p>
                @if($posterUrl)
                    <p class="mt-0.5 text-[11px] text-gray-400">Poster saat ini: <a target="_blank" class="text-primary hover:underline" href="{{ $posterUrl }}">lihat</a></p>
                @endif
            </div>
        </div>
        <x-input-error :messages="$errors->get('poster')" />
    </div>
</div>

@props(['issue' => null, 'idSuffix' => ''])
<div class="grid grid-cols-3 gap-3">
    <div>
        <x-input-label for="volume{{ $idSuffix }}">Volume</x-input-label>
        <input id="volume{{ $idSuffix }}" type="number" name="volume" min="1" max="999" value="{{ old('volume', $issue?->volume ?? 1) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
        <x-input-error :messages="$errors->get('volume')" />
    </div>
    <div>
        <x-input-label for="number{{ $idSuffix }}">Number</x-input-label>
        <input id="number{{ $idSuffix }}" type="number" name="number" min="1" max="999" value="{{ old('number', $issue?->number ?? 1) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
        <x-input-error :messages="$errors->get('number')" />
    </div>
    <div>
        <x-input-label for="year{{ $idSuffix }}">Tahun</x-input-label>
        <input id="year{{ $idSuffix }}" type="number" name="year" min="2000" max="2100" value="{{ old('year', $issue?->year ?? now()->year) }}" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
        <x-input-error :messages="$errors->get('year')" />
    </div>
</div>
<div>
    <x-input-label for="title{{ $idSuffix }}">Tema issue (opsional)</x-input-label>
    <input id="title{{ $idSuffix }}" type="text" name="title" value="{{ old('title', $issue?->title) }}" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
    <x-input-error :messages="$errors->get('title')" />
</div>
<div x-data="{
        originalCover: @js($issue?->cover_url ?? null),
        coverUrl: @js($issue?->cover_url ?? null),
        coverName: '',
        coverStatus: '',
        async setCover(file) {
            if (this.coverUrl && this.coverUrl.startsWith('blob:')) URL.revokeObjectURL(this.coverUrl);
            if (!file) {
                this.coverUrl = this.originalCover;
                this.coverName = '';
                this.coverStatus = '';
                return;
            }
            let target = file;
            if (window.mayCompressImage && window.mayCompressImage(file)) {
                this.coverStatus = 'Mengompres gambar…';
                try {
                    target = await window.processImageFile(this.$refs.coverInput, file);
                    this.coverStatus = 'Gambar >2 MB dikompres menjadi ' + Math.round(target.size / 1024) + ' KB.';
                } catch (err) {
                    this.coverStatus = 'Kompresi gagal, file asli tetap dipakai.';
                }
            }
            this.coverUrl = URL.createObjectURL(target);
            this.coverName = target.name;
        }
     }">
    <x-input-label for="cover{{ $idSuffix }}">Cover (PNG/JPG, opsional)</x-input-label>
    <div class="mt-1 flex items-start gap-4">
        <div class="h-24 w-36 shrink-0 overflow-hidden rounded-lg border border-dashed border-gray-300 bg-gray-50">
            <img x-show="coverUrl" :src="coverUrl" :alt="'Preview cover' + (coverName ? ' ' + coverName : '')" class="h-full w-full object-cover">
            <div x-show="!coverUrl" class="flex h-full w-full flex-col items-center justify-center gap-1 text-gray-300">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                <span class="text-[10px] font-medium">Belum ada cover</span>
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <input id="cover{{ $idSuffix }}" x-ref="coverInput" type="file" name="cover" accept=".png,.jpg,.jpeg" x-on:change="setCover($event.target.files ? $event.target.files[0] : null)" class="block w-full text-sm text-gray-500 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary-light file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/15">
            <p class="mt-1.5 truncate text-xs text-gray-500">
                <span x-show="coverName" x-text="coverName"></span>
                <span x-show="!coverName" class="text-gray-400">Pilih gambar untuk melihat preview.</span>
            </p>
            <p class="mt-0.5 text-xs text-amber-600" x-show="coverStatus" x-text="coverStatus"></p>
        </div>
    </div>
    <x-input-error :messages="$errors->get('cover')" />
</div>

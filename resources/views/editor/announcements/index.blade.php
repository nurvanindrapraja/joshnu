@extends('layouts.workspace')
@section('title', __('editor.announcements'))
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-gray-900">Pengumuman</h2>
        <button type="button" x-on:click="$dispatch('open-modal', 'create-announcement')" class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Buat Pengumuman
        </button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white divide-y divide-gray-100" data-ajax-results>
        @forelse($announcements as $a)
            <div class="p-4 flex items-start gap-3">
                @if($a->poster_url)
                    <img src="{{ $a->poster_url }}" alt="Poster {{ $a->title }}" class="h-16 w-24 shrink-0 rounded-lg object-cover border border-gray-200">
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400">
                        {{ $a->starts_at->format('d M Y') }}@if($a->ends_at) — {{ $a->ends_at->format('d M Y') }}@endif
                        • {{ $a->type }}
                        @if(! $a->is_active)<span class="text-amber-500">(nonaktif)</span>@endif
                        @if($a->trashed())<span class="text-red-400">(arsip)</span>@endif
                    </p>
                    <p class="font-medium text-sm text-gray-900">{{ $a->title }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($a->body, 120) }}</p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    @unless($a->trashed())
                        <button type="button" title="Edit" x-on:click="$dispatch('open-modal', 'edit-announcement-{{ $a->id }}')" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                        </button>
                    @else
                        <button type="button" title="Pulihkan" x-on:click="$dispatch('open-modal', 'restore-announcement-{{ $a->id }}')" class="inline-flex p-1.5 rounded-lg text-primary hover:bg-primary-light">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                        </button>
                    @endunless
                    <button type="button" title="{{ $a->trashed() ? 'Hapus permanen' : 'Hapus (arsip)' }}" x-on:click="$dispatch('open-modal', 'delete-announcement-{{ $a->id }}')" class="inline-flex p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    </button>
                </div>
            </div>

            {{-- Modal edit --}}
            @unless($a->trashed())
                <x-modal name="edit-announcement-{{ $a->id }}" maxWidth="md" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Pengumuman</h2>
                        <form method="POST" action="{{ route('editor.announcements.update', $a) }}" enctype="multipart/form-data" data-ajax class="space-y-4">
                            @csrf @method('PUT')
                            @include('editor.announcements._form', ['announcement' => $a, 'idSuffix' => '-edit-'.$a->id])
                            <div class="flex justify-end gap-2">
                                <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-announcement-{{ $a->id }}')">Batal</x-secondary-button>
                                <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Simpan</button>
                            </div>
                        </form>
                    </div>
                </x-modal>
            @endunless

            {{-- Modal pulihkan --}}
            @if($a->trashed())
                <x-modal name="restore-announcement-{{ $a->id }}" maxWidth="sm" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">Pulihkan pengumuman?</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Pengumuman <strong>{{ $a->title }}</strong> akan kembali aktif di arsip publik.
                        </p>
                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <x-secondary-button x-on:click="$dispatch('close-modal', 'restore-announcement-{{ $a->id }}')">Batal</x-secondary-button>
                            <form method="POST" action="{{ route('editor.announcements.restore', $a) }}" data-ajax>
                                @csrf
                                <x-danger-button class="!bg-primary hover:!bg-primary-dark">Pulihkan</x-danger-button>
                            </form>
                        </div>
                    </div>
                </x-modal>
            @endif

            {{-- Modal hapus --}}
            <x-modal name="delete-announcement-{{ $a->id }}" maxWidth="sm" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">{{ $a->trashed() ? 'Hapus permanen?' : 'Hapus pengumuman?' }}</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        @if($a->trashed())
                            Pengumuman <strong>{{ $a->title }}</strong> akan dihapus permanen (termasuk poster). Tindakan ini tidak dapat dibatalkan.
                        @else
                            Pengumuman <strong>{{ $a->title }}</strong> akan dipindahkan ke arsip dan tidak lagi tampil di halaman publik.
                        @endif
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'delete-announcement-{{ $a->id }}')">Batal</x-secondary-button>
                        <form method="POST" action="{{ route('editor.announcements.destroy', $a) }}" data-ajax>
                            @csrf @method('DELETE')
                            <x-danger-button>Hapus</x-danger-button>
                        </form>
                    </div>
                </div>
            </x-modal>
        @empty
            <div class="p-8 text-center text-sm text-gray-400">Belum ada pengumuman.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>

    {{-- Modal: Buat Pengumuman (AJAX) --}}
    <x-modal name="create-announcement" maxWidth="md" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Pengumuman Baru</h2>
            <form method="POST" action="{{ route('editor.announcements.store') }}" enctype="multipart/form-data" data-ajax class="space-y-4">
                @csrf
                @include('editor.announcements._form', ['announcement' => null, 'idSuffix' => '-create'])
                <div class="flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'create-announcement')">Batal</x-secondary-button>
                    <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Terbitkan</button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
@endsection

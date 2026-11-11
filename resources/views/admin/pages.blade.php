@extends('layouts.workspace')
@section('title', 'Pages')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">Halaman statis (nama, judul, isi HTML) yang tampil di beranda.</p>
        <button type="button" x-on:click="$dispatch('open-modal', 'create-page')"
                class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">+ Buat Page</button>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <table class="table-cards w-full text-sm min-w-[560px]">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Judul</th>
                    <th class="px-4 py-3">Diperbarui</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pages as $p)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500" data-label="Nama">/{{ $p->name }}</td>
                        <td class="px-4 py-3 text-gray-800" data-label="Judul">
                            {{ $p->title }}
                            @unless($p->is_active)
                                <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 border border-gray-200 px-2 py-0.5 text-[11px] font-medium text-gray-500">Dinonaktifkan</span>
                            @endunless
                            <a href="{{ route('pages.show', $p->name) }}" target="_blank" class="text-xs text-primary hover:underline ml-1">buka ↗</a>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs" data-label="Diperbarui">{{ $p->updated_at?->format('d M Y, H:i') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap" data-label="Aksi">
                            <form method="POST" action="{{ route('admin.pages.toggle', $p) }}" data-ajax class="inline">
                                @csrf
                                <button type="submit" title="{{ $p->is_active ? 'Sembunyikan dari beranda' : 'Tampilkan di beranda' }}"
                                        class="inline-flex p-1.5 rounded-lg {{ $p->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-300 hover:text-gray-400 hover:bg-gray-100' }}">
                                    @if($p->is_active)
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    @endif
                                </button>
                            </form>
                            <button type="button" title="Edit" x-on:click="$dispatch('open-modal', 'edit-page-{{ $p->id }}')"
                                    class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary hover:bg-gray-100">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </button>
                            <button type="button" title="Hapus" x-on:click="$dispatch('open-modal', 'delete-page-{{ $p->id }}')"
                                    class="inline-flex p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 ml-1">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">Belum ada page. Klik "+ Buat Page".</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal: Buat Page (AJAX) --}}
    <x-modal name="create-page" maxWidth="2xl" focusable>
        <div class="p-6" x-data="pageEditor('page-editor-create', 'create-page')">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Page Baru</h2>
            <form method="POST" action="{{ route('admin.pages.store') }}" data-ajax class="space-y-4" @submit="sync()">
                @csrf
                @include('admin.pages._form', ['page' => null, 'initialContent' => ''])
                <div class="flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'create-page')">Batal</x-secondary-button>
                    <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- Modal: Edit + Hapus per page --}}
    @foreach($pages as $p)
        <x-modal name="edit-page-{{ $p->id }}" maxWidth="2xl" focusable>
            <div class="p-6" x-data="pageEditor('page-editor-edit-{{ $p->id }}', 'edit-page-{{ $p->id }}')">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Page</h2>
                <form method="POST" action="{{ route('admin.pages.update', $p) }}" data-ajax class="space-y-4" @submit="sync()">
                    @csrf @method('PUT')
                    @include('admin.pages._form', ['page' => $p, 'initialContent' => $p->content])
                    <div class="flex justify-end gap-2">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-page-{{ $p->id }}')">Batal</x-secondary-button>
                        <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Simpan</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <x-modal name="delete-page-{{ $p->id }}" maxWidth="sm" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Hapus page?</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Page <strong>{{ $p->title }}</strong> akan dihapus dan tidak lagi tampil di beranda. Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'delete-page-{{ $p->id }}')">Batal</x-secondary-button>
                    <form method="POST" action="{{ route('admin.pages.destroy', $p) }}" data-ajax>
                        @csrf @method('DELETE')
                        <x-danger-button>Hapus</x-danger-button>
                    </form>
                </div>
            </div>
        </x-modal>
    @endforeach
</div>


@endsection

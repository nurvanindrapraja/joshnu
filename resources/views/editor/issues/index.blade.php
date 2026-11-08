@extends('layouts.workspace')
@section('title', __('editor.issues'))
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900">Issue Management</h2>
        <button type="button" x-on:click="$dispatch('open-modal', 'create-issue')" class="bg-primary text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-primary-dark">+ Buat Issue</button>
    </div>

    {{-- Filter (AJAX) --}}
    <form data-ajax-filter class="rounded-xl border border-gray-200 bg-white p-4 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <div>
            <label for="f-tema" class="block text-xs font-semibold text-gray-500 mb-1">Tema</label>
            <input id="f-tema" type="text" name="tema" value="{{ $filters['tema'] }}" placeholder="Cari tema issue…" class="w-full rounded-lg border-gray-300 text-sm border">
        </div>
        <div>
            <label for="f-volume" class="block text-xs font-semibold text-gray-500 mb-1">Volume</label>
            <select id="f-volume" name="volume" class="w-full rounded-lg border-gray-300 text-sm border">
                <option value="">Semua</option>
                @foreach($volumes as $v)
                    <option value="{{ $v }}" @selected($filters['volume'] === (int) $v)>Vol. {{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-year" class="block text-xs font-semibold text-gray-500 mb-1">Tahun</label>
            <select id="f-year" name="year" class="w-full rounded-lg border-gray-300 text-sm border">
                <option value="">Semua</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected($filters['year'] === (int) $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center">
            <button type="button" data-ajax-reset class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 hover:text-gray-800">Reset filter</button>
        </div>
    </form>

    {{-- Hasil (di-refresh via AJAX) --}}
    <div data-ajax-results>
        <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
            <table class="table-cards w-full text-sm min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Issue</th>
                        <th class="px-4 py-3">Tema</th>
                        <th class="px-4 py-3">Artikel terbit</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($issues as $i)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900" data-label="Issue">{{ $i->label() }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs" data-label="Tema">{{ $i->title ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600" data-label="Artikel terbit">{{ $i->published_count }}</td>
                            <td class="px-4 py-3" data-label="Status">
                                @if($i->isPublished())
                                    <span class="text-xs text-green-600 font-medium">Terbit {{ $i->published_at->format('d M Y') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap" data-label="Aksi">
                                @if($i->isPublished())
                                    <a href="{{ route('issues.show', $i->slug) }}" target="_blank" title="Lihat" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary hover:bg-gray-100">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                    @if(auth()->user()->hasRole('admin', 'eic'))
                                        <button type="button" title="Unpublish" x-on:click="$dispatch('open-modal', 'confirm-unpublish-issue-{{ $i->id }}')" class="p-1.5 rounded-lg text-red-500 hover:text-red-600 hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                        </button>
                                    @endif
                                @else
                                    <button type="button" title="Edit" x-on:click="$dispatch('open-modal', 'edit-issue-{{ $i->id }}')" class="inline-flex p-1.5 rounded-lg text-gray-500 hover:text-primary hover:bg-gray-100">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <button type="button" title="Publish" x-on:click="$dispatch('open-modal', 'confirm-publish-issue-{{ $i->id }}')" class="p-1.5 rounded-lg text-primary hover:bg-primary-light">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </button>
                                    @if(auth()->user()->hasRole('admin', 'eic'))
                                        <button type="button" title="Hapus" x-on:click="$dispatch('open-modal', 'confirm-delete-issue-{{ $i->id }}')" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada issue{{ $filters['tema'] || $filters['volume'] || $filters['year'] ? ' untuk filter ini' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $issues->links() }}</div>
    </div>

    {{-- Modal: Buat Issue (AJAX) --}}
    <x-modal name="create-issue" maxWidth="md" focusable>
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Buat Issue</h2>
            <form method="POST" action="{{ route('editor.issues.store') }}" enctype="multipart/form-data" data-ajax class="space-y-4">
                @csrf
                @include('editor.issues._form', ['issue' => null, 'idSuffix' => '-create'])
                <div class="flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close-modal', 'create-issue')">Batal</x-secondary-button>
                    <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- Modal: Edit Issue per draft (AJAX) --}}
    @foreach($issues as $i)
        @if(! $i->isPublished())
            {{-- Konfirmasi publish (AJAX) --}}
            <x-modal name="confirm-publish-issue-{{ $i->id }}" maxWidth="sm" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">Publikasikan issue?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Issue <strong>{{ $i->label() }}</strong> akan diterbitkan. Seluruh artikel terjadwal pada issue ini akan ikut terbit.
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-publish-issue-{{ $i->id }}')">Batal</x-secondary-button>
                        <form method="POST" action="{{ route('editor.issues.publish', $i) }}" data-ajax>
                            @csrf
                            <x-danger-button class="!bg-primary hover:!bg-primary-dark">Publish</x-danger-button>
                        </form>
                    </div>
                </div>
            </x-modal>

            {{-- Modal edit --}}
            <x-modal name="edit-issue-{{ $i->id }}" maxWidth="md" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Issue {{ $i->label() }}</h2>
                    <form method="POST" action="{{ route('editor.issues.update', $i) }}" enctype="multipart/form-data" data-ajax class="space-y-4">
                        @csrf @method('PUT')
                        @include('editor.issues._form', ['issue' => $i, 'idSuffix' => '-'.$i->id])
                        <div class="flex justify-end gap-2">
                            <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-issue-{{ $i->id }}')">Batal</x-secondary-button>
                            <button class="bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium">Simpan</button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif

        @if(! $i->isPublished() && auth()->user()->hasRole('admin', 'eic'))
            {{-- Konfirmasi hapus issue draft (AJAX) --}}
            <x-modal name="confirm-delete-issue-{{ $i->id }}" maxWidth="sm" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">Hapus issue?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Issue <strong>{{ $i->label() }}</strong> (draft) akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-delete-issue-{{ $i->id }}')">Batal</x-secondary-button>
                        <form method="POST" action="{{ route('editor.issues.destroy', $i) }}" data-ajax>
                            @csrf @method('DELETE')
                            <x-danger-button>Hapus</x-danger-button>
                        </form>
                    </div>
                </div>
            </x-modal>
        @endif

        @if($i->isPublished() && auth()->user()->hasRole('admin', 'eic'))
            {{-- Konfirmasi unpublish (AJAX) --}}
            <x-modal name="confirm-unpublish-issue-{{ $i->id }}" maxWidth="sm" focusable>
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">Unpublish issue?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Issue <strong>{{ $i->label() }}</strong> akan ditarik dari terbitan; artikelnya kembali ke tahap terjadwal (draft issue).
                    </p>
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <x-secondary-button x-on:click="$dispatch('close-modal', 'confirm-unpublish-issue-{{ $i->id }}')">Batal</x-secondary-button>
                        <form method="POST" action="{{ route('editor.issues.unpublish', $i) }}" data-ajax>
                            @csrf
                            <x-danger-button>Unpublish</x-danger-button>
                        </form>
                    </div>
                </div>
            </x-modal>
        @endif
    @endforeach
</div>
@endsection

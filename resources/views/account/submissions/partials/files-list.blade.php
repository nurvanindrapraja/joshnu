@php($hasManuscript = $submission->files->where('type', 'manuscript')->isNotEmpty())
@php($hasCover = $submission->files->where('type', 'cover_letter')->isNotEmpty())
<div class="rounded-xl border border-gray-200 bg-white p-6 mb-4" data-files-region>
    <h2 class="font-bold text-gray-900 mb-3">File Terunggah</h2>
    <table class="table-cards w-full text-sm">
        <tbody class="divide-y divide-gray-100">
            @forelse($submission->files as $f)
                <tr>
                    <td class="py-2 text-gray-800" data-label="File" data-full>{{ $f->original_name }}</td>
                    <td class="py-2 text-gray-500" data-label="Tipe">{{ $f->type }}{{ $f->revision ? ' (v'.$f->revision->version.')' : '' }}</td>
                    <td class="py-2 text-gray-500" data-label="Ukuran">{{ $f->humanSize() }}</td>
                    <td class="py-2 text-right space-x-3 whitespace-nowrap" data-label="Aksi">
                        <a href="{{ route('account.submissions.files.download', [$submission, $f]) }}" class="text-primary text-xs hover:underline">Unduh</a>
                        <button type="button" @click="askDeleteFile(@js(['id' => $f->id, 'name' => $f->original_name, 'url' => route('account.submissions.files.destroy', [$submission, $f])]))" class="text-red-500 text-xs hover:underline">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr><td class="py-3 text-gray-400">Belum ada file.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 text-sm text-gray-600">
        Kelengkapan:
        <span class="{{ $hasManuscript ? 'text-green-600' : 'text-red-500' }}"> {{ $hasManuscript ? '✓' : '✗' }} Naskah utama</span>
        •
        <span class="{{ $hasCover ? 'text-green-600' : 'text-red-500' }}">{{ $hasCover ? '✓' : '✗' }} Cover letter</span>
    </div>
</div>

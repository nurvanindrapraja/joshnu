@extends('layouts.workspace')
@section('title', 'Kirim Revisi — '.$submission->reference_no)
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mb-4 text-sm text-amber-900">
        <p class="font-semibold">Editor meminta revisi. Silakan unggah naskah revisi beserta respons per-komentar reviewer.</p>
        @php($lastDecision = $submission->decisions->whereNotIn('type', ['continue_review'])->last())
        @if($lastDecision?->revision_due_at)
            <p class="mt-1">Batas revisi: <strong>{{ $lastDecision->revision_due_at->format('d M Y') }}</strong></p>
        @endif
    </div>

    @php($pendingReviews = $submission->reviews->where('status', 'submitted')->where('round', $submission->current_round))
    @if($pendingReviews->count())
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
            <h2 class="font-bold text-gray-900 mb-3">Komentar Reviewer yang Harus Direspons</h2>
            @foreach($pendingReviews as $r)
                <div class="rounded-lg bg-gray-50 p-3 mb-2">
                    <p class="text-xs font-semibold text-gray-500">Reviewer ronde {{ $r->round }} • {{ \App\Models\Decision::TYPE_LABELS[$r->recommended_decision] ?? $r->recommended_decision }}</p>
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $r->comments_to_author }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('account.submissions.revisions.store', $submission) }}" enctype="multipart/form-data" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf
        <div>
            <x-input-label for="note">Catatan revisi (ringkas perubahan yang dilakukan)</x-input-label>
            <textarea id="note" name="note" rows="4" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('note') }}</textarea>
            <x-input-error class="mt-1" :messages="$errors->get('note')" />
        </div>
        <div>
            <x-input-label>File revisi (naskah baru / respons reviewer) — bisa beberapa file</x-input-label>
            <input type="file" name="files[]" multiple required class="mt-1 block w-full text-sm">
            <x-input-error class="mt-1" :messages="$errors->get('files')" />
        </div>
        <div class="flex items-center justify-between">
            <a href="{{ route('account.submissions.show', $submission) }}" class="text-sm border border-gray-300 rounded-lg px-4 py-2 text-gray-600">Batal</a>
            <button class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-6 py-2">Kirim Revisi →</button>
        </div>
    </form>
</div>
@endsection

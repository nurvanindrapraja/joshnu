@extends('layouts.workspace')
@section('title', 'Review — '.$submission->reference_no)
@section('content')
@php($setting = \App\Models\JournalSetting::current())
@php($canEdit = in_array($review->status, ['invited', 'accepted', 'in_progress', 'overdue']))
@php($blind = $setting->review_mode === 'double_blind')
<div class="max-w-3xl mx-auto">
    <div class="rounded-xl border border-gray-200 bg-white p-6 mb-4">
        <p class="text-xs font-mono text-gray-400">#{{ $submission->reference_no }} • ronde {{ $review->round }}</p>
        <h2 class="text-lg font-bold text-gray-900 mt-1">{{ $review->blindTitle() }}</h2>
        <p class="text-xs text-gray-500 mt-1">
            {{ $submission->section?->name }}
            @if($blind) • <span class="text-purple-600">double-blind: nama penulis disembunyikan</span>@endif
            @if($review->due_at) • Due: <strong class="{{ $review->isOverdue() ? 'text-red-600' : '' }}">{{ $review->due_at->format('d M Y') }}</strong>@endif
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($submission->files->whereNotIn('type', ['review_form', 'proof']) as $f)
                <a href="{{ route('review.files.download', [$submission, $f]) }}" class="text-xs border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5 flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v13.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                    {{ $f->original_name }} ({{ $f->humanSize() }})
                </a>
            @endforeach
        </div>
    </div>

    @if($submission->metadata['id_abstract'] ?? null)
        <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
            <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2">Abstrak</h3>
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $submission->metadata['id_abstract'] }}</p>
            <p class="text-sm text-gray-600 mt-2">
                <span class="font-medium">Kata kunci:</span>
                @foreach($submission->metadata['keywords'] ?? [] as $kw)<span class="inline-block bg-primary-light text-gray-700 text-xs rounded-full px-2 py-0.5">{{ $kw }}</span> @endforeach
            </p>
        </div>
    @endif

    @if($canEdit)
        <div class="rounded-xl border border-gray-200 bg-white p-6">
        <form id="reviewForm" method="POST" action="{{ route('review.update', $submission) }}" enctype="multipart/form-data" data-ajax class="space-y-5">
            @csrf
            <div>
                <x-input-label>{{ __('review.recommendation') }}</x-input-label>
                <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach(['accept' => 'Accept', 'minor_revise' => 'Minor Revise', 'major_revise' => 'Major Revise', 'reject' => 'Reject'] as $val => $label)
                        <label class="flex items-center gap-2 rounded-lg border p-2.5 text-sm cursor-pointer {{ old('recommended_decision', $review->recommended_decision) === $val ? 'border-primary bg-primary-light' : 'border-gray-300' }}">
                            <input type="radio" name="recommended_decision" value="{{ $val }}" @checked(old('recommended_decision', $review->recommended_decision) === $val) class="rounded border-gray-300 text-primary" required>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('recommended_decision')" />
            </div>

            <div>
                <x-input-label>{{ __('review.ratings') }} (1–5)</x-input-label>
                <div class="mt-2 grid sm:grid-cols-2 gap-3">
                    @foreach([['rating_novelty', 'Kebaruan'], ['rating_methodology', 'Metodologi'], ['rating_clarity', 'Kejelasan'], ['rating_contribution', 'Kontribusi']] as [$field, $label])
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-gray-700">{{ $label }}</span>
                            <select name="{{ $field }}" class="rounded-lg border-gray-300 text-sm border p-1.5 w-20">
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" @selected((int) old($field, $review->{$field}) === $i)>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <x-input-label for="comments_to_author">{{ __('review.to_author') }}</x-input-label>
                <textarea id="comments_to_author" name="comments_to_author" rows="5" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('comments_to_author', $review->comments_to_author) }}</textarea>
                <x-input-error class="mt-1" :messages="$errors->get('comments_to_author')" />
            </div>
            <div>
                <x-input-label for="comments_to_editor">{{ __('review.to_editor') }}</x-input-label>
                <textarea id="comments_to_editor" name="comments_to_editor" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 text-sm border">{{ old('comments_to_editor', $review->comments_to_editor) }}</textarea>
            </div>

            <div>
                <x-input-label for="review_file">Unggah form review (PDF/DOCX, opsional)</x-input-label>
                <input id="review_file" type="file" name="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm">
            </div>
        </form>

        <div class="mt-5 flex items-center justify-between">
            <a href="{{ route('review.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Antrian review</a>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('reviewer.invite.decline', $review->invite_token ?? '') }}" data-ajax data-prompt="Alasan penolakan (opsional):" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="reason" value="">
                    <button class="text-sm border border-gray-300 text-gray-600 hover:text-red-600 hover:border-red-300 rounded-lg px-4 py-2">{{ __('review.decline') }}</button>
                </form>
                <button type="submit" form="reviewForm" class="bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-6 py-2">{{ __('review.submit') }}</button>
            </div>
        </div>
        </div>
    @else
        @if($review->status === 'declined')
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-600">
                Anda telah menolak undangan review ini.
                @if($review->decline_reason)<p class="mt-2 text-xs text-gray-500">Alasan: {{ $review->decline_reason }}</p>@endif
            </div>
        @else
            <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-800">
                Review Anda telah dikirim {{ optional($review->submitted_at)->format('d M Y H:i') }}. Terima kasih atas kontribusinya!
            </div>
        @endif
        @if($review->recommended_decision)
            <div class="mt-4 rounded-xl border border-gray-200 bg-white p-5 text-sm">
                <p><span class="text-gray-400">Rekomendasi:</span> <strong>{{ \App\Models\Decision::TYPE_LABELS[$review->recommended_decision] }}</strong></p>
                @if($review->comments_to_author)<p class="mt-3 text-gray-700 whitespace-pre-line">{{ $review->comments_to_author }}</p>@endif
            </div>
        @endif
    @endif
</div>
@endsection

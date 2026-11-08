@extends('layouts.workspace')
@section('title', __('account.submissions'))
@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-900">{{ __('account.submissions') }}</h2>
    <a href="{{ route('account.submissions.create') }}" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg px-4 py-2">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
        {{ __('account.new_submission') }}
    </a>
</div>

<div x-data="{ withdrawTarget: null }">
<div class="space-y-4">
    @forelse($submissions as $s)
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-mono text-gray-400">{{ $s->reference_no }}</p>
                    <a href="{{ route('account.submissions.show', $s) }}" class="font-bold text-gray-900 hover:text-primary">{{ $s->title }}</a>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $s->section?->name }} • dikirim {{ $s->submitted_at->format('d M Y') }}
                        @if(!in_array($s->stage, ['published', 'rejected', 'withdrawn']))
                            • <span class="{{ $s->daysInStage() >= 30 ? 'text-amber-600 font-semibold' : '' }}">{{ $s->daysInStage() }} {{ __('editor.days_in_stage') }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @include('partials.stage-badge', ['stage' => $s->stage, 'label' => $s->stageLabel().($s->stage === 'under_review' ? ' (ronde '.$s->current_round.')' : '')])
                    @if($s->publication?->isPublished())
                        <a href="{{ route('articles.show', $s->publication->slug) }}" class="text-xs bg-emerald-100 text-emerald-800 rounded-full px-3 py-1 font-medium">{{ __('account.my_article') }}</a>
                    @endif
                </div>
            </div>

            <div class="mt-4 bg-gray-50 rounded-lg p-3">
                @include('partials.pipeline-progress', ['submission' => $s->load('events')])
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('account.submissions.show', $s) }}" class="text-sm border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-3 py-1.5">{{ __('common.view') }}</a>
                @if($s->canWithdraw())
                    <button type="button" @click="withdrawTarget = { ref: @js($s->reference_no), url: @js(route('account.submissions.withdraw', $s)) }; $dispatch('open-modal', 'withdraw-modal')" class="text-sm border border-red-200 text-red-600 hover:bg-red-50 rounded-lg px-3 py-1.5">{{ __('account.withdraw') }}</button>
                @endif
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500">
            Anda belum punya naskah. <a href="{{ route('account.submissions.create') }}" class="text-primary hover:underline">Kirim naskah pertama</a>.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $submissions->links() }}</div>

    {{-- Modal konfirmasi tarik naskah --}}
    <x-modal name="withdraw-modal" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900" x-text="withdrawTarget ? 'Yakin menarik naskah ' + withdrawTarget.ref + '?' : ''"></h3>
                    <p class="mt-2 text-sm text-gray-600">Setelah ditarik, naskah keluar dari alur review dan tidak dapat dilanjutkan kembali. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            <form :action="withdrawTarget ? withdrawTarget.url : ''" method="POST" class="mt-4">
                @csrf
                <label class="block text-xs font-semibold text-gray-600 mb-1">Alasan penarikan (opsional)</label>
                <textarea name="reason" rows="2" class="w-full rounded-lg border-gray-300 text-sm border" placeholder="Misalnya: naskah sudah dipublikasikan di jurnal lain"></textarea>
                <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="$dispatch('close-modal', 'withdraw-modal')" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-red-600 hover:bg-red-700 px-6 py-2.5 text-sm font-medium text-white transition">Tarik Naskah</button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
@endsection

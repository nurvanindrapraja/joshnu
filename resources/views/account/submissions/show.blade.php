@extends('layouts.workspace')
@section('title', $submission->reference_no)
@section('content')
@php($meta = $submission->metadata ?? [])
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <p class="text-xs font-mono text-gray-400">{{ $submission->reference_no }} @if($submission->current_round > 1)• ronde {{ $submission->current_round }}@endif</p>
            <h2 class="text-xl font-bold text-gray-900">{{ $submission->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $submission->section?->name }} • dikirim {{ $submission->submitted_at->format('d M Y') }}
                @if($isOwner)
                    @if($submission->canWithdraw())
                        • <span class="text-amber-600">{{ $submission->daysInStage() }} {{ __('editor.days_in_stage') }}</span>
                    @endif
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.stage-badge', ['stage' => $submission->stage, 'label' => $submission->stageLabel()])
            @if($submission->publication?->isPublished())
                <a href="{{ route('articles.show', $submission->publication->slug) }}" class="text-xs bg-emerald-100 text-emerald-800 rounded-full px-3 py-1 font-medium">{{ __('account.my_article') }}</a>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 mb-4">
        @include('partials.pipeline-progress', ['submission' => $submission])
    </div>

    @if($isOwner && $submission->canWithdraw())
        <div class="flex gap-2 mb-4">
            <a href="{{ route('account.submissions.files', $submission) }}" class="text-sm border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-4 py-2">File</a>
            <a href="{{ route('account.submissions.edit', $submission) }}" class="text-sm border border-gray-300 hover:border-primary hover:text-primary rounded-lg px-4 py-2">Edit Metadata</a>
            <button type="button" @click="$dispatch('open-modal', 'withdraw-modal')" class="text-sm border border-red-200 text-red-600 hover:bg-red-50 rounded-lg px-4 py-2">{{ __('account.withdraw') }}</button>
            @if($submission->stage === 'revised')
                <a href="{{ route('account.submissions.revisions.create', $submission) }}" class="text-sm bg-primary text-white hover:bg-primary-dark rounded-lg px-4 py-2 font-medium">Kirim Revisi</a>
            @endif
        </div>
    @endif

    {{-- Modal konfirmasi tarik naskah --}}
    <x-modal name="withdraw-modal" maxWidth="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Yakin menarik naskah {{ $submission->reference_no }}?</h3>
                    <p class="mt-2 text-sm text-gray-600">Setelah ditarik, naskah keluar dari alur review dan tidak dapat dilanjutkan kembali. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('account.submissions.withdraw', $submission) }}" class="mt-4">
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

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden" x-data="{ tab: 'ringkasan' }">
        <nav class="flex border-b border-gray-200 overflow-x-auto text-sm">
            @php($tabs = [
                'ringkasan' => __('account.overview'),
                'riwayat' => __('account.timeline'),
                'file' => __('account.files').' ('.$submission->files->count().')',
                'keputusan' => 'Keputusan ('.$submission->decisions->count().')',
                'review' => 'Review ('.$submission->reviews->count().')',
                'pesan' => __('account.messages').' ('.$submission->messages->count().')',
                'apc' => __('account.payment'),
            ])
            @foreach($tabs as $key => $label)
                <button @click="tab = '{{ $key }}'" class="px-4 py-3 border-b-2 whitespace-nowrap" :class="tab === '{{ $key }}' ? 'border-primary text-primary font-medium' : 'border-transparent text-gray-500 hover:text-gray-700'" :style="tab === '{{ $key }}' ? 'border-color: var(--primary); color: var(--primary)' : ''">{{ $label }}</button>
            @endforeach
        </nav>

        <div class="p-5" x-show="tab === 'ringkasan'">
            <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div><dt class="text-gray-400 text-xs uppercase font-semibold">Penulis</dt><dd class="text-gray-800 mt-0.5">{{ $submission->authors->pluck('name')->implode(', ') }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase font-semibold">Section</dt><dd class="text-gray-800 mt-0.5">{{ $submission->section?->name }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase font-semibold">Lisensi</dt><dd class="text-gray-800 mt-0.5">{{ $meta['license'] ?? 'CC BY 4.0' }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase font-semibold">Konflik kepentingan</dt><dd class="text-gray-800 mt-0.5">{{ $submission->conflict_of_interest }}</dd></div>
                @if($meta['funding'] ?? null)<div><dt class="text-gray-400 text-xs uppercase font-semibold">Pendanaan</dt><dd class="text-gray-800 mt-0.5">{{ $meta['funding'] }}</dd></div>@endif
                @if($meta['acknowledgments'] ?? null)<div><dt class="text-gray-400 text-xs uppercase font-semibold">Acknowledgments</dt><dd class="text-gray-800 mt-0.5">{{ $meta['acknowledgments'] }}</dd></div>@endif
            </dl>
            @if($meta['id_title'] ?? null)
                <h3 class="text-sm font-bold text-gray-700 mt-5 mb-2">Judul & Abstrak</h3>
                <p class="text-sm text-gray-700"><strong>ID:</strong> {{ $meta['id_title'] }}</p>
                @if($meta['en_title'] ?? null)<p class="text-sm text-gray-700"><strong>EN:</strong> {{ $meta['en_title'] }}</p>@endif
                <p class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ $meta['id_abstract'] ?? '' }}</p>
                <p class="text-sm text-gray-600 mt-2">
                    <strong>Kata kunci (ID):</strong>
                    @foreach($meta['keywords'] ?? [] as $kw)<span class="inline-block bg-primary-light text-gray-700 text-xs rounded-full px-2 py-0.5">{{ $kw }}</span> @endforeach
                </p>
                @if(!empty($meta['en_keywords']) && $meta['en_keywords'] !== ($meta['keywords'] ?? []))
                <p class="text-sm text-gray-600 mt-2">
                    <strong>Keywords (EN):</strong>
                    @foreach($meta['en_keywords'] as $kw)<span class="inline-block bg-gray-100 text-gray-700 text-xs rounded-full px-2 py-0.5">{{ $kw }}</span> @endforeach
                </p>
                @endif
            @endif
            @if($submission->suggested_reviewers)
                <h3 class="text-sm font-bold text-gray-700 mt-5 mb-2">Reviewer yang Disarankan</h3>
                @foreach($submission->suggested_reviewers as $r)
                    <p class="text-sm text-gray-600">• {{ $r['name'] }} {{ $r['email'] ? '(&lt;'.$r['email'].'&gt;) ' : '' }}{{ $r['institution'] ?? '' }}</p>
                @endforeach
            @endif
        </div>

        <div class="p-5" x-show="tab === 'riwayat'" x-cloaked>
            <ol class="relative border-l border-gray-200 ml-2">
                @foreach($submission->events as $ev)
                    <li class="mb-4 ml-4">
                        <span class="absolute -left-1.5 h-3 w-3 rounded-full bg-primary-light border-2 border-primary"></span>
                        <p class="text-sm text-gray-800">
                            <span class="font-medium">{{ $ev->event }}</span>
                            @if($ev->from_stage && $ev->to_stage)
                                <span class="text-gray-400">({{ \App\Models\Submission::STAGE_LABELS[$ev->from_stage] ?? $ev->from_stage }} → {{ \App\Models\Submission::STAGE_LABELS[$ev->to_stage] ?? $ev->to_stage }})</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $ev->created_at->format('d M Y H:i') }} • {{ $ev->actor?->name ?? 'Sistem' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="p-5" x-show="tab === 'file'" x-cloaked>
            <table class="table-cards w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="pb-2">File</th><th class="pb-2">Tipe</th><th class="pb-2">Versi</th><th class="pb-2">Ukuran</th><th class="pb-2"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($submission->files as $f)
                        <tr>
                            <td class="py-2 text-gray-800" data-label="File" data-full>{{ $f->original_name }}</td>
                            <td class="py-2 text-gray-500" data-label="Tipe">{{ $f->type }}</td>
                            <td class="py-2 text-gray-500" data-label="Versi">{{ $f->revision ? 'v'.$f->revision->version : '—' }}</td>
                            <td class="py-2 text-gray-500" data-label="Ukuran">{{ $f->humanSize() }}</td>
                            <td class="py-2 text-right" data-label="Aksi">
                                <a href="{{ route('account.submissions.files.download', [$submission, $f]) }}" class="text-primary text-xs hover:underline">Unduh</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-gray-400">Belum ada file.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-5 space-y-4" x-show="tab === 'keputusan'" x-cloaked>
            @forelse($submission->decisions as $d)
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-2">
                        @include('partials.stage-badge', ['stage' => $d->type === 'reject' ? 'rejected' : ($d->type === 'accept' ? 'accepted' : 'revised'), 'label' => $d->typeLabel()])
                        <span class="text-xs text-gray-400">ronde {{ $d->round }} • {{ $d->decided_at->format('d M Y') }} • {{ $d->editor?->name }}</span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ $d->comment_to_author }}</p>
                    @if($d->revision_due_at)<p class="text-xs text-amber-700 mt-2">Batas revisi: {{ $d->revision_due_at->format('d M Y') }}</p>@endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada keputusan.</p>
            @endforelse
        </div>

        <div class="p-5 space-y-4" x-show="tab === 'review'" x-cloaked>
            @if(! $isOwner && false)<p class="text-xs text-gray-400">Komentar reviewer hanya terlihat oleh editor.</p>@endif
            @forelse($submission->reviews as $r)
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium">{{ $isOwner ? 'Reviewer ronde '.$r->round : $r->displayReviewerName() }}</span>
                        <span class="text-xs text-gray-400">
                            {{ $r->status }}
                            @if($r->submitted_at) • {{ $r->submitted_at->format('d M Y') }}
                            @elseif($r->due_at) • due {{ $r->due_at->format('d M Y') }}
                            @endif
                        </span>
                        @if($r->recommended_decision && $r->status === 'submitted')
                            <span class="ml-auto text-xs font-medium">{{ \App\Models\Decision::TYPE_LABELS[$r->recommended_decision] }}</span>
                        @endif
                    </div>
                    @if($isOwner && $r->status === 'submitted' && $r->comments_to_author)
                        <p class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ $r->comments_to_author }}</p>
                    @elseif(! $isOwner && $r->comments_to_editor)
                        <p class="text-xs text-gray-400 mt-2 italic">Untuk editor: {{ $r->comments_to_editor }}</p>
                    @endif
                    @if($isOwner && $r->status === 'submitted' && $r->comments_to_editor)
                        <p class="text-xs text-gray-400 mt-2 italic">Komentar untuk editor disembunyikan dari Anda.</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada review.</p>
            @endforelse
        </div>

        <div class="p-5 space-y-3" x-show="tab === 'pesan'" x-cloaked>
            @forelse($submission->messages as $m)
                <div class="rounded-lg bg-gray-50 border border-gray-100 p-3">
                    <p class="text-xs text-gray-400">{{ $m->sender?->name }} → {{ $m->recipient_type }} • {{ $m->created_at->format('d M Y H:i') }} @if($m->is_internal)<span class="text-amber-600">(internal)</span>@endif</p>
                    @if($m->subject)<p class="text-sm font-medium text-gray-800">{{ $m->subject }}</p>@endif
                    <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $m->body }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada pesan.</p>
            @endforelse
        </div>

        <div class="p-5" x-show="tab === 'apc'" x-cloaked>
            @php($payment = $submission->payment)
            @if($payment)
                <div class="grid sm:grid-cols-3 gap-4 text-sm">
                    <div><p class="text-xs text-gray-400 uppercase font-semibold">Invoice</p><p class="font-mono mt-0.5">{{ $payment->invoice_no }}</p></div>
                    <div><p class="text-xs text-gray-400 uppercase font-semibold">Nominal</p><p class="mt-0.5">{{ $payment->formatAmount() }}</p></div>
                    <div><p class="text-xs text-gray-400 uppercase font-semibold">Status</p><p class="mt-0.5">{{ $payment->status }}</p></div>
                </div>
                <a href="{{ route('account.submissions.payment', $submission) }}" class="mt-4 inline-block text-sm text-primary hover:underline">Kelola pembayaran →</a>
            @else
                <p class="text-sm text-gray-400">Jurnal ini tidak memungut APC (atau naskah belum diterima).</p>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.workspace')
@section('title', $submission->reference_no.' — '.$submission->stageLabel())
@section('content')
@php($meta = $submission->metadata ?? [])
@php($workflow = app(\App\Services\SubmissionWorkflow::class))
<div class="max-w-6xl mx-auto">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <p class="text-xs font-mono text-gray-400">#{{ $submission->reference_no }} • ronde {{ $submission->current_round }} • {{ $submission->section?->name }}</p>
            <h2 class="text-xl font-bold text-gray-900">{{ $submission->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                Penulis: <a href="mailto:{{ $submission->author?->email }}" class="text-primary hover:underline">{{ $submission->authors->pluck('name')->implode(', ') }}</a>
                • dikirim {{ $submission->submitted_at->format('d M Y') }}
                • <span class="{{ $submission->daysInStage() >= 30 ? 'text-amber-600 font-semibold' : '' }}">{{ $submission->daysInStage() }} {{ __('editor.days_in_stage') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.stage-badge', ['stage' => $submission->stage, 'label' => $submission->stageLabel()])
            <a href="{{ route('editor.submissions.audit', $submission) }}" class="text-xs border border-gray-300 rounded-lg px-3 py-1 text-gray-600 hover:text-primary hover:border-primary">{{ __('editor.audit') }}</a>
        </div>
    </div>

    <!-- ACTION BAR -->
    <div class="rounded-xl border border-gray-200 bg-white p-4 mb-5 flex flex-wrap gap-2 items-center">
        @if(in_array($submission->stage, ['submitted', 'revised']))
            <form method="POST" action="{{ route('editor.submissions.stage', $submission) }}" data-ajax>
                @csrf
                <input type="hidden" name="to_stage" value="under_review">
                <button class="bg-primary text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-primary-dark">{{ __('editor.start_review') }}</button>
            </form>
        @endif
        @if($submission->stage === 'under_review' || $submission->stage === 'decision')
            <a href="#reviewer-panel" class="border border-primary text-primary hover:bg-primary-light rounded-lg px-4 py-2 text-sm font-medium">{{ __('editor.assign_reviewer') }} +</a>
        @endif
        @if(in_array($submission->stage, ['submitted', 'under_review', 'decision', 'revised']))
            <a href="#decision-panel" class="border border-gray-300 text-gray-700 hover:border-primary hover:text-primary rounded-lg px-4 py-2 text-sm font-medium">{{ __('editor.make_decision') }}</a>
        @endif
        <a href="#message-panel" class="border border-gray-300 text-gray-700 hover:border-primary hover:text-primary rounded-lg px-4 py-2 text-sm font-medium">{{ __('editor.message_author') }}</a>
        @if($submission->stage === 'accepted')
            <a href="#schedule-panel" class="border border-teal-500 text-teal-600 hover:bg-teal-50 rounded-lg px-4 py-2 text-sm font-medium">Jadwalkan ke Issue →</a>
        @endif
        @if($submission->stage === 'withdrawn' || $submission->stage === 'rejected')
            <span class="text-xs text-gray-400">Naskah terminal — tidak ada aksi lanjutan.</span>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
        <!-- Kolom kiri -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Metadata -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900 mb-3">Metadata</h3>
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div><dt class="text-xs text-gray-400 uppercase font-semibold">Penulis</dt><dd class="text-gray-800">{{ $submission->authors->pluck('name')->implode(', ') }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase font-semibold">Section</dt><dd class="text-gray-800">{{ $submission->section?->name }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase font-semibold">Lisensi</dt><dd class="text-gray-800">{{ $meta['license'] ?? 'CC BY 4.0' }}</dd></div>
                    <div><dt class="text-xs text-gray-400 uppercase font-semibold">COI</dt><dd class="text-gray-800">{{ $submission->conflict_of_interest }}</dd></div>
                </dl>
                @if($meta['id_abstract'] ?? null)
                    <details class="mt-3">
                        <summary class="text-sm text-primary cursor-pointer hover:underline">Lihat judul, abstrak & kata kunci</summary>
                        <div class="mt-2 text-sm text-gray-700">
                            <p><strong>ID:</strong> {{ $meta['id_title'] ?? '' }}</p>
                            @if($meta['en_title'] ?? null)<p><strong>EN:</strong> {{ $meta['en_title'] }}</p>@endif
                            <p class="mt-2 whitespace-pre-line text-gray-600">{{ $meta['id_abstract'] ?? '' }}</p>
                            <p class="mt-2">@foreach($meta['keywords'] ?? [] as $kw)<span class="inline-block bg-primary-light text-gray-700 text-xs rounded-full px-2 py-0.5">{{ $kw }}</span> @endforeach</p>
                        </div>
                    </details>
                @endif
            </div>

            <!-- Files -->
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900 mb-3">File ({{ $submission->files->count() }})</h3>
                <table class="table-cards w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($submission->files as $f)
                            <tr>
                                <td class="py-2 text-gray-800" data-label="File" data-full>{{ $f->original_name }}</td>
                                <td class="py-2 text-gray-500 text-xs" data-label="Tipe">{{ $f->type }}{{ $f->revision ? ' • v'.$f->revision->version : '' }}</td>
                                <td class="py-2 text-gray-500 text-xs" data-label="Ukuran">{{ $f->humanSize() }}</td>
                                <td class="py-2 text-right" data-label="Aksi">
                                    <a href="{{ route('editor.submissions.file.download', [$submission, $f]) }}" class="text-primary text-xs hover:underline">Unduh</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-3 text-gray-400">Belum ada file.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Reviews -->
            <div class="rounded-xl border border-gray-200 bg-white p-5" id="reviewer-panel">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-900">Review (ronde {{ $submission->current_round }})</h3>
                    <a href="{{ route('editor.reviewers.index') }}" class="text-xs text-primary hover:underline">Cari reviewer →</a>
                </div>
                <div class="space-y-3">
                    @forelse($submission->reviews as $r)
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-medium text-gray-800">{{ $r->displayReviewerName() }}</span>
                                <span class="text-xs text-gray-400">{{ $r->status }} @if($r->due_at)• due {{ $r->due_at->format('d M Y') }}@endif</span>
                                @if($r->status === 'submitted' && $r->recommended_decision)
                                    <span class="ml-auto text-xs font-semibold">{{ \App\Models\Decision::TYPE_LABELS[$r->recommended_decision] }}</span>
                                @endif
                                @if(in_array($r->status, ['invited', 'accepted', 'in_progress', 'overdue']))
                                    <button type="button" x-on:click="$dispatch('open-modal', 'cancel-review-{{ $r->id }}')" class="ml-auto text-xs text-red-500 hover:underline">Batalkan</button>

                                    <x-modal name="cancel-review-{{ $r->id }}" maxWidth="sm" focusable>
                                        <div class="p-6">
                                            <h2 class="text-lg font-medium text-gray-900">Batalkan reviewer?</h2>
                                            <p class="mt-2 text-sm text-gray-600">
                                                Batalkan <strong>{{ $r->displayReviewerName() }}</strong> ({{ $r->status }}) pada naskah <span class="font-mono">#{{ $submission->reference_no }}</span>?
                                                Aksi ini tidak dapat dibatalkan.
                                            </p>
                                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <x-secondary-button x-on:click="$dispatch('close-modal', 'cancel-review-{{ $r->id }}')">Batal</x-secondary-button>
                                                <form method="POST" action="{{ route('editor.submissions.reviewers.cancel', [$submission, $r]) }}" data-ajax>
                                                    @csrf @method('DELETE')
                                                    <x-danger-button>Batalkan</x-danger-button>
                                                </form>
                                            </div>
                                        </div>
                                    </x-modal>
                                @endif
                            </div>
                            @if($r->status === 'submitted')
                                <div class="mt-2 text-sm text-gray-700 space-y-1">
                                    @if($r->comments_to_editor)<p class="text-xs italic text-gray-500">Untuk editor: {{ $r->comments_to_editor }}</p>@endif
                                    <p class="whitespace-pre-line text-xs">{{ Str::limit($r->comments_to_author, 500) }}</p>
                                    @if($r->rating_novelty)<p class="text-xs text-gray-400">Rating: kebaruan {{ $r->rating_novelty }}, metodologi {{ $r->rating_methodology }}, kejelasan {{ $r->rating_clarity }}, kontribusi {{ $r->rating_contribution }}</p>@endif
                                </div>
                            @endif
                            @if($r->status === 'declined' && $r->decline_reason)
                                <p class="text-xs text-gray-500 mt-1">Alasan tolak: {{ $r->decline_reason }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Belum ada reviewer.</p>
                    @endforelse
                </div>

                @if(in_array($submission->stage, ['submitted', 'under_review', 'decision', 'revised']))
                    <div class="mt-4 grid sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <form method="POST" action="{{ route('editor.submissions.reviewers.assign', $submission) }}" data-ajax class="space-y-2">
                            @csrf
                            <p class="text-xs font-semibold text-gray-500 uppercase">Assign reviewer internal</p>
                            <select name="reviewer_ids[]" multiple size="4" class="w-full rounded-lg border-gray-300 text-sm border">
                                @foreach(\App\Models\User::whereHas('roles', fn ($r) => $r->where('role', 'reviewer'))->get() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->expertise ?: $u->institution ?: $u->email }}</option>
                                @endforeach
                            </select>
                            <div class="flex items-center gap-2">
                                <input type="number" name="due_days" value="{{ \App\Models\JournalSetting::current()->review_deadline_days }}" min="1" class="rounded-lg border-gray-300 text-sm border w-20">
                                <span class="text-xs text-gray-400">hari</span>
                                <button class="ml-auto bg-primary text-white rounded-lg px-3 py-1.5 text-xs font-medium">Assign</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('editor.submissions.reviewers.invite', $submission) }}" data-ajax class="space-y-2">
                            @csrf
                            <p class="text-xs font-semibold text-gray-500 uppercase">Invite reviewer eksternal (email)</p>
                            <input type="text" name="emails[0]" placeholder="email@instansi.ac.id" class="w-full rounded-lg border-gray-300 text-sm border" required>
                            <input type="email" name="emails[1]" placeholder="+ satu lagi (opsional)" class="w-full rounded-lg border-gray-300 text-sm border">
                            <div class="flex items-center gap-2">
                                <input type="number" name="due_days" value="{{ \App\Models\JournalSetting::current()->review_deadline_days }}" min="1" class="rounded-lg border-gray-300 text-sm border w-20">
                                <span class="text-xs text-gray-400">hari</span>
                                <button class="ml-auto border border-primary text-primary hover:bg-primary-light rounded-lg px-3 py-1.5 text-xs font-medium">Kirim Undangan</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Decisions -->
            <div class="rounded-xl border border-gray-200 bg-white p-5" id="decision-panel">
                <h3 class="font-bold text-gray-900 mb-3">Keputusan Editorial</h3>
                @if(in_array($submission->stage, ['submitted', 'under_review', 'decision', 'revised']))
                    <form method="POST" action="{{ route('editor.submissions.decision', $submission) }}" data-ajax class="space-y-3 rounded-lg bg-gray-50 p-4 mb-4">
                        @csrf
                        <div class="grid sm:grid-cols-5 gap-2">
                            @foreach(['accept' => 'Accept', 'minor_revise' => 'Minor Revise', 'major_revise' => 'Major Revise', 'reject' => 'Reject', 'continue_review' => 'Lanjut Review'] as $val => $label)
                                <label class="flex items-center gap-1.5 rounded-lg border p-2 text-xs cursor-pointer {{ old('type') === $val ? 'border-primary bg-primary-light' : 'border-gray-300 bg-white' }}">
                                    <input type="radio" name="type" value="{{ $val }}" class="rounded border-gray-300 text-primary" required>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <textarea name="comment_to_author" rows="3" required placeholder="Catatan untuk penulis (akan dikirim via email)…" class="w-full rounded-lg border-gray-300 text-sm border">{{ old('comment_to_author') }}</textarea>
                        <input type="text" name="internal_note" placeholder="Catatan internal (opsional)" class="w-full rounded-lg border-gray-300 text-sm border">
                        <div class="flex items-center gap-2">
                            <input type="number" name="revision_due_days" value="30" min="1" class="rounded-lg border-gray-300 text-sm border w-20">
                            <span class="text-xs text-gray-400">batas hari revisi (bila revise)</span>
                            <button class="ml-auto bg-primary text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-primary-dark">Simpan Keputusan & Kirim Email</button>
                        </div>
                    </form>
                @endif
                <div class="space-y-3">
                    @forelse($submission->decisions as $d)
                        <div class="rounded-lg border border-gray-100 p-3">
                            <div class="flex items-center gap-2 text-sm">
                                @include('partials.stage-badge', ['stage' => $d->type === 'reject' ? 'rejected' : ($d->type === 'accept' ? 'accepted' : 'revised'), 'label' => $d->typeLabel()])
                                <span class="text-xs text-gray-400">ronde {{ $d->round }} • {{ $d->decided_at->format('d M Y') }} • {{ $d->editor?->name }}</span>
                            </div>
                            <p class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ $d->comment_to_author }}</p>
                            @if($d->internal_note)<p class="text-xs text-gray-400 mt-1 italic">Internal: {{ $d->internal_note }}</p>@endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Belum ada keputusan.</p>
                    @endforelse
                </div>
            </div>

            <!-- Messages -->
            <div class="rounded-xl border border-gray-200 bg-white p-5" id="message-panel">
                <h3 class="font-bold text-gray-900 mb-3">Komunikasi</h3>
                <form method="POST" action="{{ route('editor.submissions.messages', $submission) }}" data-ajax class="space-y-2 rounded-lg bg-gray-50 p-4 mb-4">
                    @csrf
                    <div class="grid sm:grid-cols-3 gap-2">
                        <select name="recipient_type" class="rounded-lg border-gray-300 text-sm border">
                            <option value="author">Kepada: Penulis</option>
                            <option value="editor">Kepada: Editor (umum)</option>
                            <option value="reviewer">Kepada: Reviewer</option>
                            <option value="internal">Catatan Internal</option>
                        </select>
                        <input type="text" name="subject" placeholder="Subjek (opsional)" class="rounded-lg border-gray-300 text-sm border">
                        <label class="flex items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" name="is_internal" value="1" class="rounded border-gray-300 text-primary"> Internal only
                        </label>
                    </div>
                    <textarea name="body" rows="2" required placeholder="Tulis pesan…" class="w-full rounded-lg border-gray-300 text-sm border"></textarea>
                    <div class="flex justify-end">
                        <button class="bg-primary text-white rounded-lg px-4 py-2 text-sm font-medium">Kirim</button>
                    </div>
                </form>
                <div class="space-y-2">
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
            </div>

            <!-- Schedule -->
            @if($submission->stage === 'accepted')
                <div class="rounded-xl border border-teal-200 bg-teal-50/40 p-5" id="schedule-panel">
                    <h3 class="font-bold text-gray-900 mb-3">Jadwalkan Artikel</h3>
                    <form method="POST" action="{{ route('editor.submissions.schedule', $submission) }}" data-ajax class="grid sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Issue tujuan</label>
                            <select name="issue_id" required class="w-full rounded-lg border-gray-300 text-sm border">
                                <option value="">— pilih —</option>
                                @foreach(\App\Models\Issue::orderByDesc('volume')->orderByDesc('number')->get() as $i)
                                    <option value="{{ $i->id }}">{{ $i->label() }}{{ $i->isPublished() ? ' (terbit)' : ' (draft)' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Urutan (position)</label>
                            <input type="number" name="position" value="1" min="1" class="w-full rounded-lg border-gray-300 text-sm border">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="online_first" value="1" class="rounded border-gray-300 text-primary"> Online First
                        </label>
                        <button class="bg-primary text-white rounded-lg px-4 py-2 text-sm font-medium">Jadwalkan →</button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Kolom kanan -->
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900 mb-3">Timeline Singkat</h3>
                <div class="bg-gray-50 rounded-lg p-3">
                    @include('partials.pipeline-progress', ['submission' => $submission])
                </div>
                <ol class="mt-4 space-y-2 text-xs text-gray-600">
                    @foreach($submission->events->take(8)->reverse() as $ev)
                        <li class="flex gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary mt-1.5 shrink-0"></span>
                            <span>{{ $ev->event }} — {{ $ev->created_at->format('d M Y H:i') }} ({{ $ev->actor?->name ?? 'sistem' }})</span>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('editor.submissions.audit', $submission) }}" class="mt-3 inline-block text-xs text-primary hover:underline">Semua audit trail →</a>
            </div>

            @php($payment = $submission->payment)
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h3 class="font-bold text-gray-900 mb-3">APC</h3>
                @if($payment)
                    <p class="text-sm text-gray-700"><span class="font-mono text-xs">{{ $payment->invoice_no }}</span> — {{ $payment->formatAmount() }}</p>
                    <p class="text-sm mt-1">@include('partials.stage-badge', ['stage' => 'published', 'label' => strtoupper($payment->status)])</p>
                    <a href="{{ route('admin.payments.index') }}" class="mt-2 inline-block text-xs text-primary hover:underline">Verifikasi di Admin →</a>
                @else
                    <p class="text-sm text-gray-400">Tidak ada tagihan APC.</p>
                @endif
            </div>

            @if($submission->stage === 'published' && $submission->publication)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                    <h3 class="font-bold text-emerald-900 mb-2">Terbit</h3>
                    <p class="text-sm text-emerald-800">
                        <a href="{{ route('articles.show', $submission->publication->slug) }}" class="font-medium underline">Lihat artikel publik →</a>
                    </p>
                    @if($submission->publication->doi)<p class="text-xs font-mono text-emerald-700 mt-1">{{ $submission->publication->doi }} ({{ $submission->publication->doi_status }})</p>@endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

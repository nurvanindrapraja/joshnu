@php($j = $ctx['journal_full'] ?? 'JOSHNU')
@php($app = $ctx['app_url'] ?? url('/'))
@php($lines = $ctx['lines'] ?? [])
@php($url = $ctx['url'] ?? $app)
@php($cta = $ctx['cta_label'] ?? 'Lihat di JOSHNU')
<div class="bg-gray-50" style="padding: 24px; font-family: Inter, -apple-system, 'Segoe UI', Roboto, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #1b6e5a; padding: 20px 32px; color: #ffffff;">
            <p style="margin: 0; font-size: 15px; font-weight: 700; letter-spacing: 0.02em;">{{ $ctx['journal'] ?? 'JOSHNU' }}</p>
            <p style="margin: 2px 0 0; font-size: 12px; opacity: 0.85;">Journal of Sustainable Health and New Urbanism</p>
        </div>
        <div style="padding: 32px;">
            <h1 style="margin: 0 0 16px; font-size: 18px; font-weight: 700; color: #111827;">
                {{ match($key) {
                    'submission_submitted' => 'Naskah Baru Diterima',
                    'submission_received' => 'Naskah Anda Telah Diterima',
                    'review_started' => 'Naskah Anda Masuk Tahap Review',
                    'with_editor' => 'Naskah Anda Sedang Dinilai Redaksi',
                    'reviewer_invited' => 'Undangan Peer Review',
                    'reviewer_accepted' => 'Reviewer Menerima Undangan',
                    'reviewer_declined' => 'Reviewer Menolak Undangan',
                    'review_reminder' => 'Pengingat Review',
                    'review_overdue' => 'Review Melewati Tenggat',
                    'review_submitted' => 'Review Masuk',
                    'decision_accept' => 'Naskah Anda DITERIMA 🎉',
                    'decision_revise' => 'Keputusan: Revisi Diperlukan',
                    'decision_reject' => 'Keputusan Editorial',
                    'revision_submitted' => 'Revisi Diterima',
                    'article_accepted' => 'Naskah Diterima — Langkah Selanjutnya',
                    'payment_proof_received' => 'Bukti Pembayaran APC Masuk',
                    'payment_verified' => 'Pembayaran APC Terverifikasi',
                    'payment_rejected' => 'Bukti Pembayaran Perlu Diperbaiki',
                    'article_scheduled' => 'Naskah Anda Dijadwalkan',
                    'article_published' => 'Artikel Anda Terbit! 🎉',
                    'issue_published' => 'Issue Baru Terbit',
                    'correction_issued' => 'Pembetulan (Erratum) Artikel',
                    'retraction_issued' => 'Pencabutan (Retraction) Artikel',
                    'editor_message' => 'Pesan dari Redaksi',
                    default => 'Pemberitahuan',
                } }}
            </h1>

            <p style="margin: 0 0 16px; font-size: 14px; color: #374151; line-height: 1.6;">
                @if($key === 'reviewer_invited')
                    Yth. {{ $ctx['reviewer_name'] ?? 'Bapak/Ibu' }},<br><br>
                    Editor mengundang Anda untuk mereview naskah berikut ({{ $settingReviewMode ?? '' }}):
                @elseif(in_array($key, ['submission_received', 'review_started', 'with_editor', 'decision_accept', 'decision_revise', 'decision_reject', 'article_accepted', 'payment_verified', 'payment_rejected', 'article_scheduled', 'article_published', 'revision_submitted']))
                    Yth. Penulis,<br><br>
                @else
                    Yth. Bapak/Ibu,<br><br>
                @endif
            </p>

            @if($key === 'decision_accept' || $key === 'decision_revise' || $key === 'decision_reject')
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Judul:</strong> {{ $ctx['title'] ?? '' }}</p>
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Referensi:</strong> {{ $ctx['ref'] ?? '' }}</p>
                    @if(!empty($ctx['comment']))
                        <p style="margin: 12px 0 0; font-size: 13px; color: #4b5563; border-left: 3px solid #1b6e5a; padding-left: 12px; line-height: 1.6;">{{ $ctx['comment'] }}</p>
                    @endif
                    @if(!empty($ctx['revision_due_at']))
                        <p style="margin: 12px 0 0; font-size: 13px; color: #92400e;"><strong>Batas revisi:</strong> {{ $ctx['revision_due_at'] }}</p>
                    @endif
                </div>
            @elseif($key === 'reviewer_invited')
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Judul:</strong> {{ $ctx['title'] ?? '' }}</p>
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Bidang:</strong> {{ $ctx['section'] ?? '-' }}</p>
                    <p style="margin: 0; font-size: 14px;"><strong>Batas waktu:</strong> {{ $ctx['due_at'] ?? '-' }}</p>
                </div>
            @elseif($key === 'issue_published')
                <p style="margin: 0 0 12px; font-size: 14px;"><strong>{{ $ctx['issue'] ?? '' }}</strong></p>
                <ol style="margin: 0 0 16px; padding-left: 20px; font-size: 13px; color: #374151; line-height: 1.8;">
                    @foreach(($ctx['articles'] ?? []) as $a)
                        <li>{{ $a['title'] }} — {{ $a['authors'] }}</li>
                    @endforeach
                </ol>
            @elseif($key === 'payment_verified' || $key === 'payment_rejected' || $key === 'payment_proof_received')
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Invoice:</strong> {{ $ctx['invoice'] ?? '' }}</p>
                    @if(!empty($ctx['amount']))<p style="margin: 0 0 8px; font-size: 14px;"><strong>Nominal:</strong> {{ $ctx['amount'] }}</p>@endif
                    @if(!empty($ctx['payer']))<p style="margin: 0 0 8px; font-size: 14px;"><strong>Pengirim:</strong> {{ $ctx['payer'] }}</p>@endif
                    @if(!empty($ctx['reason']))<p style="margin: 8px 0 0; font-size: 13px; color: #b91c1c;"><strong>Alasan:</strong> {{ $ctx['reason'] }}</p>@endif
                </div>
            @elseif($key === 'article_published')
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Judul:</strong> {{ $ctx['title'] ?? '' }}</p>
                    <p style="margin: 0 0 8px; font-size: 14px;"><strong>Issue:</strong> {{ $ctx['issue'] ?? 'Online First' }}</p>
                    @if(!empty($ctx['doi']))<p style="margin: 0; font-size: 14px;"><strong>DOI:</strong> <a href="https://doi.org/{{ $ctx['doi'] }}" style="color: #1b6e5a;">{{ $ctx['doi'] }}</a></p>@endif
                </div>
            @else
                <p style="margin: 0 0 16px; font-size: 14px; color: #374151; line-height: 1.6;">
                    Naskah <strong>{{ $ctx['ref'] ?? '' }}</strong> — {{ $ctx['title'] ?? '' }}
                </p>
            @endif

            @foreach($lines as $k => $v)
                <p style="margin: 4px 0; font-size: 13px; color: #374151;"><strong>{{ $k }}:</strong> {{ $v }}</p>
            @endforeach

            <a href="{{ $url }}" style="display: inline-block; margin-top: 20px; background: #1b6e5a; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 24px; border-radius: 8px;">
                {{ $cta }}
            </a>
        </div>
        <div style="padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; line-height: 1.6;">
            Butuh bantuan? Hubungi {{ \App\Models\JournalSetting::current()->contact_email ?? 'redaksi@joshnu.example.org' }}.<br>
            Anda menerima email ini karena terkait aktivitas di {{ $j }}. © {{ now()->year }} {{ $j }} — Open Access CC BY 4.0
        </div>
    </div>
</div>

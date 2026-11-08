# Lampiran D — Template Email Notifikasi
## JOSHNU (Laravel Mail, queued, multibahasa ID/EN)
Versi: 1.0 | Tanggal: 2026-11-03

**Prinsip:**
- Semua email dikirim via **queue** (Laravel `ShouldQueue`) dari event pipeline.
- Bahasa mengikuti preferensi user (default ID); variable dalam `{{ ... }}`.
- Setiap email ber-tautan ke URL aplikasi (base URL dari `journal_settings`/config).
- Preferensi: user bisa opt-out email non-esensial (alert issue baru, digest); email transaksional (keputusan, deadline review, APC) **tidak** bisa di-opt-out.
- From: `JOSHNU <noreply@joshnu.example.org>`; reply-to: `secretariat@joshnu.example.org`.

### Layout Template (semua email)

```
┌──────────────────────────────────────────────────────────────┐
│  [logo JOSHNU]   Journal of Sustainable Health and New       │
│                      Urbanism  •  ISSN e-2XXX-XXXX           │
├──────────────────────────────────────────────────────────────┤
│  Subjek/Halaman judul (warna theme)                          │
│                                                              │
│  [ISI: sapaan, ringkasan, tombol CTA utama, detail]          │
│                                                              │
│  [CTA: tombol utama]                                         │
├──────────────────────────────────────────────────────────────┤
│  Butuh bantuan? Hubungi secretariat@...                      │
│  Anda menerima email ini karena ... [Kelola preferensi]      │
│  © 2026 JOSHNU — Open Access CC BY 4.0                       │
└──────────────────────────────────────────────────────────────┘
```

---

## 1. Daftar Event & Subjek

| # | Event (Laravel) | Penerima | Subjek (ID) | Subjek (EN) |
|---|---|---|---|---|
| 1 | `SubmissionSubmitted` | Editor section + EiC | `[JOSHNU] Naskah baru: {{reference_no}} — {{title}}` | `[JOSHNU] New submission: {{reference_no}} — {{title}}` |
| 2 | `ReviewerInvited` | Reviewer (diundang) | `Undangan review naskah {{reference_no}}` | `Review invitation for {{reference_no}}` |
| 3 | `ReviewerAccepted` | Editor yang mengundang | `{{reviewer}} menerima undangan {{reference_no}}` | `{{reviewer}} accepted invitation {{reference_no}}` |
| 4 | `ReviewerDeclined` | Editor yang mengundang | `{{reviewer}} menolak undangan {{reference_no}}` | `{{reviewer}} declined invitation {{reference_no}}` |
| 5 | `ReviewReminder` (due − 7 hari) | Reviewer | `Pengingat: review {{reference_no}} jatuh tempo {{due_at}}` | `Reminder: review {{reference_no}} due {{due_at}}` |
| 6 | `ReviewOverdue` | Reviewer + editor | `Review {{reference_no}} melewati tenggat` | `Review {{reference_no}} is overdue` |
| 7 | `ReviewSubmitted` | Editor + EiC | `Review masuk: {{reference_no}} ({{reviewer}})` | `Review received: {{reference_no}} ({{reviewer}})` |
| 8 | `DecisionMade` — accept | Penulis | `Keputusan editorial: Naskah Anda DITERIMA` | `Editorial decision: Your manuscript is ACCEPTED` |
| 9 | `DecisionMade` — minor/major revise | Penulis | `Keputusan editorial: Revisi diperlukan ({{type}})` | `Editorial decision: Revision required ({{type}})` |
| 10 | `DecisionMade` — reject | Penulis | `Keputusan editorial: Naskah {{reference_no}}` | `Editorial decision regarding {{reference_no}}` |
| 11 | `RevisionSubmitted` | Editor + EiC | `Revisi diterima: {{reference_no}} (ronde {{round}})` | `Revision received: {{reference_no}} (round {{round}})` |
| 12 | `ArticleAccepted` (APC aktif) | Penulis | `Naskah diterima — pembayaran APC {{amount}}` | `Manuscript accepted — APC payment {{amount}}` |
| 13 | `PaymentProofReceived` | Admin/EiC | `Bukti pembayaran APC: {{invoice_no}}` | `APC payment proof: {{invoice_no}}` |
| 14 | `PaymentVerified` | Penulis | `Pembayaran APC terverifikasi: {{invoice_no}}` | `APC payment verified: {{invoice_no}}` |
| 15 | `PaymentRejected` | Penulis | `Bukti pembayaran perlu diperbaiki: {{invoice_no}}` | `Payment proof needs correction: {{invoice_no}}` |
| 16 | `ArticleScheduled` | Penulis | `Naskah Anda dijadwalkan: {{issue}}` | `Your article is scheduled: {{issue}}` |
| 17 | `ArticlePublished` | Penulis | `Artikel Anda telah dipublikasikan — DOI: {{doi}}` | `Your article has been published — DOI: {{doi}}` |
| 18 | `IssuePublished` | Subscribers (alert) | `Issue baru JOSHNU terbit: {{issue}}` | `New JOSHNU issue available: {{issue}}` |
| 19 | `CorrectionIssued` / `RetractionIssued` | Penulis + editor | `Pembetulan/Pencabutan artikel {{doi}}` | `Correction/Retraction for article {{doi}}` |
| 20 | `AccountRegistered` | User baru | `Selamat datang di JOSHNU — verifikasi email Anda` | `Welcome to JOSHNU — verify your email` |

---

## 2. Template Lengkap (contoh utama)

### T1. `SubmissionSubmitted` (ke editor)

**Subjek ID:** `[JOSHNU] Naskah baru: JOSHNU-2026-0042 — Taman Kota dan Kesehatan Mental`

> Yth. Tim Redaksi,
>
> Naskah baru telah diterima:
>
> | | |
> |---|---|
> | Nomor | {{reference_no}} |
> | Judul | {{title}} |
> | Penulis | {{authors}} ({{corresponding_email}}) |
> | Section | {{section}} |
> | File | {{file_count}} file ({{file_list}}) |
> | Diterima | {{submitted_at}} |
>
> [Lihat Naskah di Workspace →]({{app_url}}/editor/submissions/{{submission_id}})
>
> — Sistem JOSHNU

*(EN version: "Dear Editors, A new manuscript has been received: ... [Open in Workspace]")*

---

### T2. `ReviewerInvited` (ke reviewer, via token)

**Subjek ID:** `Undangan review naskah JOSHNU-2026-0042`

> Yth. {{reviewer_name}},
>
> Editor *Journal of Sustainable Health and New Urbanism* (JOSHNU) mengundang Anda untuk mereview naskah berikut:
>
> - **Judul:** {{blind_title}} *(double-blind: nama penulis disembunyikan)*
> - **Bidang:** {{section}}
> - **Batas waktu:** {{due_at}} ({{due_days}} hari)
>
> Anda diundang karena keahlian Anda di bidang {{expertise_match}}.
>
> [Terima Undangan]({{app_url}}/review/invite/{{token}})  ·  [Tolak]({{app_url}}/review/invite/{{token}}?action=decline)
>
> Bila tautan tidak berfungsi, ketik: {{app_url}}/review/invite/{{token}}
> Tautan ini pribadi dan berlaku hingga {{invite_expires_at}}.
>
> — Sistem JOSHNU

> **Catatan:** bila reviewer belum memiliki akun, halaman token menampilkan form mini (nama + email) untuk membuat profil ringkas.

---

### T3. `ReviewReminder` (due − 7 hari)

> Yth. {{reviewer_name}},
>
> Pengingat bahwa review untuk naskah **{{reference_no}} — {{blind_title}}** jatuh tempo pada **{{due_at}}**.
>
> [Lanjutkan Review]({{app_url}}/review/{{submission_id}})
>
> Bila Anda tidak dapat menyelesaikan, silakan tolak dengan alasan agar editor dapat mengundang reviewer lain:
> [Tolak Undangan]({{app_url}}/review/{{submission_id}}?action=decline)

---

### T4. `DecisionMade` — Accept (ke penulis)

**Subjek ID:** `Keputusan editorial: Naskah Anda DITERIMA`

> Yth. {{author_name}},
>
> Dengan senang hati kami informasikan bahwa naskah Anda:
>
> **{{title}}** ({{reference_no}})
>
> telah **DITERIMA** untuk dipublikasikan di *Journal of Sustainable Health and New Urbanism*, Vol. {{volume}} No. {{number}} ({{year}}).
>
> Catatan editor:
> > {{comment_to_author}}
>
> **Langkah selanjutnya:**
> 1. {{#if apc_enabled}}Lengkapi pembayaran APC sebesar **{{apc_amount}} {{apc_currency}}** paling lambat **{{apc_due_at}}**: [Lihat Pembayaran]({{app_url}}/account/submissions/{{submission_id}}/payment){{/if}}
> 2. Tim produksi akan menghubungi Anda untuk koreksi layout (proof).
> 3. Naskah dijadwalkan terbit {{scheduled_date_hint}}.
>
> [Lihat Status Naskah]({{app_url}}/account/submissions/{{submission_id}})
>
> Salam,
> Pimpinan Redaksi JOSHNU

*(EN version dengan struktur sama.)*

---

### T5. `DecisionMade` — Revise (ke penulis)

> Yth. {{author_name}},
>
> Berdasarkan hasil peer review, naskah Anda **{{title}}** ({{reference_no}}) diputuskan: **{{decision_label}}** (Minor/Major Revision).
>
> Catatan editor:
> > {{comment_to_author}}
>
> Komentar reviewer terlampir/terbuka di dashboard Anda. Silakan kirim revisi beserta **respons per-komentar** paling lambat **{{revision_due_at}}**.
>
> [Kirim Revisi]({{app_url}}/account/submissions/{{submission_id}}/revisions)

---

### T6. `DecisionMade` — Reject (ke penulis)

> Yth. {{author_name}},
>
> Setelah penelaahan oleh reviewer dan editor, dengan menyesan kami informasikan bahwa naskah Anda **{{title}}** ({{reference_no}}) **tidak dapat diterima** untuk dipublikasikan di JOSHNU.
>
> Catatan editor:
> > {{comment_to_author}}
>
> Kami menghargai kepercayaan Anda kepada JOSHNU dan berharap naskah Anda menemukan tempat yang tepat di jurnal lain. Anda dipersilakan mengajukan kembali di masa mendatang (mis. setelah revisi substansial).
>
> — Tim Redaksi JOSHNU

*(EN version: "After review... we regret to inform you...").*

---

### T7. `ArticlePublished` (ke penulis)

> Yth. {{author_name}},
>
> Artikel Anda telah **dipublikasikan** 🎉
>
> - **Judul:** {{title}}
> - **Issue:** Vol. {{volume}} No. {{number}} ({{year}}){{#if online_first}} (Online First){{/if}}
> - **DOI:** [{{doi}}]({{doi_url}})
> - **Terbit:** {{published_at}}
>
> [Baca Artikel]({{app_url}}/article/{{slug}}) · [Unduh PDF of Record]
>
> Metadata artikel telah dikirim ke Crossref dan akan terindeks Google Scholar/SINTA dalam beberapa hari ke depan.
>
> Selamat! — Tim Redaksi JOSHNU

---

### T8. `IssuePublished` (ke subscribers)

> Assalamu'alaikum / Salam Sejahtera,
>
> Issue terbaru *Journal of Sustainable Health and New Urbanism* telah terbit:
>
> **Vol. {{volume}} No. {{number}} ({{year}}) — "{{issue_title}}"**
>
> Daftar artikel:
> 1. {{article_1_title}} — {{article_1_authors}}
> 2. {{article_2_title}} — {{article_2_authors}}
> 3. ...
>
> [Baca Issue Ini]({{app_url}}/archive/{{issue_slug}})
>
> [Kelola langganan]({{unsubscribe_url}})

---

### T9. `PaymentVerified` (ke penulis)

> Yth. {{author_name}},
>
> Pembayaran APC untuk **{{invoice_no}}** (naskah {{reference_no}}) sebesar **{{amount}} {{currency}}** telah **terverifikasi**.
>
> Terima kasih. Proses produksi naskah Anda akan dilanjutkan.
>
> [Lihat Status Naskah]({{app_url}}/account/submissions/{{submission_id}})

---

## 3. Template Transaksional Pendukung

| Template | Isi inti |
|---|---|
| `VerifyEmail` | Tautan verifikasi (token 48 jam) + tombol |
| `ResetPassword` | Tautan reset (60 menit) + peringatan keamanan |
| `ReviewerAccepted` | "X menerima undangan untuk Y; due date: Z" + tombol buka naskah |
| `ReviewerDeclined` | Alasan penolakan + tombol cari reviewer pengganti |
| `ReviewSubmitted` | Ringkasan: rekomendasi + rating + tombol baca form review |
| `RevisionSubmitted` | Versi ke-N diterima + tombol lihat file revisi |
| `PaymentProofReceived` | Invoice no, nominal, pengirim + tombol verifikasi/tolak |
| `PaymentRejected` | Alasan penolakan + instruksi kirim ulang |
| `ArticleScheduled` | Issue tujuan + perkiraan tanggal terbit |
| `CorrectionIssued` | Tautan artikel + teks pembetulan + tanggal |
| `RetractionIssued` | Tautan artikel + alasan retraction (publik) |
| `DigestWeekly` (opsional) | Ringkasan aktivitas per peran: naskah masuk, review due, tagihan outstanding |

## 4. Aturan Pengiriman
1. Semua email `ShouldQueue`; gagal → retry 3x (backoff) lalu alert ke admin.
2. Dedup: event yang sama pada detik yang sama tidak mengirim dua email (lock per entity+event).
3. Batas: email ke penulis/reviewer maks. 10/hari (sisa masuk digest).
4. Pencatatan: setiap pengiriman tercatat di `audit_logs` (`mail.sent`) + tabel `notifications` (in-app) untuk event yang sama.

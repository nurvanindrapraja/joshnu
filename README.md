# JOSHNU — Single-Journal Publishing System

Aplikasi penerbitan jurnal ilmiah **Journal of Sustainable Health and New Urbanism (JOSHNU)** sesuai `PRD-JOSHNU.md` + lampiran (ERD, spesifikasi endpoint, wireframe, template email, checklist QA).

**Stack:** PHP 8.2+ · Laravel 12 · MySQL 8 (produksi) / SQLite (dev lokal) · Tailwind CSS 4 + Alpine · dompdf · Composer · Node 18+

## Fitur Utama

### Portal Publik
- Landing, About (scope, editorial board, kebijakan ethics/OA/APC), Current Issue, Archive
- Halaman artikel: metadata ID/EN, penulis + ORCID, abstrak, keywords, DOI, galley PDF, JATS XML
- Citation: APA, MLA, Chicago, BibTeX, RIS, EndNote (copy-to-clipboard)
- Label CORRECTED / RETRACTED permanen
- Announcements (CFP/news), subscribe issue-alert email (opt-out), pencarian + pagination
- `sitemap.xml`, **OAI-PMH** (`Identify`, `ListRecords` oai_dc & jats, `ListIdentifiers`, `GetRecord`, param `from/until/set`), health check `/up`
- Counter views/downloads (dedup IP 24 jam)

### Author (Submission)
- Register → verifikasi email → login; profil (institusi, ORCID validasi, keahlian, website)
- Submission: metadata multibahasa, ceklis etika, multi-penulis (korespondensi max 1, co-author tanpa akun), upload file (whitelist + max 50 MB)
- Timeline stage + SLA, riwayat keputusan, pesan dari editor
- Revisi multi-round (hanya saat stage `revised`), withdraw (sebelum accepted)
- **APC**: tagihan otomatis saat accept, upload bukti transfer, status terkirim ke admin

### Reviewer
- Undangan ber-token (128-bit) via email; reviewer tanpa akun bisa terima → auto-buat profil
- Double-blind (nama penulis disembunyikan) / single-blind / open — sesuai setting
- Form review: rekomendasi + 4 rating (1–5) + komentar ke penulis & editor (confidential) + lampiran
- Tolak dengan alasan; reminder H−7 & penandaan overdue (scheduler harian)

### Editor
- Dashboard: count per stage, SLA alert > 30 hari, antrean ber-filter
- Detail naskah: file, review, keputusan, pesan, timeline, APC
- State machine transisi stage (10 stage, validasi ketat) → setiap transisi: `submission_events` + `audit_logs` + email
- Assign reviewer internal & invite eksternal (multi-email)
- Decision: template + custom, `revision_due_at`
- Issue: create/edit/publish/unpublish; scheduling artikel accepted + urutan

### Production
- Edit metadata final multibahasa, upload proof vs VoR
- Generate PDF of record (dompdf; fallback PRD) + JATS XML
- Deposit DOI ke Crossref (queue; simulasi bila API key belum diisi), status `deposited → registered / failed`
- Publish/unpublish, **online first**, correction & retraction

### Admin
- RBAC 6 role (admin, eic, section_editor, production, reviewer, author) — matrix PRD §13
- Settings jurnal (identitas, APC, kebijakan review, tema warna, integrasi DOI)
- Manajemen user & role, aktivasi/nonaktifkan
- Verifikasi/tolak bukti pembayaran APC
- Laporan: usage (top-N, trend bulanan), editorial (distribusi stage, acceptance rate, waktu per stage, rata-rata hari review), finance; export CSV
- **DOAJ export** (ZIP: CSV metadata DOAJ + semua PDF of record)
- Audit log berfilter (user/aksi/entity/tanggal)

### Notifikasi
- 20+ event T1–T20 (Lampiran D): email ID/EN (queued) + notifikasi in-app
- Template HTML responsif (`resources/views/mail/pipeline.blade.php`)
- Scheduler: reminder review harian (08:00), tandai overdue (08:05)

## Menjalankan (Lokal)

```bash
composer install
cp .env.example .env          # untuk dev cepat pakai SQLite:
#   DB_CONNECTION=sqlite
#   (hapus baris DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD)
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
# queue worker (opsional, agar email/job jalan):
php artisan queue:work
# scheduler (opsional):
php artisan schedule:work
```

Buka `http://127.0.0.1:8000`.

### Akun Demo (password: `password`)

| Email | Role |
|---|---|
| `admin@joshnu.example.org` | admin + eic |
| `editor@joshnu.example.org` | section_editor + author |
| `produksi@joshnu.example.org` | production + author |
| `rina@ugm.example.ac.id` | reviewer + author |
| `anton@ub.example.ac.id` | reviewer + author |
| `arief@author.example.org` | author |
| `maya@author.example.org` | author + reviewer |

## Testing

```bash
php artisan test
```
- `SmokeTest` — semua halaman publik + workspace tiap role + guard RBAC
- `WorkflowTest` — pipeline E2E: submit → under review → invite token → review → accept → APC → schedule → publish; jalur withdraw/reject/revisi; validasi state machine
- Test auth Breeze (login, register, reset, verifikasi, ganti password, profil)

## Struktur Penting

| Path | Isi |
|---|---|
| `app/Models/Submission.php` | State machine stage (TRANSITIONS) + aturan transisi |
| `app/Services/SubmissionWorkflow.php` | Satu-satunya service yang mengubah `stage`; event + audit + email |
| `app/Services/MailNotifier.php` | Subjek & routing email ID/EN per event |
| `app/Services/PdfService.php` | PDF artikel/issue (dompdf) + JATS XML |
| `app/Services/CrossrefService.php` | Deposit DOI (simulasi bila `CROSSREF_API_KEY` kosong) |
| `app/Services/SearchService.php` | Pencarian publik + artikel terkait |
| `app/Services/UsageTracker.php` | Counter views/downloads dedup |
| `app/Http/Middleware/EnsureRole.php` | RBAC (`role:admin,eic`) |
| `app/Http/Middleware/SetLocale.php` | Bahasa ID/EN per sesi |
| `database/migrations/` | 20 tabel sesuai ERD Lampiran B |
| `routes/web.php` | Semua endpoint (portal + workspace) |

## Konfigurasi Produksi

- `DB_CONNECTION=mysql` + kredensial (lihat `.env.example`)
- `MAIL_MAILER=smtp` + kredensial SMTP
- `CROSSREF_API_KEY` / `CROSSREF_USER` untuk deposit DOI asli (kosong = mode simulasi)
- Queue driver `database`/`redis` + worker daemon; scheduler daemon
- `php artisan config:cache route:cache view:cache`

## Batasan Implementasi (catatan)

- PDF: memakai **dompdf** (fallback PRD). Untuk pipeline Pandoc→LaTeX produksi, ganti `PdfService` dengan shell-out Pandoc (file sumber sudah tersedia).
- Crossref: mode simulasi tanpa API key; struktur deposit mengikuti spesifikasi Crossref.
- ClamAV/scan virus, load test, backup drill, dan UAT adalah item operasional checklist §9–§12 (lingkungan staging), bukan kode.

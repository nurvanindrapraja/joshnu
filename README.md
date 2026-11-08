# JOSHNU — *Journal of Sustainable Health and New Urbanism*

Aplikasi manajemen & penerbitan jurnal ilmiah tunggal (ala Open Journal Systems) untuk **JOSHNU** — jurnal di bidang kesehatan berkelanjutan (*sustainable health*) dan urbanisme baru (*new urbanism*).

Satu aplikasi, satu jurnal: seluruh alur kerja penerbitan berjalan di dalamnya — penerimaan naskah (*submission*), peer review, keputusan editorial, produksi, hingga publikasi dan distribusi digital.

> Dokumen kebutuhan lengkap: [`PRD-JOSHNU.md`](PRD-JOSHNU.md) + lampiran (wireframe, ERD, spesifikasi endpoint, template email, checklist QA) di folder [`lampiran/`](lampiran/).

---

## Ringkasan Eksekutif

JOSHNU Web dibangun dengan meniru kapabilitas inti OJS, namun dengan:

- Arsitektur **Laravel + MySQL** yang ringan, mudah di-host di satu server, dan mudah dirawat tim kecil.
- Frontend **Blade + Tailwind CSS + Alpine.js** yang responsif (desktop & mobile) dan cepat.
- Fitur lokalisasi untuk ekosistem jurnal di Indonesia (pembayaran APC manual + verifikasi bukti, integrasi Crossref, metadata sesuai standar perpustakaan nasional).

**Visi:** menjadikan JOSHNU jurnal yang kredibel, mudah diakses penulis/pembaca nasional maupun internasional, dan terindeks (Crossref, DOAJ, Google Scholar) dengan proses editorial digital yang transparan dan efisien.

### Latar Belakang

Jurnal akademik di Indonesia umumnya bergantung pada OJS per-jurnal dengan masalah: UX kaku bagi penulis non-teknis, integrasi lokal minim, laporan & metrik terbatas, dan biaya pemeliharaan tinggi. JOSHNU Web menjawabnya dengan aplikasi publikasi modern single-jurnal: alur editorial lengkap + fitur lokal yang praktis. Model data tetap rapi agar *bisa* dikembangkan multi-jurnal di masa depan (roadmap v1.2).

---

## Pengguna & Peran

| Peran | Kebutuhan Utama |
|---|---|
| **Penulis (Author)** | Submit naskah, lacak status, terima revisi, bayar APC, unduh version of record |
| **Reviewer** | Review via undangan ber-token (dengan atau tanpa akun), unggah form review, blind review |
| **Editor-in-Chief (EiC)** | Kelola alur kerja, approve issue, atur reviewer/editor, konfigurasi jurnal |
| **Section/Handling Editor** | Assign reviewer, buat keputusan, komunikasikan ke penulis |
| **Production Editor** | Metadata final, PDF of record, JATS XML, DOI, publish/online first |
| **Admin** | Setting jurnal & tema, manajemen user & role, pembayaran APC, laporan, audit log |
| **Pembaca (Reader)** | Cari & baca artikel, subscribe issue alert, unduh PDF, sitasi |

Satu user dapat memegang lebih dari satu peran (mis. EiC yang juga author).

---

## Alur Kerja Inti: Submission Pipeline

```
[1 SUBMITTED] → [2 UNDER REVIEW] → [3 EDITORIAL DECISION]
      ↓               ↓                    ↓
  (otoritatif)   (1 ronde review,   (Accept / Revise &
                  bisa multi-round)  Reject / MAJOR/MINOR)
                                        ↓
                    ┌───────────────────┼──────────────────┐
                    ↓                   ↓                  ↓
              [4 REVISED]        [5 REJECTED]         [6 ACCEPTED]
              (penulis kirim     (arsipkan +        → [7 SCHEDULED]
               revisi)            notifikasi)          (assign ke issue)
                    ↓                                   ↓
              balik ke [2] atau [3]              [8 PUBLISHED] (online first
                                                   atau per issue)
```

| # | Tahap | Aksi Utama | Aktor |
|---|---|---|---|
| 1 | **Submitted** | Unggah naskah, metadata multibahasa, cover letter, ceklis etika, COI | Author |
| 2 | **Under Review** | Assign/invite reviewer, kumpulkan review, multi-round, blind | Editor, Reviewer |
| 3 | **Decision** | Accept / Major Revise / Minor Revise / Reject + komentar editorial | Editor |
| 4 | **Revised** | Penulis unggah revisi + response to reviewers | Author |
| 5 | **Rejected** | Naskah diarsipkan, penulis dapat submit ulang | System |
| 6 | **Accepted** | Tagihan APC, koreksi, metadata final | Production |
| 7 | **Scheduled** | Assign ke volume/issue/section + urutan artikel | EiC |
| 8 | **Published** | PDF of record, JATS XML, DOI + deposit Crossref, notifikasi | System |

Aturan alur:

- Setiap transisi stage **divalidasi state machine**, tercatat di `submission_events` + **audit log** (siapa, kapan, dari/man ke mana).
- **Notifikasi otomatis (email + in-app)** pada setiap transisi — termasuk acknowledgment ke penulis, masuk review, dengan editor, keputusan, jadwal, dan terbit.
- Multi-round review (default max 3 ronde), blind review sesuai setting jurnal.
- Penulis dapat **menarik (withdraw)** naskah sebelum accepted.

---

## Modul Aplikasi

### Portal Publik (Reader)
- Landing page: masthead (logo, nama, ISSN, tagline), deskripsi, kebijakan (ethics, open access, APC), editorial board.
- Current issue & archive per volume; halaman artikel: metadata ID/EN, penulis + ORCID, abstrak, kata kunci, DOI, galley PDF, JATS XML.
- Sitasi: APA, MLA, Chicago, BibTeX, RIS, EndNote; metrik views/downloads; artikel terkait.
- Pencarian full-text + filter (issue, penulis, tahun, section, bahasa); announcements; subscribe issue alert.
- Interoperabilitas: `sitemap.xml`, **OAI-PMH** (Dublin Core & JATS), export **DOAJ**, health check `/up`.
- Multi-bahasa UI: **ID / EN** (toggle per sesi).

### Submission Portal (Author)
- Registrasi mandiri → **verifikasi email wajib** sebelum dapat login (tautan signed, kirim ulang dari halaman login).
- Profil: institusi, ORCID, bidang keahlian, website.
- Submission multi-file (manuscript, cover letter, figure, table, supplementary) dengan whitelist tipe & batas ukuran; metadata multibahasa; multi-penulis dengan koresponden.
- Timeline status per naskah + riwayat keputusan + pesan dari editor.
- Revisi multi-round; withdraw sebelum accepted.
- **APC**: tagihan otomatis saat diterima, upload bukti transfer, status pembayaran.

### Editorial Workspace
- Dashboard: antrean per tahap, badge jumlah, SLA (hari di tahap).
- Detail naskah: semua file, review, keputusan, pesan, timeline, audit trail.
- Reviewer management: cari reviewer internal (keahlian/institusi) atau **invite eksternal via email + token** (tanpa full registration); multi-reviewer per naskah.
- Decision maker: template keputusan + custom text, batas revisi.
- Komunikasi: pesan internal editor↔editor dan ke penulis/reviewer (semua tercatat).
- Issue management: buat issue (volume, nomor, cover), publish/unpublish; scheduling artikel accepted ke issue + urutan.

### Reviewer Portal
- Akses via login atau **link undangan ber-token** (auto-buat profil untuk reviewer luar).
- Blind title sesuai kebijakan (single/double-blind).
- Form review: rekomendasi (Accept/Minor/Major/Reject), rating (kebaruan, metodologi, kejelasan, kontribusi), komentar ke editor (confidential) + ke penulis, lampiran.
- Deadline otomatis, reminder H−7 & penandaan overdue (scheduler harian); tolak undangan dengan alasan.

### Production & Publishing
- Edit metadata final multibahasa; galley proof vs **version of record**.
- Generate PDF of record + **JATS XML** export (artikel & issue).
- **DOI** + deposit **Crossref** (queue; mode simulasi bila API key belum diisi), status per DOI.
- Publish/unpublish, **online first**, **correction (erratum) & retraction** dengan label permanen.

### Management & Admin
- **RBAC 6 peran** (admin, eic, section_editor, production, reviewer, author) via middleware/policy.
- Settings jurnal: identitas/masthead, APC (ada/tidak, nominal, mata uang), kebijakan review & blind, tema warna, integrasi DOI.
- Manajemen user & role (termasuk tab khusus penulis), aktivasi/non-aktifkan, hapus user tanpa riwayat.
- Verifikasi/tolak bukti pembayaran APC.
- Laporan: usage (top-N, trend bulanan), editorial (distribusi stage, acceptance rate, waktu per tahap), finance; export CSV.
- **Audit log** berfilter (user/aksi/entity/tanggal).

---

## Matriks Hak Akses (RBAC)

| Aksi | Admin | EiC | Editor | Production | Reviewer | Author |
|---|---|---|---|---|---|---|
| Edit setting jurnal / masthead | ✅ | – | – | – | – | – |
| Lihat semua submission | ✅ | ✅ | ✅ (bidangnya) | ✅ | – | – |
| Manage user & role | ✅ | ✅ (non-admin) | – | – | – | – |
| Assign reviewer | ✅ | ✅ | ✅ | – | – | – |
| Buat keputusan | ✅ | ✅ | ✅ | – | – | – |
| Edit metadata produksi | ✅ | ✅ | ✅ | ✅ | – | – |
| Publish issue / online first | ✅ | ✅ | – | – | – | – |
| Retraction / correction | ✅ | ✅ | – | – | – | – |
| Review naskah | – | ✅ | ✅ | – | ✅ (di-assign) | – |
| Lihat status naskah sendiri | – | – | – | – | – | ✅ |
| Verifikasi pembayaran | ✅ | ✅ | – | – | – | – (upload bukti) |

---

## Sistem Notifikasi

- **Kanal:** email (template HTML responsif, ID/EN, queued via SMTP) + notifikasi in-app (bel notifikasi per user).
- **Event utama:** naskah diterima (acknowledgment ke penulis), naskah baru (ke editor), masuk review (ke penulis), undangan reviewer (internal & eksternal), review masuk, keputusan (terima/revisi/tolak), revisi diterima, APC (tagihan/bukti/verifikasi/ditolak), dijadwalkan, terbit (dengan DOI), issue terbit (subscriber), correction/retraction.
- **Implementasi:** Laravel queued events (`SubmissionSubmitted`, `StageChanged`, `DecisionMade`, `ArticlePublished`, dst.) → service `MailNotifier` (satu pintu routing email + in-app) + listener.
- **Scheduler harian:** reminder review jatuh tempo (H−7) dan penandaan review overdue.

---

## Arsitektur Teknis

| Layer | Teknologi |
|---|---|
| Frontend | Blade templates (SSR) + **Tailwind CSS** + **Alpine.js**; AJAX actions (fetch) + toast + progress bar |
| Backend | **PHP 8.3+ — Laravel 12** (MVC + Eloquent), endpoint JSON untuk area interaktif |
| Database | **MySQL 8** (InnoDB, utf8mb4); migrasi & seeder Laravel |
| Workflow engine | State machine (enum stage + service transisi) — semua transisi tervalidasi & tercatat |
| Job queue | Laravel queue (database driver) |
| File storage | Local disk (`storage/app/public` + symlink), interface siap S3-compatible |
| Search | MySQL FULLTEXT (metadata + konten) |
| PDF & XML | PDF of record + JATS XML (fallback HTML-to-PDF sesuai PRD; Pandoc→LaTeX di roadmap) |
| Email | Laravel Mail (SMTP) via queue, template responsive |
| Auth | Laravel Breeze: email + password + **verifikasi email wajib**; RBAC via middleware/policy |
| DOI/Crossref | HTTP client + queue retry, status tracking per DOI |
| Infra | Satu server (Nginx/Apache + PHP-FPM + MySQL); CI: GitHub Actions (test) |

### Model Data (Tinggi Level)

```
User ──< Role (admin, eic, section_editor, production, reviewer, author)
JournalSetting (satu baris: masthead, APC, konfigurasi review, tema)
JournalSetting ──< Section, Announcement, Issue
Issue ──< Publication (Article) ──< Metadata (multibahasa), Galley, DOI
Submission ──< SubmissionFile, SubmissionAuthor, SubmissionRevision
Submission ──< Review, Decision, Assignment, Payment, SubmissionEvent
Submission ── Publication (1:1 saat published)
All ── AuditLog, Notification, Message
```

Konvensi: `id` bigint auto-increment, `created_at`/`updated_at`, soft-delete untuk data publik.

---

## Roadmap

| Fase | Deliverable |
|---|---|
| **v1.0 (sekarang)** | Pipeline submit→publish, portal publik, RBAC, review multi-round + blind, APC manual, OAI-PMH/DOAJ/sitemap, analytics, audit log, i18n ID/EN |
| **v1.1** | Payment gateway (QRIS/VA/e-wallet) + webhook, 2FA, Meilisearch (opsional), laporan COUNTER |
| **v1.2** | Multi-jurnal (multi-tenant), mobile PWA, plugin iThenticate, self-hosted option |

---

## Referensi Dokumen

| Dokumen | Isi |
|---|---|
| [`PRD-JOSHNU.md`](PRD-JOSHNU.md) | Product Requirements Document lengkap (v2.0) |
| [`lampiran/A-Wireframes.md`](lampiran/A-Wireframes.md) | Wireframe utama + design system Figma-ready |
| [`lampiran/B-ERD.md`](lampiran/B-ERD.md) | Data model ERD detail |
| [`lampiran/C-Spesifikasi-Endpoint-API.md`](lampiran/C-Spesifikasi-Endpoint-API.md) | Spesifikasi endpoint/API |
| [`lampiran/D-Template-Email-Notifikasi.md`](lampiran/D-Template-Email-Notifikasi.md) | Template email notifikasi (event T1–T20) |
| [`lampiran/E-Checklist-QA-UAT.md`](lampiran/E-Checklist-QA-UAT.md) | Checklist QA & UAT |

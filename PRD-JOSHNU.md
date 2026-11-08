# Product Requirements Document (PRD)
## Nama Proyek: **JOSHNU** — *Journal of Sustainable Health and New Urbanism*
### Aplikasi Manajemen & Publikasi Jurnal Tunggal (ala Open Journal Systems)

| Item | Detail |
|---|---|
| Versi Dokumen | 2.0 |
| Tanggal | 2026-11-03 |
| Penulis | Product Team |
| Status | Draft untuk Review |
| Referensi | Open Journal Systems (OJS) 3.x |

### Ringkasan Perubahan v2.0
- Cakupan diubah dari platform multi-jurnal menjadi **single-jurnal**: aplikasi ini khusus melayani satu jurnal, yaitu JOSHNU.
- Arsitektur teknis disederhanakan ke stack web klasik yang ringan dan mudah dioperasikan:
  - **Frontend (Browser Desktop/Mobile):** HTML, CSS, JavaScript — styling dengan **Tailwind CSS** (alternatif: Bootstrap 5).
  - **Backend:** **PHP — Laravel terbaru** (12.x).
  - **Database:** **MySQL** (8.x).
- Komponen berbiaya operasi tinggi (Kubernetes, PostgreSQL + Prisma, Meilisearch/OpenSearch, Redis Streams, microservice worker) dihapus atau dipindah ke roadmap.

---

## 1. Ringkasan Eksekutif

**Journal of Sustainable Health and New Urbanism (JOSHNU)** adalah jurnal ilmiah di bidang kesehatan berkelanjutan (*sustainable health*) dan urbanisme baru (*new urbanism*). Proyek ini membangun **satu aplikasi web untuk satu jurnal (JOSHNU)** yang menyediakan seluruh alur kerja penerbitan: penerimaan naskah (submission), peer review, editing, produksi, hingga publikasi dan distribusi digital.

Aplikasi dibangun dengan meniru kapabilitas inti OJS, namun dengan:
- Arsitektur **Laravel + MySQL** yang ringan, mudah di-host di satu server, dan mudah dirawat tim kecil.
- Frontend **HTML/CSS/JavaScript (Tailwind CSS)** yang responsive (desktop & mobile) dan cepat.
- Fitur lokalisasi untuk ekosistem jurnal di Indonesia (pembayaran APC via QRIS/VA/e-wallet, integrasi Crossref/SINTA/Scopus, metadata sesuai standar Perpustakaan Nasional RI).

**Visi:** Menjadikan JOSHNU jurnal yang kredibel, mudah diakses penulis/pembaca nasional maupun internasional, dan terindeks (NISO/JATS, Dublin Core, Crossref, DOAJ) dengan proses editorial digital yang transparan dan efisien.

---

## 2. Latar Belakang & Masalah

Jurnal akademik di Indonesia umumnya bergantung pada OJS yang di-hosting per jurnal. Masalah yang sering ditemui:

1. **UX pengguna yang kaku** — alur submit–review–produksi membingungkan bagi penulis non-teknis.
2. **Integrasi lokal minim** — pembayaran APC (e-wallet/VA/QRIS), verifikasi institusi, SINTA, Google Scholar.
3. **Laporan & metrik terbatas** — redaksi kesulitan mengukur performa jurnal.
4. **Keamanan & pembaruan** — patch keamanan OJS harus diurus per instalasi.
5. **Biaya pemeliharaan** — OJS (PHP tua) sulit dikustomisasi oleh sumber daya lokal.

JOSHNU Web menjawab masalah ini dengan aplikasi publikasi jurnal modern **berfokus pada single-jurnal (JOSHNU)**: satu aplikasi, satu jurnal, alur editorial lengkap, dan fitur lokal yang praktis. Model data tetap dirancang rapi agar di masa depan *bisa* dikembangkan menjadi multi-jurnal, namun bukan tujuan v1.0.

---

## 3. Tujuan & Kriteria Sukses (North Star Metrics)

### Tujuan
- Seluruh alur **submit → publish** berjalan di satu aplikasi tanpa intervensi manual IT.
- Memenuhi standar interoperabilitas: **Crossref, DOAJ, Google Scholar, JATS XML, Dublin Core, OAI-PMH**.
- Ringan & ekonomis: dapat di-host di **satu server VPS** (2 vCPU / 4 GB) tanpa degradasi performa.

### KPI Sukses
| Metrik | Target (12 bulan pasca launch) |
|---|---|
| Total submission per bulan | ≥ 50 naskah |
| Artikel terbit (version of record) per tahun | ≥ 12 artikel |
| Waktu rata-rata submit naskah (penulis) | < 15 menit |
| Uptime aplikasi | ≥ 99,5% |
| Kompatibilitas indeksasi Google Scholar/Crossref | 100% metadata valid |
| NPS pengguna (editor/penulis) | ≥ 40 |

---

## 4. Pengguna & Persona

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Penulis (Author)** | Dosen, peneliti, mahasiswa pascasarjana | Submit naskah, lacak status, terima revisi, bayar APC, download version of record |
| **Reviewer (Peer Reviewer)** | Pakar bidang, undangan per-naskah | Review tanpa perlu registrasi penuh (opsional), unggah form review, kerahasiaan (single/double-blind) |
| **Editor-in-Chief (EiC)** | Pemimpin redaksi | Kelola alur kerja, approve issue, atur reviewer/editor, setel konfigurasi jurnal |
| **Section/Handling Editor** | Editor penanggung jawab naskah | Assign reviewer, buat keputusan, komunikasikan ke penulis |
| **Production Editor** | Tim produksi/lay-out | Typesetting, QC proofing, ekspor PDF/XML, registrasi DOI |
| **Admin (Jurnal Manager)** | Pengelola jurnal & sistem | Setting masthead, template, biaya, modul integrasi, laporan, manajemen user |
| **Pembaca (Reader)** | Publik akademik | Cari & baca artikel, subscribe, unduh PDF, sitasi |

> Catatan: peran "Platform Admin (Super Admin)" **dihapus** karena tidak ada multi-jurnal. Admin tunggal memiliki hak atas seluruh aplikasi JOSHNU.

---

## 5. Ruang Lingkup

### 5.1 In Scope (MVP → v1.0)
- **Single-jurnal**: seluruh fitur melayani satu jurnal (JOSHNU).
- Alur kerja submission lengkap (lihat §7).
- Manajemen issue/volume/number, masthead, galley.
- Role & permission (RBAC) untuk satu jurnal.
- Penukaran pesan, notifikasi email & in-app.
- Peer review (single/double-blind, multi-round).
- Produksi: typesetting, proofing, PDF generation, JATS XML export.
- Metadata: Dublin Core, JATS, DOAJ, OAI-PMH, Sitemap.
- DOI & integrasi Crossref.
- Pencarian full-text + filter.
- Analytics penggunaan (views, downloads, top article).
- Pembayaran APC (MVP: transfer manual + bukti; v1.1: QRIS/e-wallet/VA).
- Multi-bahasa UI (ID, EN).
- Poin ekstensi (plugin ringan) untuk integrasi pihak ketiga (lihat §8.12).

### 5.2 Out of Scope (v1.0)
- **Multi-jurnal / multi-tenant** (hanya dipertimbangkan di roadmap v1.2).
- Sistem manajemen kursus/LMS.
- Penerbitan buku.
- Marketplace antar jurnal.
- Machine translation otomatis (cukup integrasi API pihak ketiga bila dibutuhkan).
- Self-hosting on-premise (v1.0: cloud-hosted oleh operator).

---

## 6. Arsitektur Fitur (Modular)

```
┌─────────────────────────────────────────────────────────────┐
│              JOSHNU — APLIKASI PUBLIKASI JURNAL             │
├──────────────┬──────────────┬──────────────┬────────────────┤
│  Portal      │  Workspace   │  Production  │  Management    │
│  Publik      │  Editorial   │  & Publishing│  & Admin       │
├──────────────┼──────────────┼──────────────┼────────────────┤
│• Landing     │• Dashboard   │• Typesetting │• User & Roles  │
│• Browse      │• Submission  │• PDF Builder │• Jurnal        │
│• Article     │  Queue       │  (Pandoc/    │  Settings      │
│• Search      │• Assignment  │  HTML→PDF)   │• Masthead      │
│• Announce.   │• Review Mgmt │• Proofing    │• Theme         │
│• Archive     │• Decision &  │• DOI Upload  │• Integrasi     │
│• Subscribe   │  Notification│• Archiving   │  (Crossref/   │
│• User Area   │• Scheduling  │              │  ORCID/SINTA)  │
│              │• Payment     │              │• Reports/      │
│              │  (APC)       │              │  Analytics     │
│              │              │              │• Audit Log     │
└──────────────┴──────────────┴──────────────┴────────────────┘
         Common Services: Auth, File Storage, Email/Notification,
         Workflow (state machine), Queue & Jobs, Audit, i18n, Search
```

---

## 7. Alur Kerja Inti: Submission Pipeline

Alur kerja adalah jantung aplikasi. Tahapan mengikuti default flow OJS + best practice:

```
[1 SUBMITTED] → [2 UNDER REVIEW] → [3 EDITORIAL DECISION]
      ↓               ↓                    ↓
  (otoritatif)   (1 ronde review,   (Accept / Revise &
                  bisa multi-round)  Reject / MAJOR/MINOR)
                                        ↓
                    ┌───────────────────┬──────────────────┐
                    ↓                   ↓                  ↓
              [4 REVISED]        [5 REJECTED]         [6 ACCEPTED]
              (penulis kirim     (arsipkan +        → [7 SCHEDULED]
               revisi)            notifikasi)          (assign ke issue)
                    ↓                                   ↓
              balik ke [2] atau [3]              [8 PUBLISHED] (online first
                                                         atau per issue)
```

### 7.1 Tahapan Detail

| # | Tahap | Aksi Utama | Aktor |
|---|---|---|---|
| 1 | **Submitted** | Penulis unggah naskah (PDF/DOCX/LaTeX), metadata, cover letter, rekomendasi reviewer (opsional), konflik kepentingan | Author, Editor |
| 2 | **Under Review** | Editor assign reviewer (manual/invite), kumpulkan review, multi-round; editor bisa minta revisi minor tanpa review penuh | Editor, Reviewer |
| 3 | **Decision** | Editor buat keputusan (Accept/Major Revise/Minor Revise/Reject), tulis editorial decision, kirim ke penulis | Editor |
| 4 | **Revised** | Penulis upload revisi (track change + response to reviewers), kembali ke stage review atau decision | Author |
| 5 | **Rejected** | Naskah diarsipkan, penulis bisa submit ulang di lain waktu | System |
| 6 | **Accepted** | Produksi: koreksi bahasa, layout, hak cipta, APC (jika ada), metadata final | Production |
| 7 | **Scheduled** | Assign ke volume/issue/section, tanggal terbit, online first (optional) | EiC |
| 8 | **Published** | Generate PDF of record, JATS XML, DOI, deposit Crossref, notify penulis, index | System |

### 7.2 Aturan alur
- Setiap transisi stage tercatat di **audit log** (siapa, kapan, alasan).
- Notifikasi otomatis (email + in-app) pada setiap transisi.
- Support **multi-round review** (maks. konfigurable, default 3 ronde).
- **Blind review**: single-blind, double-blind, open review (konfigurasi di setting jurnal).
- Penulis bisa **menarik (withdraw)** naskah sebelum accepted; editor bisa membatalkan (un-assign).
- (Fitur *cross-submission antar jurnal* dihapus — tidak relevan untuk single-jurnal.)

---

## 8. Spesifikasi Fitur per Modul

### 8.1 Portal Publik (Reader-Facing)
- **Landing page jurnal**: masthead (logo, nama, ISSN, tagline), deskripsi, kebijakan (ethics, open access, APC), editorial board, contact.
- **Current issue & Archive**: daftar issue per volume, daftar article per issue dengan abstract, metadata, galeri PDF.
- **Halaman artikel (Article Page)**:
  - Title, penulis (ORCID, afiliasi), abstrak (multi-bahasa), kata kunci
  - PDF, galley lain (supplementary, poster), JATS XML, DOI badge
  - Citation format: APA, MLA, Chicago, BibTeX, RIS, EndNote
  - Metrik: views, downloads
  - Related articles (by keyword/author)
- **Pencarian**: full-text + metadata, filter (issue, penulis, tahun, kategori, bahasa), sort by relevancy/tanggal. Dukungan advanced search.
- **Announcements**: pengumuman jurnal (call for paper, maintenance).
- **Subscription / Alert**: email alert untuk issue baru (opt-in).
- **Share & embed**: share to social media, copy citation, QR code.

### 8.2 Submission Portal (Author)
- **Registration & profil**: nama, institusi, ORCID, bidang keahlian, alamat, riwayat publikasi.
- **Multi-file submission**:
  - Main manuscript (PDF, DOCX, ODT, .tex + zip)
  - Cover letter, figures (terpisah), tables, supplementary
  - Ceklis: originalitas, belum dipublikasi, COI, persetujuan co-author
- **Metadata form**:
  - Judul (multi-bahasa), abstrak, keywords, kategori/section, hak cipta (CC BY 4.0 default), funding, acknowledgments
  - Penulis multiple dengan urutan & korespondensi
- **Revision workflow**: upload versi revisi, "response to reviewers" (PDF/DOCX), compare versi (opsional).
- **Status tracking**: timeline visual per naskah + riwayat keputusan.
- **Pembayaran APC** (jika dipungut): tautan pembayaran, upload bukti, status bayar.
- **Withdraw & re-submit**.

### 8.3 Editorial Workspace (Dashboard Editor)
- **Dashboard**: queue per tahap, badge jumlah, SLA (hari di tahap), quick actions.
- **Submission detail page**:
  - Metadata, semua file, riwayat komunikasi, review, decision log
  - Timeline & audit trail
- **Reviewer management**:
  - Cari reviewer dari database internal (berdasarkan keahlian/institusi)
  - Undangan ke reviewer luar (email invite dengan token, tanpa full registration)
  - Multi-reviewer per naskah, anonymous option
- **Reviewer assignment UI**: pilih multi-reviewer, batch assign.
- **Decision maker**: template keputusan (accept/reject/revise) + custom text; auto-generate email.
- **Communication**: pesan internal (editor↔editor), pesan ke penulis/reviewer (log semua).
- **Scheduling**: assign accepted article ke issue/section, urutan artikel.
- **Issue management**: buat issue (volume, number, tanggal, cover), publish/unpublish issue.
- **Search & filter submissions**: by status, author, section, date, keywords.

### 8.4 Reviewer Portal
- Login (atau link ber-token) → daftar naskah yang di-review (blind title sesuai kebijakan).
- **Review form** (konfigurasi per jurnal):
  - Recommendation (Accept / Minor Revise / Major Revise / Reject)
  - Rating: kebaruan, metodologi, kejelasan, kontribusi
  - Komentar untuk editor (confidential) + komentar untuk penulis
  - Upload file review (PDF/DOCX)
- **Deadline & reminder** otomatis (email 7 hari sebelum jatuh tempo, lalu overdue).
- **Decline/transfer**: reviewer bisa tolak dengan alasan (beban kerja, konflik minat).

### 8.5 Production & Publishing
- **Typesetting**:
  - Import metadata → template PDF via job queue: **Pandoc → LaTeX → PDF**, atau fallback **HTML-to-PDF** (wkhtmltopdf/Dompdf).
  - Preview side-by-side, QC checklist (spellcheck, figure resolusi, link DOI).
- **Version of Record**: PDF of record (watermark versi proof vs final), galley lain (supplementary).
- **JATS XML export** (artikel + issue) untuk deposit publisher/indeks.
- **DOI**:
  - Registrasi via **Crossref** (article, issue, galley).
  - Validasi metadata sebelum deposit.
- **Crossref deposit**: push metadata + link PDF saat publish; retry queue (Laravel queue); log status per DOI.
- **Archiving**:
  - Publik: full open access.
  - Optional: deposit Green Open Access ke repository institusi (OAI link).
  - (Roadmap) LOCKSS/Knowledgebank.
- **Unpublish/Correction/Retraction**:
  - Correction (erratum) & retraction dengan label permanen + alasan publik.
  - Withdraw (hapus dari publik, catatan internal).
- **Online First**: publikasikan sebelum issue masuk (opsi per konfigurasi).

### 8.6 User & Access Management
- **Peran (satu jurnal)**: Admin, Editor-in-Chief, Section Editor, Production Editor, Reviewer, Author.
- Satu user bisa punya lebih dari satu peran (mis. EiC yang juga author).
- **Autentikasi**: email/password (Laravel Breeze/Fortify) + opsional **Google SSO** (Laravel Socialite); 2FA (Fortify) di v1.1.
- **ORCID integrasi**: verifikasi & sync profil penulis (OAuth2).
- **Audit log**: semua aksi sensitif (ubah metadata, publish, akses file) tercatat.
- **Access control**: RBAC fine-grained (lihat §13), diimplementasikan via middleware/policy Laravel.

### 8.7 Payment & Billing (APC)
- Konfigurasi: ada/tidak APC, nominal, mata uang, diskon (student/early-bird) — disimpan di setting jurnal.
- **MVP (v1.0)**: transfer bank manual + upload bukti → verifikasi oleh admin.
- **v1.1**: QRIS, e-wallet (GoPay/OVO/DANA), virtual account via payment gateway (Midtrans/Xendit) + webhook.
- Invoice otomatis, status payment di timeline naskah, laporan keuangan APC.
- Penerimaan hak cipta/CC license agreement (e-sign sederhana: checkbox + log) sebelum publish.

### 8.8 Analytics & Reports
- **Usage**: views & downloads per artikel/issue (count dedup by session/IP + cookie), trend bulanan, top-N artikel, country breakdown.
- **Editorial**: waktu rata-rata per tahap, acceptance rate, reviewer turnaround, beban kerja editor/reviewer.
- **Finance**: APC collected, outstanding.
- **Export**: CSV/Excel; dashboard widget.
- (Roadmap) laporan **COUNTER R5/R7** compliant.
- Implementasi: query agregasi MySQL + cache (Laravel cache) untuk metrik publik.

### 8.9 Search & Discovery
- Mesin: **MySQL FULLTEXT** (metadata + teks hasil extract PDF) — cukup untuk skala single-jurnal; optional Laravel Scout + Meilisearch di roadmap bila volume besar.
- Output: sitemap.xml, OAI-PMH (Dublin Core), DOAJ submission-ready, OpenAlex/Crossref API friendly.
- (Roadmap) Google Scholar optimization (structured data, stable URL, PDF direct).

### 8.10 Theming & White-Label
- **Satu tema untuk satu jurnal**: logo, warna, font, layout — dikelola lewat **theme token (CSS variables)** + konfigurasi Tailwind, disimpan di tabel setting.
- Admin bisa preview & sesuaikan elemen halaman publik (header/footer, blok sederhana) tanpa developer.
- Multi-domain/subdomain per jurnal: **dihapus** (hanya relevan multi-jurnal).

### 8.11 Notification System
- Kanal: email (template configurable, multi-bahasa) + in-app.
- Event: submit received, assigned, review due, decision, revision received, accepted, scheduled, published, APC due/paid, issue published.
- Preferensi user: opt-in email, digest (harian/mingguan).
- Implementasi: **Laravel queued events** + Mail; in-app via tabel notification + polling sederhana (WebSocket ditunda).

### 8.12 Poin Ekstensi (Lightweight Plugin)
Menggantikan framework plugin berat dengan pola **Laravel service provider + interface + event**:
- **Plagiarism** (Quetext/Turnitin API)
- **Machine translation** (DeepL/Google)
- **ORCID** (bawaan), **Crossref** (bawaan), **SINTA** (bawaan di Indonesia)
- **Payment** (Midtrans, Xendit)
- **Storage** (default local disk; interface siap untuk S3-compatible)
Setiap ekstensi berupa **Laravel package (composer)** yang mendaftarkan service provider, subscribe event, dan/atau menambah job — berjalan dalam proses aplikasi yang sama (tanpa worker terisolasi terpisah), diuji via test suite standar.

### 8.13 Multi-Jurnal (Multi-Tenancy)
**Dihapus dari cakupan v1.0.** Data model tetap menyimpan seluruh konfigurasi jurnal (masthead, section, alur, biaya, tema) dalam tabel `journal_settings` (satu baris aktif) sehingga migrasi ke multi-jurnal di masa depan (roadmap v1.2) tidak memerlukan redesign besar.

---

## 9. Non-Functional Requirements

| Kategori | Requirement |
|---|---|
| **Performa** | Halaman publik < 800ms (p95) di server tunggal; upload naskah 50MB < 30 detik; concurrency ± 200 user aktif |
| **Skalabilitas** | Vertical scale dulu (VPS lebih besar + CDN); partisi/arsip data lama bila volume besar |
| **Ketersediaan** | 99,5% uptime; backup harian (DB dump + file naskah) ke object storage eksternal, RPO < 24 jam, RTO < 4 jam |
| **Keamanan** | OWASP Top 10 mitigasi (CSRF, XSS, SQL injection via Eloquent); validasi & scan file upload; rate limiting (throttle Laravel); audit log; TLS everywhere; hash password (bcrypt/argon2id) |
| **Kompatibilitas** | Browser: Chrome/Edge/Firefox/Safari 2 versi terakhir; mobile responsive (mobile-first) |
| **Aksesibilitas** | WCAG 2.1 AA |
| **i18n/l10n** | UI multi-bahasa (ID default, EN); format tanggal/mata uang sesuai locale |
| **Audit & Compliance** | UU PDP Indonesia: export & hapus data user; hak cipta naskah milik penulis (CC BY 4.0) |
| **Observability** | Structured log (Laravel log channel), endpoint health check, monitoring uptime eksternal, Sentry (opsional) |

---

## 10. Model Data (Tinggi Level)

```
User ──< Role (admin, eic, section_editor, production, reviewer, author)
User ── Profile (institusi, ORCID, keahlian, alamat)
JournalSetting (satu baris: masthead, biaya APC, konfigurasi review, theme token)
JournalSetting ──< Section
JournalSetting ──< Announcement
JournalSetting ──< Issue (volume, number, tanggal, cover)
Issue ──< Publication (Article)
Submission ──< SubmissionFile (manuscript, revision, proof, galley)
Submission ──< Review (round, form answers, files)
Submission ──< Decision (type, comment, date, editor)
Submission ──< Assignment (editor/reviewer, status, deadline)
Submission ──< Payment (APC: invoice, status, bukti)
Submission ──< PublicationEvent (audit trail)
Submission ── Publication (1:1, saat published)
Publication ── Metadata (multilingual: title, abstract, keywords)
Publication ── Galley (PDF/XML/supplementary)
Publication ── DOI (Crossref)
All ── AuditLog, Notification, Message
```

Konvensi: `id` (bigint auto-increment), `created_at`, `updated_at`, `created_by`, soft-delete (`deleted_at`) untuk data publik.

---

## 11. Arsitektur Teknis

### 11.1 Stack

| Layer | Teknologi |
|---|---|
| **Frontend (Browser Desktop/Mobile)** | HTML + CSS + JavaScript; **Blade templates** (server-side rendering) + **Tailwind CSS** (alternatif: Bootstrap 5); JS vanilla untuk interaktivitas ringan (opsional Alpine.js) |
| **Backend** | **PHP 8.3+ — Laravel terbaru (12.x)**; MVC + Eloquent; endpoint JSON untuk kebutuhan API internal & integrasi |
| **Database** | **MySQL 8.x** (InnoDB, utf8mb4); migrasi & seeder Laravel |
| **Workflow Engine** | State machine sederhana (enum stage + service transition) — semua transisi tervalidasi & tercatat |
| **Job Queue** | Laravel queue, **database driver** (Redis opsional untuk cache/queue bila diperlukan) |
| **File Storage** | Local disk (`storage/app/public` + symlink); interface `Storage` Laravel siap untuk S3-compatible di kemudian hari |
| **Search** | MySQL FULLTEXT (metadata + teks extract PDF); optional Scout + Meilisearch di roadmap |
| **PDF Generation** | Job queue: **Pandoc → LaTeX → PDF** (fallback: HTML-to-PDF via wkhtmltopdf/Dompdf) |
| **Email** | Laravel Mail (SMTP) via queue; template responsive (Blade) |
| **Auth** | Laravel Breeze/Fortify: email + password; 2FA (v1.1); Google SSO opsional (Socialite); RBAC via policy/middleware |
| **Payments** | Midtrans/Xendit (v1.1) + webhook endpoint |
| **DOI/Crossref** | Laravel HTTP client + queue retry; status tracking per DOI |
| **Infra** | Satu VPS (2 vCPU / 4 GB+): **Nginx + PHP-FPM + MySQL 8**; Docker Compose opsional; CDN Cloudflare |
| **CI/CD** | GitHub Actions: test (Pest/PHPUnit) + deploy otomatis ke VPS |
| **Observability** | Laravel logging terstruktur, endpoint `/up` (health check), Sentry (opsional) |

### 11.2 Deployment Model
1. **Single-server cloud** (dioperasikan operator) — default v1.0.
2. **Self-hosted** (Docker Compose image) — roadmap.

---

## 12. API & Integrasi

### 12.1 Endpoint Internal
- Route MVC Laravel untuk seluruh fitur; respons JSON untuk area interaktif (dashboard, upload, form) bila diperlukan.
- **API publik eksternal: bukan cakupan v1.0** (dipertimbangkan di roadmap).
- Rate limiting per role; API key untuk integrasi internal (bila diperlukan).

### 12.2 Interoperabilitas Standar
| Standar | Implementasi |
|---|---|
| **OAI-PMH** | Endpoint `/oai?verb=...` (Dublin Core, JATS) |
| **Sitemap** | `/sitemap.xml` (jurnal + artikel) |
| **DOAJ** | Export submission file (metadata + PDF) |
| **Crossref** | Deposit DOI + metadata saat publish; deposit issue |
| **Google Scholar** | Metadata structured, URL stabil, PDF accessible |
| **ORCID** | OAuth2, verifikasi & link profil penulis |
| **COUNTER** | (roadmap) report endpoint |

### 12.3 Event & Ekstensi
- **Laravel events (queued)** untuk: `SubmissionSubmitted`, `ReviewCompleted`, `DecisionMade`, `ArticlePublished`, `PaymentPaid`, dst.
- Ekstensi (plugin) subscribe event → eksekusi aksi (mis. deposit Crossref saat `ArticlePublished`).

---

## 13. Hak Akses Matriks (RBAC Ringkas)

| Aksi | Admin | EiC | Editor | Production | Reviewer | Author |
|---|---|---|---|---|---|---|
| Edit setting jurnal / masthead | ✅ | – | – | – | – | – |
| Lihat semua submission | ✅ | ✅ | ✅ (bidangnya) | ✅ | – | – |
| Manage user & role | ✅ | ✅ (non-admin) | – | – | – | – |
| Assign reviewer | ✅ | ✅ | ✅ | – | – | – |
| Buat keputusan | ✅ | ✅ | ✅ | – | – | – |
| Edit metadata produksi | ✅ | ✅ | ✅ | ✅ | – | – |
| Publish issue / online first | ✅ | ✅ | – | – | – | – |
| Retraction/correction | ✅ | ✅ | – | – | – | – |
| Review naskah | – | ✅ | ✅ | – | ✅ (diassign) | – |
| Lihat status naskah sendiri | – | – | – | – | – | ✅ |
| Verifikasi pembayaran | ✅ | ✅ | – | – | – | – (upload bukti) |

---

## 14. Roadmap & Milestone

| Fase | Durasi | Deliverable |
|---|---|---|
| **F0 — Discovery & Design** | 2 minggu | Riset pengguna, wireframe Figma, data model final, setup proyek Laravel |
| **F1 — Core MVP (v0.5)** | 6 minggu | Auth + RBAC, submission pipeline (submit→decision), email notifikasi, portal publik dasar, upload file |
| **F2 — Editorial & Review (v0.8)** | 5 minggu | Reviewer portal, blind review, multi-round, decision workflow, communication log, dashboard editor |
| **F3 — Production & Publish (v1.0)** | 6 minggu | Typesetting PDF, JATS XML, DOI + Crossref, issue scheduling, OAI-PMH, sitemap, public search, analytics dasar, APC manual |
| **F4 — Polish & Beta (v1.0-beta)** | 3 minggu | UAT pilot (redaksi JOSHNU), bug fix, security audit, load test, training materials |
| **F5 — GA (v1.0)** | 1 minggu | Launch publik, monitoring, onboarding |
| **v1.1** | +4 minggu | Payment gateway (QRIS/VA/e-wallet), 2FA, Meilisearch (opsional), laporan COUNTER |
| **v1.2** | +8 minggu | **Multi-jurnal (multi-tenant)** sebagai opsi pengembangan, mobile PWA, plugin iThenticate, dashboard COUNTER, self-hosted option |

**Total waktu ke GA: ± 4–5 bulan** (tim: 1 PM, 1 FE, 2 BE Laravel, 1 DevOps/QA, 1 Designer).

---

## 15. Asuransi Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| PDF generation gagal (LaTeX timeout/error) | Sedang | Job queue + retry, fallback template HTML-to-PDF sederhana, notifikasi admin |
| Beban puncak (deadline submission) | Sedang | CDN, cache, rate limit; vertical scale VPS bila perlu |
| Integrasi Crossref gagal (metadata invalid) | Sedang | Validasi metadata sebelum publish, queue retry, log status per DOI |
| Kebocoran naskah sebelum review selesai | Tinggi | File access RBAC ketat, download hanya via route terautentikasi, audit log akses |
| Adopsi rendah oleh redaksi | Tinggi | Onboarding asistif, dokumentasi ID, support langsung |
| Regulasi data (UU PDP) | Sedang | Data residency region lokal, export/hapus data user |
| Single point of failure (satu server) | Sedang | Backup harian off-server, RTO < 4 jam, monitoring uptime + alert |

---

## 16. Asumsi & Ketergantungan

- Aplikasi di-host di **satu server cloud** (VPS) yang dioperasikan operator.
- Jurnal pilot: **JOSHNU sendiri** untuk UAT.
- Akses API Crossref (account registrasi) & ORCID (client registration).
- Payment gateway: kontrak dengan Midtrans/Xendit (untuk v1.1).
- Bahasa default UI: Indonesia & Inggris.

---

## 17. Definisi Selesai (Definition of Done) per Fitur
- Kode ter-review, test coverage ≥ 80% (unit + feature test untuk modul inti).
- Dokumentasi (dev + user) terbit.
- Lint (PHPStan/Pint), a11y (axe), dan security scan (SAST) lulus di CI.
- Staging QA sign-off, UAT untuk modul editorial.

---

## 18. Open Questions (untuk keputusan stakeholder)
1. **Migrasi OJS**: apakah importer OJS (SQL/XML) wajib sejak MVP — dampak besar ke schedule?
2. **Batas ukuran file** naskah: 50 MB atau 100 MB?
3. **Open Access enforcement**: apakah JOSHNU mewajibkan CC BY & Open Access, atau allow hybrid?
4. **Payment gateway** v1.1: Midtrans atau Xendit?
5. **Frontend styling**: Tailwind CSS (rekomendasi) atau Bootstrap 5?

---

*Lampiran (terdapat di folder `lampiran/`):*
- A. Wireframe utama (landing, dashboard editor, submission form, article page)
  - Dokumen ASCII → [`lampiran/A-Wireframes.md`](lampiran/A-Wireframes.md)
  - **Versi vektor (Figma-ready)** → `lampiran/wireframes/svg/*.svg` + pratinjau `lampiran/wireframes/png/*.png` (di-generate oleh `tools/gen_diagrams.py` & `tools/render_png.py`)
  - **Spesifikasi design system & frame (Figma-ready spec)** → [`lampiran/A2-Figma-Design-Spec.md`](lampiran/A2-Figma-Design-Spec.md)
- B. Data model ERD detail
  - Dokumen → [`lampiran/B-ERD.md`](lampiran/B-ERD.md)
  - **Diagram gambar** → `lampiran/erd/ERD.svg` + `lampiran/erd/png/ERD.svg.png`
- C. Spesifikasi endpoint/API → [`lampiran/C-Spesifikasi-Endpoint-API.md`](lampiran/C-Spesifikasi-Endpoint-API.md)
- D. Template email notifikasi → [`lampiran/D-Template-Email-Notifikasi.md`](lampiran/D-Template-Email-Notifikasi.md)
- E. Checklist QA & UAT → [`lampiran/E-Checklist-QA-UAT.md`](lampiran/E-Checklist-QA-UAT.md)

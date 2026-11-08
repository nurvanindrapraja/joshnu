# Lampiran A — Wireframe Utama
## JOSHNU — Journal of Sustainable Health and New Urbanism
Versi: 1.0 | Tanggal: 2026-11-03 | Status: Draft untuk Review

**Konvensi:**
- Wireframe ASCII, prioritas desktop. Catatan *mobile* di bawah tiap wireframe.
- Navigasi utama publik: `Home | About | Current Issue | Archive | Announcements | Search`.
- Area pengguna (author/reviewer/editor/admin) memakai layout sidebar (mobile: hamburger menu).
- Bahasa UI: ID (default) / EN — toggle di header.

---

## 1. Landing Page (Portal Publik)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [logo] Journal of Sustainable Health and New Urbanism      [ID ▾] [🔍]  │
│        ISSN e-2XXX-XXXX | e-Journal bidang Sustainable Health & Urbanism │
├──────────────────────────────────────────────────────────────────────────┤
│ Home  About  Current Issue  Archive  Announcements        [Login] [Submit│
├──────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────────────────────────────────────────────────────┐ │
│ │ HERO BANNER: tagline + lingkup jurnal                                │ │
│ │ CTA: [Submit Naskah]  [Baca Issue Terbaru]                           │ │
│ └──────────────────────────────────────────────────────────────────────┘ │
├───────────────────────────────────────┬──────────────────────────────────┤
│ ANNOUNCEMENTS (terbaru 3)             │ ABOUT THE JOURNAL (ringkas)      │
│ • CFP Vol 4 No 2 (2026)      [Baca]   │ Scope, akreditasi, terindeks     │
│ • Maintenance 15 Nov 2026    [Baca]   │ (SINTA/Crossref/DOAJ), APC      │
│ • Issue Vol 3 No 2 terbit    [Baca]   │ [Selengkapnya →]                 │
├───────────────────────────────────────┴──────────────────────────────────┤
│ ARTIKEL TERBARU (4 kartu dari current issue)                             │
│ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐ ┌───────────────┐ │
│ │ [thumbnail]   │ │ [thumbnail]   │ │ [thumbnail]   │ │ [thumbnail]   │ │
│ │ Judul artikel │ │ Judul artikel │ │ Judul artikel │ │ Judul artikel │ │
│ │ Penulis, tgl  │ │               │ │               │ │               │ │
│ │ [PDF] [DOI]   │ │               │ │               │ │               │ │
│ └───────────────┘ └───────────────┘ └───────────────┘ └───────────────┘ │
│ [Lihat semua artikel →]                                                  │
├──────────────────────────────────────────────────────────────────────────┤
│ FOOTER:                                                                   │
│ Editorial Board | Policies (Ethics • Open Access • APC) | Contact        │
│ [Berlangganan alert issue baru: email____ [Subscribe]]                   │
│ ISSN ... | © 2026 JOSHNU | [Sitemap]                                     │
└──────────────────────────────────────────────────────────────────────────┘
```

**Catatan interaksi:**
- Hero banner memakai theme token (warna/logo dari `journal_settings.theme`).
- Kartu artikel: klik → halaman artikel; hover → tampilkan abstract ringkas.
- *Mobile:* hero menyusut, kartu artikel jadi 1 kolom, nav jadi hamburger.

---

## 2. Current Issue & Archive

```
┌──────────────────────────────────────────────────────────────────────────┐
│ Breadcrumb: Home > Archive                                               │
├──────────────────────────────────────────────────────────────────────────┤
│ FILTER: [Vol 4 ▾] [No 1 ▾] [Tahun 2026 ▾]        Sort: [Terbaru ▾]      │
├──────────────────────────────────────────────────────────────────────────┤
│ ▼ VOL 4, NO 1 (2026) — "Kesehatan & Perkotaan"      [Issue PDF] [Cite]  │
│   [cover kecil] Terbit: 01 Jan 2026 — 5 artikel                          │
│   ┌──────────────────────────────────────────────────────────────────┐  │
│   │ 1. Judul artikel — A. Budi, dkk. (2026)                          │  │
│   │    Abstract ringkas (3 baris)  [Baca] [PDF] [DOI]  👁 214 ⬇ 57  │  │
│   ├──────────────────────────────────────────────────────────────────┤  │
│   │ 2. ...                                                            │  │
│   └──────────────────────────────────────────────────────────────────┘  │
│ ▼ VOL 3, NO 2 (2025) — (collapsed)                                      │
│ ▼ VOL 3, NO 1 (2025) — (collapsed)                                      │
└──────────────────────────────────────────────────────────────────────────┘
```

**Catatan:**
- Halaman `/current-issue` = halaman ini dengan issue terbaru terbuka otomatis.
- *Mobile:* filter jadi accordion; list artikel 1 kolom.

---

## 3. Halaman Artikel (Article Page)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ Breadcrumb: Archive > Vol 4 No 1 (2026)                                 │
├──────────────────────────────────────────────────────────────────────────┤
│ [Label: PUBLISHED / ONLINE FIRST / CORRECTED / RETRACTED (jika ada)]    │
│ Judul Artikel (H1)                                                       │
│ Penulis A¹*  Penulis B²  Penulis C¹                                      │
│ ¹Institusi A  ²Institusi B   * korespondensi: email@inst.ac.id           │
│                                                                          │
│ Received: 01 Sep 2025 • Reviewed: 20 Okt 2025                           │
│ Accepted: 15 Des 2025 • Published: 01 Jan 2026                          │
│ DOI: 10.xxxx/joshnu.v4i1.001  [badge DOI]                                │
├──────────────────────────────────────────────────────────────────────────┤
│ ABSTRACT  [Bahasa: ID | EN]                                              │
│ Paragraf abstrak ...                                                     │
│ Keywords: kata1; kata2; kata3                                            │
├──────────────────────────────────────────┬───────────────────────────────┤
│ GALLEY & DOKUMEN                        │ CITATION & SHARE              │
│ ⬇ Download PDF (Version of Record)      │ Format: [APA ▾]               │
│ ⬇ PDF Proof (jika tersedia)             │ ┌─────────────────────────┐   │
│ ⬇ Supplementary Data (2 file)           │ │ Budi, A., dkk. (2026)...│   │
│ ⬇ JATS XML                              │ └─────────────────────────┘   │
│                                         │ [Copy] [BibTeX] [RIS]          │
│ Views: 214  |  Downloads: 57           │ [EndNote] [QR: ▣]              │
│                                         │ Share: [X][FB][WA][Email][⧉]  │
├──────────────────────────────────────────┴───────────────────────────────┤
│ Lisensi: CC BY 4.0  •  How to Cite  •  Plagiarism check: (roadmap)      │
├──────────────────────────────────────────────────────────────────────────┤
│ RELATED ARTICLES (by keyword/author) — 3 kartu                          │
└──────────────────────────────────────────────────────────────────────────┘
```

**Catatan:**
- URL stabil: `/article/{slug}` (mis. `/article/joshnu-v4i1-001`).
- Label retraction/correction permanen + link ke alasannya.
- *Mobile:* kolom galley & citation menyusut jadi 1 kolom.

---

## 4. Halaman Pencarian

```
┌──────────────────────────────────────────────────────────────────────────┐
│ 🔍 Advanced Search                                                       │
│ Keyword: [____________________]   Judul: [________]  Penulis: [______]  │
│ Tahun: [____–____]  Section: [▾]  Bahasa: [ID|EN|Both]  [Cari] [Reset]  │
├──────────────────────────────────────────────────────────────────────────┤
│ 128 hasil untuk "taman kota"  |  Sort: [Relevansi ▾ | Tanggal ▾]        │
│ ┌──────────────────────────────────────────────────────────────────────┐│
│ │ Judul artikel (matched term di-bold)                                 ││
│ │ Penulis • Vol 4 No 1 (2026) • Section: Public Health                 ││
│ │ Abstract: ...taman kota meningkatkan... [Baca] [PDF]                 ││
│ ├──────────────────────────────────────────────────────────────────────┤│
│ │ ...                                                                    ││
│ └──────────────────────────────────────────────────────────────────────┘│
│ ‹ 1 2 3 ... 13 ›                                                        │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Login / Register

```
┌──────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│                        [logo JOSHNU]                                    │
│   ┌────────────────────────────────────────────┐                         │
│   │  MASUK                                     │                         │
│   │  Email      [________________________]     │                         │
│   │  Password   [________________________]     │                         │
│   │  ☐ Ingat saya          [Lupa password?]    │                         │
│   │           [ MASUK ]                        │                         │
│   │  ── atau ──                              │                         │
│   │           [ (G) Masuk dengan Google ]     │  (opsional)             │
│   │  Belum punya akun? [Daftar di sini]       │                         │
│   └────────────────────────────────────────────┘                         │
│                                                                          │
│   Reviewer diundang? Buka tautan undangan pada email Anda.              │
└──────────────────────────────────────────────────────────────────────────┘
```

**Catatan:**
- Register: nama, email, institusi, ORCID (opsional), password. Verifikasi email (link token).
- Link undangan reviewer ber-token tidak butuh akun (auto-buat profil ringkas saat terima).

---

## 6. Author — Form Submission (Multi-Step)

### Step 1/4 — Metadata Naskah
```
┌──────────────────────────────────────────────────────────────────────────┐
│ SUBMISSION BARU                                                         │
│ (1) Metadata ● ── (2) Penulis ○ ── (3) File ○ ── (4) Konfirmasi ○     │
├──────────────────────────────────────────────────────────────────────────┤
│ Section:            [Public Health ▾]                                   │
│ Judul (ID):         [________________________________________]          │
│ Judul (EN):         [________________________________________]          │
│ Abstrak (ID):       [________________________________________]          │
│                     [________________________________________] (≥150 kb)│
│ Abstrak (EN):       [________________________________________]          │
│ Keywords (3–6):     [kata1] [kata2] [kata3] [+]                         │
│ Funding:            [________________________________________]          │
│ Acknowledgments:    [________________________________________]          │
│ Konflik kepentingan:[________________________________________]          │
│ Rekomendasi reviewer (opsional, max 3):                                  │
│   [nama/email institusi + alasan] [+ Tambah]                            │
│ Lisensi:            ☑ CC BY 4.0 (default)                               │
│ Ceklis wajib:       ☑ Orisinal  ☑ Belum dipublikasi  ☑ Persetujuan co- │
│                                                             [Draf] [→]  │
└──────────────────────────────────────────────────────────────────────────┘
```

### Step 2/4 — Penulis
```
│ Penulisan urutan & co-author:                                            │
│ 1. [Nama________] [Institusi____] [Email____] [ORCID____] ☐ korespondensi│
│ 2. [Nama________] [Institusi____] [Email____] [ORCID____]               │
│    [+ Tambah Penulis]    (urutan bisa di-swap ↑↓)                       │
│                         [← Kembali] [Simpan Draf] [Lanjut → File]       │
```

### Step 3/4 — Unggah File
```
│ NASKAH UTAMA (wajib)                                                     │
│  [Pilih file] main-manuscript.pdf  (1.2 MB)  ✔ PDF/DOCX/ODT/tex/zip     │
│ COVER LETTER (wajib)   [Pilih file] cover-letter.docx  ✔                │
│ FIGURES (opsional)     figure-1.png  ✔  figure-2.png  ✔  [+ Tambah]     │
│ TABLES (opsional)      table-1.xlsx  ✔  [+ Tambah]                      │
│ SUPPLEMENTARY (ops.)   supp-data.zip  ✔  [+ Tambah]                     │
│  Progress: ██████████░░░░░░░░  5/8 file  •  Max 50 MB/file              │
│                         [← Kembali] [Lanjut → Konfirmasi]               │
```

### Step 4/4 — Konfirmasi & Submit
```
│ Ringkasan: section, judul, penulis, n file, ceklis                      │
│ [Edit (kembali ke step)]                                                │
│ ☑ Saya menyetujui kebijakan jurnal (ethics, OA, APC)                    │
│                         [← Kembali]   [ SUBMIT NASKAH ]                 │
│ → Stage: SUBMITTED • No. referensi: JOSHNU-2026-0042                    │
```

---

## 7. Author — My Submissions (Status Tracking)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ NASKAH SAYA                                     [+ Submit Naskah Baru]  │
├──────────────────────────────────────────────────────────────────────────┤
│ JOSHNU-2026-0042 — "Taman Kota dan Kesehatan Mental"                    │
│ Status: UNDER REVIEW (ronde 1) — 12 hari di tahap ini                   │
│ ●Submitted───●Under Review───○Decision───○Revised───○Accepted───○Sched │
│ 01 Sep        15 Sep                                                  │
│ Aksi: [Lihat Detail] [Unduh File Saya] [Tarik Naskah (sebelum accept)]  │
├──────────────────────────────────────────────────────────────────────────┤
│ JOSHNU-2025-0117 — "Partisipasi Urban..."    Status: PUBLISHED         │
│ ✅ Decision log: 20 Des 2025 Accept (Prof. X)                           │
│ Aksi: [Lihat Artikel] [Unduh PDF of Record] [Cite]                      │
└──────────────────────────────────────────────────────────────────────────┘
```

**Detail naskah (tab):** `Ringkasan | File | Riwayat & Keputusan | Pesan | Pembayaran APC`

---

## 8. Editor — Dashboard

```
┌──────────────────────────────────────────────────────────────────────────┐
│ SIDEBAR                         │ DASHBOARD            [🔔 3] [avatar▾] │
│ ● Dashboard                    ├────────────────────────────────────────┤
│ ▸ Submissions (12)             │ SLA ALERT: 2 naskah > 30 hari di       │
│ ▸ Reviewers (48)                   UNDER REVIEW  [Lihat →]             │
│ ▸ Issues (7)                   ├────────────────────────────────────────┤
│ ▸ Messages (2)                 │ QUIT KUE PER TAHAP (klik = filter):   │
│ ─────────────                  │ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐     │
│ (Editor-in-Chief +)            │ │  5  │ │  3  │ │  2  │ │  1  │     │
│ ▸ Announcements                │ │SUB- │ │UND- │ │DEC- │ │ACCEP│     │
│ ▸ Users                        │ │MIT- │ │ER RE│ │ISION│ │PTED │     │
│ (Admin +)                      │ │TED  │ │VIEW │ │     │ │     │     │
│ ▸ Settings  ▸ Reports          │ └─────┘ └─────┘ └─────┘ └─────┘     │
│ ▸ Audit Log                    ├────────────────────────────────────────┤
│                                │ QUEUE (stage: UNDER REVIEW)           │
│                                │ ┌────────────────────────────────────┐│
│                                │ │ #0042 Taman Kota & Kesehatan —    ││
│                                │ │ A. Budi dkk.  [12 hari] [1/2 rev] ││
│                                │ │ [Buka] [Assign Reviewer] [Keputusan]││
│                                │ ├────────────────────────────────────┤│
│                                │ │ #0039 ...                         ││
│                                │ └────────────────────────────────────┘│
│                                │ Filter: [stage▾][section▾][author]    │
│                                │ [←] 1 2 3 [→]                         │
└──────────────────────────────────────────────────────────────────────────┘
```

### 8a. Editor — Detail Submission
```
┌──────────────────────────────────────────────────────────────────────────┐
│ #JOSHNU-2026-0042  UNDER REVIEW (ronde 1)          [⋯ aksi cepat]       │
│ Tabs: Ringkasan | File | Review | Keputusan | Pesan | Timeline | APC    │
├──────────────────────────────────────────────────────────────────────────┤
│ RINGKASAN                                                                │
│ Judul, penulis, section, submitted 01 Sep 2025, SLA: 12 hari            │
│ [Unduh Naskah] [Cek Plagiarism (roadmap)] [Withdraw approval]           │
│ ┌─ ACTION BAR ────────────────────────────────────────────────────────┐ │
│ │ [Start Review] [Assign Reviewer (+)] [Buat Keputusan] [Pesan Penulis]│ │
│ └─────────────────────────────────────────────────────────────────────┘ │
├──────────────────────────────────────────────────────────────────────────┤
│ REVIEWER:                                                                │
│ ✔ Dr. Rina (internal) — terbit 20 Sep — Minor Revise — [Lihat Form]    │
│ ⏳ Prof. Anto (invite 15 Sep, due 15 Okt) — belum diterima             │
│    [Follow-up] [Batalkan Invite] [Invite Lain: email____]               │
├──────────────────────────────────────────────────────────────────────────┤
│ TIMELINE: 01 Sep submit → 10 Sep assign editor → 12 Sep under review    │
│           → 20 Sep review #1 diterima → (sekarang)                      │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Reviewer — Review Form

```
┌──────────────────────────────────────────────────────────────────────────┐
│ REVIEW NASKAH — #JOSHNU-2026-0042 (double-blind: "Taman Kota dan ...")  │
│ Due: 15 Okt 2025 (sisa 8 hari)                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ [Unduh Naskah (anon)] [Unduh Cover Letter Editor]                        │
│ REKOMENDASI: ◉ Minor Revise  ○ Accept  ○ Major Revise  ○ Reject         │
│ RATING (1–5):  Kebaruan [●●●●○]  Metodologi [●●●●○]                    │
│               Kejelasan [●●●○○]  Kontribusi [●●●●○]                    │
│ KOMPONEN:            [✓] Judul  [✓] Abstrak  [✓] Metodologi  ...        │
│ KOMENTAR UNTUK PENULIS (akan dikirim):                                   │
│ [________________________________________________________]              │
│ KOMENTAR UNTUK EDITOR (confidential):                                    │
│ [________________________________________________________]              │
│ UPLOAD FORM REVIEW (PDF/DOCX, opsional): [Pilih file]                    │
│                                                                        │
│ [Simpan Draf]   [Submit Review]   [Tolak Undangan: alasan ▾]            │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 10. Author — Pembayaran APC

```
┌──────────────────────────────────────────────────────────────────────────┐
│ PEMBERTAN APAAN APC — #JOSHNU-2026-0042                                  │
├──────────────────────────────────────────────────────────────────────────┤
│ Tagihan: INV-2026-0042   Nominal: Rp 750.000 (IDR)   Due: 15 Jan 2026   │
│ Status: ⏳ AWAITING PROOF                                               │
│ ┌────────────────────────────────────────────────────────────────────┐  │
│ │ Transfer bank: BCA 123-456-7890 a.n. JOSHNU                        │  │
│ │ Konfirmasi nama pengirim: [________________]                       │  │
│ │ [Upload Bukti (PDF/JPG/PNG, max 5 MB)]  ✓ bukti.png (180 KB)       │  │
│ │ Catatan: [____________________]   [Kirim Bukti]                    │  │
│ └────────────────────────────────────────────────────────────────────┘  │
│ Riwayat: 05 Jan bukti dikirim → menunggu verifikasi admin               │
└──────────────────────────────────────────────────────────────────────────┘
```

*(v1.1: tambah tab pilihan QRIS / VA / e-wallet dengan status otomatis via webhook.)*

---

## 11. Admin — Settings & Users (ringkas)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ ADMIN — SETTINGS                                                         │
│ Tabs: General | Masthead & Theme | APC | Review Policy | Integrasi      │
├──────────────────────────────────────────────────────────────────────────┤
│ General: Nama jurnal [________] ISSN print [____] ISSN online [____]    │
│          Tagline [________] Email kontak [________]                     │
│ Theme:   Logo [upload]  Warna primer [🎨 #1B6E5A]  Font [Inter ▾]       │
│          [Preview →]                                                    │
│ APC:     ☑ Aktif  Nominal [750000] [IDR ▾]  Diskon student [%]         │
│ Review:  Mode [Double-blind ▾]  Max ronde [3]  Deadline [30] hari       │
│ Integrasi: Crossref (DOI suffix: 10.xxxx/joshnu) [Tes Koneksi] [Status] │
│            ORCID [Terhubung ✓]  SMTP [✓]                                 │
│                                                    [Simpan Perubahan]   │
├──────────────────────────────────────────────────────────────────────────┤
│ ADMIN — USERS                                                            │
│ Filter: [role▾][nama/email search]        [+ Tambah User]               │
│ Nama | Email | Roles | Status | Aksi                                    │
│ Dr. Rina | rina@inst.ac.id | Reviewer, Author | Aktif | [Edit][Nonaktif]│
│ ...                                                                      │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 12. Layout Mobile (prinsip umum)

| Area | Perilaku mobile |
|---|---|
| Header | Logo + hamburger menu; toggle bahasa di dalam menu |
| Publik | Hero menyusut; list/kartu 1 kolom; search jadi ikon → full-screen sheet |
| Artikel | Metadata → abstract → galley → citation (1 kolom); tombol "Download PDF" sticky di bawah |
| Workspace | Sidebar → bottom sheet/hamburger; tabel queue → kartu; form multi-step tetap 1 kolom |
| Interaksi | Target sentuh ≥ 44px; tanpa hover-dependent UI |

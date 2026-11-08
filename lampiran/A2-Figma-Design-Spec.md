# Lampiran A2 — Spesifikasi Desain Figma-Ready
## JOSHNU — Design System + Frame Spec untuk Produksi di Figma
Versi: 1.0 | Tanggal: 2026-11-03

Wireframe vektor tersedia di `wireframes/svg/` (01-landing, 02-article-page, 03-editor-dashboard, 04-submission-form) dan pratinjau PNG di `wireframes/png/`.

---

## 1. Cara Memakai File Wireframe di Figma

1. **Import SVG** ke Figma: `File → Place image…` (atau drag-and-drop) file `wireframes/svg/*.svg`.
2. Setiap SVG menjadi **frame vektor**; group `<g id="...">` menjadi **layer bernama** (header, hero, card-announcements, queue-table, dst.) — gunakan sebagai basis build, bukan sekadar gambar acuan.
3. **Disarankan**: build ulang komponen dengan Auto Layout di atas wireframe (wireframe = akurasi tata letak; Figma = struktur + interaksi).
4. Ukuran kanvas: desktop **1440px**; wireframe adalah single-state (default). State lain dibuat sebagai varian (lihat §4).

### Konvensi naming layer (ikuti id group di SVG)
```
Page / [name]-desktop
├── header            (logo, nav, bahasa, login, CTA submit)
├── hero / article-header / sidebar / topbar / submission-form / ...
├── card-*            (kartu konten)
├── section-*         (blok section)
├── panel-*           (panel 2 kolom)
├── queue-table       (tabel antrean)
└── footer
```
Komponen: `Button/Primary`, `Input/Text`, `Card/Article`, `Badge/Status`, `Table/Row`, dst.

---

## 2. Design Tokens

### 2.1 Warna

| Token | Hex | Pemakaian |
|---|---|---|
| `primary` | `#1B6E5A` | Aksi utama, link, state aktif, header tabel ERD/hero |
| `primary-dark` | `#145244` | Hover primary, aksen hero |
| `primary-light` | `#E6F2EF` | Chip/badge light, fill checkbox state |
| `bg` | `#F5F6F8` | Background halaman publik & workspace |
| `surface` | `#FFFFFF` | Kartu, input, topbar |
| `text-primary` | `#111827` | Judul, body utama |
| `text-secondary` | `#6B7280` | Meta, label, placeholder |
| `text-inverse` | `#FFFFFF` | Teks di atas primary/sidebar gelap |
| `border` | `#E5E7EB` | Border kartu/input/divider |
| `border-soft` | `#F1F5F9` | Divider tabel internal |
| `sidebar` | `#0F172A` | Sidebar & footer area pengguna |
| `success` | `#16A34A` | Status ACCEPTED, verifikasi berhasil |
| `warning` | `#F59E0B` / teks `#92400E` / bg `#FFFBEB` | Alert SLA, overdue |
| `error` | `#DC2626` | Validasi gagal, retraction |
| `info` | `#2563EB` | Link sekunder, badge info |

### 2.2 Tipografi (Inter; fallback: Roboto, Segoe UI, system)

| Token | Size / Weight | Line-height | Pemakaian |
|---|---|---|---|
| `display` | 30 / 700 | 1.2 | Hero judul |
| `h1` | 25 / 700 | 1.25 | Judul artikel, judul halaman |
| `h2` | 20 / 700 | 1.3 | Judul halaman workspace |
| `h3` | 15 / 700 | 1.3 | Judul section (`ARTIKEL TERBARU`) |
| `h4` | 13 / 700 | 1.3 | Judul kartu kecil (uppercase, letter-spacing 0.04em) |
| `body` | 14 / 400 | 1.5 | Body default |
| `body-md` | 13 / 400 | 1.5 | Tabel, meta, card |
| `small` | 12 / 400 | 1.4 | Caption, meta halus |
| `badge` | 11 / 600 | 1.2 | Chip status, tag |
| `mono` | 11.5 / 400 | 1.5 (Menlo) | Citation, DOI |

### 2.3 Spasi & Grid

- **Spacing scale**: `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64` (semua wireframe sudah di grid 8px).
- **Container publik**: maks 1280px, padding-x 80px (desktop) / 24px (tablet) / 16px (mobile).
- **Gutter kartu**: 16px; margin antar-section: 32–40px.
- **Radius**: input & tombol `8px`, kartu `10px`, chip `999px` (pill), badge kecil `5–6px`.
- **Shadow**: kartu `0 1px 2px rgba(16,24,40,.05)`; popover `0 4px 12px rgba(16,24,40,.10)`.
- **Border**: 1px `border` untuk kartu/input; 1px `border-soft` untuk divider tabel.

### 2.4 Breakpoint

| Name | Width | Catatan |
|---|---|---|
| `mobile` | 360px | 1 kolom; nav → hamburger; tombol aksi penting sticky-bottom |
| `tablet` | 768px | 2 kolom kartu; sidebar → bottom sheet |
| `desktop` | 1440px | Baseline wireframe |

---

## 3. Komponen (bikin sebagai Figma Component + Varian)

### 3.1 Button
| Varian | Fill / Stroke | Teks | Radius | Height |
|---|---|---|---|---|
| `primary` | `primary` | white 13/600 | 8 | 40 (hero: 44) |
| `secondary` (outline) | white + stroke `primary` 1.5 | `primary` 13/600 | 8 | 40 |
| `ghost` | white + stroke `border` 1 | `text-primary` 13/500 | 8 | 40 |
| State: `hover` (primary-dark / fill primary-light), `focus` (ring 2px `primary` offset 2), `disabled` (opacity 40%), `loading` (spinner kiri, teks tetap) |

### 3.2 Input / Textarea / Select
- Box: white, stroke `border` 1, radius 8, height 40 (textarea ≥ 96).
- Label di atas: 12/600 `text-primary`; `*` untuk wajib; helper text 11 `text-secondary` di bawah.
- Placeholder `text-secondary`; `focus`: stroke `primary` + ring `primary-light` 3px; `error`: stroke `error` + pesan 11 `error`.
- **Tag input (keywords)**: chip `primary-light`, teks `primary-dark` 12/500, ikon ×.

### 3.3 Card
- `Card/Base`: white, border `border`, radius 10, padding 24.
- `Card/Article`: thumbnail 64×84 kiri, judul 12.5/600 (max 2 baris), meta 11, footer: badge PDF/DOI + views.
- `Card/Stage` (dashboard): border-top 5px warna stage, angka 30/700, label 11.5/700 uppercase.

### 3.4 Badge / Chip / Status
| Status | Warna |
|---|---|
| PUBLISHED / ACTIVE | `primary-light` / `primary-dark` |
| ONLINE FIRST | `#EFF6FF` / `#1D4ED8` |
| CORRECTED | `#FFFBEB` / `#92400E` |
| RETRACTED | `#FEF2F2` / `#B91C1C` |
| UNDER REVIEW | `#EFF6FF` / `#1D4ED8` |
| OVERDUE / SLA | `#FEF3C7` / `#92400E` |

### 3.5 Tabel (queue submissions, users, payments)
- Header: 10.5/700 uppercase `text-secondary`, padding-y 12, border-b `border`.
- Row: padding-y 14, border-b `border-soft`, hover fill `#F8FAFC`.
- Kolom aksi kanan: tombol `ghost` kecil (height 26) + `primary` kecil untuk aksi utama.

### 3.6 Stepper (submission form)
- Circle 28px: aktif `primary` + teks white; belum: white + stroke `border` + teks `text-secondary`.
- Connector 1.5px `border` antar step (jangan melewati label — lihat wireframe 04).
- Label step 12.5/600; step aktif `text-primary`.

### 3.7 Sidebar (workspace)
- Width 240, fill `sidebar`; item 13/400 `#94A3B8`, aktif: fill `primary` radius 8 + white/600; badge count di dalam item.
- Bottom: avatar 28 + nama 12/600 + role 10.5.

### 3.8 Timeline (status naskah)
- Horizontal: titik 10px per stage; selesai `primary`, aktif `primary` + halo, belum `border`.
- Label 11 di bawah titik + tanggal 10 `text-secondary`.

### 3.9 Alert
- `Alert/warning`: bg `#FFFBEB`, stroke `#F59E0B`, teks `#92400E` 13/500, CTA kanan.

### 3.10 Modal / Toast
- Modal: overlay rgba(15,23,42,.5), panel white radius 12 width 480, header 15/700, footer kanan (ghost + primary).
- Toast: bawah-kanan, auto-dismiss 5 dtk, varian sukses/gagal/info.

---

## 4. Spesifikasi per Frame

### 4.1 `01-landing` (1440×1020)
| Area | Tinggi (px) | Isi | Interaksi |
|---|---|---|---|
| header (2 baris) | 0–104 | Logo+tagline; baris 2: nav (Home/About/Current Issue/Archive/Announcements) + active underline | Nav active per halaman; hover teks `primary` |
| hero | 112–336 | Banner `primary`, judul 30/700 2 baris, sub 13.5, 2 CTA (primary + outline-on-dark) | CTA → form submission / current issue |
| card-announcements | 360–568 (800×208) | 3 item: dot + judul + tanggal + "Baca →" | Item → detail announcement |
| card-about | 360–568 (456×208) | Deskripsi 3 baris, ISSN/APC, link | → /about |
| section-latest-articles | 592–820 | Judul + "Lihat semua →"; 4 × Card/Article (316×184) | Kartu → halaman artikel |
| footer | 920–1020 | Dark; ISSN/copyright, tautan kebijakan, form subscribe | Submit subscribe → toast sukses |

**State tambahan:** empty (belum ada artikel: ilustasi + CTA), loading skeleton (kartu shimmer).

### 4.2 `02-article-page` (1440×1100)
| Area | y | Isi |
|---|---|---|
| breadcrumb | 134 | `Archive › Vol 4 No 1 (2026) › slug` |
| status badge + label | 150–176 | PUBLISHED / ONLINE FIRST / CORRECTED / RETRACTED |
| judul + penulis + afiliasi | 190–310 | H1 25/700 (max 2 baris), penulis 14/500 dengan superscript, afiliasi 11.5 |
| meta timeline + DOI | 320–372 | Received/Reviewed/Accepted/Published; badge DOI + link |
| abstract card | 392–542 | Toggle bahasa ID/EN (pill), abstrak 13, keywords chips |
| panel-galley (760) | 562–778 | Row download: VoR (tombol primary lebar), proof, supplementary, JATS XML; views/downloads |
| panel-citation (496) | 562–778 | Select format (APA/MLA/Chicago/BibTeX/RIS/EndNote), box mono, tombol Copy/export, QR 56, share row |
| section-related | 806–994 | 3 kartu 416×148 |

**Interaksi:** Copy citation (clipboard + toast), download = route terproteksi (bukan link mentah file), toggle abstrak tanpa reload, share → intent (WA/FB/X/email).
**State:** label RETRACTED (badge merah + panel alasan permanen), CORRECTED (badge amber + tautan erratum), artikel di-unpublish (404 ramah).

### 4.3 `03-editor-dashboard` (1440×940)
| Area | Dimensi | Isi |
|---|---|---|
| sidebar | 240×full | Menu: Dashboard, Submissions (12), Reviewers (48), Issues (7), Messages (2) | Announcements, Users, Settings, Reports, Audit Log; user di bawah |
| topbar | 1200×56 | Judul halaman, bell + badge notifikasi, avatar |
| alert-sla | 1136×56 | Alert/warning + CTA "Lihat →" (filter queue ke overdue) |
| cards-stage | 4 × 272×92 | SUBMITTED / UNDER REVIEW / DECISION / ACCEPTED (border-top warna) — **klik = filter queue** |
| queue-table | 1136×620 | Filter (Section/Author/Round + search), header kolom `REF | JUDUL | PENULIS | HARI | REVIEW | AKSI`, row 62px, pagination, sort "hari di tahap" |

**Aturan kolom (hindari overlap seperti draft):** REF min 130px · JUDUL flex (truncation 1 baris, tooltip penuh) · PENULIS 200 · HARI 70 (overdue: `warning`/bold) · REVIEW chip 54 · AKSI 140 (`Buka` ghost, `Assign` primary).
**State:** empty queue (illustration + "Tidak ada naskah di tahap ini"), row hover, pagination.

### 4.4 `04-submission-form` (1440×980)
| Area | y | Isi |
|---|---|---|
| card form | 132–928 (960×796) | Breadcrumb, judul 20/700, stepper 4 langkah |
| Step 1 (Metadata) | 280–920 | Section select, Judul ID/EN, Abstrak ID (textarea + counter "182 kata" kanan-bawah, validasi ≥150), keywords tag-input (3–6), Funding + COI (2 kolom), license checkbox, checklist 3 item, footer: "Draf tersimpan otomatis HH:MM" kiri, `[← Kembali]` + `[Lanjut: Penulis →]` kanan |

**Alur step:** 1 Metadata → 2 Penulis (baris penulis: nama/institusi/email/ORCID/✓ korespondensi, urutkan ↑↓, tambah) → 3 File (upload multi: manuscript*, cover letter*, figures, tables, supplementary; progress; max 50 MB/file; whitelist ekstensi) → 4 Konfirmasi (ringkasan + ceklis kebijakan + tombol `SUBMIT NASKAH`).
**State:** validation error per field (toast + inline), draft autosave (indikator), progress upload per file, error upload (file ditolak + alasan), sukses submit (halaman ringkasan + reference_no + tombol lacak status).

---

## 5. Halaman Lain (buat dari wireframe ASCII `A-Wireframes.md`)
Prioritas setelah 4 frame di atas:
1. `05-archive-issue` (list issue + artikel per issue)
2. `06-submission-detail` (tab Ringkasan/File/Riwayat/Pesan/APC + timeline)
3. `07-reviewer-form` (rekomendasi, 4 rating, komentar ×2, upload, tolak undangan)
4. `08-payment-apc` (tagihan, transfer, upload bukti, riwayat status)
5. `09-admin-settings` (General/Masthead & Theme/APC/Review/Integrasi) + `10-admin-users`

## 6. Checklist before handoff ke dev
- [ ] Semua komponen di atas dibuat sebagai Component dengan varian state (default/hover/focus/disabled/error/empty/loading)
- [ ] Token warna & tipografi di-set sebagai **Figma Variables** (sesuai nama token §2)
- [ ] Grid 8px & container 1280 diterapkan di semua frame
- [ ] Versi mobile (360px) untuk: landing, article page, submission detail, APC
- [ ] Flow interaktif: submission 4-step, queue→detail→decision, review form
- [ ] Aksesibilitas: contrast AA, focus visible, label form, alt text, urutan tab logis

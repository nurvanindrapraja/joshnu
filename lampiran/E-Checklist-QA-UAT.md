# Lampiran E — Checklist QA & UAT
## JOSHNU (Laravel 12 + MySQL 8)
Versi: 1.0 | Tanggal: 2026-11-03

**Legend:** `[ ]` belum diperiksa. Kritis = blocker release.
**Lingkungan:** `staging` (mirip produksi: PHP, Nginx, MySQL, SMTP sandbox, Crossref sandbox bila ada).

---

## 1. Portal Publik

### 1.1 Halaman
- [ ] *(Kritis)* Landing: masthead, announcement, artikel terbaru tampil benar; tema sesuai `journal_settings`
- [ ] *(Kritis)* `/about`: scope, editorial board, kebijakan (ethics/OA/APC) lengkap
- [ ] *(Kritis)* `/current-issue` & `/archive`: daftar issue & artikel benar, sort volume/number
- [ ] *(Kritis)* Halaman artikel: metadata multibahasa (ID/EN), penulis + ORCID, abstrak, keywords, galley, DOI, tanggal (received/accepted/published)
- [ ] Citation: APA, MLA, Chicago, BibTeX, RIS, EndNote benar + copy-to-clipboard
- [ ] Label CORRECTED / RETRACTED tampil permanen + alasan
- [ ] Announcements: list + detail; pengumuman non-aktif/berakhir tidak tampil
- [ ] Footer: ISSN, kontak, tautan kebijakan
- [ ] 404 & halaman error ramah (500 tidak membeber stack trace)

### 1.2 Pencarian & Discovery
- [ ] *(Kritis)* Search by keyword (judul/abstrak/keywords), author, year, section
- [ ] FULLTEXT relevan; pencarian bahasa campuran tidak error
- [ ] Pagination & sort (relevansi/tanggal) berfungsi
- [ ] *(Kritis)* `/sitemap.xml` valid (validator XML) + memuat semua artikel terbit
- [ ] *(Kritis)* OAI-PMH: `Identify`, `ListRecords` (oai_dc & jats), `ListIdentifiers`, `GetRecord`, parameter `from/until/set` — validasi dengan tool OAI-PMH
- [ ] DOAJ export (admin) menghasilkan zip: metadata + PDF lengkap

### 1.3 Galley & Analytics
- [ ] *(Kritis)* Download PDF of record, supplementary, JATS XML berhasil
- [ ] Counter views & downloads bertambah (dedup session/IP per 24 jam); tidak double-count pada refresh
- [ ] File galley tidak bisa diakses langsung via path storage (harus route)

---

## 2. Author (Submission)

### 2.1 Akun
- [ ] *(Kritis)* Register → email verifikasi → login; email ganda ditolak
- [ ] Lupa/reset password berfungsi (token sekali pakai, kedaluwarsa)
- [ ] Profil: edit institusi, ORCID (format valid), keahlian
- [ ] *(Kritis)* Author **tidak bisa** mengakses submission milik orang lain (403)

### 2.2 Submission
- [ ] *(Kritis)* Multi-step form: validasi per step (judul wajib ID/EN, abstrak ≥ 150 kata, keywords 3–6, ceklis wajib)
- [ ] Draf bisa disimpan & dilanjutkan
- [ ] Penulis multiple: urutan, korespondensi (max 1), penulis eksternal tanpa akun
- [ ] *(Kritis)* Upload file: whitelist ekstensi; max 50 MB/file; file > limit ditolak dengan pesan jelas; progress bar; file korup (ekstensi .pdf isinya HTML) ditolak
- [ ] File wajib (manuscript + cover letter) dicek saat final submit
- [ ] Setelah submit: reference_no tergenerasi, timeline tampil, email ke editor terkirim
- [ ] Revisi: hanya bisa saat stage `revised`; versi bertambah; `response to reviewers` bisa diunggah
- [ ] Withdraw: hanya sebelum `accepted`; setelahnya ditolak; status jadi `withdrawn`
- [ ] Status tracking: stage, SLA hari, riwayat keputusan, pesan dari editor

### 2.3 APC
- [ ] Tagihan otomatis saat accept (jika APC aktif); nominal & mata uang benar
- [ ] Upload bukti + nama pengirim; status berubah `proof_uploaded`
- [ ] Email ke admin saat bukti masuk

---

## 3. Reviewer

- [ ] *(Kritis)* Link undangan ber-token: reviewer tanpa akun bisa terima & review (auto profil)
- [ ] Token: sekali pakai untuk identitas, kedaluwarsa sesuai setting; token acak tidak bisa ditebak (≥ 128 bit)
- [ ] Double-blind: nama penulis & institusi **tidak** muncul di naskah yang didownload & UI reviewer
- [ ] Single-blind: nama penulis tampil
- [ ] Review form: rekomendasi, 4 rating (1–5), komentar ke penulis & ke editor (confidential), upload file
- [ ] Tolak undangan dengan alasan → email ke editor; naskah tetap berjalan
- [ ] Reminder due − 7 hari & overdue terkirim (uji dengan mengubah due date)
- [ ] Multi-round: ronde 2 hanya muncul setelah editor memulai ronde baru
- [ ] Reviewer tidak bisa melihat review reviewer lain / komentar internal editor

---

## 4. Editor (Workspace)

- [ ] *(Kritis)* Dashboard: count per stage akurat (bandingkan dengan query SQL manual); SLA alert > 30 hari
- [ ] Queue: filter stage/section/author/keyword/tanggal + pagination
- [ ] Detail naskah: semua tab (ringkasan, file, review, keputusan, pesan, timeline, APC) lengkap
- [ ] *(Kritis)* Transisi stage hanya mengikuti state machine yang valid (mis. `rejected` → `under_review` ditolak); setiap transisi tercatat di `submission_events` + `audit_logs`
- [ ] Assign reviewer internal (multi-select) & invite eksternal (multi-email); due date sesuai setting
- [ ] Batalkan assign/invite → status review `cancelled`
- [ ] Decision maker: template + custom text; email ke penulis sesuai jenis keputusan; `revision_due_at` tercatat
- [ ] Messages: ke penulis/reviewer/internal; semua tercatat; internal tidak terlihat oleh penulis
- [ ] Issue: create/edit/publish/unpublish; publish gagal jika ada artikel belum lengkap (validasi)
- [ ] Scheduling: assign accepted article ke issue + urutan; stage jadi `scheduled`

---

## 5. Production & Publishing

- [ ] *(Kritis)* Edit metadata final (multibahasa) sebelum publish; perubahan tercatat audit
- [ ] Upload proof (watermark PROOF) vs PDF of record (tanpa watermark)
- [ ] *(Kritis)* Job PDF via Pandoc: sukses untuk naskah normal; naskah LaTeX error → job retry lalu fallback template; status job terlihat
- [ ] JATS XML hasil generate valid (validator JATS/NISO)
- [ ] *(Kritis)* DOI: validasi metadata sebelum deposit; deposit ke Crossref (sandbox) sukses; status `deposited`→`registered` ter-update; respons & URL tersimpan; gagal → retry queue + status `failed` + alert admin
- [ ] *(Kritis)* Publish: artikel + issue; URL stabil (`/article/{slug}`); counter mulai; sitemap ter-update; email penulis; event `ArticlePublished` memicu deposit Crossref
- [ ] Online first: artikel terbit sebelum issue (label ONLINE FIRST)
- [ ] Unpublish: artikel hilang dari publik (404/soft), data tetap ada
- [ ] Correction & Retraction: label permanen + alasan publik; email penulis & editor
- [ ] Crossref deposit issue (metadata issue + daftar artikel)

---

## 6. Admin

- [ ] *(Kritis)* RBAC matriks §13 PRD — uji negatif tiap sel ❌ (mis. Production tidak bisa `Publish issue`, Reviewer tidak bisa `/admin`)
- [ ] Settings: general, APC, review policy, integrasi — simpan & tampil konsisten di portal publik
- [ ] Theme: ganti logo/warna/font → cache theme ter-invalidate, halaman publik berubah
- [ ] Test koneksi: SMTP (email test terkirim), Crossref (credentials), ORCID
- [ ] Users: tambah, ubah role, nonaktifkan (user nonaktif tidak bisa login)
- [ ] *(Kritis)* Verifikasi/tolak bukti APC → status payment & email penulis benar
- [ ] Reports: usage (top-N, trend, country), editorial (waktu per stage, acceptance rate), finance (APC collected/outstanding); export CSV benar
- [ ] Audit log: filter user/action/entity/tanggal; aksi sensitif terekam (siapa, kapan, IP, before/after)

---

## 7. Alur E2E (skenario wajib lulus)

- [ ] *(Kritis)* **Happy path:** register → submit → under review → 2 reviewer → accept → APC verify → proof → PDF → DOI → schedule → publish → artikel tampil + Crossref + sitemap
- [ ] **Skenario revisi:** major revise → penulis upload revisi (ronde 2) → accept → publish
- [ ] **Skenario reject:** reject → naskah archived → penulis bisa submit baru
- [ ] **Skenario withdraw:** penulis withdraw sebelum accept → naskah withdrawn
- [ ] **Skenario reviewer tolak:** semua reviewer tolak → editor bisa invite ulang
- [ ] **Skenario online first:** publish sebelum issue terbit

---

## 8. Notifikasi & Email

- [ ] Semua event T1–T20 (Lampiran D) terkirim ke penerima yang benar, subjek & isi benar (ID & EN)
- [ ] Template responsif di email client utama (Gmail, Outlook, Yahoo) — uji via Litmus/manual
- [ ] Tautan di email berfungsi (token, URL absolut)
- [ ] Opt-out alert issue baru berfungsi; email transaksional tetap terkirim
- [ ] Gagal kirim → retry queue; > 3x gagal → alert admin
- [ ] Dedup: event berulang detik yang sama tidak dobel kirim

---

## 9. Keamanan (OWASP Top 10)

- [ ] *(Kritis)* SQL injection: uji input pada semua form (Laravel Eloquent; pastikan tanpa raw query string concat)
- [ ] *(Kritis)* XSS: input pada judul/abstrak/pesan ter-escape; CSP header aktif
- [ ] *(Kritis)* CSRF: semua form POST/PUT/DELETE punya token; API JSON pakai XSRF
- [ ] *(Kritis)* File upload: validasi MIME (finfo) bukan hanya ekstensi; nama file di-sanitize; disimpan di luar docroot; scan virus (ClamAV) bila tersedia
- [ ] *(Kritis)* Password: argon2id/bcrypt; login throttle 5x/menit; reset token sekali pakai
- [ ] Sesi: cookie `HttpOnly`, `Secure`, `SameSite=Lax`; regenerate session ID saat login
- [ ] *(Kritis)* Akses file: naskah di stage review hanya bisa diakses role yang diizinkan (uji dengan URL langsung + ganti role)
- [ ] Rate limiting aktif di endpoint publik, login, upload
- [ ] Header keamanan: HSTS, X-Content-Type-Options, X-Frame-Options/CSP frame-ancestors
- [ ] Secrets tidak di repo (`.env`); error page produksi tanpa detail internal
- [ ] Dependensi: `composer audit` / `npm audit` bebas critical
- [ ] *(Kritis)* Audit log mencatat akses file sensitif & perubahan metadata

---

## 10. Performa & Kapasitas

- [ ] *(Kritis)* Halaman publik p95 < 800 ms di staging (VPS 2 vCPU/4GB, tanpa CDN)
- [ ] Upload 50 MB < 30 detik (upload multipart streaming; `upload_max_filesize`/`post_max_size` sesuai)
- [ ] 200 user konkuren (k6/artillery): error rate < 1%, p95 < 2 detik
- [ ] Queue: 100 job PDF tertumpuk → diproses berurutan tanpa deadlock
- [ ] DB: query halaman artikel < 50 ms (EXPLAIN: index terpakai); tanpa N+1 (Laravel debugbar)
- [ ] Cache: settings/theme ter-cache; invalidasi saat berubah

---

## 11. Aksesibilitas & Kompatibilitas

- [ ] *(Kritis)* WCAG 2.1 AA: contrast ratio ≥ 4.5:1; semua input punya label; navigasi keyboard lengkap; focus state terlihat; alt text gambar
- [ ] Scan axe DevTools: 0 violation critical/serious
- [ ] *(Kritis)* Responsive: 360px, 768px, 1280px, 1920px — semua halaman utama (cek Wireframe §12 Lampiran A)
- [ ] Browser: Chrome, Edge, Firefox, Safari (2 versi terakhir) — form, upload, download berfungsi
- [ ] Bahasa: semua string UI tersedia ID & EN (tidak ada string mentah `__('...')`); format tanggal/mata uang locale

---

## 12. Data, Backup & Recovery

- [ ] *(Kritis)* Backup harian DB (mysqldump/xtrabackup) + folder file naskah → off-server (object storage)
- [ ] *(Kritis)* Drill restore: pulihkan DB + file ke environment bersih → aplikasi berfungsi, 1 artikel diuji end-to-end
- [ ] RPO < 24 jam & RTO < 4 jam tercapai (catat waktu drill)
- [ ] Disk usage monitor + alert > 80%
- [ ] Health check `/up` terpasang di uptime monitor eksternal (alert < 5 menit)

---

## 13. UAT — Redaksi JOSHNU (Pilot)

**Durasi:** 2 minggu | **Peserta:** EiC, 2 section editor, 1 production editor, 3 penulis, 3 reviewer, 1 admin
**Kriteria exit:** semua item Kritis lulus; tidak ada bug severity 1/2 terbuka; redaksi menandatangani sign-off.

### 13.1 Skenario UAT
| # | Skenario | Kriteria Sukses | Status |
|---|---|---|---|
| U1 | Penulis baru register & submit naskah pertama | Selesai < 15 menit tanpa bantuan; semua email diterima | `[ ]` |
| U2 | Editor memproses antrean & assign reviewer | Dashboard intuitif; ≤ 3 klik untuk assign reviewer | `[ ]` |
| U3 | Reviewer eksternal (tanpa akun) menyelesaikan review via link email | Berhasil tanpa penjelasan teknis | `[ ]` |
| U4 | Alur revisi (minor revise) selesai | Penulis paham apa yang harus direvisi dari email | `[ ]` |
| U5 | Pembayaran APC manual + verifikasi | Alur bukti → verifikasi < 1 hari kerja | `[ ]` |
| U6 | Produksi: proof, PDF, metadata final | Layout PDF sesuai template jurnal; metadata final benar | `[ ]` |
| U7 | Publish issue + verifikasi indeksasi | Artikel muncul di Crossref & ter-crawl Google (test URL) | `[ ]` |
| U8 | Admin: laporan bulanan + audit log | Laporan bisa diekspor & terbaca redaksi | `[ ]` |
| U9 | Pembaca publik: cari, baca, sitasi, download | Pengalaman mulus di desktop & mobile | `[ ]` |
| U10 | Retraction/correction (drill) | Label tampil permanen; data konsisten | `[ ]` |

### 13.2 Kuantifikasi Kepuasan
- [ ] Survei NPS + komentar bebas ke seluruh peserta UAT (target NPS ≥ 40)
- [ ] Wawancara 1-on-1: EiC + Production Editor (catat friction points)

### 13.3 Pengelolaan Defek
| Severity | Definisi | SLA Perbaikan |
|---|---|---|
| 1 (Blocker) | Data hilang/rusak, alur utama mati, kebocoran naskah | 24 jam, hotfix |
| 2 (Critical) | Fitur utama salah, tidak ada workaround | 3 hari |
| 3 (Major) | Fitur kedua salah, ada workaround | Sebelum GA |
| 4 (Minor) | Kosmetik/UX kecil | Backlog |

### 13.4 Sign-off
| Peran | Nama | Tanggal | Tanda Tangan |
|---|---|---|---|
| EiC JOSHNU | | | |
| Production Editor | | | |
| Admin Jurnal | | | |
| QA Lead | | | |
| Product Owner | | | |

---

## 14. Pra-Release (Go/No-Go)
- [ ] Semua checklist §1–§12 berstatus lulus (item Kritis wajib)
- [ ] UAT sign-off §13.4 lengkap
- [ ] Produksi: env production terisi (DNS, TLS, SMTP, Crossref account live, backup berjalan)
- [ ] Rollback plan: tag git release + backup DB pra-deploy; prosedur rollback < 30 menit
- [ ] Runbook operasional: restart queue, cek log, restore backup, scaling manual
- [ ] Monitoring: uptime, alert error (Sentry), log terpusat
- [ ] Dokumentasi user (ID) & admin dipublikasikan

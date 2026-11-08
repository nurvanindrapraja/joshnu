# Lampiran C — Spesifikasi Endpoint / API
## JOSHNU (Laravel 12 + MySQL 8)
Versi: 1.0 | Tanggal: 2026-11-03

**Catatan umum:**
- v1.0 **tidak ada API publik eksternal**; daftar di bawah adalah route MVC Laravel. Endpoint yang ditandai *(JSON)* mengembalikan/menerima JSON untuk area interaktif (dashboard, upload, form).
- Autentikasi: session cookie (Laravel Breeze/Fortify). Role enforcement via middleware `role:...` + Policy per resource.
- Konvensi respons JSON:
  - Sukses: `200`/`201` `{ "data": ... }`
  - Validasi gagal: `422` `{ "message": "...", "errors": { "field": ["..."] } }`
  - Unauthenticated: `401`; Forbidden: `403`; Not found: `404`
- Paginasi: Laravel default (`?page=1`, `per_page` max 50).
- Upload: `multipart/form-data`, max 50 MB/file (lihat Open Question §18 PRD), whitelist ekstensi: pdf, doc, docx, odt, tex, zip, png, jpg, jpeg, gif, xlsx.
- Rate limit: login 5x/menit/IP; endpoint publik 60x/menit/IP; upload 10x/jam/user.

---

## 1. Publik (tanpa login)

| Method | URI | Fungsi |
|---|---|---|
| GET | `/` | Landing page (masthead, announcements, artikel terbaru) |
| GET | `/about` | Tentang jurnal: scope, board, kebijakan, kontak |
| GET | `/current-issue` | Issue terbaru (artikel + metadata) |
| GET | `/archive` | Daftar seluruh issue |
| GET | `/archive/{issue:slug}` | Detail issue (`vol4-no1-2026`) |
| GET | `/issues/{issue:slug}/issue.pdf` | PDF rangkuman issue (galley issue) |
| GET | `/articles` | Pencarian/list artikel. Query: `q`, `year_from`, `year_to`, `section`, `author`, `lang`, `sort=relevance\|date_desc\|date_asc` *(JSON)* |
| GET | `/article/{slug}` | Halaman artikel (count `view` di `article_usage`) |
| GET | `/article/{slug}/download/{galley}` | Download galley (count `download`; redirect storage signed) |
| GET | `/article/{slug}/citations` | Citation format. Query: `format=apa\|mla\|chicago\|bibtex\|ris\|endnote` *(JSON)* |
| GET | `/article/{slug}/related` | Related articles *(JSON, opsional)* |
| GET | `/announcements` | List pengumuman |
| GET | `/announcements/{id:slug}` | Detail pengumuman |
| POST | `/subscribe` | Langganan alert issue baru. Body: `{email}` → 2-click token *(JSON)* |
| GET | `/subscribe/confirm?token=` | Konfirmasi langganan |
| POST | `/unsubscribe?token=` | Berhenti langganan |
| GET | `/sitemap.xml` | Sitemap (jurnal + issue + artikel) |
| GET | `/oai?verb=...` | OAI-PMH (Identify/ListRecords/ListIdentifiers; format oai_dc, jats) |
| GET | `/up` | Health check (DB + queue) → `200 {"status":"ok"}` |

**Contoh `GET /article/{slug}` (data yang dikonsumsi view):**
```json
{
  "data": {
    "slug": "joshnu-v4i1-001",
    "title": {"id": "...", "en": "..."},
    "authors": [
      {"name": "A. Budi", "orcid": "0000-0002-...", "institution": "...", "is_corresponding": true}
    ],
    "section": "Public Health",
    "issue": {"volume": 4, "number": 1, "year": 2026},
    "received_at": "2025-09-01", "reviewed_at": "2025-10-20",
    "accepted_at": "2025-12-15", "published_at": "2026-01-01",
    "doi": "10.xxxx/joshnu.v4i1.001",
    "abstract": {"id": "...", "en": "..."},
    "keywords": ["taman kota", "kesehatan"],
    "galleys": [
      {"label": "PDF (Version of Record)", "type": "article_pdf", "url": "/article/joshnu-v4i1-001/download/12", "size": 1234567}
    ],
    "license": "CC BY 4.0",
    "views": 214, "downloads": 57,
    "article_status": "active"
  }
}
```

---

## 2. Autentikasi & Akun (Breeze/Fortify)

| Method | URI | Fungsi |
|---|---|---|
| GET/POST | `/login` | Login (throttle 5x/menit) |
| POST | `/logout` | Logout |
| GET/POST | `/register` | Daftar (author/reviewer publik) |
| GET/POST | `/forgot-password` | Request reset |
| GET/POST | `/reset-password/{token}` | Set password baru |
| GET | `/verify-email/{id}/{hash}` | Verifikasi email |
| GET/POST | `/two-factor` | (v1.1) setup & konfirmasi 2FA |
| GET | `/auth/google` · GET `/auth/google/callback` | (opsional) Google SSO via Socialite |
| GET | `/account` | Profil user |
| PUT | `/account` | Update profil (nama, institusi, orcid, alamat, telepon) |
| PUT | `/account/password` | Ganti password |
| DELETE | `/account` | Hapus akun (UU PDP) — soft block + purge jadwal 30 hari |

---

## 3. Author — Submission (role `author`; scope hanya submission sendiri)

| Method | URI | Fungsi |
|---|---|---|
| GET | `/account/submissions` | List naskah saya + stage + SLA *(JSON)* |
| GET | `/account/submissions/new` | Form step 1 (metadata) |
| POST | `/account/submissions` | Buat submission (draft → stage `submitted` saat final). Validasi: judul ID/EN, abstrak ≥ 150 kata, keywords 3–6, section, ceklis |
| PUT | `/account/submissions/{id}` | Edit metadata (hanya saat stage `submitted`/`revised`) |
| POST | `/account/submissions/{id}/authors` | Simpan daftar penulis (urutan, korespondensi) |
| POST | `/account/submissions/{id}/files` | Upload file (type: manuscript/cover_letter/figure/table/supplementary) |
| DELETE | `/account/submissions/{id}/files/{file}` | Hapus file |
| GET | `/account/submissions/{id}/files/{file}` | Download file sendiri |
| POST | `/account/submissions/{id}/submit` | Final submit (validasi kelengkapan file wajib) → event `SubmissionSubmitted` |
| GET | `/account/submissions/{id}` | Detail naskah + timeline + keputusan + APC *(JSON)* |
| POST | `/account/submissions/{id}/revisions` | Buat revisi (versi baru) saat stage `revised`; body: `{note}` |
| POST | `/account/submissions/{id}/revisions/files` | Upload file revisi (incl. `review_response`) |
| POST | `/account/submissions/{id}/withdraw` | Tarik naskah (sebelum `accepted`) |
| GET | `/account/submissions/{id}/payment` | Status APC + status tagihan *(JSON)* |
| POST | `/account/submissions/{id}/payment/proof` | Upload bukti transfer `{payer_name, file, note}` |

**Contoh `POST /account/submissions`:**
```json
// Request
{
  "section_id": 2,
  "title_id": "Taman Kota dan Kesehatan Mental",
  "title_en": "Urban Parks and Mental Health",
  "abstract_id": "...", "abstract_en": "...",
  "keywords": ["taman kota", "kesehatan mental", "urban design"],
  "funding": "Dikti 2025", "acknowledgments": "...",
  "conflict_of_interest": "Tidak ada",
  "license": "CC-BY-4.0"
}
// Response 201
{ "data": { "id": 42, "reference_no": "JOSHNU-2026-0042", "stage": "submitted" } }
```

---

## 4. Reviewer (role `reviewer` atau token undangan)

| Method | URI | Fungsi |
|---|---|---|
| GET | `/review/invite/{token}` | Halaman undangan (terima/tolak; auto-buat profil bila belum punya akun) |
| POST | `/review/invite/{token}/accept` | Terima undangan |
| POST | `/review/invite/{token}/decline` | Tolak `{reason}` |
| GET | `/review` | Queue review saya (status, due date) *(JSON)* |
| GET | `/review/{submission}` | Detail naskah (blind sesuai `review_mode`) + form review *(JSON)* |
| GET | `/review/{submission}/files/{file}` | Download naskah (versi anon bila double-blind) |
| POST | `/review/{submission}` | Submit form review. Body di bawah; stage naskah tidak berubah, event `ReviewCompleted` |
| POST | `/review/{submission}/files` | Upload file review (PDF/DOCX) |

**Contoh `POST /review/{submission}`:**
```json
{
  "recommended_decision": "minor_revise",
  "ratings": { "novelty": 4, "methodology": 3, "clarity": 4, "contribution": 4 },
  "comments_to_author": "...",
  "comments_to_editor": "..."
}
// Response 200 { "data": { "status": "submitted" } }
```

---

## 5. Editor (role `eic`/`section_editor`/`production`)

| Method | URI | Fungsi |
|---|---|---|
| GET | `/editor` | Dashboard: count per stage, SLA alerts, queue *(JSON)* |
| GET | `/editor/submissions` | Queue. Query: `stage`, `section`, `author`, `q`, `date_from`, `date_to`, `page` |
| GET | `/editor/submissions/{id}` | Detail lengkap (metadata, file, review, decisions, messages, timeline) *(JSON)* |
| GET | `/editor/submissions/{id}/files/{file}` | Download file naskah (audit log) |
| POST | `/editor/submissions/{id}/stage` | Transisi stage manual yang diizinkan (mis. `submitted → under_review`, `revised → under_review`/`decision`). Body: `{to_stage, note}` — divalidasi state machine |
| GET | `/editor/reviewers` | Cari reviewer internal. Query: `q`, `expertise`, `institution` *(JSON)* |
| POST | `/editor/submissions/{id}/reviewers/invite` | Invite reviewer eksternal. Body: `{emails: [...], due_days: 30}` → email + token |
| POST | `/editor/submissions/{id}/reviewers/assign` | Assign reviewer internal. Body: `{reviewer_ids: [...], due_days: 30}` |
| DELETE | `/editor/submissions/{id}/reviewers/{reviewer}` | Batalkan assign/invite |
| POST | `/editor/submissions/{id}/decision` | Buat keputusan. Body: `{type, comment_to_author, internal_note, revision_due_days}` → email ke penulis, event `DecisionMade` |
| POST | `/editor/submissions/{id}/messages` | Kirim pesan. Body: `{recipient_type, recipient_id, subject, body, is_internal}` |
| GET | `/editor/submissions/{id}/audit` | Audit trail naskah *(JSON)* |
| GET | `/editor/issues` | List issue |
| POST | `/editor/issues` | Buat issue `{volume, number, year, title, cover}` |
| PUT | `/editor/issues/{id}` | Edit issue (sebelum publish) |
| POST | `/editor/issues/{id}/publish` | Publish issue (validasi: artikel complete, DOI terdeposit) |
| POST | `/editor/issues/{id}/unpublish` | Unpublish issue |
| POST | `/editor/submissions/{id}/schedule` | Jadwalkan artikel ke issue. Body: `{issue_id, position}` → stage `scheduled` |
| GET | `/editor/announcements` · POST/PUT/DELETE `/editor/announcements[/{id}]` | CRUD pengumuman (EiC/Admin) |

**Contoh `POST /editor/submissions/{id}/decision`:**
```json
{ "type": "accept", "comment_to_author": "Selamat, naskah Anda diterima...", "internal_note": null }
// Response 200
{ "data": { "stage": "accepted", "decision_id": 18, "emails_sent": ["author@example.com"] } }
```

---

## 6. Production (role `production`/`eic`/`admin`)

| Method | URI | Fungsi |
|---|---|---|
| GET | `/production` | Queue artikel `accepted`/`scheduled` *(JSON)* |
| PUT | `/production/publications/{id}/metadata` | Edit metadata final (multibahasa) sebelum publish |
| POST | `/production/submissions/{id}/proof` | Upload galley proof (watermark "PROOF") |
| POST | `/production/publications/{id}/pdf` | Trigger job typesetting → PDF of record (queue) |
| GET | `/production/publications/{id}/pdf/status` | Status job PDF *(JSON)* |
| POST | `/production/publications/{id}/doi` | Validasi metadata + deposit DOI ke Crossref (queue) |
| GET | `/production/publications/{id}/doi/status` | Status deposit DOI + respons Crossref *(JSON)* |
| POST | `/production/publications/{id}/publish` | Publish (issue terbit / online first) → galley VoR, sitemap, notifikasi |
| POST | `/production/publications/{id}/unpublish` | Unpublish (soft) |
| POST | `/production/publications/{id}/correct` | Correction/erratum `{note}` |
| POST | `/production/publications/{id}/retract` | Retraction `{reason}` (label permanen) |

---

## 7. Admin (role `admin`)

| Method | URI | Fungsi |
|---|---|---|
| GET/PUT | `/admin/settings` | Edit `journal_settings` (General, APC, Review Policy, Integrasi) |
| PUT | `/admin/settings/theme` | Edit theme token + logo + preview |
| POST | `/admin/settings/test` | Tes koneksi: SMTP, Crossref, ORCID |
| GET | `/admin/users` | List user. Query: `q`, `role`, `active` |
| POST | `/admin/users` | Tambah user + role |
| PUT | `/admin/users/{id}/roles` | Ubah role. Body: `{roles: ["eic","author"]}` |
| POST | `/admin/users/{id}/toggle-active` | Aktif/nonaktifkan |
| GET | `/admin/payments` | Daftar APC. Query: `status` |
| POST | `/admin/payments/{id}/verify` | Verifikasi bukti transfer |
| POST | `/admin/payments/{id}/reject` | Tolak bukti `{reason}` |
| GET | `/admin/reports` | Dashboard laporan (usage, editorial, finance) *(JSON)* |
| GET | `/admin/reports/usage.csv` · `editorial.csv` · `finance.csv` | Export CSV. Query: `from`, `to` |
| GET | `/admin/audit-logs` | Log audit. Query: `user`, `action`, `entity`, `from`, `to` |
| GET/POST/PUT/DELETE | `/admin/announcements[...]` | CRUD pengumuman |

---

## 8. Webhook (v1.1 — payment gateway)

| Method | URI | Fungsi |
|---|---|---|
| POST | `/webhooks/midtrans` | Update status `payments` (signature check), event `PaymentPaid` |
| POST | `/webhooks/xendit` | Sama (provider lain) |

Idempoten: update berdasarkan `gateway_payment_id`; log mentah ke `payments.webhook_log`.

---

## 9. Endpoint Interoperabilitas (detail)

### OAI-PMH (`/oai?verb=...`)
- Verbs: `Identify`, `ListMetadataFormats`, `ListSets` (per issue), `ListRecords`, `ListIdentifiers`, `GetRecord`.
- Format: `oai_dc` (Dublin Core), `jats` (JATS XML).
- Seleksi: `?from=2026-01-01&until=2026-12-31&set=vol4-no1`.
- Response: XML OAI-PMH 2.0; `400 badVerb`, `401 cannotDisseminate` sesuai spec.

### Sitemap (`/sitemap.xml`)
- `<url>` untuk: home, about, each issue, each article (lastmod = `published_at`/updated).

### DOAJ export
- `GET /admin/integrations/doaj/export.zip` (admin): metadata (Dublin Core + JATS per artikel) + PDF issue terbaru.

---

## 10. Contoh Alur HTTP (end-to-end, ringkas)

```
AUTHOR
  POST /register → verify email
  POST /account/submissions            → 201 {reference_no}
  POST .../authors, .../files (x4)
  POST .../submit                       → stage: submitted
EDITOR
  POST /editor/submissions/42/stage     {to_stage: under_review}
  POST .../reviewers/invite  {emails:[...]}
REVIEWER
  GET  /review/invite/{token} → accept
  POST /review/42               {recommended_decision: minor_revise, ...}
AUTHOR
  POST /account/submissions/42/revisions → uploads → submit
EDITOR
  POST .../decision {type: accept, ...}
PRODUCTION
  PUT  /production/publications/7/metadata
  POST .../pdf  → .../doi  → .../publish
SYSTEM
  → event ArticlePublished: deposit Crossref, sitemap, email author, alert subscribers
```

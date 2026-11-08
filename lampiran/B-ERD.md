# Lampiran B — Data Model ERD Detail
## JOSHNU — Aplikasi Publikasi Jurnal (Laravel + MySQL 8)
Versi: 1.0 | Tanggal: 2026-11-03

**Konvensi:**
- Engine InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
- PK: `id BIGINT UNSIGNED AUTO_INCREMENT` (disingkat `PK`).
- Setiap tabel memiliki `created_at`, `updated_at` TIMESTAMP (disingkat `ts`).
- Soft delete (`deleted_at`) hanya untuk entitas publik: `publications`, `galleys`, `announcements`, `issues` (archive flag).
- `created_by BIGINT UNSIGNED NULL → users.id` pada tabel transaksional (disingkat `by`).
- FULLTEXT index: `publication_metadata` (title, abstract, keywords) dan `submissions.title` (untuk search publik + internal).

### Diagram Hubungan (tinggi level)

```
users ─┬─< user_roles
       ├─< submissions (author_id) ──< submission_revisions ──< submission_files
       │        │  (section_id → sections)
       │        ├─< reviews (reviewer_id → users)
       │        ├─< decisions (editor_id → users)
       │        ├─< assignments (user_id → users)
       │        ├─< submission_authors
       │        ├─< payments
       │        └─< submission_events
       │                 │
       │                 └─1:1→ publications ──< publication_metadata
       │                       (issue_id → issues)        └──< galleys
       │                                 └──< article_usage
       ├─< notifications
       └─< audit_logs, messages

journal_settings ──< sections, issues, announcements
```

---

## 1. users

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| name | VARCHAR(150) | NOT NULL | |
| email | VARCHAR(190) | NOT NULL, UNIQUE | |
| email_verified_at | TIMESTAMP | NULL | |
| password | VARCHAR(255) | NOT NULL | bcrypt/argon2id |
| orcid | VARCHAR(19) | NULL, UNIQUE | `0000-0002-...` |
| institution | VARCHAR(255) | NULL | |
| expertise | TEXT | NULL | bidang keahlian (untuk cari reviewer) |
| address | TEXT | NULL | |
| phone | VARCHAR(30) | NULL | |
| website | VARCHAR(255) | NULL | |
| is_active | TINYINT(1) | NOT NULL DEFAULT 1 | soft block login |
| two_factor_secret | TEXT | NULL, ENCRYPTED | (v1.1, Fortify) |
| two_factor_confirmed_at | TIMESTAMP | NULL | |
| remember_token | VARCHAR(100) | NULL | |
| deleted_at | TIMESTAMP | NULL | soft delete |
| ts | — | | |

**Index:** UNIQUE(email), UNIQUE(orcid)

---

## 2. user_roles

Peran untuk satu jurnal (satu user bisa multi-peran).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| user_id | BIGINT UNSIGNED | FK → users.id, ON DELETE CASCADE | |
| role | ENUM('admin','eic','section_editor','production','reviewer','author') | NOT NULL | |
| ts | — | | |

**Index:** UNIQUE(user_id, role); INDEX(role)

---

## 3. journal_settings (satu baris aktif)

Konfigurasi single-jurnal (desain siap dipecah menjadi multi-row bila kelak multi-jurnal).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | baris 1 = jurnal JOSHNU |
| name | VARCHAR(255) | NOT NULL | "Journal of Sustainable Health and New Urbanism" |
| short_name | VARCHAR(50) | NOT NULL | "JOSHNU" |
| tagline | VARCHAR(255) | NULL | |
| issn_print | VARCHAR(20) | NULL | |
| issn_online | VARCHAR(20) | NULL | |
| scope | TEXT | NULL | lingkup jurnal |
| description | LONGTEXT | NULL | deskripsi panjang |
| ethics_policy | LONGTEXT | NULL | |
| oa_policy | LONGTEXT | NULL | |
| apc_policy | LONGTEXT | NULL | |
| editorial_board | JSON | NULL | `[{name, title, institution, email, orcid, order}]` |
| apc_enabled | TINYINT(1) | DEFAULT 0 | |
| apc_amount | DECIMAL(12,2) | NULL | |
| apc_currency | CHAR(3) | DEFAULT 'IDR' | |
| apc_bank | JSON | NULL | `[ {bank, no, name} ]` |
| review_mode | ENUM('single_blind','double_blind','open') | DEFAULT 'double_blind' | |
| max_review_rounds | TINYINT | DEFAULT 3 | |
| review_deadline_days | TINYINT | DEFAULT 30 | |
| reminder_before_days | TINYINT | DEFAULT 7 | |
| online_first_enabled | TINYINT(1) | DEFAULT 1 | |
| doi_prefix | VARCHAR(40) | NULL | `10.xxxx` |
| doi_suffix_pattern | VARCHAR(60) | NULL | mis. `joshnu.v{v}i{n}.{seq:03d}` |
| theme | JSON | NULL | `{logo, colors{primary,accent,dark}, font, layout}` |
| contact_email | VARCHAR(190) | NULL | |
| address | TEXT | NULL | |
| social | JSON | NULL | `{twitter, facebook, instagram, ...}` |
| ts | — | | |

---

## 4. sections

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| name | VARCHAR(150) | NOT NULL | mis. "Public Health", "New Urbanism" |
| slug | VARCHAR(170) | NOT NULL, UNIQUE | |
| description | TEXT | NULL | |
| sort_order | SMALLINT | DEFAULT 0 | |
| is_active | TINYINT(1) | DEFAULT 1 | |
| ts | — | | |

---

## 5. issues

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| volume | SMALLINT | NOT NULL | |
| number | SMALLINT | NOT NULL | |
| year | SMALLINT | NOT NULL | |
| title | VARCHAR(255) | NULL | tema issue (opsional) |
| cover_path | VARCHAR(255) | NULL | |
| published_at | TIMESTAMP | NULL | NULL = draft |
| deleted_at | TIMESTAMP | NULL | archive (soft) |
| ts | — | | |

**Index:** UNIQUE(volume, number)

---

## 6. submissions

Naskah dari submit hingga publish. `stage` adalah state machine utama (§7 PRD).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| reference_no | VARCHAR(25) | NOT NULL, UNIQUE | `JOSHNU-2026-0042` |
| author_id | BIGINT UNSIGNED | FK → users.id | penulis utama (submitter) |
| section_id | BIGINT UNSIGNED | FK → sections.id | |
| stage | ENUM('submitted','under_review','decision','revised','rejected','accepted','scheduled','published','withdrawn') | NOT NULL DEFAULT 'submitted' | |
| current_round | TINYINT | DEFAULT 1 | ronde review |
| title | VARCHAR(500) | NOT NULL | denormalisasi utk list/search |
| cover_letter | TEXT | NULL | |
| conflict_of_interest | TEXT | NULL | |
| suggested_reviewers | JSON | NULL | `[{name, email, institution, reason}]` |
| submitted_at | DATETIME | NOT NULL | |
| withdrawn_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | |
| ts | — | | |

**Index:** INDEX(stage), INDEX(author_id), INDEX(section_id), INDEX(submitted_at), FULLTEXT(title)

---

## 7. submission_revisions

Versi naskah (versi 1 = awal; bertambah tiap revisi).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| version | TINYINT | NOT NULL | 1, 2, 3... |
| note | TEXT | NULL | catatan penulis |
| ts | — | | |

**Index:** UNIQUE(submission_id, version)

---

## 8. submission_files

Semua file terpusat (naskah, revisi, review, proof, dll).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| revision_id | BIGINT UNSIGNED | FK → submission_revisions.id, NULL, SET NULL | NULL = file non-revisi |
| type | ENUM('manuscript','cover_letter','figure','table','supplementary','review_response','review_form','proof') | NOT NULL | |
| original_name | VARCHAR(255) | NOT NULL | |
| path | VARCHAR(500) | NOT NULL | storage path |
| mime_type | VARCHAR(100) | NOT NULL | |
| size_bytes | INT UNSIGNED | NOT NULL | |
| uploaded_by | BIGINT UNSIGNED | FK → users.id, NULL | |
| deleted_at | TIMESTAMP | NULL | |
| ts | — | | |

**Index:** INDEX(submission_id, type), INDEX(revision_id)

---

## 9. submission_authors

Penulis multiple (termasuk penulis non-user/eksternal).

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| user_id | BIGINT UNSIGNED | FK → users.id, NULL, SET NULL | NULL = penulis eksternal |
| name | VARCHAR(150) | NOT NULL | |
| email | VARCHAR(190) | NULL | |
| orcid | VARCHAR(19) | NULL | |
| institution | VARCHAR(255) | NULL | |
| is_corresponding | TINYINT(1) | DEFAULT 0 | max 1 per submission |
| display_order | SMALLINT | NOT NULL | |
| ts | — | | |

**Index:** UNIQUE(submission_id, display_order)

---

## 10. reviews

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| reviewer_id | BIGINT UNSIGNED | FK → users.id | |
| round | TINYINT | NOT NULL DEFAULT 1 | |
| status | ENUM('invited','accepted','declined','in_progress','submitted','overdue','cancelled') | NOT NULL DEFAULT 'invited' | |
| invite_token | CHAR(64) | NULL, UNIQUE | utk reviewer tanpa akun |
| decline_reason | TEXT | NULL | |
| recommended_decision | ENUM('accept','minor_revise','major_revise','reject') | NULL | |
| rating_novelty | TINYINT | NULL | 1–5 |
| rating_methodology | TINYINT | NULL | 1–5 |
| rating_clarity | TINYINT | NULL | 1–5 |
| rating_contribution | TINYINT | NULL | 1–5 |
| comments_to_author | TEXT | NULL | |
| comments_to_editor | TEXT | NULL | confidential |
| due_at | DATETIME | NULL | |
| submitted_at | TIMESTAMP | NULL | |
| ts | — | | |

**Index:** UNIQUE(submission_id, reviewer_id, round), INDEX(status), INDEX(due_at), INDEX(invite_token)

---

## 11. decisions

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| editor_id | BIGINT UNSIGNED | FK → users.id | |
| round | TINYINT | NOT NULL | |
| type | ENUM('accept','minor_revise','major_revise','reject','continue_review') | NOT NULL | |
| comment_to_author | TEXT | NOT NULL | |
| internal_note | TEXT | NULL | |
| revision_due_at | DATETIME | NULL | jika revise |
| decided_at | DATETIME | NOT NULL | |
| ts | — | | |

**Index:** INDEX(submission_id, round)

---

## 12. assignments

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| user_id | BIGINT UNSIGNED | FK → users.id | |
| type | ENUM('editor','reviewer') | NOT NULL | |
| assigned_by | BIGINT UNSIGNED | FK → users.id | |
| assigned_at | DATETIME | NOT NULL | |
| completed_at | DATETIME | NULL | |
| ts | — | | |

**Index:** INDEX(submission_id, type), INDEX(user_id, type)

---

## 13. submission_events (audit trail pipeline)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| event | VARCHAR(50) | NOT NULL | mis. `stage_changed`, `review_submitted`, `file_uploaded`, `payment_verified` |
| from_stage | VARCHAR(25) | NULL | |
| to_stage | VARCHAR(25) | NULL | |
| actor_id | BIGINT UNSIGNED | FK → users.id, NULL | NULL = sistem |
| meta | JSON | NULL | payload tambahan |
| created_at | TIMESTAMP | NOT NULL | (hanya created) |

**Index:** INDEX(submission_id, created_at)

---

## 14. payments (APC)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE, UNIQUE | 1 tagihan per naskah |
| invoice_no | VARCHAR(30) | NOT NULL, UNIQUE | `INV-2026-0042` |
| amount | DECIMAL(12,2) | NOT NULL | |
| currency | CHAR(3) | NOT NULL DEFAULT 'IDR' | |
| status | ENUM('pending','proof_uploaded','verified','rejected','paid','refunded','cancelled') | NOT NULL DEFAULT 'pending' | |
| due_at | DATETIME | NULL | |
| payer_name | VARCHAR(150) | NULL | nama pengirim transfer |
| proof_path | VARCHAR(500) | NULL | file bukti |
| rejected_reason | TEXT | NULL | |
| verified_by | BIGINT UNSIGNED | FK → users.id, NULL | |
| verified_at | TIMESTAMP | NULL | |
| gateway | VARCHAR(30) | NULL | (v1.1) 'midtrans'/'xendit' |
| gateway_payment_id | VARCHAR(100) | NULL | (v1.1) |
| webhook_log | JSON | NULL | (v1.1) |
| ts | — | | |

**Index:** UNIQUE(invoice_no), INDEX(status)

---

## 15. publications (article / version of record)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, UNIQUE | 1:1 |
| issue_id | BIGINT UNSIGNED | FK → issues.id, NULL, SET NULL | NULL = online first |
| section_id | BIGINT UNSIGNED | FK → sections.id | |
| slug | VARCHAR(200) | NOT NULL, UNIQUE | `joshnu-v4i1-001` |
| published_at | DATETIME | NULL | |
| is_online_first | TINYINT(1) | DEFAULT 0 | |
| position | SMALLINT | DEFAULT 0 | urutan dalam issue |
| doi | VARCHAR(64) | NULL, UNIQUE | `10.xxxx/joshnu.v4i1.001` |
| doi_status | ENUM('pending','deposited','registered','failed') | DEFAULT 'pending' | |
| doi_response | JSON | NULL | respons Crossref (status/URL) |
| article_status | ENUM('active','corrected','retracted') | DEFAULT 'active' | |
| correction_note | TEXT | NULL | |
| retraction_reason | TEXT | NULL | |
| views | INT UNSIGNED | DEFAULT 0 | counter denormalisasi |
| downloads | INT UNSIGNED | DEFAULT 0 | counter denormalisasi |
| deleted_at | TIMESTAMP | NULL | unpublish (soft) |
| ts | — | | |

**Index:** UNIQUE(slug), UNIQUE(doi), INDEX(issue_id, position), INDEX(published_at)

---

## 16. publication_metadata (multibahasa)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| publication_id | BIGINT UNSIGNED | FK → publications.id, CASCADE | |
| lang | CHAR(2) | NOT NULL | 'id', 'en' |
| title | VARCHAR(500) | NOT NULL | |
| abstract | TEXT | NOT NULL | |
| keywords | JSON | NOT NULL | `["a","b","c"]` |
| ts | — | | |

**Index:** UNIQUE(publication_id, lang); FULLTEXT(title, abstract)

---

## 17. galleys

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| publication_id | BIGINT UNSIGNED | FK → publications.id, CASCADE | |
| type | ENUM('article_pdf','proof','supplementary','poster','jats_xml') | NOT NULL | |
| label | VARCHAR(150) | NULL | |
| path | VARCHAR(500) | NOT NULL | |
| mime_type | VARCHAR(100) | NOT NULL | |
| size_bytes | INT UNSIGNED | NOT NULL | |
| is_version_of_record | TINYINT(1) | DEFAULT 0 | |
| deleted_at | TIMESTAMP | NULL | |
| ts | — | | |

**Index:** INDEX(publication_id, type)

---

## 18. article_usage (raw analytics)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| publication_id | BIGINT UNSIGNED | FK → publications.id, CASCADE | |
| type | ENUM('view','download') | NOT NULL | |
| ip_hash | CHAR(64) | NOT NULL | SHA-256(ip+salt) utk dedup |
| session_hash | CHAR(64) | NULL | |
| country | CHAR(2) | NULL | dari GeoIP (opsional) |
| user_agent | VARCHAR(255) | NULL | |
| created_at | TIMESTAMP | NOT NULL | |

**Index:** INDEX(publication_id, type, created_at), INDEX(ip_hash, created_at) — dedup: 1 user/session terhitung 1x per 24 jam.

---

## 19. announcements

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| title | VARCHAR(255) | NOT NULL | |
| body | TEXT | NOT NULL | |
| type | ENUM('cfp','news','maintenance','other') | DEFAULT 'news' | |
| starts_at | DATETIME | NOT NULL | |
| ends_at | DATETIME | NULL | |
| is_active | TINYINT(1) | DEFAULT 1 | |
| by | BIGINT UNSIGNED | FK → users.id, NULL | |
| deleted_at | TIMESTAMP | NULL | |
| ts | — | | |

**Index:** INDEX(is_active, starts_at)

---

## 20. subscribers (email alert issue baru)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| email | VARCHAR(190) | NOT NULL, UNIQUE | |
| token | CHAR(64) | NOT NULL, UNIQUE | konfirmasi 2-click |
| is_confirmed | TINYINT(1) | DEFAULT 0 | |
| ts | — | | |

---

## 21. notifications (in-app)

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| user_id | BIGINT UNSIGNED | FK → users.id, CASCADE | |
| type | VARCHAR(50) | NOT NULL | mis. `new_submission`, `review_due` |
| data | JSON | NULL | `{submission_id, title, ...}` |
| read_at | TIMESTAMP | NULL | |
| created_at | TIMESTAMP | NOT NULL | |

**Index:** INDEX(user_id, read_at)

---

## 22. messages

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| submission_id | BIGINT UNSIGNED | FK → submissions.id, CASCADE | |
| sender_id | BIGINT UNSIGNED | FK → users.id | |
| recipient_type | ENUM('author','editor','reviewer','internal') | NOT NULL | |
| recipient_id | BIGINT UNSIGNED | FK → users.id, NULL | NULL = semua editor |
| subject | VARCHAR(255) | NULL | |
| body | TEXT | NOT NULL | |
| is_internal | TINYINT(1) | DEFAULT 0 | internal note editor |
| created_at | TIMESTAMP | NOT NULL | |

**Index:** INDEX(submission_id, created_at), INDEX(recipient_id, is_internal)

---

## 23. audit_logs

| Kolom | Tipe | Constraint | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AI | |
| user_id | BIGINT UNSIGNED | FK → users.id, NULL | NULL = sistem |
| action | VARCHAR(100) | NOT NULL | mis. `publication.publish`, `payment.verify` |
| entity_type | VARCHAR(50) | NOT NULL | mis. `Submission`, `Publication` |
| entity_id | BIGINT UNSIGNED | NOT NULL | |
| ip | VARBINARY(16) | NULL | IPv4/IPv6 |
| meta | JSON | NULL | before/after snapshot field sensitif |
| created_at | TIMESTAMP | NOT NULL | |

**Index:** INDEX(entity_type, entity_id), INDEX(user_id, created_at), INDEX(action)

---

## 24. settings_cache (opsional)

Untuk cache konfigurasi jurnal (masthead, theme) agar halaman publik cepat; invalidasi saat admin mengubah setting.

| Kolom | Tipe | Constraint |
|---|---|---|
| key | VARCHAR(100) | PK |
| value | JSON | NOT NULL |
| ts | — | |

---

## Catatan Desain
1. **State machine** `submissions.stage` hanya boleh berubah lewat service `SubmissionWorkflow` (satu-satunya penulisan kolom `stage`), setiap transisi menulis `submission_events` + `audit_logs`.
2. **Blind review**: saat `review_mode = double_blind`, file naskah untuk reviewer disajikan dengan nama penulis di-strip (pandoc `--strip` atau metadata replace) — data asli tetap tersimpan.
3. **Denormalisasi** `submissions.title` & counter `views/downloads` disinkronkan secara transaksional; raw data tetap di `article_usage` untuk report.
4. **UUID** tidak diperlukan (PK bigint + UNIQUE business key seperti `reference_no`, `slug`, `doi` sudah cukup dan lebih efisien untuk MySQL).

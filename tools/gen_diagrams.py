#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generator diagram SVG untuk JOSHNU: ERD + wireframes Figma-ready."""
import os, html

OUT = os.path.join(os.path.dirname(__file__), "..", "lampiran")

def esc(s):
    return html.escape(str(s), quote=True)

class Canvas:
    def __init__(self, w, h, bg="#FFFFFF"):
        self.w, self.h, self.bg = w, h, bg
        self.buf = []
    def r(self, x, y, w, h, fill="none", stroke="none", sw=1, rx=0, dash=None, op=1):
        a = f'x="{x:.0f}" y="{y:.0f}" width="{w:.0f}" height="{h:.0f}"'
        if rx: a += f' rx="{rx}"'
        st = f' stroke="{stroke}" stroke-width="{sw}"' if stroke != "none" else ''
        ds = f' stroke-dasharray="{dash}"' if dash else ''
        op_ = f' opacity="{op}"' if op != 1 else ''
        self.buf.append(f'<rect {a} fill="{fill}"{st}{ds}{op_}/>')
    def p(self, pts, stroke="#94A3B8", sw=1.4, dash=None):
        s = " ".join(f"{x:.0f},{y:.0f}" for x, y in pts)
        ds = f' stroke-dasharray="{dash}"' if dash else ''
        self.buf.append(f'<polyline points="{s}" fill="none" stroke="{stroke}" stroke-width="{sw}"{ds} stroke-linecap="round" stroke-linejoin="round"/>')
    def t(self, x, y, s, size=14, w=400, fill="#111827", anchor="start", fam=None):
        f = f' font-family="{fam}"' if fam else ''
        self.buf.append(f'<text x="{x:.0f}" y="{y:.0f}" font-size="{size}" font-weight="{w}" fill="{fill}" text-anchor="{anchor}"{f}>{esc(s)}</text>')
    def c(self, cx, cy, r, fill="none", stroke="none", sw=1):
        st = f' stroke="{stroke}" stroke-width="{sw}"' if stroke != "none" else ''
        self.buf.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="{r:.0f}" fill="{fill}"{st}/>')
    def line(self, x1, y1, x2, y2, stroke="#E5E7EB", sw=1, dash=None):
        ds = f' stroke-dasharray="{dash}"' if dash else ''
        self.buf.append(f'<line x1="{x1:.0f}" y1="{y1:.0f}" x2="{x2:.0f}" y2="{y2:.0f}" stroke="{stroke}" stroke-width="{sw}"{ds}/>')
    def g(self, gid, fn):
        self.buf.append(f'<g id="{gid}">')
        fn()
        self.buf.append('</g>')
    def save(self, path):
        body = "\n".join(self.buf)
        svg = (f'<?xml version="1.0" encoding="UTF-8"?>\n'
               f'<svg xmlns="http://www.w3.org/2000/svg" width="{self.w}" height="{self.h}" '
               f'viewBox="0 0 {self.w} {self.h}" font-family="Inter, Roboto, \'Segoe UI\', Arial, sans-serif">\n'
               f'<rect width="{self.w}" height="{self.h}" fill="{self.bg}"/>\n{body}\n</svg>\n')
        os.makedirs(os.path.dirname(path), exist_ok=True)
        with open(path, "w") as f:
            f.write(svg)
        print("wrote", path)

# ============================================================
# ERD
# ============================================================
def build_erd():
    COL_W, GAP, X0, Y0, ROW_GAP = 230, 40, 60, 110, 90
    def xcol(c): return X0 + c * (COL_W + GAP)

    # (name, row, col, span, [(col, type, tag)])
    T = {
      "users": (1, 0, 2, [
        ("id","BIGINT UNSIGNED","PK"),("name","VARCHAR(150)",""),("email","VARCHAR(190)","UK"),
        ("email_verified_at","TIMESTAMP",""),("password","VARCHAR(255)",""),("orcid","VARCHAR(19)","UK"),
        ("institution","VARCHAR(255)",""),("expertise","TEXT",""),("address","TEXT",""),("phone","VARCHAR(30)",""),
        ("website","VARCHAR(255)",""),("is_active","TINYINT(1)",""),("two_factor_secret","TEXT",""),
        ("two_factor_confirmed_at","TIMESTAMP",""),("remember_token","VARCHAR(100)",""),("deleted_at","TIMESTAMP","")]),
      "submissions": (1, 2, 1, [
        ("id","BIGINT UNSIGNED","PK"),("reference_no","VARCHAR(25)","UK"),("author_id","BIGINT UNSIGNED","FK"),
        ("section_id","BIGINT UNSIGNED","FK"),("stage","ENUM(9)",""),("current_round","TINYINT",""),
        ("title","VARCHAR(500)","FT"),("cover_letter","TEXT",""),("conflict_of_interest","TEXT",""),
        ("suggested_reviewers","JSON",""),("submitted_at","DATETIME",""),("withdrawn_at","TIMESTAMP",""),
        ("deleted_at","TIMESTAMP","")]),
      "sections": (1, 3, 1, [
        ("id","BIGINT UNSIGNED","PK"),("name","VARCHAR(150)",""),("slug","VARCHAR(170)","UK"),
        ("description","TEXT",""),("sort_order","SMALLINT",""),("is_active","TINYINT(1)","")]),
      "issues": (1, 4, 1, [
        ("id","BIGINT UNSIGNED","PK"),("volume","SMALLINT","UK*"),("number","SMALLINT","UK*"),("year","SMALLINT",""),
        ("title","VARCHAR(255)",""),("cover_path","VARCHAR(255)",""),("published_at","TIMESTAMP",""),
        ("deleted_at","TIMESTAMP","")]),
      "journal_settings": (1, 5, 2, [
        ("id","BIGINT UNSIGNED","PK"),("name","VARCHAR(255)",""),("short_name","VARCHAR(50)",""),("tagline","VARCHAR(255)",""),
        ("issn_print","VARCHAR(20)",""),("issn_online","VARCHAR(20)",""),("scope","TEXT",""),("description","LONGTEXT",""),
        ("ethics_policy","LONGTEXT",""),("oa_policy","LONGTEXT",""),("apc_policy","LONGTEXT",""),("editorial_board","JSON",""),
        ("apc_enabled","TINYINT(1)",""),("apc_amount","DECIMAL(12,2)",""),("apc_currency","CHAR(3)",""),("apc_bank","JSON",""),
        ("review_mode","ENUM(3)",""),("max_review_rounds","TINYINT",""),("review_deadline_days","TINYINT",""),
        ("reminder_before_days","TINYINT",""),("online_first_enabled","TINYINT(1)",""),("doi_prefix","VARCHAR(40)",""),
        ("doi_suffix_pattern","VARCHAR(60)",""),("theme","JSON",""),("contact_email","VARCHAR(190)",""),("address","TEXT",""),
        ("social","JSON",""),("created_at/updated_at","TIMESTAMP","")]),
      "user_roles": (2, 0, 1, [
        ("id","BIGINT UNSIGNED","PK"),("user_id","BIGINT UNSIGNED","FK"),("role","ENUM(6)","UK*"),("created_at","TIMESTAMP","")]),
      "submission_authors": (2, 1, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("user_id","BIGINT UNSIGNED","FK"),
        ("name","VARCHAR(150)",""),("email","VARCHAR(190)",""),("orcid","VARCHAR(19)",""),
        ("institution","VARCHAR(255)",""),("is_corresponding","TINYINT(1)",""),("display_order","SMALLINT","UK*")]),
      "submission_files": (2, 2, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("revision_id","BIGINT UNSIGNED","FK"),
        ("type","ENUM(8)",""),("original_name","VARCHAR(255)",""),("path","VARCHAR(500)",""),
        ("mime_type","VARCHAR(100)",""),("size_bytes","INT UNSIGNED",""),("uploaded_by","BIGINT UNSIGNED","FK"),
        ("deleted_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "submission_revisions": (2, 3, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("version","TINYINT","UK*"),
        ("note","TEXT",""),("created_at","TIMESTAMP","")]),
      "reviews": (2, 4, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("reviewer_id","BIGINT UNSIGNED","FK"),
        ("round","TINYINT","UK*"),("status","ENUM(7)",""),("invite_token","CHAR(64)","UK"),
        ("decline_reason","TEXT",""),("recommended_decision","ENUM(4)",""),
        ("rating_novelty","TINYINT",""),("rating_methodology","TINYINT",""),("rating_clarity","TINYINT",""),
        ("rating_contribution","TINYINT",""),("comments_to_author","TEXT",""),("comments_to_editor","TEXT",""),
        ("due_at","DATETIME",""),("submitted_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "decisions": (2, 5, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("editor_id","BIGINT UNSIGNED","FK"),
        ("round","TINYINT",""),("type","ENUM(5)",""),("comment_to_author","TEXT",""),
        ("internal_note","TEXT",""),("revision_due_at","DATETIME",""),("decided_at","DATETIME",""),
        ("created_at","TIMESTAMP","")]),
      "payments": (2, 6, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("invoice_no","VARCHAR(30)","UK"),
        ("amount","DECIMAL(12,2)",""),("currency","CHAR(3)",""),("status","ENUM(7)",""),
        ("due_at","DATETIME",""),("payer_name","VARCHAR(150)",""),("proof_path","VARCHAR(500)",""),
        ("rejected_reason","TEXT",""),("verified_by","BIGINT UNSIGNED","FK"),("verified_at","TIMESTAMP",""),
        ("gateway","VARCHAR(30)",""),("gateway_payment_id","VARCHAR(100)",""),
        ("webhook_log","JSON",""),("created_at","TIMESTAMP","")]),
      "messages": (2, 7, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("sender_id","BIGINT UNSIGNED","FK"),
        ("recipient_type","ENUM(4)",""),("recipient_id","BIGINT UNSIGNED","FK"),("subject","VARCHAR(255)",""),
        ("body","TEXT",""),("is_internal","TINYINT(1)",""),("created_at","TIMESTAMP","")]),
      "notifications": (3, 0, 1, [
        ("id","BIGINT UNSIGNED","PK"),("user_id","BIGINT UNSIGNED","FK"),("type","VARCHAR(50)",""),
        ("data","JSON",""),("read_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "audit_logs": (3, 1, 1, [
        ("id","BIGINT UNSIGNED","PK"),("user_id","BIGINT UNSIGNED","FK"),("action","VARCHAR(100)",""),
        ("entity_type","VARCHAR(50)",""),("entity_id","BIGINT UNSIGNED",""),("ip","VARBINARY(16)",""),
        ("meta","JSON",""),("created_at","TIMESTAMP","")]),
      "submission_events": (3, 2, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("event","VARCHAR(50)",""),
        ("from_stage","VARCHAR(25)",""),("to_stage","VARCHAR(25)",""),("actor_id","BIGINT UNSIGNED","FK"),
        ("meta","JSON",""),("created_at","TIMESTAMP","")]),
      "assignments": (3, 3, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("user_id","BIGINT UNSIGNED","FK"),
        ("type","ENUM(2)",""),("assigned_by","BIGINT UNSIGNED","FK"),("assigned_at","DATETIME",""),
        ("completed_at","DATETIME",""),("created_at","TIMESTAMP","")]),
      "publications": (3, 4, 1, [
        ("id","BIGINT UNSIGNED","PK"),("submission_id","BIGINT UNSIGNED","FK"),("issue_id","BIGINT UNSIGNED","FK"),
        ("section_id","BIGINT UNSIGNED","FK"),("slug","VARCHAR(200)","UK"),("published_at","DATETIME",""),
        ("is_online_first","TINYINT(1)",""),("position","SMALLINT",""),("doi","VARCHAR(64)","UK"),
        ("doi_status","ENUM(4)",""),("doi_response","JSON",""),("article_status","ENUM(3)",""),
        ("correction_note","TEXT",""),("retraction_reason","TEXT",""),("views","INT UNSIGNED",""),
        ("downloads","INT UNSIGNED",""),("deleted_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "announcements": (4, 0, 1, [
        ("id","BIGINT UNSIGNED","PK"),("title","VARCHAR(255)",""),("body","TEXT",""),("type","ENUM(4)",""),
        ("starts_at","DATETIME",""),("ends_at","DATETIME",""),("is_active","TINYINT(1)",""),
        ("created_by","BIGINT UNSIGNED","FK"),("deleted_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "subscribers": (4, 1, 1, [
        ("id","BIGINT UNSIGNED","PK"),("email","VARCHAR(190)","UK"),("token","CHAR(64)","UK"),
        ("is_confirmed","TINYINT(1)",""),("created_at","TIMESTAMP","")]),
      "settings_cache": (4, 2, 1, [
        ("key","VARCHAR(100)","PK"),("value","JSON",""),("updated_at","TIMESTAMP","")]),
      "publication_metadata": (4, 4, 1, [
        ("id","BIGINT UNSIGNED","PK"),("publication_id","BIGINT UNSIGNED","FK"),("lang","CHAR(2)","UK*"),
        ("title","VARCHAR(500)","FT"),("abstract","TEXT","FT"),("keywords","JSON","FT"),
        ("created_at","TIMESTAMP","")]),
      "galleys": (4, 5, 1, [
        ("id","BIGINT UNSIGNED","PK"),("publication_id","BIGINT UNSIGNED","FK"),("type","ENUM(5)",""),
        ("label","VARCHAR(150)",""),("path","VARCHAR(500)",""),("mime_type","VARCHAR(100)",""),
        ("size_bytes","INT UNSIGNED",""),("is_version_of_record","TINYINT(1)",""),
        ("deleted_at","TIMESTAMP",""),("created_at","TIMESTAMP","")]),
      "article_usage": (4, 6, 1, [
        ("id","BIGINT UNSIGNED","PK"),("publication_id","BIGINT UNSIGNED","FK"),("type","ENUM(2)",""),
        ("ip_hash","CHAR(64)",""),("session_hash","CHAR(64)",""),("country","CHAR(2)",""),
        ("user_agent","VARCHAR(255)",""),("created_at","TIMESTAMP","")]),
    }

    # geometri kotak
    HEAD, RH, PAD = 34, 24, 10
    geo = {}
    for name, (row, col, span, cols) in T.items():
        w = COL_W * span + (span - 1) * GAP if span > 1 else COL_W  # wide = 500
        if span > 1:
            nrows = (len(cols) + 1) // 2
        else:
            nrows = len(cols)
        h = HEAD + nrows * RH + PAD
        geo[name] = dict(x=xcol(col), y=None, w=w, h=h, row=row, col=col, span=span, cols=cols)
    # tinggi baris
    rowH = {}
    for name, g in geo.items():
        rowH[g["row"]] = max(rowH.get(g["row"], 0), g["h"])
    y = Y0
    for r in sorted(rowH):
        ry = y
        for name, g in geo.items():
            if g["row"] == r: g["y"] = ry
        y = ry + rowH[r] + ROW_GAP
    # posisi baris: 1:90  2:560  3:1116  4:1658 (dihitung otomatis)

    # ---- geometri relasi (dihitung dari posisi baris) ----
    Y1 = geo["users"]["y"]
    U_B = Y1 + geo["users"]["h"]            # bottom users
    S_B = Y1 + geo["submissions"]["h"]      # bottom submissions
    SE_B = Y1 + geo["sections"]["h"]        # bottom sections
    IS_B = Y1 + geo["issues"]["h"]          # bottom issues
    R2T = geo["user_roles"]["y"]
    R3T = geo["notifications"]["y"]
    R4T = geo["announcements"]["y"]
    P_B = R3T + geo["publications"]["h"]    # bottom publications
    NOT_M = R3T + geo["notifications"]["h"] // 2
    F_M = R2T + geo["submission_files"]["h"] // 2
    U_M = Y1 + geo["users"]["h"] // 2        # tengah users
    SE_M = Y1 + geo["sections"]["h"] // 2    # tengah sections
    IS_M = Y1 + geo["issues"]["h"] // 2      # tengah issues
    B1 = [Y1 + rowH[1], R2T]                  # band antar row1-row2
    B2 = [R2T + rowH[2], R3T]                 # band antar row2-row3
    B3 = [P_B, R4T]                           # band antar row3-row4
    b1y = [B1[0] + 8 + i * 7 for i in range(12)]   # 12 slot garis fan
    b2y = [B2[0] + 8 + i * 10 for i in range(8)]
    b3y = [B3[0] + 10 + i * 12 for i in range(6)]

    W = 2320
    H = int(geo["announcements"]["y"] + rowH[4] + 66)

    cv = Canvas(W, H)

    # judul & legend
    cv.t(60, 44, "JOSHNU — Entity Relationship Diagram (24 tabel, MySQL 8 · InnoDB · utf8mb4)", 22, 700, "#111827")
    cv.t(60, 66, "Single-jurnal · Laravel 12 · relasi: 1 (parent) → N (child) · badge: PK = primary key, UK = unique, FK = foreign key, FT = FULLTEXT", 12, 400, "#6B7280")
    cv.t(60, 84, "Catatan: FK sekunder (uploaded_by, verified_by, assigned_by, recipient_id, created_by) disederhanakan — detail lengkap: B-ERD.md", 11.5, 400, "#94A3B8")

    def draw_box(name):
        g = geo[name]
        def fn():
            x, y0, w, h = g["x"], g["y"], g["w"], g["h"]
            cv.r(x, y0, w, h, fill="#FFFFFF", stroke="#CBD5E1", sw=1.2, rx=6)
            cv.r(x, y0, w, HEAD, fill="#1B6E5A", rx=6)
            cv.r(x, y0 + HEAD - 12, w, 12, fill="#1B6E5A")  # persegi bagian bawah header
            cv.t(x + 12, y0 + 22, name, 13.5, 700, "#FFFFFF")
            cv.t(x + w - 12, y0 + 22, f"{len(g['cols'])} kolom" + (" · 2 blok" if g["span"] > 1 else ""), 10, 400, "#D1FAE5", "end")
            tagc = {"PK": "#B45309", "UK": "#7C3AED", "UK*": "#7C3AED", "FK": "#2563EB", "FT": "#0D9488"}
            cols = g["cols"]
            if g["span"] > 1:
                half = (len(cols) + 1) // 2
                blocks = [cols[:half], cols[half:]]
                bw = (w - 10) // 2
                offx = [0, bw + 10]
            else:
                blocks = [cols]
                bw = w - 24
                offx = [0]
            for bi, block in enumerate(blocks):
                for i, (cn, ct, tag) in enumerate(block):
                    yy = y0 + HEAD + 17 + i * RH
                    cv.line(x + offx[bi] + 8, yy + 7, x + offx[bi] + bw - 6, yy + 7, stroke="#F1F5F9", sw=1) if (i > 0) else None
                    if tag:
                        cv.t(x + offx[bi] + 12, yy, tag.replace("*", ""), 8.5, 700, tagc[tag])
                    bold = 700 if tag == "PK" else 400
                    cv.t(x + offx[bi] + 34, yy, cn, 11, bold, "#111827")
                    cv.t(x + offx[bi] + bw - 12, yy, ct, 9.5, 400, "#94A3B8", "end")
        cv.g(name, fn)

    for name in T:
        draw_box(name)

    # ---- relasi ----
    def rel(pts, label, lx, ly, one_at, many_at, one_is_one=False):
        cv.p(pts, stroke="#94A3B8", sw=1.3)
        if label:
            cv.t(lx, ly, label, 10, 400, "#64748B", "middle")
        if one_at:
            cv.t(one_at[0], one_at[1], "1", 10.5, 700, "#475569", "middle")
        cv.t(many_at[0], many_at[1], "1" if one_is_one else "N", 10.5, 700, "#475569", "middle")

    G = geo
    # band1: 12 slot garis fan (6px) · band2 · band3 — semua y dihitung
    # row1 — relasi dari users & submissions (label "1" di parent dihilangkan utk garis fan agar rapi)
    rel([(175, U_B), (175, R2T)], "user_id", 150, (U_B + R2T) // 2, (188, U_B + 18), (188, R2T - 14))
    rel([(560, U_M), (600, U_M)], "author_id", 580, U_M - 8, (567, U_M - 10), (593, U_M - 10))
    rel([(350, U_B), (350, b1y[10]), (425, b1y[10]), (425, R2T)], "user_id", 388, b1y[10] - 6, None, (413, R2T - 14))
    rel([(500, U_B), (500, b1y[2]), (1230, b1y[2]), (1230, R2T)], "reviewer_id", 860, b1y[2] - 6, None, (1218, R2T - 14))
    rel([(470, U_B), (470, b1y[3]), (1500, b1y[3]), (1500, R2T)], "editor_id", 990, b1y[3] - 6, None, (1488, R2T - 14))
    rel([(540, U_B), (540, b1y[5]), (2040, b1y[5]), (2040, R2T)], "sender / recipient", 1290, b1y[5] - 6, None, (2028, R2T - 14))
    rel([(620, S_B), (620, b1y[7]), (465, b1y[7]), (465, R2T)], "submission_id", 542, b1y[7] - 6, None, (453, R2T - 14))
    rel([(715, S_B), (715, R2T)], "submission_id", 762, (S_B + R2T) // 2, (727, S_B + 16), (727, R2T - 14))
    rel([(800, S_B), (800, b1y[1]), (985, b1y[1]), (985, R2T)], "submission_id", 892, b1y[1] - 6, None, (973, R2T - 14))
    rel([(790, S_B), (790, b1y[0]), (1280, b1y[0]), (1280, R2T)], "submission_id", 1035, b1y[0] - 6, None, (1268, R2T - 14))
    rel([(780, S_B), (780, b1y[4]), (1550, b1y[4]), (1550, R2T)], "submission_id", 1165, b1y[4] - 6, None, (1538, R2T - 14))
    rel([(820, S_B), (820, b1y[6]), (1770, b1y[6]), (1770, R2T)], "submission_id", 1295, b1y[6] - 6, None, (1758, R2T - 14))
    # users lanjutan
    rel([(310, U_B), (302, U_B), (302, b2y[0]), (445, b2y[0]), (445, R3T)], "user_id", 250, 700, None, (433, R3T - 18))
    rel([(60, Y1 + 160), (30, Y1 + 160), (30, NOT_M), (60, NOT_M)], "user_id", 46, 740, (54, Y1 + 172), (46, NOT_M - 10))
    # row1 antar tabel
    rel([(870, SE_M), (830, SE_M)], "section_id", 850, SE_M - 8, (864, SE_M - 10), (836, SE_M - 10))
    # submissions → row3 (via lane antar kolom)
    rel([(610, S_B), (610, b1y[9]), (318, b1y[9]), (318, b2y[1]), (715, b2y[1]), (715, R3T)], "submission_id", 460, b1y[9] - 6, None, (703, R3T - 18))
    rel([(845, S_B), (845, b1y[11]), (850, b1y[11]), (850, b2y[2]), (985, b2y[2]), (985, R3T)], "submission_id", 915, b2y[2] - 6, None, (973, R3T - 18))
    rel([(825, S_B), (825, b1y[8]), (1120, b1y[8]), (1120, b2y[3]), (1235, b2y[3]), (1235, R3T)], "submission_id (1:1)", 975, b1y[8] - 6, None, (1223, R3T - 18), one_is_one=True)
    # issues & sections → publications
    rel([(1370, IS_M), (1390, IS_M), (1390, b2y[4]), (1280, b2y[4]), (1280, R3T)], "issue_id", 1398, 700, (1376, IS_M + 12), (1268, R3T - 18))
    rel([(1098, SE_B), (1110, SE_B), (1110, R3T + 40), (1140, R3T + 40)], "section_id", 1100, 700, (1090, SE_B + 12), (1128, R3T + 32))
    # row2 antar tabel
    rel([(870, F_M), (830, F_M)], "revision_id", 850, F_M - 8, (864, F_M - 10), (836, F_M - 10))
    # publications → row4
    rel([(1255, P_B), (1255, R4T)], "publication_id", 1298, (P_B + R4T) // 2, (1267, P_B + 16), (1267, R4T - 12))
    rel([(1290, P_B), (1290, b3y[0]), (1525, b3y[0]), (1525, R4T)], "publication_id", 1407, b3y[0] - 6, (1302, P_B + 14), (1513, R4T - 12))
    rel([(1320, P_B), (1320, b3y[1]), (1795, b3y[1]), (1795, R4T)], "publication_id", 1557, b3y[1] - 6, (1332, P_B + 14), (1783, R4T - 12))

    cv.save(os.path.join(OUT, "erd", "ERD.svg"))

# ============================================================
# WIREFRAMES
# ============================================================
PRIM = "#1B6E5A"; PRIM_D = "#145244"; PRIM_L = "#E6F2EF"
TXT = "#111827"; MUT = "#6B7280"; BORD = "#E5E7EB"; BG = "#F5F6F8"
WARN = "#FFFBEB"; WARN_B = "#F59E0B"; WARN_T = "#92400E"

def header(cv, w=1440, active=None, user_label=""):
    def fn():
        cv.r(0, 0, w, 64, fill="#FFFFFF")
        cv.c(36, 32, 16, fill=PRIM)
        cv.t(40, 37, "J", 14, 700, "#FFFFFF", "middle")
        cv.t(64, 30, "JOSHNU", 17, 700, TXT)
        cv.t(64, 46, "Journal of Sustainable Health and New Urbanism", 10, 400, MUT)
        cv.t(1188, 38, "ID ▾", 12.5, 500, MUT)
        cv.r(1232, 16, 28, 28, fill="none", stroke=BORD, rx=6)
        cv.c(1244, 28, 5.5, fill="none", stroke=MUT, sw=1.4)
        cv.line(1248, 32, 1252, 36, stroke=MUT, sw=1.4)
        cv.r(1276, 14, 62, 36, fill="none", stroke=BORD, rx=8)
        cv.t(1307, 36, "Login", 13, 500, TXT, "middle")
        cv.r(1350, 14, 78, 36, fill=PRIM, rx=8)
        cv.t(1389, 36, "Submit", 13, 600, "#FFFFFF", "middle")
        cv.line(0, 64, w, 64, stroke=BORD, sw=1)
        links = ["Home", "About", "Current Issue", "Archive", "Announcements"]
        x = 80
        for L in links:
            on = (L == active)
            cv.t(x, 94, L, 13.5, 600 if on else 500, PRIM if on else MUT)
            if on:
                cv.line(x - 2, 102, x + len(L) * 7.2 + 4, 102, stroke=PRIM, sw=2.5)
            x += len(L) * 7.4 + 34
        cv.line(0, 104, w, 104, stroke=BORD, sw=1)
    cv.g("header", fn)

def footer(cv, w=1440, y=920):
    def fn():
        cv.r(0, y, w, 100, fill="#0F172A")
        cv.t(80, y + 40, "ISSN e-2XXX-XXXX  ·  © 2026 JOSHNU  ·  Open Access CC BY 4.0", 11.5, 400, "#94A3B8")
        cv.t(80, y + 64, "Editorial Board    |    Policies (Ethics · Open Access · APC)    |    Contact    |    Sitemap", 11.5, 400, "#CBD5E1")
        cv.r(880, y + 26, 480, 40, fill="#1E293B", rx=8)
        cv.t(896, y + 50, "Berlangganan alert issue baru:  email@contoh.ac.id", 11.5, 400, "#CBD5E1")
        cv.r(1304, y + 26, 56, 40, fill=PRIM, rx=8)
        cv.t(1332, y + 50, "OK", 12, 600, "#FFFFFF", "middle")
    cv.g("footer", fn)

def button(cv, x, y, w, label, kind="primary", h=40):
    if kind == "primary":
        cv.r(x, y, w, h, fill=PRIM, rx=8)
        cv.t(x + w / 2, y + h / 2 + 4.5, label, 13, 600, "#FFFFFF", "middle")
    elif kind == "outline":
        cv.r(x, y, w, h, fill="#FFFFFF", stroke=PRIM, sw=1.4, rx=8)
        cv.t(x + w / 2, y + h / 2 + 4.5, label, 13, 600, PRIM, "middle")
    else:
        cv.r(x, y, w, h, fill="#FFFFFF", stroke=BORD, sw=1.2, rx=8)
        cv.t(x + w / 2, y + h / 2 + 4.5, label, 13, 500, TXT, "middle")

def chip(cv, x, y, text, fill=PRIM_L, color=PRIM_D, w=None, h=24):
    w = w or len(text) * 6.6 + 18
    cv.r(x, y, w, h, fill=fill, rx=12)
    cv.t(x + w / 2, y + 16, text, 11, 600, color, "middle")
    return w

def input_box(cv, x, y, w, h=40, ph="", value=""):
    cv.r(x, y, w, h, fill="#FFFFFF", stroke=BORD, sw=1.2, rx=8)
    s = value or ph
    cv.t(x + 12, y + 25, s, 13, 400, TXT if value else MUT)

def section_title(cv, x, y, s, link=""):
    cv.t(x, y, s, 15, 700, TXT)
    if link:
        cv.t(x + 700, y, link, 12.5, 500, PRIM, "end")

def wire_landing():
    cv = Canvas(1440, 1020, BG)
    header(cv, active="Home")

    def hero():
        cv.r(0, 112, 1440, 224, fill=PRIM)
        cv.r(1050, 112, 390, 224, fill=PRIM_D, op=0.55)
        cv.t(80, 178, "e-Journal · Sustainable Health & New Urbanism", 12, 500, "#A7F3D0")
        cv.t(80, 214, "Journal of Sustainable Health", 30, 700, "#FFFFFF")
        cv.t(80, 250, "and New Urbanism", 30, 700, "#FFFFFF")
        cv.t(80, 284, "Menerbitkan riset berkualitas di bidang kesehatan berkelanjutan dan perkotaan — Open Access, CC BY 4.0.", 13.5, 400, "#D1FAE5")
        button(cv, 80, 300, 170, "Submit Naskah", "primary")
        cv.r(262, 300, 190, 40, fill="none", stroke="#FFFFFF", sw=1.4, rx=8)
        cv.t(357, 324.5, "Baca Issue Terbaru", 13, 600, "#FFFFFF", "middle")
    cv.g("hero", hero)

    def ann():
        cv.r(80, 360, 800, 208, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(104, 392, "ANNOUNCEMENTS", 12.5, 700, MUT)
        items = [("CFP Vol 4 No 2 (2026) — tema: Urban Heat & Kesehatan", "28 Nov"),
                 ("Issue Vol 3 No 2 telah terbit — 6 artikel", "15 Okt"),
                 ("Maintenance sistem: 01 Des 2026 02.00–04.00 WIB", "20 Nov")]
        y = 424
        for t, d in items:
            cv.c(110, y - 4, 3.5, fill=PRIM)
            cv.t(126, y, t, 13, 500, TXT)
            cv.t(700, y, d, 11, 400, MUT)
            cv.t(760, y, "Baca →", 11.5, 500, PRIM)
            y += 42
    cv.g("card-announcements", ann)

    def about():
        cv.r(904, 360, 456, 208, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(928, 392, "ABOUT THE JOURNAL", 12.5, 700, MUT)
        cv.t(928, 424, "JOSHNU menerbitkan artikel orisinal di bidang", 13, 400, TXT)
        cv.t(928, 444, "kesehatan berkelanjutan dan new urbanism,", 13, 400, TXT)
        cv.t(928, 464, "terindeks SINTA, Crossref, DOAJ.", 13, 400, TXT)
        cv.t(928, 500, "ISSN e-2XXX-XXXX  ·  APC: Rp 750.000", 11.5, 400, MUT)
        cv.t(928, 540, "Selengkapnya →", 12.5, 600, PRIM)
    cv.g("card-about", about)

    def latest():
        cv.t(80, 616, "ARTIKEL TERBARU", 15, 700, TXT)
        cv.t(780, 616, "Lihat semua →", 12.5, 500, PRIM, "end")
        titles = [("Taman Kota dan Kesehatan Mental: Studi Longitudinal", "A. Budi, R. Sari (2026)"),
                  ("Walkability dan Aktivitas Fisik Lansia di Kota Kompak", "D. Pratama, dkk. (2026)"),
                  ("Green Roof sebagai Mitigasi Urban Heat Island", "S. Wibowo (2026)"),
                  ("Partisipasi Warga dalam Desain Blok Komunal", "L. Manurung, T. Anwar (2026)")]
        x = 80
        for i, (t, a) in enumerate(titles):
            cv.r(x, 636, 316, 184, fill="#FFFFFF", stroke=BORD, rx=10)
            cv.r(x + 16, 652, 64, 84, fill="#E2E8F0", rx=6)
            cv.t(x + 48, 700, "PDF", 10, 600, MUT, "middle")
            cv.t(x + 92, 668, t[:34], 12.5, 600, TXT)
            cv.t(x + 92, 686, t[34:] if len(t) > 34 else " ", 12.5, 600, TXT)
            cv.t(x + 92, 712, a, 11, 400, MUT)
            cv.r(x + 16, 760, 44, 22, fill=PRIM_L, rx=5)
            cv.t(x + 38, 775, "PDF", 10, 600, PRIM_D, "middle")
            cv.r(x + 68, 760, 58, 22, fill="#F1F5F9", rx=5)
            cv.t(x + 97, 775, "DOI", 10, 600, MUT, "middle")
            cv.t(x + 296, 775, "👁 214", 10.5, 400, MUT, "end")
            x += 326
    cv.g("section-latest-articles", latest)

    footer(cv, y=920)
    cv.save(os.path.join(OUT, "wireframes", "svg", "01-landing.svg"))

def wire_article():
    cv = Canvas(1440, 1100, BG)
    header(cv, active="Archive")

    def main():
        cv.t(80, 134, "Archive  ›  Vol 4 No 1 (2026)  ›  joshnu-v4i1-001", 11.5, 400, MUT)
        chip(cv, 80, 150, "PUBLISHED", PRIM_L, PRIM_D, w=96)
        cv.t(192, 168, "Online First", 11, 400, MUT)
        cv.t(80, 214, "Taman Kota dan Kesehatan Mental: Studi Longitudinal", 25, 700, TXT)
        cv.t(80, 246, "di Kota Menengah Indonesia", 25, 700, TXT)
        cv.t(80, 282, "A. Budi¹*    R. Sari²    D. Pratama¹", 14, 500, TXT)
        cv.t(80, 302, "¹Fakultas Kesehatan Masyarakat, Univ. Contoh    ²Program Studi Arsitektur, Inst. Teknologi Contoh    *Korespondensi: a.budi@univ.ac.id", 11.5, 400, MUT)
        cv.t(80, 332, "Received: 01 Sep 2025    ·    Reviewed: 20 Okt 2025    ·    Accepted: 15 Des 2025    ·    Published: 01 Jan 2026", 11.5, 400, MUT)
        cv.r(80, 344, 250, 26, fill="#F1F5F9", rx=13)
        cv.t(94, 361, "DOI", 10.5, 700, MUT)
        cv.t(118, 361, "10.xxxx/joshnu.v4i1.001", 11.5, 500, PRIM)
        # abstract
        cv.r(80, 392, 1280, 150, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(104, 422, "ABSTRACT", 12.5, 700, MUT)
        cv.r(200, 404, 34, 24, fill=PRIM, rx=6)
        cv.t(217, 420, "ID", 11, 600, "#FFFFFF", "middle")
        cv.r(240, 404, 34, 24, fill="#FFFFFF", stroke=BORD, rx=6)
        cv.t(257, 420, "EN", 11, 600, MUT, "middle")
        cv.t(104, 452, "Penelitian ini mengkaji hubungan antara frekuensi penggunaan taman kota dengan indeks kesehatan mental warga", 13, 400, TXT)
        cv.t(104, 472, "kota menengah menggunakan desain longitudinal selama 12 bulan (n=1.240). Hasil menunjukkan...", 13, 400, TXT)
        cv.t(104, 492, "...", 13, 400, MUT)
        cv.t(104, 522, "Keywords:", 12, 700, TXT)
        for i, k in enumerate(["taman kota", "kesehatan mental", "urban design"]):
            chip(cv, 172 + i * 128, 508, k, PRIM_L, PRIM_D)
    cv.g("article-header", main)

    def galley():
        cv.r(80, 562, 760, 216, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(104, 592, "GALLEY & DOKUMEN", 12.5, 700, MUT)
        rows = [("⬇  PDF — Version of Record", "1.2 MB", True), ("⬇  PDF Proof (ber-watermark)", "1.1 MB", False),
                ("⬇  Supplementary Data (2 file)", "8.4 MB", False), ("⬇  JATS XML", "184 KB", False)]
        y = 616
        for s, sz, hot in rows:
            if hot:
                cv.r(104, y - 4, 560, 34, fill=PRIM, rx=8)
                cv.t(120, y + 17, s, 12.5, 600, "#FFFFFF")
                cv.t(650, y + 17, sz, 11, 500, "#D1FAE5", "end")
            else:
                cv.t(120, y + 14, s, 12.5, 500, TXT)
                cv.t(650, y + 14, sz, 11, 400, MUT, "end")
                cv.line(104, y + 26, 664, y + 26, stroke="#F1F5F9")
            y += 38
        cv.t(104, y + 22, "Views: 214    ·    Downloads: 57", 12, 500, MUT)
    cv.g("panel-galley", galley)

    def cite():
        cv.r(864, 562, 496, 216, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(888, 592, "CITATION & SHARE", 12.5, 700, MUT)
        cv.r(1180, 576, 60, 28, fill="#FFFFFF", stroke=BORD, rx=6)
        cv.t(1210, 594, "APA ▾", 11.5, 500, TXT, "middle")
        cv.r(888, 612, 448, 74, fill="#F8FAFC", stroke="#E2E8F0", rx=8)
        cv.t(902, 634, "Budi, A., Sari, R., & Pratama, D. (2026). Taman kota dan", 11.5, 400, TXT, fam="Menlo, monospace")
        cv.t(902, 652, "kesehatan mental: studi longitudinal. JOSHNU, 4(1).", 11.5, 400, TXT, fam="Menlo, monospace")
        cv.t(902, 670, "https://doi.org/10.xxxx/joshnu.v4i1.001", 11.5, 400, PRIM, fam="Menlo, monospace")
        bx = 888
        for b in ["Copy", "BibTeX", "RIS", "EndNote"]:
            w = len(b) * 6.4 + 20
            cv.r(bx, 700, w, 28, fill="#FFFFFF", stroke=BORD, rx=6)
            cv.t(bx + w / 2, 718, b, 11.5, 500, TXT, "middle")
            bx += w + 8
        cv.r(1240, 690, 56, 56, fill="#FFFFFF", stroke=BORD, rx=6)
        cv.t(1268, 724, "▣", 16, 400, MUT, "middle")
        cv.t(888, 764, "Share:   X    Facebook    WhatsApp    Email    ⧉", 12, 400, MUT)
    cv.g("panel-citation", cite)

    def related():
        cv.t(80, 826, "RELATED ARTICLES (by keyword / author)", 13, 700, TXT)
        x = 80
        for t in ["Partisipasi Warga dalam Desain Blok Komunal", "Urban Heat Island dan Morbiditas Jantung", "Walkability dan Aktivitas Fisik Lansia"]:
            cv.r(x, 846, 416, 148, fill="#FFFFFF", stroke=BORD, rx=10)
            cv.t(x + 20, 878, t[:38], 12.5, 600, TXT)
            if len(t) > 38: cv.t(x + 20, 896, t[38:], 12.5, 600, TXT)
            cv.t(x + 20, 922, "2026 · Public Health", 11, 400, MUT)
            cv.t(x + 20, 966, "Baca →", 12, 600, PRIM)
            x += 432
    cv.g("section-related", related)
    footer(cv, y=1000)
    cv.save(os.path.join(OUT, "wireframes", "svg", "02-article-page.svg"))

def wire_dashboard():
    cv = Canvas(1440, 940, BG)

    def sidebar():
        cv.r(0, 0, 240, 940, fill="#0F172A")
        cv.c(28, 34, 16, fill=PRIM)
        cv.t(32, 39, "J", 14, 700, "#FFFFFF", "middle")
        cv.t(54, 40, "JOSHNU", 16, 700, "#FFFFFF")
        items = [("Dashboard", True), ("Submissions (12)", False), ("Reviewers (48)", False), ("Issues (7)", False), ("Messages (2)", False)]
        y = 96
        for it, on in items:
            if on:
                cv.r(12, y - 20, 216, 36, fill=PRIM, rx=8)
            cv.t(28, y + 4, it, 13, 600 if on else 400, "#FFFFFF" if on else "#94A3B8")
            y += 44
        cv.line(20, y, 220, y, stroke="#1E293B")
        y += 24
        for it in ["Announcements", "Users", "Settings", "Reports", "Audit Log"]:
            cv.t(28, y + 4, it, 13, 400, "#94A3B8")
            y += 40
        cv.c(30, 896, 14, fill="#334155")
        cv.t(54, 892, "Dr. Andi Wijaya", 12, 600, "#E2E8F0")
        cv.t(54, 908, "Section Editor", 10.5, 400, "#94A3B8")
    cv.g("sidebar", sidebar)

    def topbar():
        cv.r(240, 0, 1200, 56, fill="#FFFFFF")
        cv.line(240, 56, 1440, 56, stroke=BORD)
        cv.t(272, 35, "DASHBOARD", 15, 700, TXT)
        cv.c(1316, 28, 11, fill="none", stroke=MUT, sw=1.4)
        cv.line(1316, 39, 1316, 43, stroke=MUT, sw=1.4)
        cv.c(1330, 20, 4, fill="#EF4444")
        cv.t(1330, 24, "3", 9, 700, "#FFFFFF", "middle")
        cv.c(1388, 28, 16, fill="#CBD5E1")
        cv.t(1392, 33, "A", 12, 700, TXT, "middle")
    cv.g("topbar", topbar)

    def alert():
        cv.r(272, 80, 1136, 56, fill=WARN, stroke=WARN_B, rx=10)
        cv.t(296, 113, "⚠  SLA: 2 naskah telah > 30 hari di tahap UNDER REVIEW — segera tindak lanjuti.", 13, 500, WARN_T)
        cv.t(1360, 113, "Lihat →", 12.5, 600, WARN_T, "end")
    cv.g("alert-sla", alert)

    def cards():
        data = [("SUBMITTED", "5", PRIM), ("UNDER REVIEW", "3", "#2563EB"), ("DECISION", "2", "#7C3AED"), ("ACCEPTED", "1", "#16A34A")]
        x = 272
        for lab, n, col in data:
            cv.r(x, 152, 272, 92, fill="#FFFFFF", stroke=BORD, rx=10)
            cv.r(x, 152, 272, 5, fill=col, rx=2)
            cv.t(x + 20, 208, n, 30, 700, col)
            cv.t(x + 66, 196, lab, 11.5, 700, MUT)
            cv.t(x + 66, 212, "naskah", 10.5, 400, MUT)
            x += 288
    cv.g("cards-stage", cards)

    def queue():
        cv.r(272, 260, 1136, 620, fill="#FFFFFF", stroke=BORD, rx=10)
        cv.t(296, 296, "QUEUE — UNDER REVIEW (ronde berjalan)", 14, 700, TXT)
        cv.t(1384, 296, "3 naskah", 12, 400, MUT, "end")
        # filter row
        fx = 296
        for ph in ["Section ▾", "Author ▾", "Round ▾"]:
            cv.r(fx, 312, 120, 34, fill="#FFFFFF", stroke=BORD, rx=8)
            cv.t(fx + 12, 333, ph, 12, 400, MUT)
            fx += 132
        cv.r(fx, 312, 300, 34, fill="#FFFFFF", stroke=BORD, rx=8)
        cv.t(fx + 12, 333, "Cari: judul / penulis / no. referensi…", 12, 400, MUT)
        # table header
        hy = 372
        cv.line(296, hy, 1384, hy, stroke=BORD)
        for tx, lab in [(306, "REF"), (450, "JUDUL"), (830, "PENULIS"), (1040, "HARI"), (1130, "REVIEW"), (1240, "AKSI")]:
            cv.t(tx, hy + 24, lab, 10.5, 700, MUT)
        rows = [
            ("JOSHNU-2026-0042", "Taman Kota dan Kesehatan Mental", "A. Budi, dkk.", 12, "1/2", "under_review"),
            ("JOSHNU-2026-0041", "Walkability Lansia di Kawasan Kompak", "L. Manurung", 19, "2/2", "under_review"),
            ("JOSHNU-2026-0038", "Green Roof & Urban Heat: Eksperimen", "S. Wibowo, T. Anwar", 31, "0/2", "sla_over"),
            ("JOSHNU-2026-0035", "Partisipasi Warga & Blok Komunal", "D. Pratama", 8, "1/2", "under_review"),
            ("JOSHNU-2026-0031", "Kebijakan ODP dan Ekonomi Sirkular", "R. Sari", 27, "2/2", "under_review"),
        ]
        y = hy + 52
        for ref, judul, pen, hari, rev, st in rows:
            cv.t(306, y, ref, 12, 600, PRIM)
            cv.t(450, y, judul, 12.5, 500, TXT)
            cv.t(830, y, pen, 12, 400, MUT)
            warn = st == "sla_over"
            cv.t(1040, y, f"{hari} hari", 12, 600 if warn else 400, WARN_T if warn else MUT)
            cv.r(1130, y - 14, 54, 22, fill="#FEF3C7" if warn else PRIM_L, rx=11)
            cv.t(1157, y, rev, 11, 600, WARN_T if warn else PRIM_D, "middle")
            cv.r(1240, y - 16, 60, 26, fill="#FFFFFF", stroke=BORD, rx=6)
            cv.t(1270, y, "Buka", 11.5, 500, TXT, "middle")
            cv.r(1306, y - 16, 74, 26, fill=PRIM, rx=6)
            cv.t(1343, y, "Assign", 11.5, 600, "#FFFFFF", "middle")
            cv.line(296, y + 18, 1384, y + 18, stroke="#F1F5F9")
            y += 62
        cv.t(296, y + 26, "‹  1  2  3  ›", 12, 400, MUT)
        cv.t(1384, y + 26, "Sort: [hari di tahap ▾]", 12, 400, MUT, "end")
    cv.g("queue-table", queue)
    cv.save(os.path.join(OUT, "wireframes", "svg", "03-editor-dashboard.svg"))

def wire_submission():
    cv = Canvas(1440, 980, BG)
    header(cv, active="Home")

    def card():
        cv.r(240, 132, 960, 796, fill="#FFFFFF", stroke=BORD, rx=12)
        cv.t(280, 172, "Akun Saya  ›  Submit Naskah Baru", 11.5, 400, MUT)
        cv.t(280, 210, "Submission Baru", 20, 700, TXT)
        # stepper
        steps = [("1", "Metadata", True), ("2", "Penulis", False), ("3", "File", False), ("4", "Konfirmasi", False)]
        x = 280
        for i, (n, lab, on) in enumerate(steps):
            cv.c(x + 14, 246, 14, fill=PRIM if on else "#FFFFFF", stroke=PRIM if on else BORD, sw=1.4)
            cv.t(x + 14, 251, n, 12, 700, "#FFFFFF" if on else MUT, "middle")
            cv.t(x + 38, 251, lab, 12.5, 600 if on else 400, TXT if on else MUT)
            if i < 3:
                cv.line(x + 38 + len(lab) * 6.8 + 14, 246, x + 204, 246, stroke=BORD, sw=1.4)
            x += 224
        # form
        def field(y, label, w=880, x=280):
            cv.t(x, y, label, 12, 600, TXT)
        field(288, "Section *")
        cv.r(280, 300, 420, 40, fill="#FFFFFF", stroke=BORD, rx=8)
        cv.t(292, 325, "Public Health ▾", 13, 400, TXT)
        field(368, "Judul (ID) *")
        input_box(cv, 280, 380, 880, value="Taman Kota dan Kesehatan Mental: Studi Longitudinal di Kota Menengah")
        field(444, "Judul (EN) *")
        input_box(cv, 280, 456, 880, value="Urban Parks and Mental Health: A Longitudinal Study")
        field(528, "Abstrak (ID) *   (min. 150 kata)")
        cv.r(280, 540, 880, 96, fill="#FFFFFF", stroke=BORD, rx=8)
        cv.t(292, 566, "Penelitian ini mengkaji hubungan antara frekuensi penggunaan taman kota dengan", 12.5, 400, TXT)
        cv.t(292, 586, "indeks kesehatan mental warga kota menengah menggunakan desain longitudinal", 12.5, 400, TXT)
        cv.t(292, 606, "selama 12 bulan (n = 1.240). Hasil menunjukkan korelasi positif signifikan…", 12.5, 400, TXT)
        cv.t(1148, 628, "182 kata", 10.5, 400, PRIM, "end")
        field(664, "Keywords (3–6) *")
        kx = 280
        for k in ["taman kota", "kesehatan mental", "urban design"]:
            w = len(k) * 6.8 + 24
            cv.r(kx, 676, w, 30, fill=PRIM_L, rx=15)
            cv.t(kx + w / 2, 695, k + "  ×", 12, 500, PRIM_D, "middle")
            kx += w + 10
        cv.r(kx, 676, 96, 30, fill="none", stroke=BORD, rx=15, dash="4 3")
        cv.t(kx + 48, 695, "+ Tambah", 11.5, 500, MUT, "middle")
        field(736, "Funding (opsional)")
        input_box(cv, 280, 748, 420, ph="Contoh: Dikti 2025")
        cv.t(724, 736, "Konflik kepentingan (opsional)")
        input_box(cv, 724, 748, 436, value="Tidak ada")
        cv.r(280, 812, 18, 18, fill="#FFFFFF", stroke=PRIM, sw=1.6, rx=4)
        cv.t(286, 826, "✓", 12, 700, PRIM)
        cv.t(308, 826, "Lisensi: CC BY 4.0 (default) — hak cipta tetap milik penulis", 12.5, 500, TXT)
        # checklist
        cx = 280
        for c in ["Naskah orisinal", "Belum dipublikasikan", "Persetujuan co-author"]:
            cv.r(cx, 848, 18, 18, fill="#FFFFFF", stroke=PRIM, sw=1.6, rx=4)
            cv.t(cx + 6, 862, "✓", 12, 700, PRIM)
            cv.t(cx + 28, 862, c, 12.5, 400, TXT)
            cx += 300
        # actions
        cv.t(280, 908, "Draf tersimpan otomatis 09:41", 11, 400, MUT)
        cv.r(880, 884, 120, 40, fill="#FFFFFF", stroke=BORD, rx=8)
        cv.t(940, 908.5, "← Kembali", 13, 500, TXT, "middle")
        cv.r(1012, 884, 148, 40, fill=PRIM, rx=8)
        cv.t(1086, 908.5, "Lanjut: Penulis →", 13, 600, "#FFFFFF", "middle")
    cv.g("submission-form", card)
    cv.save(os.path.join(OUT, "wireframes", "svg", "04-submission-form.svg"))

if __name__ == "__main__":
    build_erd()
    wire_landing()
    wire_article()
    wire_dashboard()
    wire_submission()
    print("done")

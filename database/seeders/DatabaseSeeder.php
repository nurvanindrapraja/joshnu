<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\Galley;
use App\Models\Issue;
use App\Models\JournalSetting;
use App\Models\Payment;
use App\Models\Publication;
use App\Models\PublicationMetadata;
use App\Models\Review;
use App\Models\Section;
use App\Models\Subscriber;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\SubmissionEvent;
use App\Models\SubmissionFile;
use App\Models\SubmissionRevision;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Journal setting ----------
        $setting = JournalSetting::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Journal of Sustainable Health and New Urbanism',
                'short_name' => 'JOSHNU',
                'tagline' => 'e-Journal bidang Sustainable Health & New Urbanism — open access, peer review',
                'issn_print' => '3088-0001',
                'issn_online' => '3088-0002',
                'scope' => "JOSHNU menerbitkan penelitian peer-reviewed di persimpangan kesehatan berkelanjutan (sustainable health) dan urbanisme baru (new urbanism): perencanaan kota sehat, ruang publik dan kesehatan, transportasi aktif, ketahanan pangan perkotaan, serta determinan sosial kesehatan dalam konteks urban Indonesia.",
                'description' => "Journal of Sustainable Health and New Urbanism (JOSHNU) adalah jurnal ilmiah terbuka (open access) yang terbit dua kali setahun. Jurnal ini menjadi wadah diseminasi riset interdisipliner antara kesehatan masyarakat, perencanaan wilayah dan kota, arsitektur, dan kebijakan publik.",
                'ethics_policy' => "Redaksi menjunjung integritas akademik: orisinalitas, pelaporan yang jujur, larangan plagiarisme dan fabrikasi data, pengungkapan konflik kepentingan, serta akuntabilitas penulis. Kelalaian etik dapat berujung pada retraction.",
                'oa_policy' => "Seluruh konten JOSHNU terbuka dengan lisensi CC BY 4.0. Penulis mempertahankan hak cipta dan memberikan jurnal hak terbit pertama.",
                'apc_policy' => "Biaya dipungut setelah naskah diterima (accepted). Transfer bank, diverifikasi redaksi.",
                'editorial_board' => [
                    ['name' => 'Prof. Dr. dr. Siti Aminah, M.Kes.', 'title' => 'Editor-in-Chief', 'institution' => 'Universitas Indonesia', 'email' => 'eic@joshnu.example.org', 'orcid' => '0000-0002-1825-0097'],
                    ['name' => 'Dr. Ir. Budi Santoso, M.URP', 'title' => 'Managing Editor', 'institution' => 'ITB', 'email' => 'editor@joshnu.example.org', 'orcid' => '0000-0001-5109-3700'],
                    ['name' => 'Dr. Rina Wati, M.PH', 'title' => 'Section Editor, Public Health', 'institution' => 'UGM', 'email' => 'rina@ugm.example.ac.id'],
                    ['name' => 'Ir. Anton Nugroho, M.Sc', 'title' => 'Section Editor, New Urbanism', 'institution' => 'UB', 'email' => 'anton@ub.example.ac.id'],
                ],
                'apc_enabled' => true,
                'apc_amount' => 750000,
                'apc_currency' => 'IDR',
                'apc_bank' => [
                    ['bank' => 'BCA', 'no' => '123-456-7890', 'name' => 'JOSHNU'],
                    ['bank' => 'Mandiri', 'no' => '887-001-2233', 'name' => 'JOSHNU'],
                ],
                'review_mode' => 'double_blind',
                'max_review_rounds' => 3,
                'review_deadline_days' => 30,
                'reminder_before_days' => 7,
                'online_first_enabled' => true,
                'doi_prefix' => '10.56253',
                'doi_suffix_pattern' => 'joshnu.v{v}i{n}.{seq:03d}',
                'theme' => ['colors' => ['primary' => '#1b6e5a', 'accent' => '#f59e0b']],
                'contact_email' => 'secretariat@joshnu.example.org',
                'address' => 'Fakultas Kesehatan Masyarakat, Jl. Cerdas Berbudaya No. 1, Bandung 40132, Indonesia',
                'social' => [],
            ]
        );
        JournalSetting::refreshCache();

        // ---------- Sections ----------
        $sections = [
            ['name' => 'Public Health & Urban Design', 'slug' => 'public-health', 'sort_order' => 1],
            ['name' => 'New Urbanism & Policy', 'slug' => 'new-urbanism-policy', 'sort_order' => 2],
            ['name' => 'Sustainable Cities', 'slug' => 'sustainable-cities', 'sort_order' => 3],
            ['name' => 'Review Article', 'slug' => 'review', 'sort_order' => 4],
        ];
        foreach ($sections as $s) {
            Section::firstOrCreate(['slug' => $s['slug']], $s + ['description' => $s['name'], 'is_active' => true]);
        }

        // ---------- Users ----------
        $users = [
            ['name' => 'Siti Aminah', 'email' => 'admin@joshnu.example.org', 'roles' => ['admin', 'eic'], 'institution' => 'UI', 'expertise' => 'kesehatan masyarakat'],
            ['name' => 'Budi Santoso', 'email' => 'editor@joshnu.example.org', 'roles' => ['section_editor', 'author'], 'institution' => 'ITB', 'expertise' => 'perencanaan kota'],
            ['name' => 'Lestari Dewi', 'email' => 'produksi@joshnu.example.org', 'roles' => ['production', 'author'], 'institution' => 'ITB', 'expertise' => 'produksi publikasi'],
            ['name' => 'Rina Wati', 'email' => 'rina@ugm.example.ac.id', 'roles' => ['reviewer', 'author'], 'institution' => 'UGM', 'expertise' => 'epidemiologi urban'],
            ['name' => 'Anton Nugroho', 'email' => 'anton@ub.example.ac.id', 'roles' => ['reviewer', 'author'], 'institution' => 'UB', 'expertise' => 'perilaku aktif (active living)'],
            ['name' => 'Arief Budi', 'email' => 'arief@author.example.org', 'roles' => ['author'], 'institution' => 'UNPAD', 'expertise' => 'kesehatan mental urban'],
            ['name' => 'Maya Sari', 'email' => 'maya@author.example.org', 'roles' => ['author', 'reviewer'], 'institution' => 'Unair', 'expertise' => 'ruang publik'],
        ];

        $users = collect($users)->map(function ($u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('password'),
                    'institution' => $u['institution'],
                    'expertise' => $u['expertise'],
                    'email_verified_at' => now(),
                    'orcid' => '0000-0001-'.Str::random(4).'-0000',
                ]
            );
            foreach ($u['roles'] as $role) {
                $user->roles()->firstOrCreate(['role' => $role]);
            }

            return $user;
        });

        $admin = $users->firstWhere('email', 'admin@joshnu.example.org');
        $editor = $users->firstWhere('email', 'editor@joshnu.example.org');
        $production = $users->firstWhere('email', 'produksi@joshnu.example.org');
        $reviewer1 = $users->firstWhere('email', 'rina@ugm.example.ac.id');
        $reviewer2 = $users->firstWhere('email', 'anton@ub.example.ac.id');
        $author1 = $users->firstWhere('email', 'arief@author.example.org');
        $author2 = $users->firstWhere('email', 'maya@author.example.org');

        $secPh = Section::where('slug', 'public-health')->first();
        $secNu = Section::where('slug', 'new-urbanism-policy')->first();

        // ---------- Issue terbit + 2 artikel published ----------
        $issue = Issue::firstOrCreate(
            ['volume' => 3, 'number' => 2],
            ['year' => 2025, 'title' => 'Kota Sehat & Ruang Publik', 'published_at' => now()->subMonths(2)]
        );

        $publishedData = [
            [
                'title_id' => 'Pengaruh Taman Kota terhadap Kesehatan Mental Penduduk Urban: Studi Kasus Bandung',
                'title_en' => 'The Influence of Urban Parks on Mental Health of Urban Residents: A Case Study of Bandung',
                'abstract_id' => 'Penelitian ini menganalisis pengaruh ketersediaan taman kota terhadap tingkat stres dan kesehatan mental penduduk urban di Kota Bandung. Menggunakan desain cross-sectional dengan kuesioner DASS-21 dan penilaian aksesibilitas taman, studi ini melibatkan 412 responden. Hasil analisis regresi menunjukkan bahwa jarak tempuh ke taman kota yang lebih pendek secara signifikan terkait dengan skor stres yang lebih rendah (p<0,01). Temuan ini menegaskan pentingnya integrasi ruang hijau dalam perencanaan kota berkelanjutan dan rekomendasi kebijakan untuk meningkatkan rasio tutupan hijau per kapita.',
                'keywords_id' => ['taman kota', 'kesehatan mental', 'perencanaan kota', 'ruang hijau'],
                'authors' => [
                    ['name' => 'Arief Budi', 'email' => 'arief@author.example.org', 'institution' => 'FKM UNPAD', 'orcid' => '0000-0001-1111-2222', 'corresponding' => true],
                    ['name' => 'Maya Sari', 'email' => 'maya@author.example.org', 'institution' => 'FISIP Unair', 'orcid' => null, 'corresponding' => false],
                ],
            ],
            [
                'title_id' => 'Implementasi Prinsip New Urbanism dalam Kebijakan Kota: Analisis Regulasi 2020–2025',
                'title_en' => 'Implementing New Urbanism Principles in City Policy: A Regulatory Analysis 2020–2025',
                'abstract_id' => 'Artikel ini mengkaji sejauh mana prinsip new urbanism — mixed-use development, walkability, dan transpor publik terintegrasi — diimplementasikan dalam regulasi kota di lima kota metropolitan Indonesia periode 2020–2025. Dengan pendekatan analisis dokumen dan wawancara key informant (n=15), ditemukan implementasi parsial: walkability mulai diakomodasi melalui program kota 15 menit, namun mixed-use development masih terhambat zonasi mono-fungsional. Artikel ini menawarkan kerangka kebijakan bertahap untuk mendekatkan regulasi dengan prinsip new urbanism yang kontekstual.',
                'keywords_id' => ['new urbanism', 'kebijakan kota', 'walkability', 'zonasi'],
                'authors' => [
                    ['name' => 'Budi Santoso', 'email' => 'editor@joshnu.example.org', 'institution' => 'ITB', 'orcid' => '0000-0001-5109-3700', 'corresponding' => true],
                ],
            ],
        ];

        foreach ($publishedData as $i => $d) {
            $author = $i === 0 ? $author1 : $editor;
            $submission = Submission::firstOrCreate(
                ['reference_no' => 'JOSHNU-2025-01'.(17 + $i)],
                [
                    'author_id' => $author->id,
                    'section_id' => $i === 0 ? $secPh->id : $secNu->id,
                    'stage' => 'published',
                    'current_round' => 1,
                    'title' => $d['title_id'],
                    'cover_letter' => 'Yth. Tim Redaksi, kami mengajukan naskah…',
                    'conflict_of_interest' => 'Tidak ada',
                    'metadata' => [
                        'id_title' => $d['title_id'],
                        'en_title' => $d['title_en'],
                        'id_abstract' => $d['abstract_id'],
                        'en_abstract' => $d['abstract_id'],
                        'keywords' => $d['keywords_id'],
                        'license' => 'CC-BY-4.0',
                    ],
                    'submitted_at' => now()->subMonths(5),
                ]
            );

            foreach ($d['authors'] as $j => $a) {
                SubmissionAuthor::firstOrCreate(
                    ['submission_id' => $submission->id, 'display_order' => $j + 1],
                    [
                        'user_id' => $author->id,
                        'name' => $a['name'], 'email' => $a['email'], 'orcid' => $a['orcid'],
                        'institution' => $a['institution'], 'is_corresponding' => $a['corresponding'],
                    ]
                );
            }

            $publication = Publication::firstOrCreate(
                ['submission_id' => $submission->id],
                [
                    'issue_id' => $issue->id,
                    'section_id' => $submission->section_id,
                    'slug' => 'joshnu-v3i2-00'.($i + 1),
                    'published_at' => $issue->published_at,
                    'position' => $i + 1,
                    'doi' => '10.56253/joshnu.v3i2.00'.($i + 1),
                    'doi_status' => 'registered',
                    'views' => random_int(80, 400),
                    'downloads' => random_int(20, 120),
                ]
            );

            PublicationMetadata::firstOrCreate(
                ['publication_id' => $publication->id, 'lang' => 'id'],
                ['title' => $d['title_id'], 'abstract' => $d['abstract_id'], 'keywords' => $d['keywords_id']]
            );
            PublicationMetadata::firstOrCreate(
                ['publication_id' => $publication->id, 'lang' => 'en'],
                ['title' => $d['title_en'], 'abstract' => $d['abstract_id'], 'keywords' => $d['keywords_id']]
            );

            // PDF galley sederhana
            $target = 'publications/'.$publication->id.'/article.pdf';
            if (! \Storage::disk('local')->exists($target)) {
                \Storage::disk('local')->put($target, $this->makePlaceholderPdf($d['title_id']));
            }
            Galley::firstOrCreate(
                ['publication_id' => $publication->id, 'type' => 'article_pdf'],
                [
                    'label' => 'PDF (Version of Record)',
                    'path' => $target,
                    'mime_type' => 'application/pdf',
                    'size_bytes' => \Storage::disk('local')->size($target),
                    'is_version_of_record' => true,
                ]
            );

            SubmissionEvent::log($submission, 'article_published', ['doi' => $publication->doi]);
        }

        // ---------- Naskah in-progress di berbagai stage ----------
        // 1) SUBMITTED baru
        $sub1 = Submission::firstOrCreate(
            ['reference_no' => 'JOSHNU-'.now()->year.'-0041'],
            [
                'author_id' => $author2->id,
                'section_id' => $secPh->id,
                'stage' => 'submitted',
                'title' => 'Partisipasi Warga dalam Perencanaan Ruang Publik Inklusif',
                'cover_letter' => 'Yth. Redaksi…',
                'conflict_of_interest' => 'Tidak ada',
                'metadata' => [
                    'id_title' => 'Partisipasi Warga dalam Perencanaan Ruang Publik Inklusif',
                    'en_title' => 'Community Participation in Inclusive Public Space Planning',
                    'id_abstract' => str_repeat('Penelitian ini mengkaji partisipasi warga. ', 30),
                    'keywords' => ['partisipasi warga', 'ruang publik', 'inklusi'],
                    'license' => 'CC-BY-4.0',
                ],
                'submitted_at' => now()->subDays(2),
            ]
        );
        $this->seedRevision1($sub1, $author2);

        // 2) UNDER REVIEW dengan 1 review submitted + 1 invited
        $sub2 = Submission::firstOrCreate(
            ['reference_no' => 'JOSHNU-'.now()->year.'-0042'],
            [
                'author_id' => $author1->id,
                'section_id' => $secNu->id,
                'stage' => 'under_review',
                'current_round' => 1,
                'title' => 'Transportasi Aktif dan Kualitas Udara di Kota padat: Bukti dari Jalur Sepeda',
                'conflict_of_interest' => 'Tidak ada',
                'metadata' => [
                    'id_title' => 'Transportasi Aktif dan Kualitas Udara di Kota padat: Bukti dari Jalur Sepeda',
                    'en_title' => 'Active Transport and Air Quality in Dense Cities',
                    'id_abstract' => str_repeat('Studi ini mengukur kaitan jalur sepeda dengan kualitas udara. ', 30),
                    'keywords' => ['transportasi aktif', 'kualitas udara', 'sepeda'],
                    'license' => 'CC-BY-4.0',
                ],
                'submitted_at' => now()->subDays(25),
            ]
        );
        $this->seedRevision1($sub2, $author1);

        Review::firstOrCreate(
            ['submission_id' => $sub2->id, 'reviewer_id' => $reviewer1->id, 'round' => 1],
            [
                'status' => 'submitted',
                'invite_token' => Str::random(64),
                'invite_email' => $reviewer1->email,
                'due_at' => now()->subDays(5),
                'submitted_at' => now()->subDays(8),
                'recommended_decision' => 'minor_revise',
                'rating_novelty' => 4, 'rating_methodology' => 3, 'rating_clarity' => 4, 'rating_contribution' => 4,
                'comments_to_author' => 'Secara umum naskah baik. Saran: (1) perkuat bagian metodologi sampling, (2) tambahkan perbandingan data lintas kota.',
                'comments_to_editor' => 'Kandidat diterima dengan revisi minor.',
            ]
        );
        Review::firstOrCreate(
            ['submission_id' => $sub2->id, 'reviewer_id' => $reviewer2->id, 'round' => 1],
            [
                'status' => 'accepted',
                'invite_token' => Str::random(64),
                'invite_email' => $reviewer2->email,
                'due_at' => now()->addDays(10),
            ]
        );
        Assignment::firstOrCreate(
            ['submission_id' => $sub2->id, 'user_id' => $reviewer1->id, 'type' => 'reviewer'],
            ['assigned_by' => $editor->id, 'assigned_at' => now()->subDays(20)]
        );

        // 3) REVISED (menunggu keputusan setelah revisi)
        $sub3 = Submission::firstOrCreate(
            ['reference_no' => 'JOSHNU-'.now()->year.'-0043'],
            [
                'author_id' => $author2->id,
                'section_id' => $secPh->id,
                'stage' => 'decision',
                'current_round' => 2,
                'title' => 'Determinan Sosial Akses Layanan Kesehatan Primer di Kawasan KUM',
                'conflict_of_interest' => 'Tidak ada',
                'metadata' => [
                    'id_title' => 'Determinan Sosial Akses Layanan Kesehatan Primer di Kawasan Kumuh',
                    'en_title' => 'Social Determinants of Primary Health Care Access in Slum Areas',
                    'id_abstract' => str_repeat('Analisis determinan sosial akses layanan kesehatan primer. ', 30),
                    'keywords' => ['akses kesehatan', 'kumuh', 'determinan sosial'],
                    'license' => 'CC-BY-4.0',
                ],
                'submitted_at' => now()->subDays(60),
            ]
        );
        $this->seedRevision1($sub3, $author2);
        SubmissionRevision::firstOrCreate(
            ['submission_id' => $sub3->id, 'version' => 2],
            ['note' => 'Revisi mayor: metodologi diperbaiki']
        );
        Decision::firstOrCreate(
            ['submission_id' => $sub3->id, 'editor_id' => $editor->id, 'round' => 1],
            [
                'type' => 'major_revise',
                'comment_to_author' => 'Naskah menjanjikan namun perlu revisi mayor pada bagian metodologi dan interpretasi.',
                'revision_due_at' => now()->subDays(10),
                'decided_at' => now()->subDays(30),
            ]
        );

        // 4) ACCEPTED + APC pending (siap produksi)
        $sub4 = Submission::firstOrCreate(
            ['reference_no' => 'JOSHNU-'.now()->year.'-0044'],
            [
                'author_id' => $author1->id,
                'section_id' => $secPh->id,
                'stage' => 'accepted',
                'title' => 'Green Corridor sebagai Mitigasi Heat Island Urban: Studi Komparatif',
                'conflict_of_interest' => 'Tidak ada',
                'metadata' => [
                    'id_title' => 'Green Corridor sebagai Mitigasi Heat Island Urban: Studi Komparatif',
                    'en_title' => 'Green Corridor as an Urban Heat Island Mitigation: A Comparative Study',
                    'id_abstract' => str_repeat('Studi komparatif efektivitas green corridor terhadap suhu permukaan. ', 30),
                    'keywords' => ['green corridor', 'heat island', 'mitigasi'],
                    'license' => 'CC-BY-4.0',
                ],
                'submitted_at' => now()->subDays(90),
            ]
        );
        $this->seedRevision1($sub4, $author1);
        Decision::firstOrCreate(
            ['submission_id' => $sub4->id, 'editor_id' => $admin->id, 'round' => 1],
            [
                'type' => 'accept',
                'comment_to_author' => 'Naskah diterima untuk dipublikasikan. Silakan lengkapi pembayaran APC.',
                'decided_at' => now()->subDays(7),
            ]
        );
        Payment::firstOrCreate(
            ['submission_id' => $sub4->id],
            [
                'invoice_no' => Submission::nextInvoiceNo(),
                'amount' => $setting->apc_amount,
                'currency' => 'IDR',
                'status' => 'pending',
                'due_at' => now()->addDays(14),
            ]
        );

        // ---------- Announcements ----------
        Announcement::firstOrCreate(
            ['title' => 'Call for Paper Vol. 4 No. 1 (2026)'],
            [
                'body' => 'Redaksi membuka penerimaan naskah untuk Vol. 4 No. 1 dengan tema "Kota Tangguh dan Sehat". Deadline: 28 Februari 2026.',
                'type' => 'cfp',
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(60),
                'is_active' => true,
                'by' => $admin->id,
            ]
        );
        Announcement::firstOrCreate(
            ['title' => 'Issue Vol. 3 No. 2 telah terbit'],
            [
                'body' => 'Issue terbaru telah tersedia untuk dibaca dan diunduh secara gratis.',
                'type' => 'news',
                'starts_at' => $issue->published_at,
                'is_active' => true,
                'by' => $admin->id,
            ]
        );

        // ---------- Subscriber ----------
        Subscriber::firstOrCreate(
            ['email' => 'reader@example.com'],
            ['token' => Str::random(64), 'is_confirmed' => true]
        );

        AuditLog::log($admin->id, 'seed.complete', 'Database', 0, ['note' => 'demo data']);
    }

    private function seedRevision1(Submission $s, User $author): void
    {
        SubmissionRevision::firstOrCreate(
            ['submission_id' => $s->id, 'version' => 1],
            ['note' => 'Versi awal']
        );
    }

    /** PDF placeholder minimal (1 halaman, valid) untuk data demo. */
    private function makePlaceholderPdf(string $title): string
    {
        $safe = mb_substr($title, 0, 60);
        $content = "BT /F1 16 Tf 60 750 Td (JOSHNU - Demo Article) Tj ET\nBT /F1 11 Tf 60 720 Td (".$safe.") Tj ET";

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n4 0 obj<</Length ".strlen($content).">>stream\n".$content."\nendstream endobj\n5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\nxref\n0 6\n0000000000 65535 f \ntrailer<</Size 6/Root 1 0 R>>\nstartxref\n0\n%%EOF";
    }
}

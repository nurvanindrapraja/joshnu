<?php

namespace App\Http\Controllers;

use App\Models\ArticleUsage;
use App\Models\Payment;
use App\Models\Publication;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->date('from') ?? now()->subMonths(6)->startOfMonth();
        $to = $request->date('to') ?? now();

        $data = Cache::remember('reports.dashboard:'.$from->getTimestamp().':'.$to->getTimestamp(), now()->addMinutes(10), function () use ($from, $to) {
            $totalViews = ArticleUsage::where('type', 'view')->whereBetween('created_at', [$from, $to])->count();
            $totalDownloads = ArticleUsage::where('type', 'download')->whereBetween('created_at', [$from, $to])->count();

            $topArticles = Publication::published()->with('metadata')
                ->orderByDesc('views')->limit(5)->get();

            $monthlyTrend = ArticleUsage::whereBetween('created_at', [$from, $to])
                ->get(['type', 'created_at'])
                ->groupBy(fn ($r) => $r->created_at->format('Y-m'))
                ->map(fn ($rows) => [
                    'view' => $rows->where('type', 'view')->count(),
                    'download' => $rows->where('type', 'download')->count(),
                ])
                ->sortBy('key')
                ->values();

            // editorial metrics
            $stageCounts = Submission::selectRaw('stage, count(*) as total')->groupBy('stage')->get()->keyBy('stage');

            $avgDaysByStage = $this->avgDaysByStage();

            $published = Submission::stage('published')->count();
            $rejected = Submission::stage('rejected')->count();
            $acceptanceRate = ($published + $rejected) > 0 ? round($published / ($published + $rejected) * 100, 1) : 0;

            $reviewStats = Review::where('submitted_at', '!=', null)->get();
            $avgReviewDays = $reviewStats->filter(fn ($r) => $r->due_at && $r->submitted_at)
                ->map(fn ($r) => $r->created_at->diffInHours($r->submitted_at) / 24)
                ->avg();

            // finance
            $finance = Payment::whereBetween('created_at', [$from, $to])
                ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount')
                ->groupBy('status')->get();

            return compact(
                'totalViews', 'totalDownloads', 'topArticles', 'monthlyTrend',
                'stageCounts', 'avgDaysByStage', 'acceptanceRate', 'avgReviewDays', 'finance'
            );
        });

        return view('admin.reports', $data + compact('from', 'to'));
    }

    public function usageCsv(Request $request)
    {
        $from = $request->date('from') ?? now()->subMonths(6)->startOfMonth();
        $to = $request->date('to') ?? now();

        $rows = ArticleUsage::whereBetween('created_at', [$from, $to])
            ->get(['publication_id', 'type', 'created_at'])
            ->groupBy(fn ($r) => $r->publication_id.'|'.$r->type.'|'.$r->created_at->format('Y-m'))
            ->map(fn ($g) => [$g->first()->publication_id, $g->first()->type, $g->first()->created_at->format('Y-m'), $g->count()])
            ->values();

        return $this->csvResponse('usage.csv', [
            ['publication_id', 'type', 'month', 'total'],
            ...$rows,
        ]);
    }

    public function editorialCsv(Request $request)
    {
        $rows = Submission::with('section')->get()->map(fn ($s) => [
            $s->reference_no, $s->stage, $s->submitted_at->toDateString(),
            $s->daysInStage(), $s->section?->name,
        ]);

        return $this->csvResponse('editorial.csv', [
            ['reference_no', 'stage', 'submitted_at', 'days_in_stage', 'section'],
            ...$rows,
        ]);
    }

    public function financeCsv(Request $request)
    {
        $rows = Payment::with('submission')->get()->map(fn ($p) => [
            $p->invoice_no, $p->submission->reference_no, $p->amount,
            $p->currency, $p->status, optional($p->verified_at)->toDateString(),
        ]);

        return $this->csvResponse('finance.csv', [
            ['invoice_no', 'reference_no', 'amount', 'currency', 'status', 'verified_at'],
            ...$rows,
        ]);
    }

    private function avgDaysByStage(): array
    {
        $result = [];

        $subs = Submission::whereIn('stage', ['submitted', 'under_review', 'decision', 'revised', 'accepted', 'scheduled'])
            ->with('events')
            ->get();

        foreach ($subs as $s) {
            $result[$s->stage] = ($result[$s->stage] ?? 0) + $s->daysInStage();
        }

        $counts = Submission::whereIn('stage', ['submitted', 'under_review', 'decision', 'revised', 'accepted', 'scheduled'])
            ->selectRaw('stage, count(*) as c')->groupBy('stage')->get()->keyBy('stage');

        foreach ($result as $stage => $sum) {
            $result[$stage] = round($sum / max(1, (int) ($counts[$stage]->c ?? 1)), 1);
        }

        return $result;
    }

    public function doajExport()
    {
        $zip = new \ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'doaj');
        unlink($tmp);
        if ($zip->open($tmp, \ZipArchive::CREATE) !== true) {
            abort(500, 'Gagal membuat arsip export.');
        }

        $setting = \App\Models\JournalSetting::current();
        $csv = fopen('php://temp', 'w+');
        fwrite($csv, "\xEF\xBB\xBF");
        fputcsv($csv, [
            'source_id', 'journal_issn', 'journal_title', 'journal_url', 'volume', 'number',
            'issue_date', 'publication_date', 'article_title', 'article_doi', 'abstract',
            'language', 'keywords', 'authors', 'affiliations', 'full_text_url', 'pdf_url',
            'oa_status', 'open_access_type', 'license',
        ], ',', '"', '\\');

        $publications = Publication::published()
            ->with(['metadata', 'issue', 'authors', 'galleys'])
            ->orderBy('published_at')->get();

        foreach ($publications as $p) {
            $meta = $p->metadata->firstWhere('lang', 'en') ?? $p->metadata->first();
            $pdfGalley = $p->galleys->firstWhere('type', 'article_pdf');
            $authors = $p->authors->pluck('name');
            $affils = $p->authors->pluck('institution')->filter()->unique();

            fputcsv($csv, [
                $p->doi ?? 'joshnu-'.$p->slug,
                $setting->issn_online ?? $setting->issn_print,
                $setting->name,
                config('app.url'),
                $p->issue?->volume,
                $p->issue?->number,
                $p->issue?->published_at?->format('Y-m-d'),
                $p->published_at?->format('Y-m-d'),
                $meta?->title ?? '',
                $p->doi,
                $meta?->abstract ?? '',
                'en',
                implode('; ', $meta->keywords ?? []),
                $authors->implode('; '),
                $affils->implode('; '),
                route('articles.show', $p->slug),
                $pdfGalley ? route('articles.download', [$p, $pdfGalley->id]) : '',
                'gold',
                'immediate',
                'CC BY 4.0',
            ], ',', '"', '\\');

            if ($pdfGalley && Storage::disk('local')->exists($pdfGalley->path)) {
                $zip->addFile(
                    Storage::disk('local')->path($pdfGalley->path),
                    'pdf/'.$p->slug.'.pdf'
                );
            }
        }
        $zip->addFromString('doaj_articles.csv', stream_get_contents($csv));
        fclose($csv);
        $zip->addFromString('README.txt', "JOSHNU DOAJ export\nGenerated: ".now()->toIso8601String()."\nJournal: {$setting->name}\nISSN online: {$setting->issn_online}\nISSN print: {$setting->issn_print}\n\nIsi: doaj_articles.csv (metadata DOAJ) + pdf/ (PDF of record).\n");
        $zip->close();

        return response()->download($tmp, 'joshnu-doaj-export-'.now()->format('Ymd').'.zip')->deleteFileAfterSend(true);
    }

    private function csvResponse(string $filename, array $rows)
    {
        $out = fopen('php://temp', 'w+');
        foreach ($rows as $row) {
            fputcsv($out, array_map(fn ($c) => (string) ($c ?? ''), $row), ',', '"', '\\');
        }
        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return response($content, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}

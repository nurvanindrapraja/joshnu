<?php

namespace App\Http\Controllers;

use App\Models\Galley;
use App\Models\Publication;
use App\Services\CitationService;
use App\Services\PdfService;
use App\Services\SearchService;
use App\Services\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleController extends Controller
{
    public function show(string $slug, UsageTracker $usage): View
    {
        $publication = Publication::published()
            ->where('slug', $slug)
            ->with(['metadata', 'issue', 'section', 'galleys', 'submission.authors'])
            ->firstOrFail();

        $usage->record($publication, 'view');
        $publication->refresh();

        $related = app(SearchService::class)->related($publication)
            ->with('metadata')->limit(3)->get();

        return view('public.article', compact('publication', 'related'));
    }

    public function download(string $slug, Galley $galley, UsageTracker $usage, PdfService $pdf): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($galley->publication->slug === $slug, 404);
        $publication = $galley->publication()->with('submission')->firstOrFail();

        // generate PDF of record on-demand bila file hilang (dev)
        $path = $galley->path;
        if (! Storage::disk('local')->exists($path) && $galley->type === 'article_pdf') {
            $pdf->generateArticlePdf($publication);
            $path = $galley->refresh()->path;
        }
        abort_unless(Storage::disk('local')->exists($path), 404, 'File tidak tersedia.');

        $usage->record($publication, 'download');

        $name = 'joshnu-'.$publication->slug.'-'.($galley->type === 'article_pdf' ? 'article' : $galley->type).'.pdf';
        if ($galley->type === 'jats_xml') {
            $name = 'joshnu-'.$publication->slug.'.xml';
        }

        return response()->download(Storage::disk('local')->path($path), $name);
    }

    public function citations(string $slug, Request $request, CitationService $citations): JsonResponse
    {
        $publication = Publication::published()->where('slug', $slug)->with('submission.authors')->firstOrFail();
        $format = in_array($request->query('format'), ['apa', 'mla', 'chicago', 'bibtex', 'ris', 'endnote'], true)
            ? $request->query('format')
            : 'apa';

        return response()->json(['data' => [
            'format' => $format,
            'citation' => $citations->format($publication, $format, app()->isLocale('en') ? 'en' : 'id'),
        ]]);
    }

    public function related(string $slug, SearchService $search): JsonResponse
    {
        $publication = Publication::published()->where('slug', $slug)->with('metadata')->firstOrFail();

        $items = $search->related($publication)->limit(5)->get()->map(fn ($p) => [
            'slug' => $p->slug,
            'title' => $p->titleFor('id'),
            'url' => route('articles.show', $p->slug),
        ]);

        return response()->json(['data' => $items]);
    }
}

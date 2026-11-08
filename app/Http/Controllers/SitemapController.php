<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Publication;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [
            ['', now()->toAtomString()],
            ['/about', now()->toAtomString()],
            ['/archive', now()->toAtomString()],
            ['/current-issue', now()->toAtomString()],
            ['/announcements', now()->toAtomString()],
        ];

        foreach (Issue::published()->get() as $issue) {
            $urls[] = ['/archive/'.$issue->slug, optional($issue->published_at)->toAtomString() ?? now()->toAtomString()];
        }

        foreach (Publication::published()->with('issue')->get() as $p) {
            $urls[] = ['/article/'.$p->slug, optional($p->published_at)->toAtomString() ?? now()->toAtomString()];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as [$path, $lastmod]) {
            $xml .= "  <url>\n    <loc>{$base}{$path}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}

<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian artikel publik.
 * MySQL 8: FULLTEXT MATCH (metadata + judul).
 * SQLite (dev): fallback LIKE.
 */
class SearchService
{
    public function searchPublic(array $f): LengthAwarePaginator
    {
        $q = trim((string) ($f['q'] ?? ''));
        $yearFrom = (int) ($f['year_from'] ?? 0);
        $yearTo = (int) ($f['year_to'] ?? 0);
        $section = (string) ($f['section'] ?? '');
        $author = trim((string) ($f['author'] ?? ''));
        $sort = $f['sort'] ?? 'relevance';

        $isMysql = DB::connection()->getDriverName() === 'mysql';
        $useFulltext = $isMysql && $q !== '' && preg_match('/[\x20-\x7E]/', $q) === 1;

        $perPage = min(50, max(5, (int) ($f['per_page'] ?? 10)));

        $query = Publication::query()
            ->published()
            ->with(['metadata', 'section', 'issue'])
            ->whereHas('metadata', fn ($m) => $m->where('lang', app()->isLocale('en') ? 'en' : 'id'));

        if ($q !== '') {
            if ($useFulltext) {
                $query->whereHas('metadata', function ($m) use ($q) {
                    $m->whereRaw('MATCH(title, abstract) AGAINST (? IN BOOLEAN MODE)', ['+".'.$q.'"']);
                });
            } else {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                $query->whereHas('metadata', function ($m) use ($like) {
                    $m->where('title', 'like', $like)->orWhere('abstract', 'like', $like);
                });
            }
        }

        if ($yearFrom) {
            $query->whereHas('issue', fn ($i) => $i->where('year', '>=', $yearFrom));
        }
        if ($yearTo) {
            $query->whereHas('issue', fn ($i) => $i->where('year', '<=', $yearTo));
        }
        if ($section !== '') {
            $query->where('section_id', $section);
        }
        if ($author !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $author).'%';
            $query->whereHas('submission.authors', fn ($a) => $a->where('name', 'like', $like));
        }

        if ($sort === 'date_desc') {
            $query->orderByDesc('published_at');
        } elseif ($sort === 'date_asc') {
            $query->orderBy('published_at');
        } elseif ($sort === 'views') {
            $query->orderByDesc('views');
        }
        // relevance: default tanpa sort khusus (atau skor fulltext pada MySQL)

        return $query->paginate($perPage)->withQueryString();
    }

    /** Related articles by keyword/author overlap. */
    public function related(Publication $p, int $limit = 3): Builder
    {
        $keywords = collect($p->keywordsFor('id'))->merge($p->keywordsFor('en'))->unique()->values();
        $authorIds = $p->authors->pluck('name')->values();
        $isMysql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql';

        $query = Publication::query()
            ->published()
            ->where('id', '!=', $p->id)
            ->with('metadata');

        $query->where(function ($q) use ($keywords, $authorIds, $isMysql) {
            if ($keywords->isNotEmpty()) {
                $q->whereHas('metadata', function ($m) use ($keywords, $isMysql) {
                    if ($isMysql) {
                        foreach ($keywords as $kw) {
                            $m->orWhereRaw("JSON_SEARCH(keywords, 'one', ?) IS NOT NULL", ['%'.$kw.'%']);
                        }
                    } else {
                        // SQLite: keywords disimpan sebagai JSON text
                        foreach ($keywords as $kw) {
                            $m->orWhere('keywords', 'like', '%'.$kw.'%');
                        }
                    }
                });
            }
            $q->orWhereHas('submission.authors', function ($a) use ($authorIds) {
                $a->whereIn('name', $authorIds->all());
            });
        });

        return $query;
    }
}

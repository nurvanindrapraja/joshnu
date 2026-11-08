<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request, SearchService $search): View
    {
        $filters = $request->only(['q', 'year_from', 'year_to', 'section', 'author', 'sort', 'per_page']);
        $results = $search->searchPublic($filters);

        return view('public.search', [
            'results' => $results,
            'filters' => $filters,
            'sections' => Section::active()->orderBy('name')->get(),
            'hasResults' => $results->total() > 0,
        ]);
    }
}

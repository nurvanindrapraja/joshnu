<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Halaman statis publik yang dikelola admin (mis. "Pedoman Penulis").
     */
    public function show(string $name): View
    {
        $page = Page::where('name', $name)->firstOrFail();

        abort_unless($page->is_active, 404);

        return view('public.page', compact('page'));
    }
}

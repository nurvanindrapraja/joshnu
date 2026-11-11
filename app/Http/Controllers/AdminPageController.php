<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsToAjax;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AdminPageController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $pages = Page::orderBy('title')->get();

        return view('admin.pages', compact('pages'));
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        $data = $this->extractImage($request, $data);

        Page::create([
            'name' => $this->uniqueName($data['name'], null),
            'title' => $data['title'],
            'content' => $data['content'],
            'is_active' => true,
            'image_path' => $data['image_path'] ?? null,
        ]);

        return $this->ajaxOrRedirect(
            $request,
            'Page "'.$data['title'].'" dibuat.',
            redirect()->route('admin.pages.index')
        );
    }

    public function update(Request $request, Page $page): Response
    {
        $data = $this->validated($request);
        $data = $this->extractImage($request, $data);

        $page->update([
            'name' => $this->uniqueName($data['name'], $page->id),
            'title' => $data['title'],
            'content' => $data['content'],
            'image_path' => $data['image_path'] ?? $page->image_path,
        ]);

        // Gambar lama diganti → hapus file lama
        if (($data['image_path'] ?? null) && $data['image_path'] !== $page->image_path) {
            $this->deleteImageFile($page->image_path);
        }

        return $this->ajaxOrRedirect(
            $request,
            'Page "'.$page->title.'" diperbarui.',
            redirect()->route('admin.pages.index')
        );
    }

    public function destroy(Request $request, Page $page): Response
    {
        $this->deleteImageFile($page->image_path);
        $page->delete();

        return $this->ajaxOrRedirect(
            $request,
            'Page "'.$page->title.'" dihapus.',
            redirect()->route('admin.pages.index')
        );
    }

    /**
     * Toggle tampil/sembunyikan page di beranda (dan akses publik).
     */
    public function toggleActive(Request $request, Page $page): Response
    {
        $nowActive = ! $page->is_active;
        $page->update(['is_active' => $nowActive]);

        $message = $nowActive
            ? 'Page "'.$page->title.'" tampil di beranda.'
            : 'Page "'.$page->title.'" disembunyikan dari beranda.';

        return $this->ajaxOrRedirect($request, $message, redirect()->route('admin.pages.index'));
    }

    private function validated(Request $request): array
    {
        // Nama bebas format (mis. "Pedoman Penulis") — otomatis dislug-kan
        // oleh uniqueName(); duplikat ditangani akhiran -2, -3, dst.
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);
    }

    /** Simpan file gambar (bila ada) ke storage publik; kunci 'image' diganti 'image_path'. */
    private function extractImage(Request $request, array $data): array
    {
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('pages', 'public');
        }
        unset($data['image']);

        return $data;
    }

    private function deleteImageFile(?string $path): void
    {
        if (! $path) {
            return;
        }
        try {
            \Storage::disk('public')->delete($path);
        } catch (\Throwable) {
            // file sudah hilang — biarkan
        }
    }

    /**
     * Pastikan slug unik: jika nama sudah dipakai, tambah akhiran -2, -3, dst.
     */
    private function uniqueName(string $name, ?int $ignoreId): string
    {
        $base = Page::slugify($name);
        if ($base === '') {
            $base = 'page';
        }

        $candidate = $base;
        $i = 2;
        while (DB::table('pages')->where('name', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }
}

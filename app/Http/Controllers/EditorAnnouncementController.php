<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsToAjax;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EditorAnnouncementController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $announcements = Announcement::withTrashed()->latest('starts_at')->paginate(20);

        return view('editor.announcements.index', compact('announcements'));
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request);
        $data['poster_path'] = $this->extractPoster($request);
        unset($data['poster']);

        $announcement = Announcement::create($data + ['by' => $request->user()->id]);

        return $this->ajaxOrRedirect($request, 'Pengumuman "'.$announcement->title.'" dibuat.', redirect()->route('editor.announcements.index'));
    }

    public function update(Request $request, Announcement $announcement): Response
    {
        $data = $this->validated($request);

        if ($request->hasFile('poster')) {
            $this->deletePoster($announcement);
            $data['poster_path'] = $request->file('poster')->store('announcements', 'public');
        }
        unset($data['poster']);

        $announcement->update($data);

        return $this->ajaxOrRedirect($request, 'Pengumuman "'.$announcement->title.'" diperbarui.', back());
    }

    public function restore(Request $request, Announcement $announcement): Response
    {
        $announcement->restore();

        return $this->ajaxOrRedirect($request, 'Pengumuman "'.$announcement->title.'" dipulihkan.', back());
    }

    public function destroy(Request $request, Announcement $announcement): Response
    {
        if ($announcement->trashed()) {
            $this->deletePoster($announcement);
            $announcement->forceDelete();

            return $this->ajaxOrRedirect($request, 'Pengumuman "'.$announcement->title.'" dihapus permanen.', back());
        }

        $announcement->delete();

        return $this->ajaxOrRedirect($request, 'Pengumuman "'.$announcement->title.'" dihapus (arsip).', back());
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'type' => ['required', 'in:cfp,news,maintenance,other'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'poster' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => true];
    }

    /** Simpan file poster (bila ada) ke storage publik; kembalikan path-nya. */
    private function extractPoster(Request $request): ?string
    {
        if (! $request->hasFile('poster')) {
            return null;
        }

        return $request->file('poster')->store('announcements', 'public');
    }

    private function deletePoster(Announcement $announcement): void
    {
        if (! $announcement->poster_path) {
            return;
        }

        try {
            \Storage::disk('public')->delete($announcement->poster_path);
        } catch (\Throwable) {
            // file poster sudah hilang — biarkan
        }

        $announcement->update(['poster_path' => null]);
    }
}

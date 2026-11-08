<?php

namespace App\Http\Controllers;

use App\Events\IssuePublished;
use App\Http\Concerns\RespondsToAjax;
use App\Jobs\SendIssueAlert;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EditorIssueController extends Controller
{
    use RespondsToAjax;

    public function index(Request $request): View
    {
        $tema = trim((string) $request->query('tema', ''));
        $volume = (int) $request->query('volume', 0) ?: null;
        $year = (int) $request->query('year', 0) ?: null;

        $filters = compact('tema', 'volume', 'year');

        $issues = Issue::withCount(['publishedPublications as published_count' => fn ($q) => $q->whereNotNull('published_at')->whereNull('deleted_at')])
            ->when($tema !== '', fn ($q) => $q->where('title', 'like', '%'.$tema.'%'))
            ->when($volume, fn ($q) => $q->where('volume', $volume))
            ->when($year, fn ($q) => $q->where('year', $year))
            ->orderByDesc('volume')->orderByDesc('number')
            ->paginate(20)
            ->withQueryString();

        $volumes = Issue::distinct()->orderBy('volume')->pluck('volume');
        $years = Issue::distinct()->orderByDesc('year')->pluck('year');

        return view('editor.issues.index', compact('issues', 'filters', 'volumes', 'years'));
    }

    public function create(): View
    {
        return view('editor.issues.form', ['issue' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validateIssue($request);
        $data = $this->extractCover($request, $data);

        $issue = Issue::create($data + ['published_at' => null]);

        AuditLog::log($request->user()->id, 'issue.create', 'Issue', $issue->id);

        return $this->ajaxOrRedirect($request, 'Issue '.$issue->label().' dibuat (draft).', redirect()->route('editor.issues.index'));
    }

    public function edit(Issue $issue): View
    {
        return view('editor.issues.form', compact('issue'));
    }

    public function update(Request $request, Issue $issue): Response
    {
        abort_if($issue->isPublished(), 422, 'Issue yang sudah terbit tidak dapat diedit.');

        $data = $this->validateIssue($request);
        $data = $this->extractCover($request, $data);

        $issue->update($data);

        AuditLog::log($request->user()->id, 'issue.update', 'Issue', $issue->id);

        return $this->ajaxOrRedirect($request, 'Issue '.$issue->label().' diperbarui.', redirect()->route('editor.issues.index'));
    }

    public function publish(Request $request, Issue $issue): Response
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $published = $issue->publishedPublications()->count();
        abort_if($published === 0, 422, 'Issue tidak memiliki artikel yang siap terbit.');

        $unpublished = Publication::where('issue_id', $issue->id)
            ->whereNull('published_at')->whereNull('deleted_at')->count();
        if ($unpublished > 0) {
            // publikasikan semua artikel terjadwal pada issue ini
            foreach ($issue->publications()->whereNull('published_at')->get() as $p) {
                app(\App\Services\SubmissionWorkflow::class)->publish($p->submission, $request->user(), false);
            }
        }

        $issue->update(['published_at' => now()]);
        AuditLog::log($request->user()->id, 'issue.publish', 'Issue', $issue->id);
        event(new IssuePublished($issue, $request->user->id));
        SendIssueAlert::dispatch($issue);

        return $this->ajaxOrRedirect($request, 'Issue '.$issue->label().' terbit!', back());
    }

    public function unpublish(Request $request, Issue $issue): Response
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        // unpublish artikel pada issue ini
        $issue->publications()->get()->each(function (Publication $p) {
            $p->update(['published_at' => null, 'is_online_first' => false]);
            $p->submission()->update(['stage' => 'scheduled']);
        });

        $issue->update(['published_at' => null]);
        AuditLog::log($request->user()->id, 'issue.unpublish', 'Issue', $issue->id);

        return $this->ajaxOrRedirect($request, 'Issue di-unpublish (kembali draft).', back());
    }

    public function destroy(Request $request, Issue $issue): Response
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);
        abort_if($issue->isPublished(), 422, 'Issue yang sudah terbit tidak dapat dihapus.');

        $attached = $issue->publications()->whereNull('deleted_at')->count();
        abort_if($attached > 0, 422, 'Issue masih memiliki artikel terjadwal. Unpublish artikelnya terlebih dahulu.');

        if ($issue->cover_path) {
            try {
                \Storage::disk('public')->delete($issue->cover_path);
            } catch (\Throwable) {
                // file cover sudah hilang — biarkan
            }
        }

        $label = $issue->label();
        $issue->delete();
        AuditLog::log($request->user()->id, 'issue.delete', 'Issue', $issue->id);

        return $this->ajaxOrRedirect($request, 'Issue '.$label.' dihapus.', redirect()->route('editor.issues.index'));
    }

    private function validateIssue(Request $request): array
    {
        return $request->validate([
            'volume' => ['required', 'integer', 'min:1', 'max:999'],
            'number' => ['required', 'integer', 'min:1', 'max:999'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'title' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:4096'],
        ]) + ['published_at' => null];
    }

    /** Simpan file cover (bila ada) ke storage publik, kembalikan data bersih tanpa kunci 'cover'. */
    private function extractCover(Request $request, array $data): array
    {
        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('issues', 'public');
        }
        unset($data['cover']);

        return $data;
    }
}

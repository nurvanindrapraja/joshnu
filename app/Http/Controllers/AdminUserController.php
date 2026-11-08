<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $tab = $request->query('tab') === 'authors' ? 'authors' : 'staff';

        // Basis query (filter cari + role)
        $base = fn () => User::with('roles')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', '%'.$q.'%')
                ->orWhere('email', 'like', '%'.$q.'%')))
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('role', $role)));

        // Tab Penulis = tanpa role sama sekali (baru daftar) ATAU "hanya author"
        // (punya role author dan TIDAK punya role lain)
        $authorTab = fn ($query) => $query->where(fn ($w) => $w
            ->whereDoesntHave('roles')
            ->orWhere(fn ($w2) => $w2
                ->whereHas('roles', fn ($r) => $r->where('role', 'author'))
                ->whereDoesntHave('roles', fn ($r) => $r->where('role', '!=', 'author'))));

        // Tab Staff = punya minimal satu role selain author
        $staff = fn ($query) => $query->whereHas('roles', fn ($r) => $r->where('role', '!=', 'author'));

        $users = $base()
            ->when($tab === 'authors', $authorTab, $staff)
            ->withCount(['submissions', 'reviews'])
            ->addSelect([
                'coauthor_count' => \App\Models\SubmissionAuthor::selectRaw('count(*)')->whereColumn('user_id', 'users.id'),
                'assignments_count' => \App\Models\Assignment::selectRaw('count(*)')->whereColumn('user_id', 'users.id'),
                'decisions_count' => \App\Models\Decision::selectRaw('count(*)')->whereColumn('editor_id', 'users.id'),
                'messages_sent_count' => \App\Models\Message::selectRaw('count(*)')->whereColumn('sender_id', 'users.id'),
                'announcements_count' => \App\Models\Announcement::selectRaw('count(*)')->whereColumn('by', 'users.id'),
                'file_uploads_count' => \App\Models\SubmissionFile::selectRaw('count(*)')->whereColumn('uploaded_by', 'users.id'),
                'payments_verified_count' => \App\Models\Payment::selectRaw('count(*)')->whereColumn('verified_by', 'users.id'),
                'audit_entries_count' => AuditLog::selectRaw('count(*)')->whereColumn('user_id', 'users.id'),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $staffCount = $base()->tap($staff)->count();
        $authorCount = $base()->tap($authorTab)->count();

        return view('admin.users', compact('users', 'q', 'role', 'tab', 'staffCount', 'authorCount'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'institution' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'string', 'max:500'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(User::ROLES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'institution' => $data['institution'] ?? null,
            'expertise' => $data['expertise'] ?? null,
            'email_verified_at' => now(),
        ]);

        foreach ($data['roles'] as $role) {
            $user->roles()->firstOrCreate(['role' => $role]);
        }

        AuditLog::log($request->user()->id, 'user.create', 'User', $user->id, ['email' => $user->email, 'roles' => $data['roles']]);

        $status = 'User '.$user->name.' ditambahkan.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status]);
        }

        return back()->with('status', $status);
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'institution' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'string', 'max:500'],
            'orcid' => ['nullable', 'string', 'max:19', Rule::unique('users', 'orcid')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution' => $data['institution'] ?? null,
            'expertise' => $data['expertise'] ?? null,
            'orcid' => $data['orcid'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        AuditLog::log($request->user()->id, 'user.update', 'User', $user->id, ['email' => $user->email]);

        $status = 'Data user '.$user->name.' diperbarui.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status]);
        }

        return back()->with('status', $status);
    }

    public function updateRoles(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::in(User::ROLES)],
        ]);

        // proteksi: jangan sampai tidak ada admin
        if ($user->hasRole('admin') && ! in_array('admin', $data['roles'])) {
            $adminCount = User::whereHas('roles', fn ($r) => $r->where('role', 'admin'))->count();
            abort_if($adminCount <= 1, 422, 'Minimal harus ada satu admin.');
        }

        $user->roles()->whereNotIn('role', $data['roles'])->delete();
        foreach ($data['roles'] as $role) {
            $user->roles()->firstOrCreate(['role' => $role]);
        }
        $user->load('roles');

        AuditLog::log($request->user()->id, 'user.roles_update', 'User', $user->id, ['roles' => $data['roles']]);

        $status = 'Role user diperbarui.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status, 'roles' => $user->roles()->pluck('role')->all()]);
        }

        return back()->with('status', $status);
    }

    public function toggleActive(Request $request, User $user): JsonResponse|RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Anda tidak dapat menonaktifkan akun sendiri.');

        $user->update(['is_active' => ! $user->is_active]);

        AuditLog::log($request->user()->id, 'user.toggle_active', 'User', $user->id, ['is_active' => $user->is_active]);

        $status = 'Status user diubah.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status, 'is_active' => $user->is_active, 'name' => $user->name]);
        }

        return back()->with('status', $status);
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->hasRole('admin')) {
            $adminCount = User::whereHas('roles', fn ($r) => $r->where('role', 'admin'))->count();
            abort_if($adminCount <= 1, 422, 'Admin terakhir tidak dapat dihapus.');
        }

        abort_if($user->id === $request->user()->id, 422, 'Anda tidak dapat menghapus akun sendiri.');

        // hanya user tanpa riwayat proses dalam jurnal yang boleh dihapus
        $blockers = $user->deleteBlockers();
        abort_if($blockers !== [], 422, 'User tidak dapat dihapus karena sudah memiliki riwayat proses dalam jurnal: '.implode(', ', $blockers).'.');

        $name = $user->name;
        $email = $user->email;
        $user->delete();

        AuditLog::log($request->user()->id, 'user.delete', 'User', $user->id, ['email' => $email]);

        $status = 'User '.$name.' dihapus.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status]);
        }

        return back()->with('status', $status);
    }
}

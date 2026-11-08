@extends('layouts.workspace')
@section('title', 'Users & Roles')
@section('content')
<div class="max-w-5xl mx-auto" x-data="{
    saving: false,
    toasts: [],
    toastId: 0,
    csrf: document.querySelector('meta[name=csrf-token]').content,
    storeUrl: @js(route('admin.users.store')),
    usersBase: @js(url('admin/users')),
    editing: null,
    deleting: null,
    editForm: { name: '', email: '', institution: '', expertise: '', orcid: '', phone: '', password: '', password_confirmation: '' },
    notify(type, text, ttl = 3500) {
        const id = ++this.toastId;
        this.toasts.push({ id, type, text, leaving: false });
        setTimeout(() => this.markLeaving(id), ttl);
    },
    markLeaving(id) {
        const t = this.toasts.find(t => t.id === id);
        if (!t || t.leaving) return;
        t.leaving = true;
        setTimeout(() => {
            const i = this.toasts.findIndex(x => x.id === id);
            if (i !== -1) this.toasts.splice(i, 1);
        }, 300);
    },
    refreshList() {
        const f = document.querySelector('form[data-ajax-filter]');
        if (f && f._ajaxFilter) f._ajaxFilter.fetch(window.location.pathname + window.location.search);
    },
    syncRoles(form, roles) {
        Array.from(form.querySelectorAll('input')).filter(i => i.name === 'roles[]').forEach(cb => {
            const on = roles.includes(cb.value);
            cb.checked = on;
            const label = cb.closest('label');
            if (!label) return;
            label.classList.remove('border-primary', 'bg-primary-light', 'border-gray-200');
            label.classList.add(on ? 'border-primary' : 'border-gray-200');
            if (on) label.classList.add('bg-primary-light');
        });
    },
    async saveRoles(e) {
        const form = e.target;
        this.saving = true;
        try {
            const fd = new FormData(form);
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ _method: 'PUT', roles: fd.getAll('roles[]') })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.syncRoles(form, data.roles || []);
                this.notify('success', data.message || 'Role user diperbarui.');
            } else {
                this.notify('error', data.message || 'Gagal memperbarui role.', 6000);
                this.refreshList();
            }
        } catch (err) {
            this.notify('error', 'Gagal memperbarui role. Periksa koneksi lalu coba lagi.', 6000);
            this.refreshList();
        } finally {
            this.saving = false;
        }
    },
    async toggleActive(e) {
        const form = e.target;
        this.saving = true;
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({})
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                const tr = form.closest('tr');
                const status = tr ? tr.querySelector('[data-status]') : null;
                if (status) {
                    status.textContent = data.is_active ? 'Aktif' : 'Nonaktif';
                    status.className = 'text-xs ' + (data.is_active ? 'text-green-600' : 'text-red-500');
                }
                const td = form.closest('td');
                if (window.Alpine && td) {
                    const d = Alpine.$data(td);
                    if (d) d.on = data.is_active;
                }
                this.notify('success', data.message || 'Status user diubah.');
            } else {
                this.notify('error', data.message || 'Gagal mengubah status.', 6000);
            }
        } catch (err) {
            this.notify('error', 'Gagal mengubah status. Periksa koneksi lalu coba lagi.', 6000);
        } finally {
            this.saving = false;
        }
    },
    openEditUser(u) {
        this.editing = { id: u.id, name: u.name };
        this.editForm = {
            name: u.name || '', email: u.email || '', institution: u.institution || '',
            expertise: u.expertise || '', orcid: u.orcid || '', phone: u.phone || '',
            password: '', password_confirmation: ''
        };
        this.$dispatch('open-modal', 'user-edit-modal');
    },
    openDeleteUser(u) {
        this.deleting = u;
        this.$dispatch('open-modal', 'user-delete-modal');
    },
    async deleteUser() {
        if (!this.deleting) return;
        this.saving = true;
        try {
            const res = await fetch(this.usersBase + '/' + this.deleting.id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ _method: 'DELETE' })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                this.$dispatch('close-modal', 'user-delete-modal');
                this.notify('success', data.message || 'User dihapus.');
                this.refreshList();
            } else {
                this.notify('error', data.message || 'Gagal menghapus user.', 6000);
            }
        } catch (err) {
            this.notify('error', 'Gagal menghapus user. Periksa koneksi lalu coba lagi.', 6000);
        } finally {
            this.saving = false;
            this.deleting = null;
        }
    },
    saveEdit() {
        if (this.editForm.password && this.editForm.password !== this.editForm.password_confirmation) {
            this.notify('error', 'Konfirmasi password tidak sama.', 5000);
            return;
        }
        const payload = {
            _method: 'PUT',
            name: this.editForm.name,
            email: this.editForm.email,
            institution: this.editForm.institution || null,
            expertise: this.editForm.expertise || null,
            orcid: this.editForm.orcid || null,
            phone: this.editForm.phone || null,
            password: this.editForm.password || null,
            password_confirmation: this.editForm.password_confirmation || null
        };
        this.saving = true;
        fetch(this.usersBase + '/' + this.editing.id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
            body: JSON.stringify(payload)
        })
        .then(res => res.json().catch(() => ({})).then(data => ({ res, data })))
        .then(({ res, data }) => {
            if (res.ok) {
                this.$dispatch('close-modal', 'user-edit-modal');
                this.notify('success', data.message || 'Data user diperbarui.');
                this.refreshList();
            } else {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal memperbarui data user.');
                this.notify('error', errs, 6000);
            }
        })
        .catch(() => this.notify('error', 'Gagal memperbarui data user. Periksa koneksi lalu coba lagi.', 6000))
        .finally(() => { this.saving = false; });
    },
    addUser(e) {
        const form = e.target;
        const fd = new FormData(form);
        if (fd.get('password') !== fd.get('password_confirmation')) {
            this.notify('error', 'Konfirmasi password tidak sama.', 5000);
            return;
        }
        this.saving = true;
        fetch(this.storeUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
            body: JSON.stringify({
                name: fd.get('name'),
                email: fd.get('email'),
                password: fd.get('password'),
                password_confirmation: fd.get('password_confirmation'),
                institution: fd.get('institution') || '',
                roles: fd.getAll('roles[]')
            })
        })
        .then(res => res.json().catch(() => ({})).then(data => ({ res, data })))
        .then(({ res, data }) => {
            if (res.ok) {
                form.reset();
                this.$dispatch('close-modal', 'user-add-modal');
                this.notify('success', data.message || 'User ditambahkan.');
                this.refreshList();
            } else {
                const errs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Gagal menambah user.');
                this.notify('error', errs, 6000);
            }
        })
        .catch(() => this.notify('error', 'Gagal menambah user. Periksa koneksi lalu coba lagi.', 6000))
        .finally(() => { this.saving = false; });
    }
}">

    {{-- Toast notification: overlay pojok kanan atas (slideshow) --}}
    <div class="fixed top-4 right-4 z-50 w-80 max-w-[calc(100vw-2rem)] space-y-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-lg"
                 :class="[t.leaving ? 'toast-out' : 'toast-in', t.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800']">
                <svg x-show="t.type === 'success'" class="h-5 w-5 shrink-0 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                <svg x-show="t.type === 'error'" class="h-5 w-5 shrink-0 text-red-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                <span class="flex-1 pt-0.5" x-text="t.text"></span>
                <button type="button" @click="markLeaving(t.id)" class="shrink-0 opacity-60 hover:opacity-100" aria-label="Tutup">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Progres bar saat proses (AJAX) --}}
    <div x-cloak x-show="saving" class="fixed top-0 left-0 right-0 z-50 h-1 overflow-hidden bg-gray-200">
        <div class="h-full w-1/3 rounded-r bg-primary" style="animation: js-progress 1.1s ease-in-out infinite;"></div>
    </div>
    <style>
        @keyframes js-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(300%); } }
        @keyframes toast-in { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes toast-out { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(24px); } }
        .toast-in { animation: toast-in 0.3s ease-out; }
        .toast-out { animation: toast-out 0.3s ease-in forwards; }
        [x-cloak] { display: none !important; }
        .role-pill:has(input:checked) { border-color: var(--primary); background-color: var(--primary-light); color: var(--primary); }
    </style>

    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Users &amp; Roles</h2>
            <p class="text-sm text-gray-500">Kelola user dan role akses. Perubahan tersimpan langsung (AJAX, tanpa reload).</p>
        </div>
        <button type="button" @click="$dispatch('open-modal', 'user-add-modal')" :disabled="saving"
                class="shrink-0 bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition disabled:opacity-60 disabled:cursor-not-allowed">
            + Tambah User
        </button>
    </div>

    <div>
        <form method="GET" data-ajax-filter class="grid grid-cols-1 sm:grid-cols-[1fr_auto_auto] gap-2 items-end mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Cari</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email…" class="w-full rounded-lg border-gray-300 text-sm border">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Role</label>
                <select name="role" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm border">
                    <option value="">Semua role</option>
                    @foreach(\App\Models\User::ROLES as $r)
                        <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.users.index') }}" data-ajax-reset class="justify-self-start sm:justify-self-end border border-gray-300 text-gray-600 hover:text-gray-900 rounded-lg px-4 py-2 text-sm">Reset</a>
        </form>

        <div data-ajax-results>
        {{-- Tab: staff vs penulis-only --}}
        <div class="flex items-center gap-2 mb-3" role="tablist">
            <a href="{{ route('admin.users.index', array_filter(['tab' => 'staff', 'q' => $q, 'role' => $role ?: null])) }}"
               role="tab" aria-selected="{{ $tab === 'staff' ? 'true' : 'false' }}"
               class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition {{ $tab === 'staff' ? 'border-primary bg-primary-light text-primary' : 'border-gray-200 text-gray-500 hover:bg-gray-50' }}">
                Staff
                <span class="rounded-full px-2 py-0.5 text-xs {{ $tab === 'staff' ? 'bg-white/70' : 'bg-gray-100' }}">{{ $staffCount }}</span>
            </a>
            <a href="{{ route('admin.users.index', array_filter(['tab' => 'authors', 'q' => $q, 'role' => $role ?: null])) }}"
               role="tab" aria-selected="{{ $tab === 'authors' ? 'true' : 'false' }}"
               class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium transition {{ $tab === 'authors' ? 'border-primary bg-primary-light text-primary' : 'border-gray-200 text-gray-500 hover:bg-gray-50' }}">
                Penulis
                <span class="rounded-full px-2 py-0.5 text-xs {{ $tab === 'authors' ? 'bg-white/70' : 'bg-gray-100' }}">{{ $authorCount }}</span>
            </a>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
            <table class="table-cards w-full text-sm min-w-[560px]">
                <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
                    <tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $u)
                        <tr>
                            <td class="px-4 py-3" data-label="User">
                                <p class="font-medium text-gray-900">{{ $u->name }}</p>
                                <p class="text-xs text-gray-400">{{ $u->email }}</p>
                            </td>
                            <td class="px-4 py-3" data-label="Roles">
                                <form method="POST" action="{{ route('admin.users.roles', $u) }}" @submit.prevent="saveRoles($event)" class="flex flex-wrap gap-1 items-center">
                                    @csrf @method('PUT')
                                    @foreach(\App\Models\User::ROLES as $r)
                                        <label class="flex items-center gap-1 text-xs border rounded-full px-2 py-0.5 cursor-pointer {{ $u->hasRole($r) ? 'border-primary bg-primary-light' : 'border-gray-200' }}" title="Klik untuk ubah role">
                                            <input type="checkbox" name="roles[]" value="{{ $r }}" @checked($u->hasRole($r)) class="w-3 h-3" onchange="this.form.requestSubmit()">
                                            {{ $r }}
                                        </label>
                                    @endforeach
                                </form>
                            </td>
                            <td class="px-4 py-3" data-label="Status">
                                <span data-status class="text-xs {{ $u->is_active ? 'text-green-600' : 'text-red-500' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right" data-label="Aksi" x-data="{ on: @js($u->is_active) }">
                                @php($blockers = $u->deleteBlockers())
                                @php($isSelf = $u->id === auth()->id())
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" @click="openEditUser(@js(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'institution' => $u->institution, 'expertise' => $u->expertise, 'orcid' => $u->orcid, 'phone' => $u->phone]))" :disabled="saving" title="Edit data user"
                                            class="inline-flex items-center justify-center rounded-lg p-1.5 text-primary hover:bg-primary-light transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0 2.638 2.638M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <form method="POST" action="{{ route('admin.users.toggle', $u) }}" @submit.prevent="toggleActive($event)">
                                    @csrf
                                    <button type="submit" :disabled="saving" :title="on ? 'Nonaktifkan user' : 'Aktifkan user'"
                                            :class="on ? 'text-red-500 hover:bg-red-50' : 'text-green-600 hover:bg-green-50'"
                                            class="inline-flex items-center justify-center rounded-lg p-1.5 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg x-show="on" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        <svg x-cloak x-show="!on" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                    </button>
                                </form>
                                <button type="button" @click="openDeleteUser(@js(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'blockers' => $blockers]))" :disabled="saving || {{ $blockers !== [] || $isSelf ? 'true' : 'false' }}"
                                        :title="{{ $isSelf ? 'Anda tidak dapat menghapus akun sendiri' : ($blockers ? 'Tidak dapat dihapus: '.e(implode(', ', $blockers)) : 'Hapus user') }}"
                                        class="inline-flex items-center justify-center rounded-lg p-1.5 text-red-500 hover:bg-red-50 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Tidak ada user di tab ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>

    {{-- Modal popup form tambah user --}}
    <x-modal name="user-add-modal" maxWidth="lg" focusable>
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Tambah User</h3>
            <form method="POST" action="{{ route('admin.users.store') }}" @submit.prevent="addUser($event)" class="space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label>Nama</x-input-label>
                        <input type="text" name="name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Email</x-input-label>
                        <input type="email" name="email" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Password (min 8)</x-input-label>
                        <input type="password" name="password" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Ulangi password</x-input-label>
                        <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                </div>
                <div>
                    <x-input-label>Institusi (opsional)</x-input-label>
                    <input type="text" name="institution" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 mb-1">Roles</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach(\App\Models\User::ROLES as $r)
                            <label class="role-pill flex items-center gap-1 text-xs border border-gray-200 rounded-full px-2 py-0.5 cursor-pointer">
                                <input type="checkbox" name="roles[]" value="{{ $r }}" class="w-3 h-3">
                                {{ $r }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="$dispatch('close-modal', 'user-add-modal')" :disabled="saving"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving" class="bg-primary hover:bg-primary-dark text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-text="saving ? 'Menyimpan…' : 'Tambah User'">Tambah User</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
    {{-- Modal popup form edit data user --}}
    <x-modal name="user-edit-modal" maxWidth="lg" focusable>
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4" x-text="editing ? 'Edit User: ' + editing.name : 'Edit User'"></h3>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label>Nama</x-input-label>
                        <input type="text" x-model="editForm.name" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Email</x-input-label>
                        <input type="email" x-model="editForm.email" required class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Institusi</x-input-label>
                        <input type="text" x-model="editForm.institution" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>ORCID (opsional)</x-input-label>
                        <input type="text" x-model="editForm.orcid" maxlength="19" placeholder="0000-0000-0000-0000" class="mt-1 w-full rounded-lg border-gray-300 text-sm border font-mono">
                    </div>
                </div>
                <div>
                    <x-input-label>Keahlian / bidang (opsional)</x-input-label>
                    <textarea x-model="editForm.expertise" rows="2" class="mt-1 w-full rounded-lg border-gray-300 text-sm border"></textarea>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label>No. telepon (opsional)</x-input-label>
                        <input type="text" x-model="editForm.phone" maxlength="30" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Password baru (opsional)</x-input-label>
                        <input type="password" x-model="editForm.password" placeholder="Kosongkan untuk tetap" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <x-input-label>Ulangi password baru</x-input-label>
                        <input type="password" x-model="editForm.password_confirmation" class="mt-1 w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="$dispatch('close-modal', 'user-edit-modal')" :disabled="saving"
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                        Batal
                    </button>
                    <button type="button" @click="saveEdit()" :disabled="saving" class="bg-primary hover:bg-primary-dark text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-text="saving ? 'Menyimpan…' : 'Simpan Perubahan'">Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </div>
    </x-modal>
    {{-- Modal popup konfirmasi hapus user --}}
    <x-modal name="user-delete-modal" maxWidth="sm" focusable>
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">Hapus user?</h3>
            <p class="mt-2 text-sm text-gray-600">
                User <strong x-text="deleting ? deleting.name : ''"></strong>
                <span class="text-gray-400" x-text="deleting ? deleting.email : ''"></span>
                akan dihapus permanen dari jurnal. Tindakan ini tidak dapat dibatalkan.
            </p>
            <p class="mt-2 text-xs text-gray-500">Hanya user tanpa riwayat proses (naskah, review, keputusan editorial, dsb.) yang dapat dihapus.</p>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'user-delete-modal')" :disabled="saving"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60">
                    Batal
                </button>
                <button type="button" @click="deleteUser()" :disabled="saving"
                        class="bg-red-600 hover:bg-red-700 text-white rounded-lg px-6 py-2.5 text-sm font-medium transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <span x-text="saving ? 'Menghapus…' : 'Hapus User'">Hapus User</span>
                </button>
            </div>
        </div>
    </x-modal>
</div>
@endsection

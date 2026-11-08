<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\JournalSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['setting' => JournalSetting::current()]);
    }

    public function update(Request $request): Response|RedirectResponse|JsonResponse
    {
        $setting = JournalSetting::current();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'issn_print' => ['nullable', 'string', 'max:20'],
            'issn_online' => ['nullable', 'string', 'max:20'],
            'scope' => ['nullable', 'string', 'max:5000'],
            'scope_en' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'description_en' => ['nullable', 'string', 'max:20000'],
            'ethics_policy' => ['nullable', 'string', 'max:20000'],
            'ethics_policy_en' => ['nullable', 'string', 'max:20000'],
            'oa_policy' => ['nullable', 'string', 'max:20000'],
            'oa_policy_en' => ['nullable', 'string', 'max:20000'],
            'apc_policy' => ['nullable', 'string', 'max:20000'],
            'apc_policy_en' => ['nullable', 'string', 'max:20000'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:2000'],
            'apc_enabled' => ['sometimes', 'boolean'],
            'apc_amount' => ['nullable', 'numeric', 'min:0'],
            'apc_currency' => ['required', 'size:3'],
            'apc_bank_text' => ['nullable', 'string', 'max:2000'],
            'review_mode' => ['required', 'in:single_blind,double_blind,open'],
            'max_review_rounds' => ['required', 'integer', 'min:1', 'max:10'],
            'review_deadline_days' => ['required', 'integer', 'min:1', 'max:180'],
            'reminder_before_days' => ['required', 'integer', 'min:0', 'max:30'],
            'online_first_enabled' => ['sometimes', 'boolean'],
            'doi_prefix' => ['nullable', 'string', 'max:40'],
            'doi_suffix_pattern' => ['nullable', 'string', 'max:60'],
            'theme.logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'theme.colors.primary' => ['required', 'string', 'max:20'],
            'theme.colors.accent' => ['required', 'string', 'max:20'],
        ]);

        // Gabung pengaturan tema: warna selalu dikirim, logo hanya jika file baru dipilih
        $logoFile = $data['theme']['logo'] ?? null;
        $colors = $data['theme']['colors'];
        unset($data['theme']);
        $theme = $setting->theme ?? [];
        $theme['colors'] = $colors;
        if ($logoFile instanceof \Illuminate\Http\UploadedFile) {
            $theme['logo'] = $logoFile->store('theme', 'local');
        }
        $data['theme'] = $theme;

        // parse teks rekening: "bank | nomor | nama" per baris
        $banks = [];
        foreach (explode("\n", (string) ($data['apc_bank_text'] ?? '')) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) >= 3) {
                $banks[] = ['bank' => $parts[0], 'no' => $parts[1], 'name' => $parts[2]];
            }
        }

        $setting->update($data + [
            'apc_enabled' => (bool) ($data['apc_enabled'] ?? $setting->apc_enabled),
            'online_first_enabled' => (bool) ($data['online_first_enabled'] ?? $setting->online_first_enabled),
            'apc_bank' => $banks,
        ]);

        JournalSetting::refreshCache();
        AuditLog::log($request->user()->id, 'settings.update', 'JournalSetting', $setting->id);

        $status = 'Pengaturan jurnal tersimpan.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $status]);
        }

        return back()->with('status', $status);
    }

    /**
     * CRUD anggota Dewan Editorial (AJAX, tanpa reload).
     */
    public function storeBoardMember(Request $request): JsonResponse
    {
        $setting = JournalSetting::current();
        $board = $setting->editorial_board ?? [];
        $board[] = $this->boardMemberData($request);
        $setting->update(['editorial_board' => $board]);
        JournalSetting::refreshCache();
        AuditLog::log($request->user()->id, 'settings.board_member_store', 'JournalSetting', $setting->id);

        return response()->json(['message' => 'Anggota dewan editorial berhasil ditambahkan.', 'board' => $setting->editorial_board]);
    }

    public function updateBoardMember(Request $request, int $index): JsonResponse
    {
        $setting = JournalSetting::current();
        $board = $setting->editorial_board ?? [];
        if (! array_key_exists($index, $board)) {
            return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
        }
        $board[$index] = array_merge($board[$index], $this->boardMemberData($request));
        $setting->update(['editorial_board' => $board]);
        JournalSetting::refreshCache();
        AuditLog::log($request->user()->id, 'settings.board_member_update', 'JournalSetting', $setting->id);

        return response()->json(['message' => 'Data anggota dewan editorial berhasil diperbarui.', 'board' => $setting->editorial_board]);
    }

    public function destroyBoardMember(Request $request, int $index): JsonResponse
    {
        $setting = JournalSetting::current();
        $board = $setting->editorial_board ?? [];
        if (! array_key_exists($index, $board)) {
            return response()->json(['message' => 'Anggota tidak ditemukan.'], 404);
        }
        // Hapus file foto dari disk jika ada
        if (! empty($board[$index]['photo'])) {
            Storage::disk('public')->delete($board[$index]['photo']);
        }
        unset($board[$index]);
        $board = array_values($board);
        $setting->update(['editorial_board' => $board]);
        JournalSetting::refreshCache();
        AuditLog::log($request->user()->id, 'settings.board_member_destroy', 'JournalSetting', $setting->id);

        return response()->json(['message' => 'Anggota dewan editorial berhasil dihapus.', 'board' => $setting->editorial_board]);
    }

    /**
     * Validasi data anggota + simpan foto (jika diupload).
     * Jika foto kosong, kunci 'photo' tidak dikembalikan sehingga foto lama tetap dipertahankan saat update.
     */
    private function boardMemberData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'orcid' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $photo = $data['photo'] ?? null;
        unset($data['photo']);
        if ($photo instanceof UploadedFile) {
            $data['photo'] = $photo->store('board', 'public');
        }

        return $data;
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'array'],
            'theme.logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'theme.colors.primary' => ['required', 'string', 'max:20'],
            'theme.colors.accent' => ['required', 'string', 'max:20'],
        ]);

        $setting = JournalSetting::current();
        $theme = $setting->theme ?? [];
        $theme['colors'] = $data['theme']['colors'];

        if (! empty($data['theme']['logo']) && $data['theme']['logo'] instanceof \Illuminate\Http\UploadedFile) {
            $theme['logo'] = $data['theme']['logo']->store('theme', 'public');
        }

        $setting->update(['theme' => $theme]);
        JournalSetting::refreshCache();
        AuditLog::log($request->user()->id, 'settings.theme_update', 'JournalSetting', $setting->id);

        return back()->with('status', 'Tema diperbarui.');
    }
}

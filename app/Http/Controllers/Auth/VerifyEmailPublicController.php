<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailPublicController extends Controller
{
    /**
     * Tautan verifikasi diakses tanpa login: user yang tervervalidasi
     * otomatis login setelah email terverifikasi.
     *
     * Keamanan: middleware 'signed' (URL bertanda tangan, berlaku 2 hari)
     * + pembanding hash sha1(email) seperti EmailVerificationRequest bawaan.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Tautan verifikasi tidak valid.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        // otomatis login (atau pertahankan sesi bila sudah login sebagai user yang sama)
        if (! Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Email berhasil diverifikasi. Selamat datang, '.$user->name.'!');
    }
}

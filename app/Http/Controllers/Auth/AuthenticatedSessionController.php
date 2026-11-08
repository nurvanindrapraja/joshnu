<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        // Email belum diverifikasi: tolak login, arahkan kembali ke halaman login.
        if (! $request->user()->hasVerifiedEmail()) {
            $email = $request->user()->email;
            Auth::guard('web')->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'unverified' => true,
                    'email' => $email,
                    'message' => 'Email Anda ('.$email.') belum diverifikasi. Klik tautan verifikasi di email Anda, atau kirim ulang tautannya di bawah.',
                ], 422);
            }

            return redirect()->route('login')->with('unverified', $email);
        }

        $request->session()->regenerate();

        $target = route('dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Login berhasil. Selamat datang kembali!',
                'redirect' => $target,
            ]);
        }

        return redirect()->intended($target);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailPublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Verifikasi email diakses TANPA login: setelah klik tautan, user otomatis login.
    Route::get('verify-email/{id}/{hash}', VerifyEmailPublicController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Kirim ulang tautan verifikasi (tanpa login) — respons selalu sama agar email tidak dapat ditebak.
    Route::post('email/resend-verification', function (Request $request) {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        $user = \App\Models\User::where('email', $data['email'])->first();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Jika email terdaftar dan belum diverifikasi, tautan verifikasi telah dikirim ulang.');
    })->middleware('throttle:6,1')->name('verification.resend');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Fallback GET (mis. URL diketik langsung): tidak error 405,
    // redirect kembali dengan pemberitahuan. Logout tetap hanya via POST (CSRF).
    Route::get('logout', function () {
        return redirect()->back()
            ->with('error', 'Logout hanya dapat dilakukan melalui tombol Logout.');
    })->name('logout.get');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

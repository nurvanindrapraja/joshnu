<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Email belum diverifikasi: blokir login + kirim ulang tautan.
         Ditampilkan dari flash session (bukan-AJAX) atau oleh JS saat
         login AJAX mengembalikan 422 {unverified}. -->
    <div data-unverified-box
         @if (! session('unverified')) hidden @endif
         class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold">Email belum diverifikasi</p>
        <p class="mt-1 leading-relaxed">
            Akun <span class="font-medium" data-unverified-email>{{ session('unverified', '') }}</span> sudah terdaftar, namun Anda belum dapat login.
            Silakan klik tautan verifikasi yang kami kirim ke email tersebut.
        </p>
        <form method="POST" action="{{ route('verification.resend') }}" class="mt-3">
            @csrf
            <input type="hidden" name="email" data-unverified-email-input value="{{ session('unverified', '') }}">
            <button type="submit" class="inline-flex items-center rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-800 shadow-sm hover:bg-amber-100 transition">
                Kirim ulang tautan verifikasi
            </button>
        </form>
    </div>

    <div class="text-center text-sm text-gray-600 mb-4">
        {{ __("Don't have an account yet?") }}
        <a class="font-medium text-indigo-600 underline hover:text-indigo-900" href="{{ route('register') }}">
            {{ __('Register') }}
        </a>
    </div>

    <form method="POST" action="{{ route('login') }}" data-ajax data-ajax-redirect>
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <a href="{{ route('home') }}"
       class="mt-4 -mx-6 -mb-4 block py-3 text-center text-sm font-medium text-white bg-primary transition-colors hover:bg-primary-dark">
        {{ __('Back to Home') }}
    </a>

    <script>
        // Login via AJAX mengembalikan 422 {unverified} → tampilkan peringatan di atas
        window.addEventListener('auth:unverified', (event) => {
            const box = document.querySelector('[data-unverified-box]');
            if (!box) return;
            const email = (event.detail && event.detail.email) || '';
            box.hidden = false;
            const label = box.querySelector('[data-unverified-email]');
            if (label) label.textContent = email;
            const input = box.querySelector('[data-unverified-email-input]');
            if (input) input.value = email;
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    </script>
</x-guest-layout>

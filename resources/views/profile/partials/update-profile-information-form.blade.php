<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Profil</h2>
        <p class="mt-1 text-sm text-gray-600">
            Perbarui informasi profil, institusi, ORCID, dan keahlian Anda.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" :value="'Nama'" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="orcid" :value="'ORCID (opsional)'" />
                <x-text-input id="orcid" name="orcid" type="text" class="mt-1 block w-full font-mono" placeholder="0000-0002-1825-0097" :value="old('orcid', $user->orcid)" />
                <x-input-error class="mt-2" :messages="$errors->get('orcid')" />
            </div>
            <div>
                <x-input-label for="institution" :value="'Institusi'" />
                <x-text-input id="institution" name="institution" type="text" class="mt-1 block w-full" :value="old('institution', $user->institution)" />
                <x-input-error class="mt-2" :messages="$errors->get('institution')" />
            </div>
            <div>
                <x-input-label for="expertise" :value="'Bidang keahlian'" />
                <x-text-input id="expertise" name="expertise" type="text" class="mt-1 block w-full" :value="old('expertise', $user->expertise)" />
                <x-input-error class="mt-2" :messages="$errors->get('expertise')" />
            </div>
            <div>
                <x-input-label for="phone" :value="'Telepon'" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="website" :value="'Website'" />
                <x-text-input id="website" name="website" type="text" class="mt-1 block w-full" :value="old('website', $user->website)" />
                <x-input-error class="mt-2" :messages="$errors->get('website')" />
            </div>
        </div>

        <div>
            <x-input-label for="address" :value="'Alamat'" />
            <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('address', $user->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (session('status') === 'verification-link-sent')
                <p class="mt-2 font-medium text-sm text-green-600">
                    Tautan verifikasi baru telah dikirim ke email Anda.
                </p>
            @endif
        </div>

        <div>
            <p class="text-sm text-gray-500 mb-2">Role Anda di jurnal ini:</p>
            <div class="flex flex-wrap gap-1">
                @foreach($user->roles as $role)
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-0.5 text-xs font-medium text-gray-700">{{ $role->role }}</span>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">Tersimpan.</p>
            @endif
        </div>
    </form>
</section>

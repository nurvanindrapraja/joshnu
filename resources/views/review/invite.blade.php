<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan Review — {{ \App\Models\JournalSetting::current()->short_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="max-w-lg w-full rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="bg-primary text-white px-6 py-5">
            <p class="text-xs uppercase tracking-wider text-primary-light">Undangan Peer Review</p>
            <h1 class="text-lg font-bold mt-1">{{ \App\Models\JournalSetting::current()->name }}</h1>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600">Anda diundang untuk mereview naskah berikut:</p>
            <div class="mt-4 rounded-lg bg-gray-50 border border-gray-100 p-4">
                <p class="font-semibold text-gray-900">{{ $review->blindTitle() }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    #{{ $submission->reference_no }} • {{ $submission->section?->name }}
                    @if($review->due_at) • Batas: <strong>{{ $review->due_at->format('d M Y') }}</strong>@endif
                </p>
            </div>

            @if(auth()->check())
                <div class="mt-5">
                    <p class="text-sm text-gray-700 mb-3">Anda sedang login sebagai <strong>{{ auth()->user()->name }}</strong>. Terimalah undangan ini untuk mulai review.</p>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('reviewer.invite.accept', $review->invite_token) }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <button class="flex-1 bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2.5 text-sm font-medium">{{ __('review.accept') }} & Mulai Review</button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('reviewer.invite.accept', $review->invite_token) }}" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm border" placeholder="Nama lengkap Anda">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Email (akan dibuat akun reviewer)</label>
                        <input type="email" name="email" value="{{ $review->invite_email }}" required class="w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Institusi <span class="text-gray-400">(opsional)</span></label>
                        <input type="text" name="institution" class="w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Bidang keahlian <span class="text-gray-400">(opsional)</span></label>
                        <input type="text" name="expertise" class="w-full rounded-lg border-gray-300 text-sm border">
                    </div>
                    <p class="text-[11px] text-gray-400">Dengan menerima, akun reviewer dibuat secara otomatis (Anda dapat login dengan email tersebut di lain waktu).</p>
                    <div class="flex gap-3 pt-1">
                        <button class="flex-1 bg-primary hover:bg-primary-dark text-white rounded-lg px-4 py-2.5 text-sm font-medium">{{ __('review.accept') }} Undangan</button>
                        <details class="relative">
                            <summary class="border border-gray-300 text-gray-600 hover:text-red-600 rounded-lg px-4 py-2.5 text-sm cursor-pointer">{{ __('review.decline') }}</summary>
                            <div class="absolute z-10 mt-2 w-72 rounded-xl border border-gray-200 bg-white shadow-lg p-4">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Alasan penolakan</label>
                                <textarea name="reason" rows="3" class="w-full rounded-lg border-gray-300 text-sm border" placeholder="mis. beban kerja, konflik minat…"></textarea>
                                <button type="submit" class="mt-3 w-full bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm font-medium">Kirim Penolakan</button>
                            </div>
                        </details>
                    </div>
                </form>
            @endif
        </div>
    </div>
</body>
</html>

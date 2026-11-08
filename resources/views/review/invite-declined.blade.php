<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undangan Ditolak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="max-w-md w-full rounded-2xl border border-gray-200 bg-white shadow-sm p-8 text-center">
        <div class="h-12 w-12 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </div>
        <h1 class="font-bold text-lg text-gray-900">Undangan Ditolak</h1>
        <p class="mt-2 text-sm text-gray-600">Terima kasih atas respons Anda. Editor akan menyampaikan kepada tim redaksi dan mencari reviewer pengganti.</p>
        @if($review->decline_reason)
            <p class="mt-3 text-xs text-gray-400">Catatan: {{ $review->decline_reason }}</p>
        @endif
    </div>
</body>
</html>

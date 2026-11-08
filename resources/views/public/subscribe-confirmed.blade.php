@extends('layouts.app')
@section('title', 'Langganan Terkonfirmasi')
@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="rounded-xl border border-gray-200 bg-white p-8 text-center">
        <div class="h-12 w-12 mx-auto rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
        </div>
        <h1 class="font-bold text-lg text-gray-900">Langganan Berhasil</h1>
        <p class="mt-2 text-sm text-gray-600">Anda akan menerima email alert setiap ada issue baru. Anda dapat berhenti berlangganan kapan saja melalui tautan di email kami.</p>
        <a href="{{ route('home') }}" class="mt-4 inline-block text-sm text-primary hover:underline">← Kembali ke Beranda</a>
    </div>
</div>
@endsection

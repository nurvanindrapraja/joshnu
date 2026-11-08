@extends('layouts.app')
@section('title', __('nav.about').' — '.$setting->name)
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('nav.about') }} {{ $setting->name }}</h1>

    <div class="space-y-8">
        <img src="{{ asset('images/about_joshnu.jpeg') }}" alt="{{ __('about.illustration_alt') }}" class="w-full h-auto block rounded-xl border border-gray-200">

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-bold mb-3">{{ __('about.journal_scope') }}</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $setting->text('scope') }}</p>
            @if($setting->text('description'))
                <p class="mt-4 text-sm text-gray-600 whitespace-pre-line">{{ $setting->text('description') }}</p>
            @endif
        </section>

        @if(!empty($setting->editorial_board))
        <section class="rounded-xl border border-gray-200 bg-white p-6" id="board">
            <h2 class="text-lg font-bold mb-4">{{ __('footer.board') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach($setting->editorial_board as $member)
                    <div class="rounded-lg bg-gray-50 p-3 flex items-center gap-3">
                        @if(!empty($member['photo']))
                            <img src="{{ asset('storage/'.$member['photo']) }}" alt="{{ $member['name'] ?? '' }}" class="h-12 w-12 shrink-0 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="h-12 w-12 shrink-0 rounded-full bg-gray-200 flex items-center justify-center">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-sm text-gray-900">{{ $member['name'] ?? '' }}</p>
                            <p class="text-xs text-gray-500">{{ $member['title'] ?? '' }} {{ $member['institution'] ? '— '.$member['institution'] : '' }}</p>
                            @if(!empty($member['orcid']))<p class="text-xs font-mono text-gray-400 mt-1">ORCID: {{ $member['orcid'] }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6" id="ethics">
            <h2 class="text-lg font-bold mb-3">{{ __('about.ethics') }}</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $setting->text('ethics_policy') ?? __('about.ethics_fallback') }}</p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6" id="open-access">
            <h2 class="text-lg font-bold mb-3">{{ __('about.open_access') }}</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $setting->text('oa_policy') ?? __('about.oa_fallback') }}</p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6" id="apc">
            <h2 class="text-lg font-bold mb-3">{{ __('about.apc') }}</h2>
            <p class="text-sm text-gray-600 whitespace-pre-line">
                @if($setting->apc_enabled)
                    {{ __('about.apc_charge') }} <strong>{{ $setting->apc_currency }} {{ number_format((float) $setting->apc_amount, 0, ',', '.') }}</strong>.
                @else
                    {{ __('about.no_apc') }}
                @endif
                {{ $setting->text('apc_policy') }}
            </p>
            @if($setting->apc_enabled && !empty($setting->apc_bank))
                <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm">
                    @foreach($setting->apc_bank as $bank)
                        <p>{{ $bank['bank'] }}: <span class="font-mono">{{ $bank['no'] }}</span> {{ __('about.bank_an') }} {{ $bank['name'] }}</p>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6" id="contact">
            <h2 class="text-lg font-bold mb-3">{{ __('footer.contact') }}</h2>
            <p class="text-sm text-gray-600">{{ $setting->contact_email }}</p>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $setting->address }}</p>
        </section>
    </div>
</div>
@endsection

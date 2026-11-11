@extends('layouts.app')
@section('title', $page->title.' — '.($setting ?? \App\Models\JournalSetting::current())->name)
@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10">
    <nav class="text-xs text-gray-400 mb-6" aria-label="breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-primary">{{ __('home.breadcrumb_home') }}</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-600">{{ $page->title }}</span>
    </nav>

    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $page->title }}</h1>
    <hr class="my-6 border-gray-100">

    @if($page->image_path)
        <img src="{{ $page->image_url }}" alt="{{ $page->title }}" class="mb-6 w-full rounded-xl border border-gray-100" style="max-height: 320px; object-fit: cover;">
    @endif

    <div class="page-content text-gray-700 leading-relaxed
                [&_h1]:text-2xl [&_h1]:font-bold [&_h1]:text-gray-900 [&_h1]:mt-6 [&_h1]:mb-3
                [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-gray-900 [&_h2]:mt-6 [&_h2]:mb-3
                [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-gray-900 [&_h3]:mt-5 [&_h3]:mb-2
                [&_h4]:text-base [&_h4]:font-semibold [&_h4]:text-gray-900 [&_h4]:mt-4 [&_h4]:mb-2
                [&_p]:my-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-3
                [&_blockquote]:border-l-4 [&_blockquote]:border-gray-200 [&_blockquote]:pl-4 [&_blockquote]:text-gray-500 [&_blockquote]:my-4
                [&_a]:text-primary [&_a]:underline [&_b]:text-gray-900 [&_strong]:text-gray-900 [&_em]:italic
                [&_table]:w-full [&_table]:my-4 [&_table]:text-sm [&_th]:border [&_th]:border-gray-300 [&_th]:bg-gray-50 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left
                [&_td]:border [&_td]:border-gray-300 [&_td]:px-3 [&_td]:py-2
                [&_pre]:bg-gray-50 [&_pre]:border [&_pre]:border-gray-200 [&_pre]:rounded-lg [&_pre]:p-3 [&_pre]:text-xs [&_pre]:overflow-x-auto
                [&_img]:max-w-full [&_hr]:my-6 [&_hr]:border-gray-200">
        {!! $page->content !!}
    </div>
</div>
@endsection

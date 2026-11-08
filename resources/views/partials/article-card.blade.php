@props(['publication'])
<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:shadow transition flex flex-col">
    <div class="flex items-center gap-2 mb-2">
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ optional($publication->issue)->label() ?? 'Online First' }}</span>
    </div>
    <h3 class="font-semibold text-gray-900 leading-snug mb-2">
        <a href="{{ route('articles.show', $publication->slug) }}" class="hover:text-primary">{{ $publication->titleFor(app()->isLocale('en') ? 'en' : 'id') }}</a>
    </h3>
    <p class="text-sm text-gray-500 mb-3">{{ $publication->authorString() }}</p>
    <div class="mt-auto flex items-center gap-3 text-xs text-gray-500">
        <a href="{{ route('articles.show', $publication->slug) }}" class="text-primary font-medium hover:underline">{{ __('common.view') }}</a>
        @if($publication->versionOfRecord())
            <a href="{{ route('articles.download', [$publication->slug, $publication->versionOfRecord()->id]) }}" class="hover:text-primary">PDF</a>
        @endif
        @if($publication->doi)
            <span class="truncate font-mono" title="{{ $publication->doi }}">DOI</span>
        @endif
        <span class="ml-auto flex items-center gap-1"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>{{ number_format($publication->views) }}</span>
    </div>
</div>

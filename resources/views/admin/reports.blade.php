@extends('layouts.workspace')
@section('title', 'Reports & Analytics')
@section('content')
<div class="max-w-6xl mx-auto">
    <form method="GET" data-ajax-filter class="flex flex-wrap items-end gap-2 mb-5">
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">Dari</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border-gray-300 text-sm border"></div>
        <div><label class="block text-xs font-semibold text-gray-500 mb-1">S/d</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border-gray-300 text-sm border"></div>
        <a href="{{ route('admin.reports.index') }}" data-ajax-reset class="border border-gray-300 text-gray-600 hover:text-gray-900 rounded-lg px-4 py-2 text-sm">Reset</a>
    </form>

    <div data-ajax-results>
    <div class="flex flex-wrap gap-2 justify-end mb-3">
        <a href="{{ route('admin.reports.usage.csv', ['from' => $from, 'to' => $to]) }}" class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-2">usage.csv</a>
        <a href="{{ route('admin.reports.editorial.csv') }}" class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-2">editorial.csv</a>
        <a href="{{ route('admin.reports.finance.csv') }}" class="text-xs border border-gray-300 hover:border-primary rounded-lg px-3 py-2">finance.csv</a>
        <a href="{{ route('admin.reports.doaj') }}" class="text-xs bg-primary text-white hover:bg-primary-dark rounded-lg px-3 py-2">DOAJ export (ZIP)</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-2xl font-bold">{{ number_format($totalViews) }}</p><p class="text-xs text-gray-500 mt-1">Total views (periode)</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-2xl font-bold">{{ number_format($totalDownloads) }}</p><p class="text-xs text-gray-500 mt-1">Total downloads</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-2xl font-bold">{{ $acceptanceRate }}%</p><p class="text-xs text-gray-500 mt-1">Acceptance rate</p></div>
        @php($verifiedTotal = $finance->where('status', 'verified')->sum('amount'))
        <div class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-2xl font-bold">Rp {{ number_format((float) $verifiedTotal, 0, ',', '.') }}</p><p class="text-xs text-gray-500 mt-1">APC terkumpul</p></div>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900 mb-3">Top Artikel</h3>
            <table class="table-cards w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    @forelse($topArticles as $p)
                        <tr>
                            <td class="py-2 pr-2" data-label="Artikel" data-full>
                                <a href="{{ route('articles.show', $p->slug) }}" class="text-primary hover:underline text-xs">{{ Str::limit($p->titleFor('id'), 50) }}</a>
                            </td>
                            <td class="py-2 text-right text-xs text-gray-500 whitespace-nowrap" data-label="Views / Unduhan">{{ number_format($p->views) }} views • {{ number_format($p->downloads) }} dl</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-gray-400 text-xs">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900 mb-3">Pipeline Naskah</h3>
            <div class="space-y-2 text-sm">
                @foreach(\App\Models\Submission::STAGE_LABELS as $st => $label)
                    @php($count = $stageCounts->get($st)?->total ?? 0)
                    @php($avg = $avgDaysByStage[$st] ?? null)
                    <div class="flex items-center gap-2">
                        <span class="w-36 text-xs text-gray-500 shrink-0">{{ $label }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ $count ? min(100, $count * 12) : 0 }}%"></div>
                        </div>
                        <span class="text-xs text-gray-600 w-8 text-right">{{ $count }}</span>
                        @if($avg !== null)<span class="text-[10px] text-gray-400 w-16 text-right">r{{ $avg }} hari</span>@endif
                    </div>
                @endforeach
            </div>
            @if($avgReviewDays)
                <p class="mt-3 text-xs text-gray-500">Rata-rata turnaround review: <strong>{{ round((float) $avgReviewDays, 1) }} hari</strong></p>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900 mb-3">Tren Bulanan</h3>
            @php($maxTrend = max(1, $monthlyTrend->map(fn ($t) => $t['view'] + $t['download'])->max()))
            <div class="flex items-end gap-2 h-32">
                @forelse($monthlyTrend as $month => $t)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full flex flex-col justify-end" style="height: 80px">
                            <div class="bg-primary rounded-t" style="height: {{ round($t['view'] / $maxTrend * 100) }}%" title="views: {{ $t['view'] }}"></div>
                            <div class="bg-primary-light" style="height: {{ round($t['download'] / $maxTrend * 100) }}%" title="downloads: {{ $t['download'] }}"></div>
                        </div>
                        <span class="text-[10px] text-gray-400">{{ substr($month, 5) }}/{{ substr($month, 2, 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">Belum ada data usage.</p>
                @endforelse
            </div>
            <p class="mt-2 text-[10px] text-gray-400">■ views ■ downloads</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900 mb-3">Keuangan APC (periode)</h3>
            <table class="table-cards w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-400 uppercase"><th class="pb-2">Status</th><th class="pb-2 text-right">Jml</th><th class="pb-2 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($finance as $row)
                        <tr>
                            <td class="py-1.5" data-label="Status">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                            <td class="py-1.5 text-right" data-label="Jml">{{ $row->total }}</td>
                            <td class="py-1.5 text-right" data-label="Total">Rp {{ number_format((float) $row->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-gray-400 text-xs">Belum ada tagihan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
@endsection

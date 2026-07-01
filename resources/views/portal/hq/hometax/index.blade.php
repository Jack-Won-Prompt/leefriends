@extends('portal.layout')
@section('title', '매출/매입 관리')

@section('content')
@php
    use App\Models\HometaxCollectJob;
    $isBuy = $type === HometaxCollectJob::TYPE_BUY;
    $defStart = now()->startOfMonth()->format('Y-m-d');
    $defEnd = now()->format('Y-m-d');
    $polling = $selected && ! $selected->isDone();
@endphp

<x-wms.page-head title="매출/매입 관리" subtitle="국세청 홈택스에 등록된 본사의 매출·매입 전자세금계산서를 수집·조회합니다." icon="📊">
</x-wms.page-head>

{{-- 연동 상태 --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <div class="rounded-2xl bg-white p-5 shadow-sm border border-neutral-100 flex items-center justify-between">
        <div>
            <p class="text-sm text-neutral-500 font-medium">홈택스 수집 공동인증서</p>
            @if ($certExpire)
                <p class="text-lg font-black text-emerald-600 mt-1">등록됨 · 만료 {{ \Illuminate\Support\Str::of($certExpire)->substr(0,8)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3') }}</p>
            @else
                <p class="text-lg font-black text-rose-500 mt-1">미등록</p>
                <p class="text-xs text-neutral-400 mt-0.5">홈택스 수집을 위해 공동인증서 등록이 필요합니다.</p>
            @endif
        </div>
        <a href="{{ route('portal.hq.hometax.cert') }}" target="_blank"
           class="shrink-0 inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm transition">인증서 등록</a>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm border border-neutral-100 flex items-center justify-between">
        <div>
            <p class="text-sm text-neutral-500 font-medium">정액제 상태</p>
            @php $frState = $flatRate?->state; @endphp
            @if ($frState === 1)
                <p class="text-lg font-black text-emerald-600 mt-1">사용 중</p>
                <p class="text-xs text-neutral-400 mt-0.5">만료 {{ \Illuminate\Support\Str::of((string) ($flatRate?->useEndDate ?? ''))->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3') }}</p>
            @else
                <p class="text-lg font-black text-neutral-500 mt-1">미신청 / 건당 과금</p>
                <p class="text-xs text-neutral-400 mt-0.5">정액제 신청 시 수집 건당 과금이 면제됩니다.</p>
            @endif
        </div>
        <a href="{{ route('portal.hq.hometax.flatrate') }}" target="_blank"
           class="shrink-0 inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm transition">정액제 신청</a>
    </div>
</div>

{{-- 매출/매입 탭 --}}
<div class="flex items-center gap-2 mb-4">
    <a href="{{ route('portal.hq.hometax.index', ['type' => 'SELL']) }}"
       class="px-5 py-2 rounded-xl text-sm font-bold transition {{ ! $isBuy ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">매출</a>
    <a href="{{ route('portal.hq.hometax.index', ['type' => 'BUY']) }}"
       class="px-5 py-2 rounded-xl text-sm font-bold transition {{ $isBuy ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">매입</a>
</div>

{{-- 수집 요청 --}}
<x-wms.panel class="mb-5">
    <form method="POST" action="{{ route('portal.hq.hometax.request') }}" class="p-5 flex flex-wrap items-end gap-3">
        @csrf
        <input type="hidden" name="ti_type" value="{{ $type }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">기준일자</label>
            <select name="date_type" class="rounded-xl border-neutral-200 text-sm py-2">
                <option value="W">작성일자</option>
                <option value="I">발행일자</option>
                <option value="S">전송일자</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="start_date" value="{{ old('start_date', $defStart) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="end_date" value="{{ old('end_date', $defEnd) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">
            🔄 {{ $isBuy ? '매입' : '매출' }} 수집 요청
        </button>
        @error('start_date') <p class="w-full text-xs text-rose-500">{{ $message }}</p> @enderror
        @error('end_date') <p class="w-full text-xs text-rose-500">{{ $message }}</p> @enderror
    </form>
</x-wms.panel>

<div x-data="hometax({{ $polling ? 'true' : 'false' }}, '{{ $selected->job_id ?? '' }}')">

{{-- 수집 이력 --}}
@if ($jobs->isNotEmpty())
<x-wms.panel class="mb-5">
    <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">수집 이력</div>
    <div class="divide-y divide-neutral-100">
        @foreach ($jobs as $j)
            <a href="{{ route('portal.hq.hometax.index', ['type' => $j->ti_type, 'job_id' => $j->job_id]) }}"
               class="flex items-center justify-between px-5 py-3 text-sm hover:bg-neutral-50 {{ optional($selected)->id === $j->id ? 'bg-mango-50/60' : '' }}">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $j->ti_type === 'BUY' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $j->typeLabel() }}</span>
                    <span class="font-medium text-neutral-700">
                        {{ \Illuminate\Support\Str::of($j->start_date)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1.$2.$3') }}
                        ~ {{ \Illuminate\Support\Str::of($j->end_date)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1.$2.$3') }}
                    </span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    @if ($j->job_state === 3)
                        <span class="text-emerald-600 font-bold">완료 · {{ number_format((int) $j->collect_count) }}건</span>
                    @elseif ($j->error_code)
                        <span class="text-rose-500 font-bold">오류 {{ $j->error_code }}</span>
                    @else
                        <span class="text-amber-500 font-bold">{{ $j->stateLabel() }}</span>
                    @endif
                    <span class="text-neutral-400">{{ $j->created_at->format('m/d H:i') }}</span>
                </div>
            </a>
        @endforeach
    </div>
</x-wms.panel>
@endif

{{-- 수집 진행 안내 (폴링) --}}
<div x-show="polling" x-cloak class="mb-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium flex items-center gap-2">
    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
    <span x-text="stateLabel">수집 중…</span> — 완료되면 자동으로 목록이 표시됩니다.
</div>

@if ($error)
    <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3.5 text-sm font-medium">조회 오류: {{ $error }}</div>
@endif

{{-- 요약 --}}
@if ($summary)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <x-wms.stat label="건수" :value="number_format((int) ($summary->count ?? 0)).'건'" variant="info" icon="🧾" />
    <x-wms.stat label="공급가액" :value="number_format((int) ($summary->supplyCostTotal ?? 0)).'원'" variant="default" />
    <x-wms.stat label="세액" :value="number_format((int) ($summary->taxTotal ?? 0)).'원'" variant="warn" />
    <x-wms.stat label="합계" :value="number_format((int) ($summary->amountTotal ?? 0)).'원'" :variant="$isBuy ? 'info' : 'success'" icon="💰" />
</div>
@endif

{{-- 결과 목록 --}}
@if ($list)
<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3 w-28">작성일</th>
                <th class="text-left font-semibold px-5 py-3">{{ $isBuy ? '공급자(매입처)' : '공급받는자(매출처)' }}</th>
                <th class="text-left font-semibold px-5 py-3 w-32">사업자번호</th>
                <th class="text-right font-semibold px-5 py-3 w-28">공급가액</th>
                <th class="text-right font-semibold px-5 py-3 w-24">세액</th>
                <th class="text-right font-semibold px-5 py-3 w-28">합계</th>
                <th class="text-right font-semibold px-5 py-3 w-20">상세</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($list->list as $r)
                @php
                    $counterName = $isBuy ? ($r->invoicerCorpName ?? '') : ($r->invoiceeCorpName ?? '');
                    $counterNum = $isBuy ? ($r->invoicerCorpNum ?? '') : ($r->invoiceeCorpNum ?? '');
                @endphp
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 text-neutral-600">{{ \Illuminate\Support\Str::of((string) $r->writeDate)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1.$2.$3') }}</td>
                    <td class="px-5 py-3 font-medium text-neutral-800">
                        {{ $counterName ?: '-' }}
                        @if (($r->taxType ?? '') && $r->taxType !== '과세')
                            <span class="ml-1 text-xs text-neutral-400">({{ $r->taxType }})</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-neutral-500">{{ $counterNum ?: '-' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ number_format((int) ($r->supplyCostTotal ?? 0)) }}</td>
                    <td class="px-5 py-3 text-right tabular-nums text-neutral-500">{{ number_format((int) ($r->taxTotal ?? 0)) }}</td>
                    <td class="px-5 py-3 text-right tabular-nums font-bold">{{ number_format((int) ($r->totalAmount ?? 0)) }}</td>
                    <td class="px-5 py-3 text-right">
                        <button type="button" @click="openDetail('{{ $r->ntsconfirmNum }}')" class="text-mango-600 hover:underline font-semibold">보기</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-neutral-400">수집된 세금계산서가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- 페이지네이션 --}}
    @php $totalPages = $list->pageCount ?? 1; @endphp
    @if ($totalPages > 1)
        <div class="flex items-center justify-center gap-1 py-4">
            @for ($p = 1; $p <= $totalPages; $p++)
                <a href="{{ route('portal.hq.hometax.index', ['type' => $type, 'job_id' => $selected->job_id, 'page' => $p]) }}"
                   class="w-9 h-9 grid place-items-center rounded-lg text-sm font-semibold {{ $p === $page ? 'bg-mango-500 text-white' : 'hover:bg-neutral-100 text-neutral-600' }}">{{ $p }}</a>
            @endfor
        </div>
    @endif
</x-wms.panel>
@elseif (! $polling && ! $error)
    <x-wms.panel>
        <div class="px-5 py-12 text-center text-neutral-400 text-sm">
            기간을 선택하고 <span class="font-bold text-neutral-500">수집 요청</span>을 눌러 홈택스 데이터를 가져오세요.
        </div>
    </x-wms.panel>
@endif

{{-- 상세 모달 --}}
<div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="detailOpen = false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] overflow-y-auto">
        <div class="sticky top-0 bg-white flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h3 class="font-bold text-neutral-800">세금계산서 상세</h3>
            <button type="button" @click="detailOpen = false" class="text-neutral-400 hover:text-neutral-600 text-xl leading-none">✕</button>
        </div>
        <div x-html="detailHtml">
            <div class="p-10 text-center text-neutral-400 text-sm">불러오는 중…</div>
        </div>
    </div>
</div>

</div>

<script>
function hometax(polling, jobId) {
    return {
        polling: polling,
        jobId: jobId,
        stateLabel: '수집 중…',
        detailOpen: false,
        detailHtml: '',
        init() {
            if (this.polling && this.jobId) this.poll();
        },
        poll() {
            fetch(`{{ url('portal/hq/hometax/jobs') }}/${this.jobId}/state`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) { this.stateLabel = '상태 확인 실패'; return; }
                    this.stateLabel = d.label;
                    if (d.done) { window.location.reload(); return; }
                    setTimeout(() => this.poll(), 3000);
                })
                .catch(() => setTimeout(() => this.poll(), 4000));
        },
        openDetail(nts) {
            this.detailOpen = true;
            this.detailHtml = '<div class="p-10 text-center text-neutral-400 text-sm">불러오는 중…</div>';
            fetch(`{{ url('portal/hq/hometax/detail') }}?nts=${encodeURIComponent(nts)}`)
                .then(r => r.text())
                .then(html => { this.detailHtml = html; })
                .catch(() => { this.detailHtml = '<div class="p-6 text-sm text-rose-600">상세 조회 실패</div>'; });
        },
    };
}
</script>
@endsection

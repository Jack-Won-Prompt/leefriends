@extends('portal.layout')
@section('title', '계좌연동 입금확인')

@section('content')
@php $polling = $selected && ! $selected->isDone(); @endphp

<x-wms.page-head title="계좌연동 입금확인" subtitle="등록된 계좌의 입금내역을 수집하고, 입금자↔매장 매핑으로 매장 주문과 대사합니다." icon="🏦">
    <x-slot:actions>
        <form method="POST" action="{{ route('portal.hq.bank.auto_match') }}" class="inline">
            @csrf
            <input type="hidden" name="acc" value="{{ $selAcc }}">
            <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">⚡ 자동 대사</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

@if ($accountsError)
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium">
        계좌 목록 조회 실패: {{ $accountsError }} <span class="text-amber-500">— 운영(IsTest=false)에서 팝빌 콘솔에 계좌를 등록한 뒤 이용하세요.</span>
    </div>
@elseif (empty($accounts))
    <div class="mb-5 rounded-xl bg-neutral-50 border border-neutral-200 text-neutral-500 px-5 py-3.5 text-sm">
        등록된 계좌가 없습니다. 팝빌 홈페이지 → 계좌조회에서 계좌를 등록해 주세요.
    </div>
@endif

<div x-data="bank({{ $polling ? 'true' : 'false' }}, '{{ $selected->job_id ?? '' }}')">

{{-- 계좌 선택 + 기간 수집 --}}
<x-wms.panel class="mb-5">
    <form method="GET" action="{{ route('portal.hq.bank.index') }}" class="px-5 pt-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">계좌</label>
            <select name="acc" onchange="this.form.submit()" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[15rem]">
                @foreach ($accounts as $a)
                    <option value="{{ $a->bankCode }}|{{ $a->accountNumber }}" @selected($selAcc === $a->bankCode.'|'.$a->accountNumber)>
                        {{ $a->accountName ?: '계좌' }} · {{ $a->accountNumber }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
    <form method="POST" action="{{ route('portal.hq.bank.request') }}" class="px-5 pb-5 pt-3 flex flex-wrap items-end gap-3">
        @csrf
        <input type="hidden" name="acc" value="{{ $selAcc }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="start_date" value="{{ old('start_date', $defStart) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="end_date" value="{{ old('end_date', $defEnd) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" @disabled(! $selAcc) class="inline-flex items-center gap-1 rounded-xl bg-neutral-800 hover:bg-neutral-900 disabled:opacity-40 text-white font-bold px-5 py-2.5 text-sm transition">🔄 입금내역 수집</button>
    </form>
</x-wms.panel>

{{-- 수집 진행 --}}
<div x-show="polling" x-cloak class="mb-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium flex items-center gap-2">
    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
    <span x-text="stateLabel">수집 중…</span> — 완료되면 자동으로 입금내역이 표시됩니다.
</div>

{{-- 요약 --}}
@if ($deposits->isNotEmpty())
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <x-wms.stat label="입금 건수" :value="number_format($summary['count']).'건'" variant="info" icon="🧾" />
    <x-wms.stat label="입금 합계" :value="number_format($summary['total']).'원'" variant="success" icon="💰" />
    <x-wms.stat label="대사 완료" :value="number_format($summary['matched']).'건'" variant="accent" />
    <x-wms.stat label="미대사" :value="number_format($summary['unmatched']).'건'" variant="warn" />
</div>
@endif

{{-- 입금내역 --}}
<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3 w-28">거래일</th>
                <th class="text-left font-semibold px-5 py-3">입금자</th>
                <th class="text-left font-semibold px-5 py-3 w-44">매장(매핑)</th>
                <th class="text-right font-semibold px-5 py-3 w-28">입금액</th>
                <th class="text-left font-semibold px-5 py-3 w-72">대사</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($deposits as $d)
                @php
                    $sid = $resolvedStore[$d->id] ?? null;
                    $store = $sid ? ($storeById[$sid] ?? null) : null;
                    $cands = $candidates[$d->id] ?? collect();
                @endphp
                <tr class="hover:bg-neutral-50 align-top">
                    <td class="px-5 py-3 text-neutral-600">{{ \Illuminate\Support\Str::of((string) $d->trade_date)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1.$2.$3') }}</td>
                    <td class="px-5 py-3 font-medium text-neutral-800">
                        {{ $d->depositor ?: '(입금자명 없음)' }}
                        @if ($d->remark)<span class="block text-xs text-neutral-400">{{ $d->remark }}</span>@endif
                    </td>
                    <td class="px-5 py-3">
                        @if ($store)
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">🏪 {{ $store->name }}</span>
                            <button type="button" @click="openMap('{{ addslashes($d->depositor) }}', {{ $sid }})" class="block text-xs text-neutral-400 hover:underline mt-0.5">매핑 변경</button>
                        @else
                            <button type="button" @click="openMap('{{ addslashes($d->depositor) }}', null)" class="inline-flex items-center gap-1 rounded-lg bg-amber-100 text-amber-700 font-bold px-2.5 py-1 text-xs hover:bg-amber-200">＋ 매장 매핑</button>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums font-bold text-emerald-600">{{ number_format((int) $d->acc_in) }}</td>
                    <td class="px-5 py-3">
                        @if ($d->isMatched())
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-mango-100 text-mango-700 font-bold px-2.5 py-1 text-xs">✔ {{ optional($d->matchedOrder)->order_no }}</span>
                                <form method="POST" action="{{ route('portal.hq.bank.unmatch', $d) }}" onsubmit="return confirm('대사를 해제할까요?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-neutral-400 hover:text-rose-500">해제</button>
                                </form>
                            </div>
                        @elseif (! $store)
                            <span class="text-xs text-neutral-400">입금자 매핑 후 대사 가능</span>
                        @elseif ($cands->isEmpty())
                            <span class="text-xs text-neutral-400">금액이 일치하는 미입금 주문 없음</span>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($cands as $o)
                                    <form method="POST" action="{{ route('portal.hq.bank.match') }}">
                                        @csrf
                                        <input type="hidden" name="deposit_id" value="{{ $d->id }}">
                                        <input type="hidden" name="order_id" value="{{ $o->id }}">
                                        <button class="inline-flex items-center gap-1 rounded-lg border border-mango-300 text-mango-700 hover:bg-mango-50 font-bold px-2.5 py-1 text-xs">
                                            {{ $o->order_no }} · {{ number_format($o->order_total) }}원 대사
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400 text-sm">
                    계좌와 기간을 선택하고 <span class="font-bold text-neutral-500">입금내역 수집</span>을 눌러 입금 거래를 가져오세요.
                </td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 입금자 → 매장 매핑 모달 --}}
<div x-show="mapOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="mapOpen = false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h3 class="font-bold text-neutral-800">입금자 → 매장 매핑</h3>
            <button type="button" @click="mapOpen = false" class="text-neutral-400 hover:text-neutral-600 text-xl leading-none">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.bank.map') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="acc" value="{{ $selAcc }}">
            <div>
                <label class="block text-xs font-semibold text-neutral-500 mb-1">입금자명</label>
                <input type="text" name="depositor_name" x-model="mapDepositorName" readonly class="w-full rounded-xl border-neutral-200 bg-neutral-50 text-sm py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-neutral-500 mb-1">매장</label>
                <select name="store_id" x-model="mapStoreId" class="w-full rounded-xl border-neutral-200 text-sm py-2">
                    <option value="">매장 선택…</option>
                    @foreach ($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-neutral-400">저장하면 이후 같은 입금자명은 자동으로 이 매장으로 인식됩니다.</p>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="mapOpen = false" class="rounded-xl border border-neutral-200 px-4 py-2 text-sm font-bold hover:bg-neutral-50">취소</button>
                <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white px-4 py-2 text-sm font-bold">저장</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
function bank(polling, jobId) {
    return {
        polling: polling, jobId: jobId, stateLabel: '수집 중…',
        mapOpen: false, mapDepositorName: '', mapStoreId: '',
        init() { if (this.polling && this.jobId) this.poll(); },
        poll() {
            fetch(`{{ url('portal/hq/bank/jobs') }}/${this.jobId}/state`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) { this.stateLabel = '상태 확인 실패'; return; }
                    this.stateLabel = d.label;
                    if (d.done) { window.location.reload(); return; }
                    setTimeout(() => this.poll(), 3000);
                })
                .catch(() => setTimeout(() => this.poll(), 4000));
        },
        openMap(depositor, storeId) {
            this.mapDepositorName = depositor || '';
            this.mapStoreId = storeId ? String(storeId) : '';
            this.mapOpen = true;
        },
    };
}
</script>
@endsection

@extends('portal.layout')
@section('title', '세금계산서 작성')

@section('content')
<x-wms.page-head title="세금계산서 작성 (본사 → 매장)" subtitle="매장과 기간을 선택하고 발주를 골라 한 장으로 발행합니다" icon="🧾" />

{{-- 필터 --}}
<form method="GET" action="{{ route('portal.hq.tax_invoices.create') }}"
      class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-5 mb-6 grid md:grid-cols-4 gap-4 items-end">
    <input type="hidden" name="searched" value="1">
    <div>
        <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장</label>
        <select name="store_id" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected($filters['store_id'] == $s->id)>
                    {{ $s->name }}@unless($s->biz_no) (⚠ 사업자번호 없음)@endunless
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-bold text-neutral-700 mb-1.5">발주일 시작</label>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </div>
    <div>
        <label class="block text-sm font-bold text-neutral-700 mb-1.5">발주일 종료</label>
        <input type="date" name="to" value="{{ $filters['to'] }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-900 hover:bg-neutral-800 text-white font-bold px-4 py-2.5 text-sm transition">조회</button>
</form>

@if ($searched)
    @if ($store && ! $store->biz_no)
        <div class="mb-6 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium">
            ⚠ «{{ $store->name }}» 매장의 사업자등록번호가 없습니다. <a href="{{ route('portal.hq.stores.index') }}" class="underline font-bold">매장 관리</a>에서 먼저 등록해야 발행할 수 있습니다.
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 px-6 py-16 text-center text-neutral-400">
            조회 조건에 해당하는 발행 가능한(미발행) 발주가 없습니다.
        </div>
    @else
        <form method="POST" action="{{ route('portal.hq.tax_invoices.store') }}"
              x-data="{
                  picked: [],
                  meta: {{ Illuminate\Support\Js::from($orders->mapWithKeys(fn($o) => [$o->id => ['amt' => (int) $o->store_amount, 'store' => (int) $o->store_id]])) }},
                  get total() { return this.picked.reduce((s, id) => s + (this.meta[id]?.amt || 0), 0); },
                  get stores() { return [...new Set(this.picked.map(id => this.meta[id]?.store))]; },
                  toggleAll(e) { this.picked = e.target.checked ? Object.keys(this.meta).map(Number) : []; }
              }"
              @submit="if (!picked.length) { $event.preventDefault(); alert('발행할 발주를 선택해 주세요.'); return; }
                       if (stores.length > 1) { $event.preventDefault(); alert('서로 다른 매장의 발주는 함께 발행할 수 없습니다.\n같은 매장 발주만 선택하세요.'); }">
            @csrf

            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
                    <span class="font-extrabold text-neutral-900">{{ $store ? $store->name : '전체 매장' }} · 미발행 발주 {{ $orders->count() }}건</span>
                    <label class="flex items-center gap-2 text-sm font-semibold text-neutral-600">
                        <input type="checkbox" @change="toggleAll($event)" class="rounded text-mango-500 focus:ring-mango-400"> 전체 선택
                    </label>
                </div>
                @unless ($store)
                    <p class="px-6 pt-3 text-xs text-amber-600">※ 전체 매장 조회 중입니다. 세금계산서는 매장 1곳 기준이므로 <b>같은 매장 발주만</b> 함께 선택해 발행하세요.</p>
                @endunless
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-neutral-50 text-neutral-500">
                            <tr>
                                <th class="px-6 py-3 w-10"></th>
                                <th class="text-left font-semibold px-4 py-3">발주번호</th>
                                <th class="text-left font-semibold px-4 py-3">매장</th>
                                <th class="text-left font-semibold px-4 py-3">발주일</th>
                                <th class="text-left font-semibold px-4 py-3">상태</th>
                                <th class="text-right font-semibold px-4 py-3">품목수</th>
                                <th class="text-right font-semibold px-6 py-3">매장가 합계</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($orders as $o)
                                <tr class="hover:bg-mango-50/40 transition" :class="picked.includes({{ $o->id }}) ? 'bg-mango-50/60' : ''">
                                    <td class="px-6 py-3.5">
                                        <input type="checkbox" name="order_ids[]" value="{{ $o->id }}" x-model.number="picked"
                                               class="rounded text-mango-500 focus:ring-mango-400">
                                    </td>
                                    <td class="px-4 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                                    <td class="px-4 py-3.5 text-neutral-600">{{ $o->store->name ?? '-' }}@unless(optional($o->store)->biz_no)<span class="ml-1 text-[11px] text-amber-600">⚠</span>@endunless</td>
                                    <td class="px-4 py-3.5 text-neutral-500">{{ $o->created_at->format('Y.m.d') }}</td>
                                    <td class="px-4 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                                    <td class="px-4 py-3.5 text-right text-neutral-500">{{ $o->items_count }}개</td>
                                    <td class="px-6 py-3.5 text-right font-semibold text-mango-700">{{ number_format($o->store_amount) }}원</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sticky bottom-4 mt-6 rounded-2xl bg-neutral-900 text-white p-5 flex items-center justify-between shadow-lg">
                <div>
                    <span class="text-white/60 text-sm">선택 <span class="font-bold text-white" x-text="picked.length"></span>건 · 합계</span>
                    <span class="ml-2 text-2xl font-black text-mango-300"><span x-text="total.toLocaleString()"></span>원</span>
                    <span class="block text-xs text-white/40 mt-0.5" x-show="stores.length <= 1">실제 공급가액·부가세는 제품별 부가세구분에 따라 계산됩니다.</span>
                    <span class="block text-xs text-rose-300 mt-0.5" x-show="stores.length > 1" x-cloak>⚠ 서로 다른 매장이 선택되었습니다. 같은 매장만 선택하세요.</span>
                </div>
                <button type="submit" :disabled="!picked.length || stores.length > 1"
                        class="rounded-xl bg-mango-500 hover:bg-mango-600 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold px-6 py-3 text-sm transition"
                        onclick="return confirm('선택한 발주로 세금계산서를 발행합니다. 진행하시겠습니까?')">
                    🧾 세금계산서 발행
                </button>
            </div>
        </form>
    @endif
@else
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 px-6 py-16 text-center text-neutral-400">
        매장(또는 전체)과 기간을 선택한 뒤 <span class="font-bold text-neutral-600">조회</span>를 눌러 주세요.
    </div>
@endif
@endsection

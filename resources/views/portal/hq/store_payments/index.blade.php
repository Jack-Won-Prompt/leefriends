@extends('portal.layout')
@section('title', '매장별 입금현황')

@section('content')
<x-wms.page-head title="매장별 입금현황" subtitle="매장별 총 발주액 대비 입금완료·미입금 집계 (계좌 대사 기준)" icon="💳" />

{{-- 전체 + 월별(1~12월) + 기간 조회 --}}
@php
    $btn = 'inline-flex items-center justify-center rounded-xl px-3.5 py-2 text-sm font-bold transition';
    $isRange = ! $month && ($from || $to);
@endphp
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('portal.hq.store_payments.index') }}"
           class="{{ $btn }} {{ ! $month && ! $isRange ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">전체</a>
        <span class="mx-1 text-neutral-300">|</span>
        <span class="text-sm font-bold text-neutral-500 mr-1">{{ $year }}년</span>
        @for ($m = 1; $m <= 12; $m++)
            <a href="{{ route('portal.hq.store_payments.index', ['year' => $year, 'month' => $m]) }}"
               class="{{ $btn }} {{ (int) $month === $m ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">{{ $m }}월</a>
        @endfor
    </div>
    <form method="GET" action="{{ route('portal.hq.store_payments.index') }}"
          class="flex items-end gap-2 {{ $isRange ? 'ring-2 ring-mango-300 rounded-xl p-2' : '' }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="from" value="{{ $isRange ? $from : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="to" value="{{ $isRange ? $to : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm transition">기간 조회</button>
    </form>
</div>

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl bg-neutral-900 text-white p-6">
        <p class="text-white/70 font-semibold text-sm">총 발주액</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['total']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">입금완료</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['paid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">미입금</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['unpaid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">미입금 발주</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['unpaid_cnt']) }}<span class="text-lg">건</span></p>
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장별 입금현황</div>
    @if ($byStore->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">집계할 발주가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3">발주</th>
                        <th class="text-right font-semibold px-6 py-3">총 발주액</th>
                        <th class="text-right font-semibold px-6 py-3">입금완료</th>
                        <th class="text-right font-semibold px-6 py-3">미입금</th>
                        <th class="text-right font-semibold px-6 py-3">미입금 건</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">최근입금</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($byStore as $s)
                        @php $unpaidAmt = (int) $s->total - (int) $s->paid; @endphp
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer"
                            onclick="location.href='{{ route('portal.hq.store_payments.show', ['store' => $s->id, 'period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month]) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->name }}<span class="block text-xs font-normal text-neutral-400">{{ $s->region }}</span></td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($s->cnt) }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold tabular-nums">{{ number_format($s->total) }}</td>
                            <td class="px-6 py-3.5 text-right tabular-nums text-emerald-600 font-semibold">{{ number_format($s->paid) }}</td>
                            <td class="px-6 py-3.5 text-right tabular-nums {{ $unpaidAmt > 0 ? 'text-amber-600 font-bold' : 'text-neutral-400' }}">{{ number_format($unpaidAmt) }}</td>
                            <td class="px-6 py-3.5 text-right">
                                @if ($s->unpaid_cnt > 0)
                                    <span class="inline-flex items-center justify-center min-w-[2rem] rounded-full bg-amber-100 text-amber-700 font-bold px-2 py-0.5 text-xs">{{ $s->unpaid_cnt }}</span>
                                @else
                                    <span class="text-emerald-600 text-xs font-bold">완납</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400 text-xs">{{ $s->last_paid_at ? \Illuminate\Support\Carbon::parse($s->last_paid_at)->format('Y.m.d') : '-' }}</td>
                            <td class="px-6 py-3.5 text-right" onclick="event.stopPropagation()">
                                @if ($s->unpaid_cnt > 0)
                                    <form method="POST" action="{{ route('portal.hq.store_payments.request_unpaid', ['store' => $s->id, 'period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month]) }}"
                                          onsubmit="return confirm('{{ $s->name }}에 미입금 {{ $s->unpaid_cnt }}건 · {{ number_format($unpaidAmt) }}원 안내 SMS를 전송합니다.\n진행하시겠습니까?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-1.5 text-xs transition">💬 미입금 SMS</button>
                                    </form>
                                @else
                                    <span class="text-neutral-300">›</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

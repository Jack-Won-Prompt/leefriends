@extends('portal.layout')
@section('title', '아르바이트 급여')

@section('content')
<x-wms.page-head title="아르바이트 급여" subtitle="기간별 아르바이트 근무·일당 합계 확인 및 급여 입금 처리" icon="💵" />

{{-- 기간 조회 --}}
<form method="GET" action="{{ route('portal.wages.index') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-5 py-2.5 text-sm transition">조회</button>
    <div class="ml-auto text-right">
        <p class="text-xs text-neutral-400">기간 급여 합계</p>
        <p class="text-2xl font-black text-mango-600">{{ number_format($grandAmount) }}<span class="text-base">원</span></p>
    </div>
</form>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">아르바이트</th>
                <th class="text-right font-semibold px-5 py-3">시급</th>
                <th class="text-right font-semibold px-5 py-3">근무일</th>
                <th class="text-right font-semibold px-5 py-3">근무시간</th>
                <th class="text-right font-semibold px-5 py-3">급여(일당 합계)</th>
                <th class="text-left font-semibold px-5 py-3">입금</th>
                <th class="text-right font-semibold px-5 py-3 w-40">처리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($rows as $r)
                @php $u = $r['user']; $paid = $r['settlement'] && $r['settlement']->status === 'paid'; @endphp
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-bold text-neutral-900">
                        {{ $u->name }}
                        <a href="{{ route('portal.attendance.manage', ['user' => $u->id, 'from' => $from, 'to' => $to]) }}"
                           class="block text-xs font-semibold text-mango-600 hover:underline mt-0.5">🕐 출퇴근 관리</a>
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums text-neutral-500">{{ number_format($u->hourly_wage) }}원</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $r['days'] }}일</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $r['hours'] }}시간</td>
                    <td class="px-5 py-3 text-right tabular-nums font-bold">{{ number_format($r['amount']) }}원</td>
                    <td class="px-5 py-3">
                        @if ($paid)
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">💰 입금완료</span>
                            <span class="block text-[11px] text-neutral-400">{{ optional($r['settlement']->paid_at)->format('m.d H:i') }}</span>
                        @else
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-400">미입금</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if ($r['amount'] > 0 && ! $paid)
                            <form method="POST" action="{{ route('portal.wages.pay') }}"
                                  onsubmit="return confirm('{{ $u->name }}님 급여 {{ number_format($r['amount']) }}원을 입금 처리합니다.\n(아르바이트에게 알림 전송)\n진행할까요?')">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $u->id }}">
                                <input type="hidden" name="from" value="{{ $from }}">
                                <input type="hidden" name="to" value="{{ $to }}">
                                <input type="hidden" name="hours" value="{{ $r['hours'] }}">
                                <input type="hidden" name="amount" value="{{ $r['amount'] }}">
                                <button class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs">💰 입금 처리</button>
                            </form>
                        @elseif ($paid)
                            <form method="POST" action="{{ route('portal.wages.unpay', $r['settlement']) }}" onsubmit="return confirm('입금 처리를 취소할까요?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-neutral-400 hover:text-rose-500 font-bold">입금취소</button>
                            </form>
                        @else
                            <span class="text-xs text-neutral-300">근무 없음</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-neutral-400">소속 아르바이트가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>
@endsection

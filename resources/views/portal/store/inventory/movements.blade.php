@extends('portal.layout')
@section('title', '입출고 내역')

@section('content')
<div class="flex items-center justify-between mb-5">
    <a href="{{ route('portal.store.inventory.index') }}" class="text-sm font-bold text-neutral-500 hover:text-mango-600">← 재고 관리</a>
    <div class="flex gap-2">
        <a href="{{ route('portal.store.inventory.movements') }}" class="px-4 py-2 rounded-full text-sm font-bold transition {{ $type === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">전체</a>
        @foreach ($types as $key => $label)
            <a href="{{ route('portal.store.inventory.movements', ['type' => $key]) }}" class="px-4 py-2 rounded-full text-sm font-bold transition {{ $type === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($movements->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">일시</th>
                    <th class="text-left font-semibold px-6 py-3">구분</th>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-right font-semibold px-6 py-3">변동</th>
                    <th class="text-right font-semibold px-6 py-3">잔량</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">메모</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($movements as $m)
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-6 py-3 text-neutral-400">{{ $m->created_at->format('m.d H:i') }}</td>
                        <td class="px-6 py-3">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $m->type === 'in' ? 'bg-emerald-100 text-emerald-700' : ($m->type === 'out' ? 'bg-rose-100 text-rose-600' : 'bg-neutral-100 text-neutral-600') }}">{{ $m->type_label }}</span>
                        </td>
                        <td class="px-6 py-3 font-semibold text-neutral-800">{{ $m->product_name }} <span class="text-xs text-neutral-400">{{ $m->unit_name }}</span></td>
                        <td class="px-6 py-3 text-right font-bold {{ $m->qty >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $m->qty > 0 ? '+' : '' }}{{ number_format($m->qty) }}</td>
                        <td class="px-6 py-3 text-right text-neutral-700">{{ number_format($m->balance_after) }}</td>
                        <td class="px-6 py-3 hidden md:table-cell text-neutral-400">{{ $m->note }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-6">{{ $movements->links() }}</div>
@endsection

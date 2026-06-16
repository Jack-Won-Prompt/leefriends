@extends('admin.layout')
@section('title', '대시보드')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    @php
        $cards = [
            ['전체 창업문의', $stats['inquiries'], '📩', 'from-mango-400 to-mango-600', route('admin.inquiries.index')],
            ['신규 문의', $stats['new_inquiries'], '🔔', 'from-rose-400 to-rose-600', route('admin.inquiries.index', ['status' => 'new'])],
            ['메뉴', $stats['menus'], '🍧', 'from-amber-400 to-orange-500', route('admin.menus.index')],
            ['매장', $stats['stores'], '🏬', 'from-emerald-400 to-teal-600', route('admin.stores.index')],
            ['공지', $stats['notices'], '📢', 'from-sky-400 to-indigo-500', route('admin.notices.index')],
        ];
    @endphp
    @foreach ($cards as [$label, $value, $icon, $grad, $url])
        <a href="{{ $url }}" class="rounded-2xl bg-white p-5 shadow-sm hover:shadow-md transition border border-neutral-100">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $grad }} grid place-items-center text-xl mb-3">{{ $icon }}</div>
            <p class="text-sm text-neutral-500 font-medium">{{ $label }}</p>
            <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($value) }}</p>
        </a>
    @endforeach
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
        <h2 class="font-extrabold text-neutral-900">최근 창업 문의</h2>
        <a href="{{ route('admin.inquiries.index') }}" class="text-sm font-bold text-mango-600 hover:text-mango-700">전체보기 →</a>
    </div>
    @if ($recentInquiries->isEmpty())
        <p class="px-6 py-12 text-center text-neutral-400">접수된 문의가 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">성함</th>
                    <th class="text-left font-semibold px-6 py-3">연락처</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">희망지역</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($recentInquiries as $iq)
                    <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('admin.inquiries.show', $iq) }}'">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $iq->name }}</td>
                        <td class="px-6 py-3.5">{{ $iq->phone }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $iq->region ?: '-' }}</td>
                        <td class="px-6 py-3.5">@include('admin.partials.status-badge', ['status' => $iq->status, 'label' => $iq->status_label])</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $iq->created_at->format('Y.m.d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection

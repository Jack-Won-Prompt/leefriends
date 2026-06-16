@extends('admin.layout')
@section('title', '창업문의 관리')

@section('content')
<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('admin.inquiries.index') }}"
       class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">전체</a>
    @foreach ($statuses as $key => $label)
        <a href="{{ route('admin.inquiries.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($inquiries->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">문의 내역이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">성함</th>
                        <th class="text-left font-semibold px-6 py-3">연락처</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">희망지역</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">예산</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($inquiries as $iq)
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('admin.inquiries.show', $iq) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $iq->name }}</td>
                            <td class="px-6 py-3.5">{{ $iq->phone }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $iq->region ?: '-' }}</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $iq->budget ?: '-' }}</td>
                            <td class="px-6 py-3.5">@include('admin.partials.status-badge', ['status' => $iq->status, 'label' => $iq->status_label])</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $iq->created_at->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-6">{{ $inquiries->links() }}</div>
@endsection

@extends('portal.layout')
@section('title', '창업 문의 상세')

@php
    $badgeClass = [
        'new' => 'bg-rose-100 text-rose-700',
        'contacted' => 'bg-sky-100 text-sky-700',
        'done' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp

@section('content')
<x-wms.page-head title="창업 문의 상세" subtitle="{{ $inquiry->created_at->format('Y년 m월 d일 H:i') }} 접수" icon="📨">
    <x-slot:actions>
        <a href="{{ route('portal.hq.inquiries.index') }}" class="inline-flex items-center gap-1 rounded-xl bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-600 font-bold px-4 py-2 text-sm transition">← 목록</a>
    </x-slot:actions>
</x-wms.page-head>

<div class="grid gap-5 lg:grid-cols-3">
    {{-- 문의 내용 --}}
    <div class="lg:col-span-2">
        <x-wms.panel>
            <div class="p-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-extrabold text-neutral-900">{{ $inquiry->name }}</h2>
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold {{ $badgeClass[$inquiry->status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $inquiry->status_label }}</span>
                </div>

                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-neutral-400 font-semibold mb-1">연락처</dt>
                        <dd class="font-bold text-neutral-800"><a href="tel:{{ $inquiry->phone }}" class="hover:text-mango-600">{{ $inquiry->phone }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-neutral-400 font-semibold mb-1">이메일</dt>
                        <dd class="font-bold text-neutral-800">
                            @if ($inquiry->email)<a href="mailto:{{ $inquiry->email }}" class="hover:text-mango-600">{{ $inquiry->email }}</a>@else-@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-neutral-400 font-semibold mb-1">희망 지역</dt>
                        <dd class="font-bold text-neutral-800">{{ $inquiry->region ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-neutral-400 font-semibold mb-1">창업 예산</dt>
                        <dd class="font-bold text-neutral-800">{{ $inquiry->budget ?: '-' }}</dd>
                    </div>
                </dl>

                <div>
                    <dt class="text-neutral-400 font-semibold mb-1.5 text-sm">문의 내용</dt>
                    <div class="rounded-xl bg-neutral-50 border border-neutral-100 px-4 py-3.5 text-sm text-neutral-700 whitespace-pre-line min-h-[80px]">{{ $inquiry->message ?: '내용 없음' }}</div>
                </div>
            </div>
        </x-wms.panel>
    </div>

    {{-- 상태 관리 --}}
    <div class="space-y-4">
        <x-wms.panel>
            <div class="p-6 space-y-4">
                <h3 class="font-extrabold text-neutral-900">상담 상태</h3>
                <form method="POST" action="{{ route('portal.hq.inquiries.update', $inquiry) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-col gap-2">
                        @foreach ($statuses as $key => $label)
                            <label class="flex items-center gap-2.5 rounded-xl border px-4 py-2.5 cursor-pointer transition {{ $inquiry->status === $key ? 'border-mango-400 bg-mango-50' : 'border-neutral-200 hover:bg-neutral-50' }}">
                                <input type="radio" name="status" value="{{ $key }}" @checked($inquiry->status === $key) class="text-mango-500 focus:ring-mango-400">
                                <span class="text-sm font-bold text-neutral-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">상태 저장</button>
                </form>
            </div>
        </x-wms.panel>

        <form method="POST" action="{{ route('portal.hq.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('이 문의를 삭제할까요? 되돌릴 수 없습니다.')">
            @csrf
            @method('DELETE')
            <button class="w-full rounded-xl bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold px-4 py-2.5 text-sm transition">문의 삭제</button>
        </form>
    </div>
</div>
@endsection

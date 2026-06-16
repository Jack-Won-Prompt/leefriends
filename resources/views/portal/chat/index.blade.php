@extends('portal.layout')
@section('title', '채팅')

@section('content')
<x-wms.page-head title="채팅" subtitle="{{ $mode === 'hq' ? '매장·공급처와 실시간으로 메시지를 주고받습니다' : '본사와 실시간으로 메시지를 주고받습니다' }}" icon="💬" />

@if ($mode === 'hq')
    <div class="grid lg:grid-cols-3 gap-4">
        {{-- 대화 목록 --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-neutral-100 font-extrabold text-neutral-900 text-sm">매장 · 공급처</div>
                <div class="max-h-[calc(100vh-15rem)] overflow-y-auto divide-y divide-neutral-50">
                    @foreach ($conversations as $row)
                        @php $isActive = $conversation && $conversation->party_type === $row['type'] && (int) $conversation->party_id === (int) $row['id']; @endphp
                        <a href="{{ route('portal.chat.index', ['open' => $row['open_param']]) }}"
                           class="flex items-center gap-3 px-4 py-3 transition {{ $isActive ? 'bg-mango-50' : 'hover:bg-neutral-50' }}">
                            <span class="w-9 h-9 grid place-items-center rounded-full {{ $row['type'] === 'supplier' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }} font-bold shrink-0">{{ mb_substr($row['name'], 0, 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-neutral-900 text-sm truncate">{{ $row['name'] }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $row['type'] === 'supplier' ? 'bg-sky-100 text-sky-600' : 'bg-emerald-100 text-emerald-600' }}">{{ $row['label'] }}</span>
                                </div>
                                <p class="text-xs text-neutral-400 truncate">{{ $row['last_message'] ?: '대화 시작하기' }}</p>
                            </div>
                            @if ($row['unread'] > 0)
                                <span class="shrink-0 min-w-[18px] h-[18px] px-1 grid place-items-center text-[10px] font-bold text-white bg-rose-500 rounded-full">{{ $row['unread'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 대화 스레드 --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                @if ($conversation)
                    @include('portal.chat._thread')
                @else
                    <div class="h-[420px] grid place-items-center text-center text-neutral-400">
                        <div>
                            <p class="text-4xl mb-2">💬</p>
                            <p class="text-sm">왼쪽에서 매장 또는 공급처를 선택해 대화를 시작하세요.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@else
    {{-- 매장/공급처: 본사와의 단일 대화 --}}
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden max-w-3xl">
        @include('portal.chat._thread')
    </div>
@endif
@endsection

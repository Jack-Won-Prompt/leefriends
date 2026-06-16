@extends('portal.layout')
@section('title', '알림')

@section('content')
<div class="flex justify-end mb-5">
    <form method="POST" action="{{ route('portal.notifications.read_all') }}">@csrf
        <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-5 py-2.5 text-sm">모두 읽음 처리</button>
    </form>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($notifications->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">알림이 없습니다.</p>
    @else
        <div class="divide-y divide-neutral-100">
            @foreach ($notifications as $n)
                <div class="flex items-start gap-4 px-6 py-4 {{ $n->read_at ? '' : 'bg-mango-50/40' }}">
                    <div class="text-2xl">{{ $n->type === 'shipment_confirmed' ? '🚚' : '🔔' }}</div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-neutral-900">{{ $n->title }}</p>
                        <p class="text-sm text-neutral-600 mt-0.5">{{ $n->body }}</p>
                        <p class="text-xs text-neutral-400 mt-1">{{ $n->created_at->format('Y.m.d H:i') }}</p>
                    </div>
                    @if (! $n->read_at)
                        <form method="POST" action="{{ route('portal.notifications.read', $n) }}">@csrf
                            <button class="text-xs font-bold text-mango-600 hover:text-mango-700 shrink-0">읽음</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="mt-6">{{ $notifications->links() }}</div>
@endsection

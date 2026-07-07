@props(['from' => null, 'to' => null, 'label' => '조회 기간'])

{{-- 날짜 기간 필터. 기존 GET 파라미터(정렬/상태 등)는 hidden 으로 보존. --}}
<form method="GET" class="flex flex-wrap items-end gap-2 mb-4">
    @foreach (request()->except(['from', 'to', 'page']) as $k => $v)
        @if (! is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <div>
        <label class="block text-xs font-bold text-neutral-500 mb-1">{{ $label }}</label>
        <div class="flex items-center gap-1.5">
            <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
            <span class="text-neutral-400">~</span>
            <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        </div>
    </div>
    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">조회</button>
    @if ($from || $to)
        <a href="{{ url()->current() }}" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-500 font-bold px-3 py-2 text-sm">초기화</a>
    @endif
</form>

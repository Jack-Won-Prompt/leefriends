{{-- 매장 필터 드롭다운 (GET, 기존 쿼리 보존). $stores, $store 필요 --}}
<form method="GET" class="flex items-center gap-2">
    @foreach (request()->except(['store', 'page']) as $k => $v)
        @if (! is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <span class="text-sm font-semibold text-neutral-500 hidden sm:inline">매장</span>
    <select name="store" onchange="this.form.submit()"
            class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        <option value="all">전체 매장</option>
        @foreach ($stores as $s)
            <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</form>

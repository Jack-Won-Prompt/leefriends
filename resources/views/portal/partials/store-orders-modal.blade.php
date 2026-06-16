{{-- 매장별 발주 상세 팝업. 행에서 $dispatch('open-store-orders', { url }) 로 호출 --}}
<div x-data="{ open: false, loading: false, html: '' }"
     x-on:open-store-orders.window="
        open = true; loading = true; html = '';
        fetch($event.detail.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text()).then(t => { html = t; loading = false; })
            .catch(() => { loading = false; html = '<div class=\'p-16 text-center text-rose-500\'>불러오기에 실패했습니다.</div>'; })">
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto">
            <button type="button" @click="open = false" class="absolute top-4 right-4 z-20 w-8 h-8 grid place-items-center rounded-lg bg-white hover:bg-neutral-100 text-neutral-500 shadow-sm">✕</button>
            <div x-show="loading" class="p-16 text-center text-neutral-400">불러오는 중…</div>
            <div x-html="html"></div>
        </div>
    </div>
</div>

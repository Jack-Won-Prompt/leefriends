@props(['id'])

{{-- 공용 상세 팝업. 상위 x-data 에 open(=선택 id) 상태가 있어야 함. --}}
<div x-show="open === {{ $id }}" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-black/50 print:static print:bg-white print:overflow-visible"
     @keydown.escape.window="open = null">
    <div class="absolute inset-0 print:hidden" @click="open = null"></div>
    <div class="relative mx-auto max-w-3xl my-10 px-4 print:my-0 print:px-0 print:max-w-none">
        <div class="flex items-center justify-end gap-2 mb-3 print:hidden">
            {{ $actions ?? '' }}
            <button type="button" @click="open = null"
                    class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">닫기 ✕</button>
        </div>
        {{ $slot }}
    </div>
</div>

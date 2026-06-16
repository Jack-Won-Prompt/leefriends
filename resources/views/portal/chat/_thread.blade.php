@php
    $mySide = $mode === 'hq' ? 'hq' : auth()->user()->role;
@endphp

<div class="flex flex-col h-[calc(100vh-13rem)] min-h-[420px]">
    {{-- 헤더 --}}
    <div class="flex items-center gap-2 px-5 py-3 border-b border-neutral-100">
        <span class="w-9 h-9 grid place-items-center rounded-full bg-mango-100 text-mango-700 font-bold">{{ mb_substr($partnerName, 0, 1) }}</span>
        <div>
            <p class="font-extrabold text-neutral-900 leading-tight">{{ $partnerName }}</p>
            <p class="text-[11px] text-neutral-400">{{ $mode === 'hq' ? $conversation->party_label : '본사' }}와의 대화</p>
        </div>
    </div>

    {{-- 메시지 목록 --}}
    <div id="chat-messages" class="flex-1 overflow-y-auto px-5 py-4 space-y-2.5 bg-neutral-50">
        @forelse ($messages as $m)
            @php $mine = $m->sender_role === $mySide; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-mid="{{ $m->id }}">
                <div class="max-w-[75%]">
                    @unless ($mine)
                        <p class="text-[11px] text-neutral-400 mb-0.5 ml-1">{{ $m->sender_name }}</p>
                    @endunless
                    <div class="flex items-end gap-1.5 {{ $mine ? 'flex-row-reverse' : '' }}">
                        <div class="rounded-2xl px-3.5 py-2 text-sm whitespace-pre-line break-words {{ $mine ? 'bg-mango-500 text-white rounded-br-sm' : 'bg-white border border-neutral-200 text-neutral-800 rounded-bl-sm' }}">{{ $m->body }}</div>
                        <span class="text-[10px] text-neutral-400 shrink-0">{{ optional($m->created_at)->format('H:i') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <p id="chat-empty" class="text-center text-sm text-neutral-400 py-10">아직 대화가 없습니다. 첫 메시지를 보내보세요.</p>
        @endforelse
    </div>

    {{-- 입력 --}}
    <form id="chat-form" class="flex items-end gap-2 p-3 border-t border-neutral-100 bg-white">
        <textarea id="chat-input" rows="1" maxlength="2000" placeholder="메시지를 입력하세요 (Enter 전송, Shift+Enter 줄바꿈)"
                  class="flex-1 resize-none rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm focus:border-mango-400 focus:ring-mango-400 max-h-32"></textarea>
        <button type="submit" class="shrink-0 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">전송</button>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const CONV_ID = @json($conversation->id);
    const MY_SIDE = @json($mySide);
    const SEND_URL = @json(route('portal.chat.send', $conversation));
    const POLL_URL = @json(route('portal.chat.poll', $conversation));
    const csrf = document.querySelector('meta[name=csrf-token]')?.content;
    const box = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');

    const esc = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const scroll = () => { box.scrollTop = box.scrollHeight; };
    scroll();

    function append(m) {
        if (box.querySelector(`[data-mid="${m.id}"]`)) return; // 중복 방지
        const empty = document.getElementById('chat-empty');
        if (empty) empty.remove();
        const mine = m.sender_role === MY_SIDE;
        const row = document.createElement('div');
        row.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
        row.dataset.mid = m.id;
        const bubble = mine
            ? 'bg-mango-500 text-white rounded-br-sm'
            : 'bg-white border border-neutral-200 text-neutral-800 rounded-bl-sm';
        row.innerHTML =
            '<div class="max-w-[75%]">' +
            (mine ? '' : '<p class="text-[11px] text-neutral-400 mb-0.5 ml-1">' + esc(m.sender_name) + '</p>') +
            '<div class="flex items-end gap-1.5 ' + (mine ? 'flex-row-reverse' : '') + '">' +
            '<div class="rounded-2xl px-3.5 py-2 text-sm whitespace-pre-line break-words ' + bubble + '">' + esc(m.body) + '</div>' +
            '<span class="text-[10px] text-neutral-400 shrink-0">' + esc(m.time || '') + '</span>' +
            '</div></div>';
        box.appendChild(row);
        scroll();
    }

    // 실시간 수신 (레이아웃의 Pusher 연결 재사용, 없으면 폴링 폴백)
    let realtime = false;
    if (window.appPusher) {
        try {
            const ch = window.appPusher.subscribe('private-chat.conversation.' + CONV_ID);
            ch.bind('message.sent', (m) => append(m));
            realtime = true;
        } catch (e) { console.error('chat subscribe 실패', e); }
    }
    if (!realtime) {
        let lastId = 0;
        box.querySelectorAll('[data-mid]').forEach(el => { lastId = Math.max(lastId, +el.dataset.mid || 0); });
        setInterval(async () => {
            try {
                const r = await fetch(POLL_URL + '?after=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const d = await r.json();
                (d.messages || []).forEach(m => { append(m); lastId = Math.max(lastId, m.id); });
            } catch (e) {}
        }, 4000);
    }

    // 전송
    async function send() {
        const body = input.value.trim();
        if (!body) return;
        input.value = '';
        input.style.height = 'auto';
        try {
            const r = await fetch(SEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ body }),
            });
            if (!r.ok) throw new Error('send failed');
            const d = await r.json();
            append(d.message); // 본인 메시지 즉시 표시
        } catch (e) {
            alert('메시지 전송에 실패했습니다.');
            input.value = body;
        }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 128) + 'px'; });
})();
</script>
@endpush

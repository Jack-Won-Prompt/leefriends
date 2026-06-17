@php
    $mySide = $mode === 'hq' ? 'hq' : auth()->user()->role;
@endphp

<div class="relative flex flex-col h-[calc(100vh-13rem)] min-h-[420px]" id="chat-root">
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
                        <div class="flex flex-col gap-1 {{ $mine ? 'items-end' : 'items-start' }}">
                            @if ($m->body)
                                <div class="rounded-2xl px-3.5 py-2 text-sm whitespace-pre-line break-words {{ $mine ? 'bg-mango-500 text-white rounded-br-sm' : 'bg-white border border-neutral-200 text-neutral-800 rounded-bl-sm' }}">{{ $m->body }}</div>
                            @endif
                            @if ($m->attachment_path)
                                @if ($m->is_image)
                                    <a href="{{ $m->attachment_url }}" target="_blank" class="block">
                                        <img src="{{ $m->attachment_url }}" alt="{{ $m->attachment_name }}" class="max-w-[220px] max-h-[220px] rounded-xl border border-black/5">
                                    </a>
                                @else
                                    <a href="{{ $m->attachment_url }}" target="_blank" download
                                       class="flex items-center gap-2 rounded-xl bg-white border border-neutral-200 px-3 py-2 text-xs text-neutral-700 hover:bg-neutral-50">
                                        <span class="text-base">📎</span>
                                        <span class="truncate max-w-[160px] font-semibold">{{ $m->attachment_name }}</span>
                                        @if ($m->attachment_size_label)<span class="text-neutral-400">{{ $m->attachment_size_label }}</span>@endif
                                    </a>
                                @endif
                            @endif
                        </div>
                        <span class="text-[10px] text-neutral-400 shrink-0">{{ optional($m->created_at)->format('H:i') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <p id="chat-empty" class="text-center text-sm text-neutral-400 py-10">아직 대화가 없습니다. 첫 메시지를 보내보세요.</p>
        @endforelse
    </div>

    {{-- 드롭 안내 오버레이 --}}
    <div id="chat-drop" class="hidden absolute inset-0 z-20 m-2 rounded-2xl border-2 border-dashed border-mango-400 bg-mango-50/80 grid place-items-center text-mango-700 font-bold pointer-events-none">📎 파일을 놓아 전송</div>

    {{-- 입력 --}}
    <form id="chat-form" class="border-t border-neutral-100 bg-white p-3">
        <div id="chat-file-preview" class="hidden items-center gap-2 mb-2 rounded-lg bg-neutral-100 px-3 py-2 text-xs">
            <span id="chat-file-name" class="truncate max-w-[240px] font-semibold text-neutral-700"></span>
            <span id="chat-file-size" class="text-neutral-400"></span>
            <button type="button" id="chat-file-remove" class="ml-auto text-neutral-400 hover:text-rose-500" title="첨부 취소">✕</button>
        </div>
        <div class="flex items-end gap-2">
            <input type="file" id="chat-file" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip">
            <button type="button" id="chat-attach" title="파일 첨부"
                    class="shrink-0 w-10 h-10 grid place-items-center rounded-xl border border-neutral-200 text-lg hover:bg-neutral-50">📎</button>
            <textarea id="chat-input" rows="1" maxlength="2000" placeholder="메시지 입력 (Enter 전송, Shift+Enter 줄바꿈, 이미지 붙여넣기 가능)"
                      class="flex-1 resize-none rounded-xl border border-neutral-200 px-3.5 py-2.5 text-sm focus:border-mango-400 focus:ring-mango-400 max-h-32"></textarea>
            <button type="submit" class="shrink-0 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">전송</button>
        </div>
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
    const fileInput = document.getElementById('chat-file');
    const preview = document.getElementById('chat-file-preview');
    const dropEl = document.getElementById('chat-drop');
    let pendingFile = null;

    const esc = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const scroll = () => { box.scrollTop = box.scrollHeight; };
    const fmtSize = (b) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB';
    scroll();

    function setFile(f) {
        pendingFile = f || null;
        if (f) {
            document.getElementById('chat-file-name').textContent = f.name || '첨부파일';
            document.getElementById('chat-file-size').textContent = f.size ? fmtSize(f.size) : '';
            preview.classList.remove('hidden'); preview.classList.add('flex');
        } else {
            fileInput.value = '';
            preview.classList.add('hidden'); preview.classList.remove('flex');
        }
    }

    function attachmentHtml(m) {
        if (!m.attachment_url) return '';
        if (m.attachment_is_image) {
            return '<a href="' + m.attachment_url + '" target="_blank" class="block"><img src="' + m.attachment_url + '" class="max-w-[220px] max-h-[220px] rounded-xl border border-black/5"></a>';
        }
        return '<a href="' + m.attachment_url + '" target="_blank" download class="flex items-center gap-2 rounded-xl bg-white border border-neutral-200 px-3 py-2 text-xs text-neutral-700 hover:bg-neutral-50">' +
               '<span class="text-base">📎</span><span class="truncate max-w-[160px] font-semibold">' + esc(m.attachment_name) + '</span>' +
               (m.attachment_size_label ? '<span class="text-neutral-400">' + esc(m.attachment_size_label) + '</span>' : '') + '</a>';
    }

    function append(m) {
        if (box.querySelector(`[data-mid="${m.id}"]`)) return;
        const empty = document.getElementById('chat-empty');
        if (empty) empty.remove();
        const mine = m.sender_role === MY_SIDE;
        const bubble = mine ? 'bg-mango-500 text-white rounded-br-sm' : 'bg-white border border-neutral-200 text-neutral-800 rounded-bl-sm';
        const bodyHtml = m.body ? '<div class="rounded-2xl px-3.5 py-2 text-sm whitespace-pre-line break-words ' + bubble + '">' + esc(m.body) + '</div>' : '';
        const row = document.createElement('div');
        row.className = 'flex ' + (mine ? 'justify-end' : 'justify-start');
        row.dataset.mid = m.id;
        row.innerHTML =
            '<div class="max-w-[75%]">' +
            (mine ? '' : '<p class="text-[11px] text-neutral-400 mb-0.5 ml-1">' + esc(m.sender_name) + '</p>') +
            '<div class="flex items-end gap-1.5 ' + (mine ? 'flex-row-reverse' : '') + '">' +
            '<div class="flex flex-col gap-1 ' + (mine ? 'items-end' : 'items-start') + '">' + bodyHtml + attachmentHtml(m) + '</div>' +
            '<span class="text-[10px] text-neutral-400 shrink-0">' + esc(m.time || '') + '</span>' +
            '</div></div>';
        box.appendChild(row);
        scroll();
    }

    // 실시간 수신
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
    let sending = false;
    async function send() {
        if (sending) return;
        const body = input.value.trim();
        if (!body && !pendingFile) return;
        sending = true;
        const fd = new FormData();
        if (body) fd.append('body', body);
        if (pendingFile) fd.append('attachment', pendingFile, pendingFile.name || 'attachment');
        const saved = { body, file: pendingFile };
        input.value = ''; input.style.height = 'auto'; setFile(null);
        try {
            const r = await fetch(SEND_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            if (!r.ok) throw new Error('send failed');
            const d = await r.json();
            append(d.message);
        } catch (e) {
            alert('전송에 실패했습니다. (파일 용량/형식을 확인하세요)');
            input.value = saved.body; setFile(saved.file);
        } finally { sending = false; }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
    input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 128) + 'px'; });

    // 첨부 버튼 / 파일 선택
    document.getElementById('chat-attach').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => { if (fileInput.files[0]) setFile(fileInput.files[0]); });
    document.getElementById('chat-file-remove').addEventListener('click', () => setFile(null));

    // 붙여넣기(Copy & Paste)로 이미지 첨부
    input.addEventListener('paste', (e) => {
        const items = (e.clipboardData || window.clipboardData)?.items || [];
        for (const it of items) {
            if (it.kind === 'file' && it.type.startsWith('image/')) {
                const blob = it.getAsFile();
                if (blob) {
                    const ext = (it.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
                    const named = new File([blob], 'pasted-' + (new Date().getTime()) + '.' + ext, { type: it.type });
                    setFile(named);
                    e.preventDefault();
                }
                break;
            }
        }
    });

    // 드래그 앤 드롭
    const root = document.getElementById('chat-root');
    ['dragenter', 'dragover'].forEach(ev => root.addEventListener(ev, (e) => { e.preventDefault(); dropEl.classList.remove('hidden'); }));
    ['dragleave', 'drop'].forEach(ev => root.addEventListener(ev, (e) => { e.preventDefault(); if (ev !== 'drop' && e.target !== root && root.contains(e.relatedTarget)) return; dropEl.classList.add('hidden'); }));
    root.addEventListener('drop', (e) => { const f = e.dataTransfer?.files?.[0]; if (f) setFile(f); });
})();
</script>
@endpush

@extends('portal.layout')
@section('title', '매장 관리')

@section('content')
<div x-data="{
        inviteOpen: {{ $errors->has('email') && old('_invite') ? 'true' : 'false' }},
        editOpen: false,
        editForm: { id: null, name: '', region: '', phone: '', email: '', postcode: '', address: '', address_detail: '', biz_no: '', ceo: '', biz_type: '', biz_class: '', is_active: true },
        openEdit(s) { this.editForm = Object.assign({ postcode:'', address:'', address_detail:'', biz_no:'', ceo:'', biz_type:'', biz_class:'' }, s); this.editOpen = true; },
     }"
     @store-edit-open.window="openEdit($event.detail)">

<x-wms.page-head title="매장 관리" subtitle="가맹 매장을 이메일로 초대하고 계정 상태를 관리합니다" icon="🏪">
    <x-slot:actions>
        <button type="button" @click="inviteOpen = true"
                class="inline-flex items-center gap-1 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm transition">📧 이메일로 매장 초대</button>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $stores->map(function ($st) {
        $acc = $st->account;
        $state = ($acc && ! $acc->invite_token) ? 'active' : (($acc && $acc->invite_token) ? 'invited' : 'none');
        return [
            'name' => $st->name,
            'region' => $st->region ?: '',
            'phone' => $st->phone ?: '',
            'email' => $st->email ?: '',
            'acc_state' => $state,
            'has_email' => (bool) $st->email,
            'can_impersonate' => $state === 'active',
            'impersonate_url' => route('portal.hq.stores.impersonate', $st),
            'reinvite_url' => route('portal.hq.stores.reinvite', $st),
            'destroy_url' => route('portal.hq.stores.destroy', $st),
            'confirm' => '매장 «'.$st->name.'»을(를) 삭제할까요? 계정·채팅·재고도 함께 삭제되며 되돌릴 수 없습니다.',
            'edit' => [
                'id' => $st->id, 'name' => $st->name, 'region' => $st->region, 'phone' => $st->phone, 'email' => $st->email,
                'postcode' => $st->postcode, 'address' => $st->address, 'address_detail' => $st->address_detail,
                'biz_no' => $st->biz_no, 'ceo' => $st->ceo, 'biz_type' => $st->biz_type, 'biz_class' => $st->biz_class,
                'is_active' => (bool) $st->is_active,
            ],
        ];
    })->values();
@endphp

<x-wms.panel :title="'목록 (합계 '.number_format($stores->total()).'건)'">
    <div id="hqStoresGrid"></div>
</x-wms.panel>

<div class="mt-5">{{ $stores->links() }}</div>

{{-- ===== 매장 정보 수정 모달 ===== --}}
<div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="editOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">🏪 매장 정보 수정</h2>
            <button type="button" @click="editOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form method="POST" :action="'{{ url('portal/hq/stores') }}/' + editForm.id" class="p-6 space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장명 <span class="text-rose-500">*</span></label>
                <input type="text" name="name" x-model="editForm.name" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">지역</label>
                    <input type="text" name="region" x-model="editForm.region" maxlength="50"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처</label>
                    <input type="text" name="phone" x-model="editForm.phone" maxlength="30"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일 <span class="text-neutral-400 font-normal">(초대·알림용)</span></label>
                <input type="email" name="email" x-model="editForm.email" maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">주소 (배송지)</label>
                <div class="flex gap-2">
                    <input type="text" name="postcode" x-model="editForm.postcode" readonly placeholder="우편번호"
                           class="w-32 rounded-xl border-neutral-200 bg-neutral-50 text-sm">
                    <button type="button" @click="findAddress(d => { editForm.postcode = d.postcode; editForm.address = d.address })"
                            class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 text-sm">주소 검색</button>
                </div>
                <input type="text" name="address" x-model="editForm.address" readonly placeholder="주소 검색 버튼을 눌러 주세요"
                       class="w-full mt-2 rounded-xl border-neutral-200 bg-neutral-50 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">상세주소</label>
                <input type="text" name="address_detail" x-model="editForm.address_detail" maxlength="255"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="동·호수 등">
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-100 p-3 space-y-3">
                <p class="text-xs font-bold text-amber-700">📄 사업자정보 <span class="font-normal text-amber-500">(세금계산서 발행용)</span></p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">사업자등록번호</label>
                        <input type="text" name="biz_no" x-model="editForm.biz_no" maxlength="20" placeholder="000-00-00000"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">대표자</label>
                        <input type="text" name="ceo" x-model="editForm.ceo" maxlength="50"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">업태</label>
                        <input type="text" name="biz_type" x-model="editForm.biz_type" maxlength="100"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">종목</label>
                        <input type="text" name="biz_class" x-model="editForm.biz_class" maxlength="100"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                </div>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                <span class="text-sm font-semibold text-neutral-700">활성 매장</span>
            </label>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">저장</button>
                <button type="button" @click="editOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 매장 이메일 초대 모달 ===== --}}
<div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="inviteOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">📧 이메일로 매장 초대</h2>
            <button type="button" @click="inviteOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.stores.invite') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_invite" value="1">
            <p class="text-sm text-neutral-500">입력한 이메일로 초대 메일이 발송되며, 매장이 <b>비밀번호를 직접 설정</b>하면 포털을 사용할 수 있습니다.</p>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장명 <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('_invite') ? old('name') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="예: 리프렌즈 강남점">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">초대 이메일 <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('_invite') ? old('email') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="store@example.com">
                @if ($errors->has('email') && old('_invite'))<p class="text-xs text-rose-500 mt-1">{{ $errors->first('email') }}</p>@endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">지역 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="region" value="{{ old('_invite') ? old('region') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="서울">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="phone" value="{{ old('_invite') ? old('phone') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400">
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-7 py-3 transition">초대 메일 발송</button>
                <button type="button" @click="inviteOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>
@include('portal.partials.postcode-search')

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const reinviteForm = (action, label) => {
        const f = document.createElement('form'); f.method = 'POST'; f.action = action;
        const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; f.appendChild(t);
        const b = document.createElement('button'); b.textContent = label; b.className = 'text-[11px] font-semibold text-emerald-600 hover:underline'; f.appendChild(b);
        return f;
    };
    // 매장으로 보기 — 상단창(_top)에서 전체 셸을 매장 계정으로 다시 로드
    const impersonateForm = (action) => {
        const f = document.createElement('form'); f.method = 'POST'; f.action = action; f.target = '_top';
        const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; f.appendChild(t);
        const b = document.createElement('button'); b.type = 'submit'; b.textContent = '🖥 매장으로 보기';
        b.className = 'inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition';
        f.appendChild(b); return f;
    };
    ww.grid('hqStoresGrid', [
        { header: '매장명', name: 'name', width: 180, renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '지역', name: 'region', width: 110, renderer: (v) => v ? v : ww.dash() },
        { header: '연락처', name: 'phone', width: 140, renderer: (v) => v ? v : ww.dash() },
        { header: '이메일', name: 'email', width: 200, renderer: (v) => v ? v : ww.dash() },
        { header: '계정상태', name: 'acc_state', width: 130, align: 'center', exportable: false,
          renderer: (v, row) => {
              if (v === 'active') return ww.badge('활성', 'bg-emerald-100 text-emerald-700');
              if (v === 'invited') {
                  const wrap = ww.el('div', 'flex flex-col items-center gap-1');
                  wrap.appendChild(ww.badge('초대됨 · 대기', 'bg-amber-100 text-amber-700'));
                  wrap.appendChild(reinviteForm(row.reinvite_url, '재발송'));
                  return wrap;
              }
              const wrap = ww.el('div', 'flex flex-col items-center gap-1');
              wrap.appendChild(ww.badge('계정 없음', 'bg-neutral-100 text-neutral-400'));
              if (row.has_email) wrap.appendChild(reinviteForm(row.reinvite_url, '초대 메일 발송'));
              return wrap;
          } },
        { header: '매장 화면', name: 'impersonate_url', width: 150, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => row.can_impersonate
              ? impersonateForm(v)
              : ww.el('span', 'text-[11px] text-neutral-300', '계정 준비 후 가능') },
        { header: '관리', name: 'destroy_url', width: 110, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex items-center justify-center whitespace-nowrap');
              const eb = document.createElement('button'); eb.type = 'button'; eb.textContent = '수정';
              eb.className = 'text-xs font-bold text-mango-600 hover:text-mango-700 mr-3';
              eb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('store-edit-open', { detail: row.edit })));
              wrap.appendChild(eb);
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url; form.className = 'inline';
              form.addEventListener('submit', (e) => { if (!confirm(row.confirm)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
              const db = document.createElement('button'); db.textContent = '삭제'; db.className = 'text-xs font-bold text-rose-500 hover:text-rose-600'; form.appendChild(db);
              wrap.appendChild(form);
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection

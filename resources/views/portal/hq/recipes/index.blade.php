@extends('portal.layout')
@section('title', '레시피')

@php
    $recipeRows = $recipes->map(fn ($r) => [
        'id' => $r->id,
        'title' => $r->title,
        'product_name' => $r->product_name ?: (optional($r->product)->name ?? ''),
        'content' => (string) $r->content,
        'image_url' => $r->image_url,
        'author' => optional($r->author)->name ?? '본사',
        'created_at' => $r->created_at->format('Y.m.d'),
        'destroy_url' => route('portal.hq.recipes.destroy', $r),
    ])->values();
@endphp

@section('content')
<div x-data="{
        composeOpen: {{ $errors->any() ? 'true' : 'false' }},
        detailOpen: false,
        sel: {},
        preview: '',
        pick(e){ const f=e.target.files[0]; if(f) this.preview=URL.createObjectURL(f); },
        open(r){ this.sel = r; this.detailOpen = true; },
     }">

<x-wms.page-head title="레시피" subtitle="물품을 선택해 레시피(이미지·글)를 등록합니다. 카드를 클릭하면 상세를 봅니다." icon="📖">
    <x-slot:actions>
        <button type="button" @click="composeOpen = true; preview=''"
                class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">✏️ 레시피 작성</button>
    </x-slot:actions>
</x-wms.page-head>

@if ($recipes->isEmpty())
    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-12 text-center text-neutral-400">
        등록된 레시피가 없습니다. «레시피 작성»으로 추가하세요.
    </div>
@else
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach ($recipeRows as $r)
            <button type="button" @click='open(@json($r))'
                    class="text-left rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden hover:shadow-md hover:border-mango-200 transition">
                <div class="aspect-[4/3] bg-neutral-100 grid place-items-center overflow-hidden">
                    @if ($r['image_url'])
                        <img src="{{ $r['image_url'] }}" alt="" class="w-full h-full object-cover">
                    @else
                        <span class="text-4xl text-neutral-300">📖</span>
                    @endif
                </div>
                <div class="p-3">
                    @if ($r['product_name'])<span class="inline-block text-[11px] font-bold text-mango-700 bg-mango-50 rounded px-1.5 py-0.5 mb-1">{{ $r['product_name'] }}</span>@endif
                    <p class="font-extrabold text-neutral-900 text-sm line-clamp-1">{{ $r['title'] }}</p>
                    <p class="text-[11px] text-neutral-400 mt-1">{{ $r['author'] }} · {{ $r['created_at'] }}</p>
                </div>
            </button>
        @endforeach
    </div>
    @if ($recipes->hasPages())<div class="mt-5">{{ $recipes->links() }}</div>@endif
@endif

{{-- 작성 팝업 --}}
<div x-show="composeOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="composeOpen=false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">✏️ 레시피 작성</h2>
            <button @click="composeOpen=false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.recipes.store') }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">물품 선택 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <select name="supply_product_id" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <option value="">물품 없음 (일반 레시피)</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected((string) old('supply_product_id') === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 <span class="text-rose-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="레시피 제목">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용 <span class="text-neutral-400 font-normal">(서식 편집 가능)</span></label>
                <div id="recipeEditor" class="bg-white rounded-xl" style="min-height:180px"></div>
                <input type="hidden" name="content" id="recipeContent" value="{{ old('content') }}">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이미지 <span class="text-neutral-400 font-normal">(jpg·png·webp, 5MB 이하)</span></label>
                <input type="file" name="image_file" accept="image/*" @change="pick($event)"
                       class="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-mango-50 file:text-mango-600 file:font-bold file:px-4 file:py-2">
                <template x-if="preview"><img :src="preview" class="mt-2 w-full max-h-48 object-cover rounded-xl"></template>
            </div>
            @error('title')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('image_file')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">등록</button>
                <button type="button" @click="composeOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- 상세 팝업 --}}
<div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="detailOpen=false" @keydown.escape.window="detailOpen=false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <template x-if="sel.image_url">
            <img :src="sel.image_url" alt="" class="w-full max-h-72 object-cover">
        </template>
        <div class="p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <span x-show="sel.product_name" class="inline-block text-[11px] font-bold text-mango-700 bg-mango-50 rounded px-1.5 py-0.5 mb-1" x-text="sel.product_name"></span>
                    <h3 class="text-lg font-black text-neutral-900" x-text="sel.title"></h3>
                    <p class="text-xs text-neutral-400 mt-0.5"><span x-text="sel.author"></span> · <span x-text="sel.created_at"></span></p>
                </div>
                <button @click="detailOpen=false" class="text-neutral-400 hover:text-neutral-600 shrink-0">✕</button>
            </div>
            <div class="mt-4 text-sm text-neutral-800 leading-relaxed recipe-content" x-html="sel.content && sel.content.length ? sel.content : '<span class=\'text-neutral-400\'>내용이 없습니다.</span>'"></div>
            <div class="mt-5 pt-4 border-t border-neutral-100 flex justify-end">
                <form :action="sel.destroy_url" method="POST" onsubmit="return confirm('이 레시피를 삭제할까요?')">
                    @csrf @method('DELETE')
                    <button class="rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold px-4 py-2 text-sm">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>

@push('head')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow { border-color:#e5e7eb; border-top-left-radius:12px; border-top-right-radius:12px; }
    .ql-container.ql-snow { border-color:#e5e7eb; border-bottom-left-radius:12px; border-bottom-right-radius:12px; min-height:140px; font-family:inherit; font-size:14px; }
    .recipe-content ul { list-style:disc; padding-left:1.4em; }
    .recipe-content ol { list-style:decimal; padding-left:1.4em; }
    .recipe-content h1 { font-size:1.25rem; font-weight:800; margin:.4em 0; }
    .recipe-content h2 { font-size:1.1rem; font-weight:800; margin:.4em 0; }
    .recipe-content h3 { font-size:1rem; font-weight:700; margin:.3em 0; }
    .recipe-content a { color:#F2784B; text-decoration:underline; }
    .recipe-content p { margin:.3em 0; }
    .recipe-content img { max-width:100%; border-radius:8px; margin:.4em 0; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
    function initQuill() {
        const el = document.getElementById('recipeEditor');
        if (!el || el.__quill || typeof Quill === 'undefined') return;
        const q = new Quill(el, {
            theme: 'snow',
            placeholder: '재료 · 조리 순서 등을 입력하세요',
            modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], [{ header: [1, 2, 3, false] }], ['link', 'clean']] },
        });
        el.__quill = q;
        const hidden = document.getElementById('recipeContent');
        if (hidden && hidden.value) q.root.innerHTML = hidden.value;
        const form = el.closest('form');
        if (form) form.addEventListener('submit', () => { hidden.value = q.root.innerHTML.replace(/^<p><br><\/p>$/, ''); });
    }
    if (document.readyState !== 'loading') initQuill(); else document.addEventListener('DOMContentLoaded', initQuill);
    let tries = 0;
    const iv = setInterval(() => { initQuill(); const el = document.getElementById('recipeEditor'); if ((el && el.__quill) || ++tries > 25) clearInterval(iv); }, 200);
})();
</script>
@endpush
@endsection

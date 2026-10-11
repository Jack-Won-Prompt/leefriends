@extends('portal.layout')
@section('title', '레시피')

@php
    $recipeRows = $recipes->map(fn ($r) => [
        'id' => $r->id,
        'title' => $r->title,
        'supply_product_id' => $r->supply_product_id,
        'product_name' => $r->product_name ?: (optional($r->product)->name ?? ''),
        'content' => (string) $r->content,
        'image_url' => $r->image_url,
        'video_url' => $r->video_url,
        'video_link' => (string) $r->video_link,
        'video_embed_url' => $r->video_embed_url,
        'has_video' => $r->has_video,
        'author' => optional($r->author)->name ?? '본사',
        'created_at' => $r->created_at->format('Y.m.d'),
        'destroy_url' => route('portal.hq.recipes.destroy', $r),
    ])->values();
    $editId = old('edit_id');
@endphp

@section('content')
<div x-data="{
        rows: @js($recipeRows),
        baseUrl: '{{ route('portal.hq.recipes.store') }}',
        maxMb: {{ (int) $videoMaxMb }},
        formOpen: {{ $errors->any() ? 'true' : 'false' }},
        mode: '{{ $editId ? 'edit' : 'create' }}',
        form: { id: {{ $editId ? (int) $editId : 'null' }}, product: '{{ old('supply_product_id') }}', link: @js(old('video_link', '')) },
        curImage: '', curVideo: '', curEmbed: '',
        removeImage: false, removeVideo: false,
        imgPreview: '', vidPreview: '', vidName: '', sizeError: '',
        detailOpen: false, sel: {},

        init() {
            // 검증 실패로 되돌아온 경우 수정 모드와 기존 첨부를 복원한다
            if (this.mode === 'edit' && this.form.id) {
                const r = this.rows.find(x => x.id === this.form.id);
                if (r) { this.curImage = r.image_url || ''; this.curVideo = r.video_url || ''; this.curEmbed = r.video_embed_url || ''; }
            }
        },
        get action() { return this.mode === 'edit' ? this.baseUrl + '/' + this.form.id : this.baseUrl; },
        resetAttachments() {
            this.curImage = ''; this.curVideo = ''; this.curEmbed = '';
            this.removeImage = false; this.removeVideo = false;
            this.imgPreview = ''; this.vidPreview = ''; this.vidName = ''; this.sizeError = '';
        },
        openCreate() {
            this.mode = 'create';
            this.form = { id: null, product: '', link: '' };
            this.resetAttachments();
            this.$refs.titleInput.value = '';
            this.$refs.imageInput.value = ''; this.$refs.videoInput.value = '';
            this.setEditor('');
            this.formOpen = true;
        },
        openEdit(r) {
            this.mode = 'edit';
            this.form = { id: r.id, product: r.supply_product_id ?? '', link: r.video_link || '' };
            this.resetAttachments();
            this.curImage = r.image_url || ''; this.curVideo = r.video_url || ''; this.curEmbed = r.video_embed_url || '';
            this.$refs.titleInput.value = r.title;
            this.$refs.imageInput.value = ''; this.$refs.videoInput.value = '';
            this.setEditor(r.content || '');
            this.detailOpen = false;
            this.formOpen = true;
        },
        setEditor(html) {
            const el = document.getElementById('recipeEditor');
            // root.innerHTML 에 직접 넣으면 Quill 내부 모델과 어긋나 목록(ul/ol)이 사라진다
            if (el && el.__quill) el.__quill.setContents(el.__quill.clipboard.convert({ html: html || '' }), 'silent');
            const hidden = document.getElementById('recipeContent');
            if (hidden) hidden.value = html || '';
        },
        pickImage(e) { const f = e.target.files[0]; this.imgPreview = f ? URL.createObjectURL(f) : ''; if (f) this.removeImage = false; },
        pickVideo(e) {
            const f = e.target.files[0];
            this.sizeError = ''; this.vidPreview = ''; this.vidName = '';
            if (!f) return;
            if (f.size > this.maxMb * 1024 * 1024) {
                this.sizeError = '이 서버가 받을 수 있는 영상 용량은 약 ' + this.maxMb + 'MB 입니다. 더 큰 영상은 아래 «영상 링크»를 사용하세요.';
                e.target.value = '';
                return;
            }
            this.vidPreview = URL.createObjectURL(f); this.vidName = f.name; this.removeVideo = false;
        },
        open(r) { this.sel = r; this.detailOpen = true; },
     }">

<x-wms.page-head title="레시피" subtitle="물품을 선택해 레시피(이미지·영상·글)를 등록합니다. 카드를 클릭하면 상세를 보고 수정할 수 있습니다." icon="📖">
    <x-slot:actions>
        <button type="button" @click="openCreate()"
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
                <div class="relative aspect-[4/3] bg-neutral-100 grid place-items-center overflow-hidden">
                    @if ($r['image_url'])
                        <img src="{{ $r['image_url'] }}" alt="" class="w-full h-full object-cover">
                    @else
                        <span class="text-4xl text-neutral-300">📖</span>
                    @endif
                    @if ($r['has_video'])
                        <span class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full bg-black/60 text-white text-[10px] font-bold px-2 py-0.5">▶ 영상</span>
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

{{-- 작성·수정 팝업 (같은 폼을 mode 로 나눠 쓴다) --}}
<div x-show="formOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="formOpen=false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'edit' ? '✏️ 레시피 수정' : '✏️ 레시피 작성'"></h2>
            <button @click="formOpen=false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'">
                <div>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="edit_id" :value="form.id">
                </div>
            </template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">물품 선택 <span class="text-neutral-400 font-normal">(선택 · 이름으로 검색)</span></label>
                <div class="relative"
                     x-data="{
                        products: @js($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values()),
                        pquery: '',
                        popen: false,
                        get pfiltered() { const q = this.pquery.trim().toLowerCase(); return q === '' ? this.products : this.products.filter(p => p.name.toLowerCase().includes(q)); },
                        psync() { const sel = this.products.find(p => String(p.id) === String(this.form.product)); this.pquery = sel ? sel.name : ''; },
                        ppick(p) { this.form.product = p.id; this.pquery = p.name; this.popen = false; },
                        pclear() { this.form.product = ''; this.pquery = ''; this.popen = false; },
                     }"
                     x-init="psync(); $watch('form.product', () => psync())"
                     @click.outside="popen = false">
                    <input type="hidden" name="supply_product_id" :value="form.product">
                    <div class="relative">
                        <input type="text" x-model="pquery" @focus="popen = true" @input="popen = true"
                               placeholder="물품 없음 (일반 레시피)" autocomplete="off"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm pr-8">
                        <button type="button" x-show="form.product" @click="pclear()" x-cloak
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600">✕</button>
                    </div>
                    <ul x-show="popen" x-cloak
                        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-neutral-200 rounded-xl shadow-lg text-sm">
                        <li @click="pclear()" class="px-3 py-2 hover:bg-mango-50 cursor-pointer text-neutral-500">물품 없음 (일반 레시피)</li>
                        <template x-for="p in pfiltered" :key="p.id">
                            <li @click="ppick(p)" x-text="p.name"
                                class="px-3 py-2 hover:bg-mango-50 cursor-pointer"
                                :class="String(p.id) === String(form.product) ? 'bg-mango-50 font-bold text-mango-700' : 'text-neutral-800'"></li>
                        </template>
                        <li x-show="pfiltered.length === 0" class="px-3 py-2 text-neutral-400">검색 결과 없음</li>
                    </ul>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 <span class="text-rose-500">*</span></label>
                <input type="text" name="title" x-ref="titleInput" value="{{ old('title') }}" maxlength="150" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="레시피 제목">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용 <span class="text-neutral-400 font-normal">(서식 편집 가능)</span></label>
                <div id="recipeEditor" class="bg-white rounded-xl" style="min-height:180px"></div>
                <input type="hidden" name="content" id="recipeContent" value="{{ old('content') }}">
            </div>

            {{-- 이미지 --}}
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이미지 <span class="text-neutral-400 font-normal">(jpg·png·webp, 5MB 이하)</span></label>
                {{-- 조건에 removeImage 를 넣으면 체크하는 순간 체크박스까지 사라져 값이 전송되지 않는다 --}}
                <template x-if="curImage && !imgPreview">
                    <div class="mb-2">
                        <img :src="curImage" class="w-full max-h-40 object-cover rounded-xl" :class="removeImage && 'opacity-30 grayscale'">
                        <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-neutral-500">
                            <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="rounded border-neutral-300 text-rose-500 focus:ring-rose-400">
                            기존 이미지 삭제
                        </label>
                    </div>
                </template>
                <input type="file" name="image_file" accept="image/*" x-ref="imageInput" @change="pickImage($event)"
                       class="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-mango-50 file:text-mango-600 file:font-bold file:px-4 file:py-2">
                <template x-if="imgPreview"><img :src="imgPreview" class="mt-2 w-full max-h-48 object-cover rounded-xl"></template>
            </div>

            {{-- 영상 --}}
            <div class="rounded-xl bg-neutral-50 border border-neutral-100 p-4 space-y-3">
                <p class="text-sm font-bold text-neutral-700">🎬 영상 <span class="text-neutral-400 font-normal">(매장에서 레시피를 열면 함께 재생됩니다)</span></p>
                <div>
                    <label class="block text-xs font-bold text-neutral-500 mb-1.5">영상 파일 업로드 <span class="font-normal">(mp4·webm·mov·m4v, 약 <span x-text="maxMb"></span>MB 이하)</span></label>
                    <template x-if="curVideo && !vidPreview">
                        <div class="mb-2">
                            <video :src="curVideo" controls preload="metadata" class="w-full max-h-48 rounded-xl bg-black" :class="removeVideo && 'opacity-30'"></video>
                            <label class="mt-1 inline-flex items-center gap-1.5 text-xs text-neutral-500">
                                <input type="checkbox" name="remove_video" value="1" x-model="removeVideo" class="rounded border-neutral-300 text-rose-500 focus:ring-rose-400">
                                기존 영상 삭제
                            </label>
                        </div>
                    </template>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,video/*" x-ref="videoInput" @change="pickVideo($event)"
                           class="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:border file:border-neutral-200 file:text-neutral-600 file:font-bold file:px-4 file:py-2">
                    <template x-if="vidPreview">
                        <div class="mt-2">
                            <video :src="vidPreview" controls preload="metadata" class="w-full max-h-48 rounded-xl bg-black"></video>
                            <p class="text-[11px] text-neutral-400 mt-1" x-text="vidName"></p>
                        </div>
                    </template>
                    <p x-show="sizeError" x-text="sizeError" class="text-xs text-rose-500 mt-1.5"></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-neutral-500 mb-1.5">또는 영상 링크 <span class="font-normal">(유튜브·Vimeo 등, 큰 영상은 이쪽이 편합니다)</span></label>
                    <input type="url" name="video_link" x-model="form.link" maxlength="500" placeholder="https://youtu.be/..."
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <p class="text-[11px] text-neutral-400">업로드한 파일과 링크를 모두 넣으면 매장 화면에 둘 다 보입니다.</p>
            </div>

            @error('title')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('image_file')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('video_file')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('video_link')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition"
                        x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="formOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
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

            <template x-if="sel.video_url">
                <div class="mt-4">
                    <p class="text-xs font-bold text-neutral-500 mb-1.5">🎬 레시피 영상</p>
                    <video :src="sel.video_url" controls preload="metadata" class="w-full rounded-xl bg-black"></video>
                </div>
            </template>
            <template x-if="sel.video_embed_url">
                <div class="mt-4">
                    <p class="text-xs font-bold text-neutral-500 mb-1.5">🎬 영상 링크</p>
                    <div class="aspect-video rounded-xl overflow-hidden bg-black">
                        <iframe :src="sel.video_embed_url" class="w-full h-full" frameborder="0" allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                    </div>
                </div>
            </template>

            <div class="mt-4 text-sm text-neutral-800 leading-relaxed recipe-content" x-html="sel.content && sel.content.length ? sel.content : '<span class=\'text-neutral-400\'>내용이 없습니다.</span>'"></div>
            <div class="mt-5 pt-4 border-t border-neutral-100 flex justify-end gap-2">
                <button type="button" @click="openEdit(sel)" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">수정</button>
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
        if (hidden && hidden.value) q.setContents(q.clipboard.convert({ html: hidden.value }), 'silent');
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

@extends('portal.layout')
@section('title', '레시피')

@php
    $recipeRows = $recipes->map(fn ($r) => [
        'title' => $r->title,
        'product_name' => $r->product_name ?: (optional($r->product)->name ?? ''),
        'content' => (string) $r->content,
        'image_url' => $r->image_url,
        'video_url' => $r->video_url,
        'video_embed_url' => $r->video_embed_url,
        'has_video' => $r->has_video,
        'created_at' => $r->created_at->format('Y.m.d'),
    ])->values();
@endphp

@section('content')
<div x-data="{ detailOpen: false, sel: {}, open(r){ this.sel = r; this.detailOpen = true; }, close(){ this.detailOpen = false; this.sel = {}; } }">

<x-wms.page-head title="레시피" subtitle="본사가 등록한 레시피입니다. 카드를 클릭하면 상세와 영상을 봅니다." icon="📖" />

@if ($recipes->isEmpty())
    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-12 text-center text-neutral-400">
        등록된 레시피가 없습니다.
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
                    <p class="text-[11px] text-neutral-400 mt-1">{{ $r['created_at'] }}</p>
                </div>
            </button>
        @endforeach
    </div>
    @if ($recipes->hasPages())<div class="mt-5">{{ $recipes->links() }}</div>@endif
@endif

{{-- 상세 팝업 — 닫을 때 sel 을 비워 재생 중인 영상이 멈춘다 --}}
<div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/50 p-4" @click.self="close()" @keydown.escape.window="close()">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <template x-if="sel.image_url">
            <img :src="sel.image_url" alt="" class="w-full max-h-72 object-cover">
        </template>
        <div class="p-5">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <span x-show="sel.product_name" class="inline-block text-[11px] font-bold text-mango-700 bg-mango-50 rounded px-1.5 py-0.5 mb-1" x-text="sel.product_name"></span>
                    <h3 class="text-lg font-black text-neutral-900" x-text="sel.title"></h3>
                    <p class="text-xs text-neutral-400 mt-0.5"><span x-text="sel.created_at"></span></p>
                </div>
                <button @click="close()" class="text-neutral-400 hover:text-neutral-600 shrink-0">✕</button>
            </div>

            {{-- 영상 — 업로드 파일 / 외부 링크 --}}
            <template x-if="sel.video_url">
                <div class="mt-4">
                    <p class="text-xs font-bold text-neutral-500 mb-1.5">🎬 레시피 영상</p>
                    <video :src="sel.video_url" controls playsinline preload="metadata" class="w-full rounded-xl bg-black"></video>
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
        </div>
    </div>
</div>

</div>

@push('head')
<style>
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
@endsection

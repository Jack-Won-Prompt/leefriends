@extends('admin.layout')
@section('title', '팝업 관리')

@section('content')
<div x-data="{
        active: {{ $popup->is_active ? 'true' : 'false' }},
        title: @js($popup->title ?? '과일 납품 문의'),
        body: @js($popup->body ?? '프랜차이즈·매장·카페에 필요한 신선 과일, 리프랜즈가 정성껏 공급합니다.'),
        contact: @js($popup->contact ?? ''),
        linkLabel: @js($popup->link_label ?: '문의하기'),
        preview: @js($popup->image ? asset($popup->image) : ''),
        removeImage: false,
        pick(e){ const f=e.target.files[0]; if(f){ this.preview=URL.createObjectURL(f); this.removeImage=false; } },
        dropImg(){ this.preview=''; this.removeImage=true; },
     }" class="max-w-5xl">

    <div class="mb-6">
        <h1 class="text-2xl font-black text-neutral-900">메인 페이지 팝업</h1>
        <p class="text-neutral-500 mt-1 text-sm">메인 방문 시 뜨는 팝업을 설정합니다. (프랜차이즈·매장·카페 과일 문의 등)</p>
    </div>

    <form method="POST" action="{{ route('admin.popup.update') }}" enctype="multipart/form-data" class="grid lg:grid-cols-2 gap-6">
        @csrf @method('PATCH')

        {{-- 설정 --}}
        <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6 space-y-5">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" x-model="active" class="w-5 h-5 rounded text-mango-500 focus:ring-mango-400">
                <span class="font-bold text-neutral-800">메인 페이지에 팝업 노출</span>
                <span class="text-xs px-2 py-0.5 rounded-full" :class="active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400'" x-text="active ? '노출 중' : '숨김'"></span>
            </label>

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 <span class="text-rose-500">*</span></label>
                <input type="text" name="title" x-model="title" maxlength="100" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">안내 문구</label>
                <textarea name="body" x-model="body" rows="3" maxlength="500"
                          class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400"></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이미지 <span class="text-neutral-400 font-normal">(jpg·png·webp, 5MB 이하)</span></label>
                <input type="file" name="image_file" accept="image/*" @change="pick($event)"
                       class="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-mango-50 file:text-mango-600 file:font-bold file:px-4 file:py-2">
                <template x-if="preview">
                    <button type="button" @click="dropImg()" class="mt-2 text-xs font-bold text-rose-500 hover:underline">이미지 제거</button>
                </template>
                <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">
            </div>

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">문의 연락처 <span class="text-neutral-400 font-normal">(전화 등)</span></label>
                <input type="text" name="contact" x-model="contact" maxlength="100" placeholder="예: 1600-0000 / 카카오채널 @리프랜즈"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">이동 링크(URL)</label>
                    <input type="text" name="link_url" value="{{ $popup->link_url ?: '/franchise#inquiry' }}" maxlength="255"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                    <p class="text-[11px] text-neutral-400 mt-1">클릭 시 이동할 문의하기 페이지. 기본: /franchise#inquiry</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">버튼 문구</label>
                    <input type="text" name="link_label" x-model="linkLabel" maxlength="30"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>

            <div class="pt-1">
                <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-6 py-3 transition">저장</button>
            </div>
        </div>

        {{-- 실시간 미리보기 --}}
        <div>
            <p class="text-sm font-bold text-neutral-500 mb-3">미리보기</p>
            <div class="rounded-2xl bg-neutral-100 border border-neutral-200 p-6 grid place-items-center min-h-[420px]">
                <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl overflow-hidden" :class="!active && 'opacity-50'">
                    <template x-if="preview">
                        <img :src="preview" class="w-full h-48 object-cover" alt="">
                    </template>
                    <div class="p-6 text-center">
                        <h3 class="text-xl font-black text-neutral-900" x-text="title"></h3>
                        <p class="text-sm text-neutral-500 mt-2 whitespace-pre-line" x-text="body"></p>
                        <template x-if="contact">
                            <p class="mt-3 text-mango-600 font-extrabold text-lg" x-text="contact"></p>
                        </template>
                        <div class="mt-5 rounded-xl bg-mango-500 text-white font-bold px-5 py-3" x-text="linkLabel"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

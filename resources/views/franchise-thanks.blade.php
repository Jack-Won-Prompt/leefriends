@extends('layouts.app')

@section('title', '문의 완료 · LEEFRIENDS')

@section('content')
<section class="min-h-screen pt-[72px] grid place-items-center bg-gradient-to-br from-mango-50 to-white">
    <div class="text-center px-5 py-24 max-w-lg animate-fadeup">
        <div class="text-7xl mb-6 animate-floaty">🥭</div>
        <h1 class="text-3xl md:text-4xl font-black text-neutral-900 mb-4">문의가 접수되었습니다!</h1>
        <p class="text-neutral-500 text-lg leading-relaxed mb-10">
            소중한 창업 문의 감사합니다.<br>
            담당자가 확인 후 영업일 기준 1~2일 내에<br>남겨주신 연락처로 연락드리겠습니다.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-full bg-mango-500 hover:bg-mango-600 text-white font-bold px-8 py-3.5 shadow-soft transition">홈으로</a>
            <a href="{{ route('franchise') }}" class="rounded-full border-2 border-mango-500 text-mango-700 font-bold px-8 py-3.5 hover:bg-mango-50 transition">창업 안내 다시 보기</a>
        </div>
    </div>
</section>
@endsection

<footer class="bg-neutral-900 text-neutral-400">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-16">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl">🥭</span>
                    <span class="font-black text-xl text-white"><span class="text-mango-400">LEE</span>FRIENDS</span>
                </div>
                <p class="text-sm leading-relaxed max-w-md">
                    농익은 애플망고로 만드는 프리미엄 망고빙수 전문점.<br>
                    사계절 즐기는 디저트 카페, 리프렌즈와 함께하세요.
                </p>
                <div class="flex gap-3 mt-6">
                    @foreach (['instagram' => 'IG', 'youtube' => 'YT', 'blog' => 'BLOG'] as $k => $v)
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 hover:bg-mango-500 hover:text-white grid place-items-center text-xs font-bold transition">{{ $v }}</a>
                    @endforeach
                </div>
            </div>

            <div>
                <h4 class="text-white font-bold mb-4 text-sm">바로가기</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('brand') }}" class="hover:text-mango-400 transition">브랜드 스토리</a></li>
                    <li><a href="{{ route('menu') }}" class="hover:text-mango-400 transition">메뉴 안내</a></li>
                    <li><a href="{{ route('store') }}" class="hover:text-mango-400 transition">매장 찾기</a></li>
                    <li><a href="{{ route('franchise') }}" class="hover:text-mango-400 transition">창업 안내</a></li>
                    <li><a href="{{ route('notice.index') }}" class="hover:text-mango-400 transition">공지사항</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-4 text-sm">창업 문의</h4>
                <p class="text-3xl font-black text-white">1600-0000</p>
                <p class="text-sm mt-2">평일 09:00 - 18:00 (주말·공휴일 휴무)</p>
                <a href="{{ route('franchise') }}#inquiry"
                   class="inline-flex mt-5 items-center gap-1.5 rounded-full bg-mango-500 hover:bg-mango-600 px-5 py-2.5 text-sm font-bold text-white transition">
                    온라인 창업문의 →
                </a>
            </div>
        </div>

        <div class="border-t border-white/10 mt-12 pt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 text-xs">
            <div class="space-y-1">
                <p>(주)리프렌즈 &nbsp;|&nbsp; 대표 홍길동 &nbsp;|&nbsp; 사업자등록번호 123-45-67890</p>
                <p>서울특별시 강남구 테헤란로 123 망고빌딩 &nbsp;|&nbsp; 통신판매업 제2026-서울강남-0000호</p>
                <p class="text-neutral-500">© {{ date('Y') }} LEEFRIENDS. All rights reserved.</p>
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-white transition">이용약관</a>
                <a href="#" class="font-semibold text-neutral-300 hover:text-white transition">개인정보처리방침</a>
                <a href="{{ route('admin.login') }}" class="hover:text-white transition">관리자</a>
            </div>
        </div>
    </div>
</footer>

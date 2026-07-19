<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>회원가입 · LEEFRIENDS 발주포털</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
            colors: { mango: { 50:'#FFF9ED',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B' } },
        }}}
    </script>
</head>
<body class="font-sans min-h-screen grid place-items-center bg-gradient-to-br from-neutral-800 via-neutral-900 to-black p-5">
    <div class="w-full max-w-md my-8">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🥭</div>
            <h1 class="text-2xl font-black text-white"><span class="text-mango-400">LEE</span>FRIENDS</h1>
            <p class="text-neutral-400 mt-1 text-sm font-semibold tracking-widest">B2B 발주포털 회원가입</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-xl font-extrabold text-neutral-900 mb-1 text-center">회원가입</h2>
            <p class="text-center text-sm text-neutral-400 mb-6">가입 후 본사 승인이 완료되면 이용하실 수 있습니다</p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.register.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">회원 종류</label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (\App\Models\User::SIGNUP_TYPES as $val => $label)
                            <label class="relative flex flex-col items-center justify-center rounded-xl border-2 border-neutral-200 py-4 cursor-pointer has-[:checked]:border-mango-500 has-[:checked]:bg-mango-50 transition">
                                <input type="radio" name="member_type" value="{{ $val }}" class="sr-only peer"
                                       {{ old('member_type', 'store') === $val ? 'checked' : '' }} required>
                                <span class="text-2xl mb-1">{{ $val === 'store' ? '🛒' : '📦' }}</span>
                                <span class="text-sm font-bold text-neutral-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-xs text-neutral-400">구매자는 매장이 아니어도 신청하실 수 있습니다.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">상호(회사명)</label>
                    <input type="text" name="org_name" value="{{ old('org_name') }}" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="리프렌즈 상사">
                </div>

                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">담당자명</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="홍길동">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="010-1234-5678">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">사업자번호 <span class="font-normal text-neutral-400">(선택)</span></label>
                        <input type="text" name="biz_no" value="{{ old('biz_no') }}"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="000-00-00000">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일 <span class="font-normal text-neutral-400">(로그인 아이디)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="buyer@example.com">
                </div>

                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">주소 <span class="font-normal text-neutral-400">(선택)</span></label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="서울시 강남구 …">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">비밀번호</label>
                        <input type="password" name="password" required
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="8자 이상">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">비밀번호 확인</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="8자 이상">
                    </div>
                </div>

                <button class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-bold py-3.5 hover:brightness-105 active:scale-[0.99] transition">가입 신청</button>
            </form>

            <div class="mt-6 pt-5 border-t border-neutral-100 text-center">
                <a href="{{ route('portal.login') }}" class="text-sm font-bold text-mango-600 hover:text-mango-700">← 로그인으로 돌아가기</a>
            </div>
        </div>
    </div>
</body>
</html>

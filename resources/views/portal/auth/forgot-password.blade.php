<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>비밀번호 찾기 · 발주포털</title>
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
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🔑</div>
            <h1 class="text-2xl font-black text-white">비밀번호 찾기</h1>
            <p class="text-neutral-400 mt-1 text-sm">가입한 이메일로 재설정 링크를 보내드립니다.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">
            @if (session('status'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="가입한 이메일 주소">
                </div>
                <button class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-bold py-3.5 hover:brightness-105 active:scale-[0.99] transition">재설정 링크 보내기</button>
            </form>
        </div>

        <p class="text-center text-neutral-500 text-sm mt-6">
            <a href="{{ route('portal.login') }}" class="hover:text-white underline underline-offset-4">← 로그인으로</a>
        </p>
    </div>
</body>
</html>

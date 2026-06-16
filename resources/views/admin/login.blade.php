<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리자 로그인 · LEEFRIENDS</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
            colors: { mango: { 50:'#FFF9ED',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B' } },
        }}}
    </script>
</head>
<body class="font-sans min-h-screen grid place-items-center bg-gradient-to-br from-mango-400 via-mango-500 to-mango-600 p-5">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🥭</div>
            <h1 class="text-2xl font-black text-white"><span class="text-white">LEE</span>FRIENDS <span class="text-white/80 text-lg font-bold">ADMIN</span></h1>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-xl font-extrabold text-neutral-900 mb-6 text-center">관리자 로그인</h2>

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="admin@mangobing.kr">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">비밀번호</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-600">
                    <input type="checkbox" name="remember" class="rounded text-mango-500 focus:ring-mango-400"> 로그인 상태 유지
                </label>
                <button class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-bold py-3.5 hover:brightness-105 active:scale-[0.99] transition">
                    로그인
                </button>
            </form>

            <p class="text-center text-xs text-neutral-400 mt-6">
                초기 계정: admin@mangobing.kr / admin1234
            </p>
        </div>

        <p class="text-center text-white/80 text-sm mt-6">
            <a href="{{ route('home') }}" class="hover:text-white underline underline-offset-4">← 홈페이지로 돌아가기</a>
        </p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>공급처 초대 · LEEFRIENDS</title>
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
            <div class="text-5xl mb-3">🥭</div>
            <h1 class="text-2xl font-black text-white"><span class="text-mango-400">LEE</span>FRIENDS</h1>
            <p class="text-neutral-400 mt-1 text-sm font-semibold tracking-widest">공급처 포털 초대</p>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8">
            @if (! $user)
                <div class="text-center py-6">
                    <div class="text-4xl mb-3">⚠️</div>
                    <h2 class="text-lg font-extrabold text-neutral-900 mb-2">유효하지 않은 초대 링크</h2>
                    <p class="text-sm text-neutral-500 mb-6">초대 링크가 만료되었거나 이미 사용되었습니다. 본사에 재발송을 요청해 주세요.</p>
                    <a href="{{ route('portal.login') }}" class="inline-block rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-6 py-2.5 text-sm">로그인으로</a>
                </div>
            @else
                @php
                    $roleLabel = $user->role === 'store' ? '매장' : '공급처';
                    $orgName = $user->store?->name ?? $user->supplier?->name ?? $user->name;
                @endphp
                <h2 class="text-xl font-extrabold text-neutral-900 mb-1 text-center">비밀번호 설정</h2>
                <p class="text-center text-sm text-neutral-400 mb-2">
                    <b class="text-mango-600">{{ $orgName }}</b> {{ $roleLabel }} 초대
                </p>
                <p class="text-center text-xs text-neutral-400 mb-6">{{ $user->email }}</p>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('portal.invite.accept', $token) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">비밀번호 <span class="text-neutral-400 font-normal">(8자 이상)</span></label>
                        <input type="password" name="password" required autofocus minlength="8"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">비밀번호 확인</label>
                        <input type="password" name="password_confirmation" required minlength="8"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="••••••••">
                    </div>
                    <button class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-bold py-3.5 hover:brightness-105 active:scale-[0.99] transition">비밀번호 설정하고 시작하기</button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>

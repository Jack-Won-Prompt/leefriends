<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · LEEFRIENDS</title>
    <link rel="icon" href="{{ asset('images/menu/mango-cheese-bingsu.svg') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(160deg, #fff7ed 0%, #ffedd5 100%); color: #1f2937; padding: 24px;
        }
        .card {
            background: #fff; border-radius: 28px; box-shadow: 0 20px 60px rgba(255,159,28,.15);
            border: 1px solid #fde9cf; padding: 48px 40px; max-width: 480px; width: 100%; text-align: center;
        }
        .emoji { font-size: 56px; line-height: 1; }
        .code {
            font-size: 84px; font-weight: 900; line-height: 1; margin-top: 8px;
            background: linear-gradient(135deg, #FF9F1C, #F97316); -webkit-background-clip: text;
            -webkit-text-fill-color: transparent; background-clip: text;
        }
        h1 { font-size: 22px; font-weight: 800; color: #111827; margin-top: 14px; }
        p { font-size: 15px; color: #6b7280; margin-top: 10px; line-height: 1.6; }
        .actions { margin-top: 28px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a.btn {
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
            font-weight: 800; font-size: 14px; padding: 12px 22px; border-radius: 14px; transition: .15s;
        }
        a.primary { background: #FF9F1C; color: #fff; }
        a.primary:hover { background: #f59315; }
        a.ghost { background: #f3f4f6; color: #4b5563; }
        a.ghost:hover { background: #e5e7eb; }
        .foot { margin-top: 26px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <div class="emoji">@yield('emoji', '🥭')</div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn primary">🏠 홈으로</a>
            <a href="{{ url()->previous() }}" class="btn ghost">← 이전 페이지</a>
        </div>
        <div class="foot">LEEFRIENDS</div>
    </div>
</body>
</html>

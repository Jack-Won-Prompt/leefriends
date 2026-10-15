<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>결제 결과 · LEEFRIENDS</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:#FFF8F1;
               font-family:'Pretendard Variable',Pretendard,system-ui,sans-serif; color:#2A2320; padding:24px; }
        .card { background:#fff; border:1px solid #F0E6DC; border-radius:20px; padding:28px 24px; max-width:360px; width:100%; text-align:center; }
        .icon { font-size:40px; line-height:1; }
        .msg { margin-top:12px; font-size:15px; font-weight:700; line-height:1.6; }
        .hint { margin-top:8px; font-size:13px; color:#8B7B6E; line-height:1.6; }
    </style>
</head>
{{-- 앱 웹뷰는 이 주소(/pay/app/done)로 이동하는 순간 창을 닫고 결과를 API 로 다시 확인한다.
     가로채지 못했을 때를 위해 사람이 읽을 수 있는 안내를 보여준다. --}}
<body>
    <div class="card">
        <div class="icon">
            @if ($status === 'paid') ✅
            @elseif ($status === 'waiting_deposit') 🏦
            @elseif ($status === 'fail') ⚠️
            @else ↩️
            @endif
        </div>
        <p class="msg">{{ $message }}</p>
        <p class="hint">앱으로 돌아가 주세요. 창이 자동으로 닫히지 않으면 뒤로 가기를 눌러 주세요.</p>
    </div>
</body>
</html>

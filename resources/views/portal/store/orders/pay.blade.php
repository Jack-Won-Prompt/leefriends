<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>발주 결제 · LEEFRIENDS</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://js.tosspayments.com/v2/standard"></script>
    <style>body{font-family:'Pretendard Variable',Pretendard,system-ui,sans-serif}</style>
</head>
{{-- 토스 결제는 보안상 포털 iframe 안에서 동작하지 않으므로 상단 창으로 올려 팝업처럼 띄운다 --}}
<script>if (window.top !== window.self) { window.top.location.href = window.location.href; }</script>
<body class="min-h-screen bg-neutral-900/60 backdrop-blur-sm grid place-items-center p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
            <h1 class="text-lg font-black text-neutral-900">🧾 발주 결제</h1>
            <a href="{{ $createUrl }}" class="text-sm text-neutral-400 hover:text-neutral-600">← 발주로 돌아가기</a>
        </div>

        <div class="px-6 py-4 bg-neutral-50 border-b border-neutral-100">
            <div class="flex items-center justify-between">
                <span class="text-neutral-500 text-sm">결제 금액</span>
                <span class="text-2xl font-black text-neutral-900">{{ number_format($amount) }}<span class="text-base font-bold">원</span></span>
            </div>
            <p class="mt-1 text-[12px] text-neutral-400">결제가 완료되어야 발주가 본사에 접수됩니다.</p>
        </div>

        <div class="p-5">
            <div id="payment-method"></div>
            <div id="agreement" class="mt-2"></div>
            <p id="pay-error" class="hidden mt-3 text-sm text-rose-600 font-semibold"></p>
            <button id="payment-button"
                    class="mt-5 w-full rounded-xl text-white font-bold px-4 py-3.5 text-base transition hover:brightness-95 disabled:opacity-50"
                    style="background:#F2784B">
                {{ number_format($amount) }}원 결제하기
            </button>
            <p class="text-center text-[11px] text-neutral-400 mt-3">
                토스페이먼츠 안전결제@if ($testMode) · <b>테스트 모드</b>@endif
            </p>
        </div>
    </div>

    <script>
        (function () {
            const clientKey = @json($clientKey);
            const customerKey = @json($customerKey);
            const amount = { currency: 'KRW', value: {{ $amount }} };
            const btn = document.getElementById('payment-button');
            const errEl = document.getElementById('pay-error');
            const showErr = (m) => { errEl.textContent = m; errEl.classList.remove('hidden'); };

            if (!clientKey) { showErr('결제 설정이 없습니다. 본사에 문의해 주세요.'); btn.disabled = true; return; }

            const tossPayments = TossPayments(clientKey);
            const widgets = tossPayments.widgets({ customerKey });
            let ready = false;

            (async () => {
                try {
                    await widgets.setAmount(amount);
                    await Promise.all([
                        widgets.renderPaymentMethods({ selector: '#payment-method', variantKey: 'DEFAULT' }),
                        widgets.renderAgreement({ selector: '#agreement', variantKey: 'AGREEMENT' }),
                    ]);
                    ready = true;
                } catch (e) {
                    showErr('결제 수단을 불러오지 못했습니다. 새로고침 후 다시 시도해 주세요.');
                }
            })();

            btn.addEventListener('click', async () => {
                if (!ready) return;
                errEl.classList.add('hidden');
                btn.disabled = true;
                try {
                    await widgets.requestPayment({
                        orderId: @json($tossOrderId),
                        orderName: @json($orderName),
                        successUrl: @json($successUrl),
                        failUrl: @json($failUrl),
                        customerName: @json($customerName),
                    });
                } catch (e) {
                    btn.disabled = false;
                    if (e && e.code !== 'USER_CANCEL') showErr(e.message || '결제를 시작하지 못했습니다.');
                }
            });
        })();
    </script>
</body>
</html>

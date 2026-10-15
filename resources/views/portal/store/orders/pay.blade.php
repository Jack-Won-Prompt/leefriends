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
<body class="bg-neutral-100 min-h-screen">
    {{-- MDI iframe 안이면 상단 프레임으로 빠져나와 토스 결제창이 정상 동작하도록 --}}
    <script>if (window.top !== window.self) { window.top.location.href = window.location.href; }</script>

    <div class="max-w-xl mx-auto px-4 py-8">
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-neutral-100 flex items-center justify-between">
                <h1 class="text-lg font-black text-neutral-900">🧾 발주 결제</h1>
                <a href="{{ route('portal.store.orders.show', $order) }}" class="text-sm text-neutral-400 hover:text-neutral-600">나중에 결제</a>
            </div>

            <div class="px-6 py-5 bg-neutral-50 border-b border-neutral-100">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">발주번호</span>
                    <span class="font-bold text-neutral-800">{{ $order->order_no }}</span>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-neutral-500 text-sm">결제 금액</span>
                    <span class="text-2xl font-black text-neutral-900">{{ number_format($amount) }}<span class="text-base font-bold">원</span></span>
                </div>
                @if ($vaEnabled)
                    <p class="mt-2 text-[12px] text-neutral-400">카드 결제 또는 가상계좌 입금을 선택할 수 있습니다. 가상계좌는 입금 확인 후 발주가 확정됩니다.</p>
                @endif
            </div>

            <div class="p-5">
                <div id="payment-method"></div>
                <div id="agreement" class="mt-2"></div>
                <p id="pay-error" class="hidden mt-3 text-sm text-rose-600 font-semibold"></p>
                <button id="payment-button"
                        class="mt-5 w-full rounded-xl bg-mango-500 text-white font-bold px-4 py-3.5 text-base transition hover:brightness-95 disabled:opacity-50"
                        style="background:#F2784B">
                    {{ number_format($amount) }}원 결제하기
                </button>
            </div>
        </div>
        <p class="text-center text-[11px] text-neutral-400 mt-4">
            토스페이먼츠 안전결제 @if (config('services.toss.test_mode'))· <b>테스트 모드</b>@endif
        </p>
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

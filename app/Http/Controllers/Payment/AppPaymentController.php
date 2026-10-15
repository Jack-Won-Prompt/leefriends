<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Store\OrderController as StoreOrderController;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\Payment\TossException;
use App\Services\Payment\TossService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 모바일 앱 — 토스페이먼츠 발주 결제 (웹 Portal\Store\PaymentController 와 같은 흐름).
 *
 * 앱은 토큰 인증이라 웹 세션을 쓸 수 없으므로, 결제 전 장바구니 스냅샷은
 * 캐시(app_toss_checkout:{tossOrderId})에 담는다. 결제창은 서명된 URL(인증 없음)로 연다.
 *
 * 흐름: 앱 POST /api/v1/orders → (토스 매장) 발주 미생성 + checkout_url 반환
 *      → 앱 웹뷰에서 결제위젯 → success → 토스 confirm → 발주 생성·본사 접수
 *      → done 페이지로 이동(앱이 가로채 닫음) → 앱이 결과 API 로 재확인.
 */
class AppPaymentController extends Controller
{
    /** 캐시 키 — 결제 전 장바구니 스냅샷 */
    public static function checkoutKey(string $co): string
    {
        return 'app_toss_checkout:'.$co;
    }

    /** 캐시 키 — 결제 결과(앱이 다시 확인) */
    public static function resultKey(string $co): string
    {
        return 'app_toss_result:'.$co;
    }

    public function __construct(private TossService $toss) {}

    /** 결제창 — 서명된 URL로만 열린다(앱 웹뷰). 발주는 아직 생성되지 않은 상태. */
    public function checkout(Request $request, string $co)
    {
        $checkout = Cache::get(self::checkoutKey($co));
        if (! $checkout) {
            return $this->done($request->merge([
                'status' => 'fail',
                'message' => '결제 시간이 만료되었습니다. 발주를 다시 진행해 주세요.',
            ]));
        }

        return view('portal.store.orders.pay', [
            'amount' => (int) $checkout['amount'],
            'clientKey' => $this->toss->clientKey(),
            'tossOrderId' => $co,
            'orderName' => ($checkout['store_name'] ?? '매장').' 발주',
            'customerKey' => 'store_'.$checkout['store_id'],
            'customerName' => $checkout['store_name'] ?? '매장',
            'successUrl' => route('pay.app.success'),
            'failUrl' => route('pay.app.fail'),
            'testMode' => $this->toss->isTestMode(),
            'vaEnabled' => (bool) config('services.toss.va.enabled'),
            // 결제창의 "돌아가기" — 앱이 가로채 웹뷰를 닫는다
            'createUrl' => route('pay.app.done', ['status' => 'cancel']),
        ]);
    }

    /** 토스 성공 리다이렉트 → 승인 → 발주 생성·접수 (웹 PaymentController@success 와 동일 규칙) */
    public function success(Request $request)
    {
        $data = $request->validate([
            'paymentKey' => ['required', 'string'],
            'orderId' => ['required', 'string'],
            'amount' => ['required', 'integer'],
        ]);

        $co = $data['orderId'];
        $checkout = Cache::get(self::checkoutKey($co));
        if (! $checkout) {
            return $this->redirectDone('fail', '결제 시간이 만료되었거나 이미 처리되었습니다.');
        }

        // 금액 위변조 방지
        if ((int) $data['amount'] !== (int) $checkout['amount']) {
            return $this->redirectDone('fail', '결제 금액이 일치하지 않습니다. 다시 시도해 주세요.');
        }

        $user = User::find($checkout['user_id']);
        if (! $user || (int) $user->store_id !== (int) $checkout['store_id']) {
            return $this->redirectDone('fail', '결제 세션 정보가 일치하지 않습니다.');
        }

        // 1) 토스 결제 승인
        try {
            $result = $this->toss->confirm($data['paymentKey'], $co, (int) $checkout['amount']);
        } catch (TossException $e) {
            return $this->redirectDone('fail', '결제 승인 실패: '.$e->getMessage());
        }

        // 2) 승인됐으니 발주 생성 (웹과 같은 코어 사용)
        $orders = app(StoreOrderController::class);
        $selected = collect($checkout['qty'])->filter(fn ($q) => (int) $q > 0);
        $orderData = ['note' => $checkout['note'] ?? null, 'unit' => $checkout['unit'] ?? []];

        try {
            $order = $orders->createOrderCore($user, $selected, $orderData, $checkout['type']);
        } catch (\App\Exceptions\StockShortageException $e) {
            // 결제는 됐지만 재고 부족 → 자동 환불
            try {
                $this->toss->cancel($data['paymentKey'], '재고 부족으로 자동 취소');
            } catch (TossException $ce) {
                Log::error('토스 자동환불 실패(앱)', ['paymentKey' => $data['paymentKey'], 'msg' => $ce->getMessage()]);
            }
            Cache::forget(self::checkoutKey($co));

            return $this->redirectDone('fail', '결제는 승인되었으나 재고 부족으로 자동 취소(환불)되었습니다: '.$e->summary());
        }

        // 3) 결제 결과 기록 + 발주 상태 갱신
        $tossStatus = $result['status'] ?? '';
        $isDeposited = $tossStatus === 'DONE';
        $isWaitingVa = $tossStatus === 'WAITING_FOR_DEPOSIT';
        $va = $result['virtualAccount'] ?? null;

        DB::transaction(function () use ($order, $data, $checkout, $result, $isDeposited, $isWaitingVa, $tossStatus, $va, $co) {
            OrderPayment::create([
                'order_id' => $order->id,
                'toss_order_id' => $co,
                'payment_key' => $data['paymentKey'],
                'method' => $result['method'] ?? null,
                'amount' => (int) $checkout['amount'],
                'status' => $isDeposited ? 'done' : ($isWaitingVa ? 'waiting_deposit' : Str::lower($tossStatus)),
                'va_bank' => $va['bank'] ?? null,
                'va_account' => $va['accountNumber'] ?? null,
                'va_due_at' => isset($va['dueDate']) ? Carbon::parse($va['dueDate']) : null,
                'approved_at' => isset($result['approvedAt']) ? Carbon::parse($result['approvedAt']) : now(),
                'raw' => $result,
            ]);

            $order->update([
                'payment_status' => $isDeposited ? 'paid' : ($isWaitingVa ? 'waiting_deposit' : 'awaiting'),
                'paid_amount' => $isDeposited ? (int) $checkout['amount'] : null,
                'paid_at' => $isDeposited ? now() : null,
            ]);
        });

        // 4) 본사/공급처 접수 — 결제가 끝난 지금
        $orders->finalizeOrder($order, sms: false);

        $message = $isDeposited
            ? '결제가 완료되어 발주가 접수되었습니다.'
            : ($isWaitingVa
                ? "가상계좌가 발급되었습니다. {$va['bank']} {$va['accountNumber']} 로 입금하시면 결제가 완료됩니다."
                : '결제가 접수되었습니다.');

        // 앱이 다시 확인할 결과 (리다이렉트만 믿지 않는다)
        Cache::put(self::resultKey($co), [
            'status' => $isDeposited ? 'paid' : ($isWaitingVa ? 'waiting_deposit' : 'awaiting'),
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'message' => $message,
        ], now()->addHours(2));
        Cache::forget(self::checkoutKey($co));

        return $this->redirectDone($isDeposited ? 'paid' : 'waiting_deposit', $message, $order->id);
    }

    /** 토스 실패/취소 — 발주는 생성되지 않았다. */
    public function fail(Request $request)
    {
        $co = (string) $request->query('orderId', '');
        $message = (string) $request->query('message', '결제가 취소되었거나 실패했습니다.');
        if ($co !== '') {
            Cache::forget(self::checkoutKey($co));
            Cache::put(self::resultKey($co), ['status' => 'failed', 'message' => $message], now()->addHours(2));
        }

        return $this->redirectDone('fail', $message);
    }

    /**
     * 앱이 가로채는 종료 지점. 앱 웹뷰는 이 경로로 이동하는 순간 닫고 결과 API 로 확인한다.
     * (가로채지 못한 경우에도 사람이 읽을 수 있도록 간단한 안내를 보여준다)
     */
    public function done(Request $request)
    {
        $status = (string) $request->query('status', 'cancel');
        $message = (string) $request->query('message', match ($status) {
            'paid' => '결제가 완료되었습니다. 앱으로 돌아가 주세요.',
            'waiting_deposit' => '가상계좌가 발급되었습니다. 앱으로 돌아가 주세요.',
            'fail' => '결제가 취소되었거나 실패했습니다.',
            default => '결제를 취소했습니다.',
        });

        return response()->view('payment.app_done', ['status' => $status, 'message' => $message]);
    }

    private function redirectDone(string $status, string $message, ?int $orderId = null)
    {
        return redirect()->route('pay.app.done', array_filter([
            'status' => $status,
            'message' => $message,
            'order_id' => $orderId,
        ]));
    }
}

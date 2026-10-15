<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Payment\TossException;
use App\Services\Payment\TossService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 매장 발주 — 토스페이먼츠 결제(결제위젯 v2). 토스 사용 매장만.
 *
 * 흐름: 발주 접수(모달) → 결제위젯 requestPayment → successUrl(success)
 *      → 토스 confirm(승인) → 승인된 뒤에야 발주 생성 + 본사 접수.
 * 즉 "결제 완료 전에는 본사에 발주가 접수되지 않는다".
 * 결제 전 장바구니 스냅샷은 세션(toss_checkout.{tossOrderId})에 보관한다.
 */
class PaymentController extends Controller
{
    public function __construct(private TossService $toss) {}

    /** 결제창(팝업형) — 세션 장바구니 스냅샷(co)으로 토스 결제위젯을 띄운다. 발주는 아직 미생성. */
    public function checkout(Request $request)
    {
        $co = (string) $request->query('co');
        $checkout = $co !== '' ? session('toss_checkout.' . $co) : null;
        $user = Auth::user();

        if (! $checkout || ! $user || (int) $user->id !== (int) $checkout['user_id']) {
            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제 세션이 만료되었습니다. 발주를 다시 진행해 주세요.']);
        }

        $store = $user->store;

        return view('portal.store.orders.pay', [
            'amount' => (int) $checkout['amount'],
            'clientKey' => $this->toss->clientKey(),
            'tossOrderId' => $co,
            'orderName' => ($store->name ?? '매장') . ' 발주',
            'customerKey' => 'store_' . $user->store_id,
            'customerName' => $store->name ?? '매장',
            'successUrl' => route('portal.store.pay.success'),
            'failUrl' => route('portal.store.pay.fail'),
            'testMode' => $this->toss->isTestMode(),
            'vaEnabled' => (bool) config('services.toss.va.enabled'),
            'createUrl' => route('portal.store.orders.create'),
        ]);
    }

    /** 토스 결제 성공 리다이렉트 → 서버 승인(confirm) → 발주 생성·접수 */
    public function success(Request $request)
    {
        $data = $request->validate([
            'paymentKey' => ['required', 'string'],
            'orderId' => ['required', 'string'],
            'amount' => ['required', 'integer'],
        ]);

        $key = 'toss_checkout.' . $data['orderId'];
        $checkout = session($key);
        if (! $checkout) {
            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제 세션이 만료되었거나 이미 처리되었습니다. 발주를 다시 진행해 주세요.']);
        }

        // 본인(해당 매장) 확인
        $user = Auth::user();
        if (! $user || (int) $user->id !== (int) $checkout['user_id'] || (int) $user->store_id !== (int) $checkout['store_id']) {
            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제 세션 정보가 일치하지 않습니다.']);
        }

        // 금액 위변조 방지
        if ((int) $data['amount'] !== (int) $checkout['amount']) {
            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제 금액이 일치하지 않습니다. 다시 시도해 주세요.']);
        }

        // 1) 토스 결제 승인
        try {
            $result = $this->toss->confirm($data['paymentKey'], $data['orderId'], (int) $checkout['amount']);
        } catch (TossException $e) {
            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제 승인 실패: ' . $e->getMessage()]);
        }

        // 2) 승인됐으니 이제 발주 생성 (여기서부터 본사 접수)
        $orders = app(OrderController::class);
        $selected = collect($checkout['qty'])->filter(fn ($q) => (int) $q > 0);
        $orderData = ['note' => $checkout['note'] ?? null, 'unit' => $checkout['unit'] ?? []];

        try {
            $order = $orders->createOrderCore($user, $selected, $orderData, $checkout['type']);
        } catch (\App\Exceptions\StockShortageException $e) {
            // 결제는 됐지만 재고 부족 → 자동 환불
            try {
                $this->toss->cancel($data['paymentKey'], '재고 부족으로 자동 취소');
            } catch (TossException $ce) {
                // 환불 실패는 로깅만 (수동 처리)
                \Log::error('토스 자동환불 실패', ['paymentKey' => $data['paymentKey'], 'msg' => $ce->getMessage()]);
            }
            session()->forget($key);

            return redirect()->route('portal.store.orders.create')
                ->withErrors(['payment' => '결제는 승인되었으나 재고 부족으로 자동 취소(환불)되었습니다: ' . $e->summary()]);
        }

        // 3) 결제 결과 기록 + 발주 상태 갱신
        $tossStatus = $result['status'] ?? '';
        $isDeposited = $tossStatus === 'DONE';
        $isWaitingVa = $tossStatus === 'WAITING_FOR_DEPOSIT';
        $va = $result['virtualAccount'] ?? null;

        DB::transaction(function () use ($order, $data, $checkout, $result, $isDeposited, $isWaitingVa, $tossStatus, $va) {
            OrderPayment::create([
                'order_id' => $order->id,
                'toss_order_id' => $data['orderId'],
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

        // 4) 본사/공급처 접수 (알림 + 발주서) — 결제가 끝난 지금 보낸다
        $orders->finalizeOrder($order, sms: false);

        session()->forget($key);

        $msg = $isDeposited
            ? '결제가 완료되어 발주가 접수되었습니다.'
            : ($isWaitingVa
                ? "가상계좌가 발급되었습니다. {$va['bank']} {$va['accountNumber']} 로 입금하시면 결제가 완료됩니다."
                : '결제가 접수되었습니다.');

        return redirect()->route('portal.store.orders.show', $order)->with('success', $msg);
    }

    /** 토스 결제 실패/취소 — 발주는 생성되지 않았으므로 발주 화면으로 되돌린다. */
    public function fail(Request $request)
    {
        $orderId = $request->query('orderId');
        $message = $request->query('message', '결제가 취소되었거나 실패했습니다.');

        if ($orderId) {
            session()->forget('toss_checkout.' . $orderId);
        }

        return redirect()->route('portal.store.orders.create')->withErrors(['payment' => $message]);
    }
}

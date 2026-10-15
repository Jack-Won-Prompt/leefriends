<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Payment\TossException;
use App\Services\Payment\TossService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 매장 발주 — 토스페이먼츠 결제 (결제위젯 v2). 토스 사용 매장만.
 * 흐름: pay(결제창) → requestPayment → successUrl(success) → 토스 confirm → 발주 결제 확정.
 */
class PaymentController extends Controller
{
    public function __construct(private TossService $toss) {}

    /** 결제 페이지 — 토스 결제위젯 렌더 */
    public function pay(Order $order)
    {
        $this->authorizePayable($order);

        $amount = (int) $order->order_total;
        abort_if($amount <= 0, 400, '결제 금액이 올바르지 않습니다.');

        // 이번 결제 시도용 토스 orderId (발주번호-난수, 6~64자)
        $tossOrderId = $order->order_no . '-' . Str::upper(Str::random(8));
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'toss_order_id' => $tossOrderId,
            'amount' => $amount,
            'status' => 'ready',
        ]);

        $itemCount = $order->items()->count();
        $orderName = $order->order_no . ($itemCount > 1 ? " 외 {$itemCount}건" : '');

        return view('portal.store.orders.pay', [
            'order' => $order,
            'amount' => $amount,
            'clientKey' => $this->toss->clientKey(),
            'tossOrderId' => $tossOrderId,
            'orderName' => $orderName,
            'customerKey' => 'store_' . $order->store_id,
            'customerName' => optional($order->store)->name ?? '매장',
            'successUrl' => route('portal.store.pay.success'),
            'failUrl' => route('portal.store.pay.fail'),
            'vaEnabled' => (bool) config('services.toss.va.enabled'),
        ]);
    }

    /** 토스 결제 성공 리다이렉트 → 서버 승인(confirm) */
    public function success(Request $request)
    {
        $data = $request->validate([
            'paymentKey' => ['required', 'string'],
            'orderId' => ['required', 'string'],
            'amount' => ['required', 'integer'],
        ]);

        $payment = OrderPayment::where('toss_order_id', $data['orderId'])->firstOrFail();
        $order = $payment->order()->with('store')->firstOrFail();
        $this->authorizeOwner($order);

        // 금액 위변조 방지 — 발급 시 금액과 반드시 일치
        if ((int) $data['amount'] !== (int) $payment->amount) {
            return redirect()->route('portal.store.orders.show', $order)
                ->withErrors(['payment' => '결제 금액이 일치하지 않습니다. 다시 시도해 주세요.']);
        }

        try {
            $result = $this->toss->confirm($data['paymentKey'], $data['orderId'], (int) $payment->amount);
        } catch (TossException $e) {
            $payment->update(['status' => 'failed', 'payment_key' => $data['paymentKey'], 'raw' => $e->body]);

            return redirect()->route('portal.store.orders.show', $order)
                ->withErrors(['payment' => '결제 승인 실패: ' . $e->getMessage()]);
        }

        $tossStatus = $result['status'] ?? '';
        $isDeposited = $tossStatus === 'DONE';
        $isWaitingVa = $tossStatus === 'WAITING_FOR_DEPOSIT';
        $va = $result['virtualAccount'] ?? null;

        $payment->update([
            'payment_key' => $data['paymentKey'],
            'method' => $result['method'] ?? null,
            'status' => $isDeposited ? 'done' : ($isWaitingVa ? 'waiting_deposit' : Str::lower($tossStatus)),
            'va_bank' => $va['bank'] ?? null,
            'va_account' => $va['accountNumber'] ?? null,
            'va_due_at' => isset($va['dueDate']) ? \Illuminate\Support\Carbon::parse($va['dueDate']) : null,
            'approved_at' => isset($result['approvedAt']) ? \Illuminate\Support\Carbon::parse($result['approvedAt']) : now(),
            'raw' => $result,
        ]);

        $order->update([
            'payment_status' => $isDeposited ? 'paid' : ($isWaitingVa ? 'waiting_deposit' : 'awaiting'),
            'paid_amount' => $isDeposited ? (int) $payment->amount : $order->paid_amount,
            'paid_at' => $isDeposited ? now() : $order->paid_at,
        ]);

        $msg = $isDeposited
            ? '결제가 완료되어 발주가 확정되었습니다.'
            : ($isWaitingVa
                ? "가상계좌가 발급되었습니다. {$va['bank']} {$va['accountNumber']} 로 입금하시면 발주가 확정됩니다."
                : '결제가 접수되었습니다.');

        return redirect()->route('portal.store.orders.show', $order)->with('success', $msg);
    }

    /** 토스 결제 실패/취소 리다이렉트 */
    public function fail(Request $request)
    {
        $orderId = $request->query('orderId');
        $message = $request->query('message', '결제가 취소되었거나 실패했습니다.');

        $payment = $orderId ? OrderPayment::where('toss_order_id', $orderId)->first() : null;
        if ($payment) {
            $payment->update(['status' => 'failed', 'raw' => $request->query()]);
            $order = $payment->order;
            if ($order) {
                return redirect()->route('portal.store.orders.show', $order)
                    ->withErrors(['payment' => $message]);
            }
        }

        return redirect()->route('portal.store.orders.index')->withErrors(['payment' => $message]);
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->store_id && $order->store_id === Auth::user()->store_id, 403);
    }

    private function authorizePayable(Order $order): void
    {
        $this->authorizeOwner($order);
        abort_unless(optional($order->store)->toss_enabled, 403, '토스 결제가 활성화되지 않은 매장입니다.');
        abort_if(in_array($order->payment_status, ['paid', 'waiting_deposit'], true), 400, '이미 결제가 처리된 발주입니다.');
        abort_if($order->status === 'canceled', 400, '취소된 발주입니다.');
    }
}

<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** 입금요청 SMS 전송 + 주문 상태를 '접수'로 */
    public function paymentRequest(Order $order, \App\Services\Order\PaymentRequestSms $sms)
    {
        try {
            $sms->dispatch($order);
        } catch (\Throwable $e) {
            return back()->with('error', '입금요청 SMS 전송 실패: '.$e->getMessage());
        }

        if ($order->status !== 'pending') {
            $order->update(['status' => 'pending']);
        }

        return back()->with('success', '입금요청 SMS를 전송하고 주문을 접수 상태로 변경했습니다. ('.($order->store->name ?? '매장').')');
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $store = $request->query('store', 'all');
        $tax = $request->query('tax', 'all');
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $query = Order::with('store')->withCount('items')->latest();
        if (array_key_exists($status, Order::STATUSES)) {
            $query->where('status', $status);
        }
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        if ($tax === 'issued') {
            $query->whereNotNull('tax_invoice_id');
        } elseif ($tax === 'pending') {
            $query->whereNull('tax_invoice_id');
        }
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        $orders = $query->paginate(15)->withQueryString();

        return view('portal.hq.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => Order::STATUSES,
            'stores' => Store::orderBy('name')->get(),
            'store' => $store,
            'tax' => $tax,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.supplier', 'store', 'user']);

        return view('portal.hq.orders.show', compact('order'));
    }

    /** 발주 거래명세서 PDF 다운로드/미리보기 */
    public function statementPdf(Request $request, Order $order)
    {
        $order->load(['items', 'store']);
        $statementDate = $this->statementDate($request->query('date'), $order);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.order-statement-pdf', compact('order', 'statementDate'))
            ->setPaper('a4')->stream('거래명세서_'.$order->order_no.'.pdf');
    }

    /** 발주 거래명세서 PDF를 매장 이메일로 전송 + 전송상태 기록 */
    public function statementEmail(Request $request, Order $order)
    {
        $order->load(['items.supplyProduct', 'store']);
        $to = $order->store?->email;
        if (! $to) {
            return back()->withErrors(['statement' => '매장 이메일이 없습니다. 매장 관리에서 이메일을 먼저 등록하세요.']);
        }

        $statementDate = $this->statementDate($request->input('statement_date'), $order);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.order-statement-pdf', compact('order', 'statementDate'))->setPaper('a4');

        \Illuminate\Support\Facades\Mail::to($to)->send(
            new \App\Mail\OrderStatementMail($order, $pdf->output(), '거래명세서_'.$order->order_no.'.pdf')
        );

        // 거래명세서 이력(Statement) 기록 — 발주 상세 전송도 «거래명세서 이력» 화면과 매장 수취 화면에 노출
        $lines = $order->items->map(fn ($it) => [
            'code' => $it->supplyProduct?->code ?? '',
            'name' => $it->product_name,
            'unit' => $it->unit,
            'qty' => (int) $it->qty,
            'price' => (int) $it->store_unit_price,
            'amount' => (int) $it->store_line_amount,
        ])->values()->all();
        if ((int) $order->shipping_box_count > 0) {
            $lines[] = [
                'code' => '', 'name' => '택배비', 'unit' => '박스',
                'qty' => (int) $order->shipping_box_count,
                'price' => (int) $order->shipping_unit_price,
                'amount' => (int) $order->shipping_box_count * (int) $order->shipping_unit_price,
            ];
        }

        $statement = \App\Models\Statement::updateOrCreate(
            ['order_id' => $order->id],
            [
                'store_id' => $order->store_id,
                'store_name' => $order->store->name,
                'email' => $to,
                'statement_date' => $statementDate->toDateString(),
                'item_count' => $order->items->count(),
                'total' => (int) $order->order_total,
                'items' => $lines,
                'sent_by' => auth()->id(),
                'sent_at' => now(),
                // 재전송 시 매장 수취 상태 초기화 (새 명세서로 재안내)
                'viewed_at' => null,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]
        );
        if (! $statement->wasRecentlyCreated) {
            $statement->increment('resend_count');
        }

        // 매장 알림 (웹 토스트 + 앱 FCM)
        app(\App\Services\Notification\NotificationService::class)->notifyStore(
            $order->store_id, 'statement', '🧾 거래명세서 도착',
            "{$statementDate->format('Y.m.d')} 거래명세서가 도착했습니다. (".number_format($order->order_total).'원)',
            ['statement_id' => $statement->id]
        );

        $order->update(['statement_emailed_at' => now(), 'statement_email_count' => $order->statement_email_count + 1]);

        return back()->with('success', "거래명세서({$statementDate->format('Y.m.d')})를 매장({$to})으로 전송했습니다.");
    }

    /** 거래명세서 발행일자 파싱 (기본값: 발주일) */
    private function statementDate($input, Order $order): \Illuminate\Support\Carbon
    {
        try {
            return $input ? \Illuminate\Support\Carbon::parse($input)->startOfDay() : $order->created_at;
        } catch (\Throwable $e) {
            return $order->created_at;
        }
    }

    /** 발주 품목 수정 — 공급가·출고가·수량 (매장/판매주문/정산 반영) */
    public function editItem(Request $request, Order $order, OrderItem $item, \App\Services\Notification\NotificationService $notifications)
    {
        abort_unless($item->order_id === $order->id, 403);

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99999'],
            'store_unit_price' => ['required', 'integer', 'min:0'],
            'supply_unit_price' => ['nullable', 'integer', 'min:0'],
        ], [
            'qty.required' => '수량을 입력해 주세요.',
            'store_unit_price.required' => '출고가를 입력해 주세요.',
        ]);

        $qty = (int) $data['qty'];
        $store = (int) $data['store_unit_price'];
        $supply = $item->supply_type === 'supplier' ? (int) ($data['supply_unit_price'] ?? 0) : 0;

        $item->update([
            'qty' => $qty,
            'store_unit_price' => $store,
            'supply_unit_price' => $supply,
            'store_line_amount' => $store * $qty,
            'supply_line_amount' => $supply * $qty,
            'price_pending' => false,
        ]);

        $order->recomputeAmounts(); // 발주 + 판매주문 합계 동기화

        try {
            $notifications->notifyStore(
                (int) $order->store_id,
                'order_item_updated',
                '✏️ 발주 품목 수정',
                "{$item->product_name} 품목(출고가·수량)이 본사에서 수정되었습니다.",
                ['order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "«{$item->product_name}» 품목을 수정했습니다. (매장·정산에 반영)");
    }

    /** 택배비(박스·단가) 추가/수정 → 발주 합계에 반영 */
    public function updateShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'shipping_box_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'shipping_unit_price' => ['nullable', 'integer', 'min:0', 'max:9999999'],
        ]);

        $box = (int) ($data['shipping_box_count'] ?? 0);
        $unit = (int) ($data['shipping_unit_price'] ?? 0);

        $order->update([
            'shipping_box_count' => $box ?: null,
            'shipping_unit_price' => $unit ?: null,
            'shipping_fee' => $box * $unit,
        ]);

        return back()->with('success', '택배비를 저장했습니다. (발주 합계에 반영)');
    }

    /** 본사 직공급 품목의 배송상태 처리 */
    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id && $item->supply_type === 'hq', 403);

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,shipping,delivered'],
        ]);

        $item->fulfillment_status = $data['fulfillment_status'];
        $item->shipped_at = in_array($data['fulfillment_status'], ['shipping', 'delivered'], true)
            ? ($item->shipped_at ?? now())
            : null;
        $item->save();

        $order->syncStatus();

        return back()->with('success', '본사 공급 품목의 배송상태가 변경되었습니다.');
    }

    /** 싯가 품목 단가 확정 */
    public function setItemPrice(Request $request, Order $order, OrderItem $item, \App\Services\Notification\NotificationService $notifications)
    {
        abort_unless($item->order_id === $order->id, 403);
        abort_unless($item->price_pending, 422, '단가 확정 대기 중인 품목이 아닙니다.');

        $data = $request->validate([
            'store_unit_price' => ['required', 'integer', 'min:1', 'max:100000000'],
        ], ['store_unit_price.required' => '단가를 입력해 주세요.']);

        $price = (int) $data['store_unit_price'];
        $item->update([
            'store_unit_price' => $price,
            'store_line_amount' => $price * $item->qty,
            'price_pending' => false,
        ]);

        $order->recomputeAmounts();

        try {
            $notifications->notifyStore(
                (int) $order->store_id,
                'market_price_set',
                '🥭 싯가 가격 확정',
                "{$item->product_name} 단가가 ".number_format($price).'원으로 확정되었습니다.',
                ['order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "«{$item->product_name}» 단가를 ".number_format($price).'원으로 확정했습니다.');
    }
}

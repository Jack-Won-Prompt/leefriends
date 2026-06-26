<?php

namespace App\Services\Order;

use App\Mail\SupplierOrderMail;
use App\Models\Order;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

/**
 * 매장 발주 시 공급처(직배송 품목)에 발주서 PDF 메일을 발송한다. (수량 기준, 금액 미표기)
 * 메일 실패가 발주를 막지 않도록 공급처별로 예외를 격리한다.
 */
class SupplierOrderMailer
{
    public function sendForOrder(Order $order): void
    {
        $order->loadMissing(['items', 'store']);

        $bySupplier = $order->items
            ->where('supply_type', 'supplier')
            ->whereNotNull('supplier_id')
            ->groupBy('supplier_id');

        foreach ($bySupplier as $supplierId => $items) {
            try {
                $supplier = Supplier::find($supplierId);
                if (! $supplier || ! $supplier->email) {
                    continue; // 수신 이메일 없으면 건너뜀
                }

                $pdf = Pdf::loadView('portal.print.supplier-order', [
                    'order' => $order,
                    'supplier' => $supplier,
                    'items' => $items->values(),
                ])->setPaper('a4');

                $fileName = '발주서_'.$order->order_no.'_'.$supplier->name.'.pdf';

                Mail::to($supplier->email)->send(new SupplierOrderMail(
                    $order,
                    $supplier,
                    $items->count(),
                    (int) $items->sum('qty'),
                    $pdf->output(),
                    $fileName,
                ));
            } catch (\Throwable $e) {
                report($e); // 개별 실패는 무시(발주는 정상 처리)
            }
        }
    }
}

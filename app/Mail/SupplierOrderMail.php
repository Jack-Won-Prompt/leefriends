<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 매장 발주 시 공급처로 전송하는 발주서 PDF 첨부 메일 (수량 기준, 금액 미표기).
 */
class SupplierOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Supplier $supplier,
        public int $itemCount,
        public int $totalQty,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        return $this->subject('[LEEFRIENDS] 발주서 · '.$this->order->order_no)
            ->view('emails.supplier-order')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}

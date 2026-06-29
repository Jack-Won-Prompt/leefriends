<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 발주 거래명세서 PDF 첨부 메일 (본사 → 매장).
 */
class OrderStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        return $this->subject('[LEEFRIENDS] 거래명세서 · '.$this->order->order_no)
            ->view('emails.order-statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}

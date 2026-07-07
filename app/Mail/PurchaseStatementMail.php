<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 구매(매입) 거래명세서 PDF 첨부 메일 (본사 → 공급처).
 */
class PurchaseStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $po,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        return $this->subject('[망고정] 구매 거래명세서 · '.$this->po->po_no)
            ->view('emails.purchase-statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}

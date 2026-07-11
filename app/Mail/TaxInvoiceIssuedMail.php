<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * 세금계산서 발행 확인 메일 (본사 담당자 수신).
 * 팝빌은 공급받는자(매장)에게만 자동 발송하므로, 발행 본사에 확인용으로 별도 발송.
 */
class TaxInvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<array> $docs 발행 문서 요약 [invoice_no, note, supply, vat, total, print_url] */
    public function __construct(
        public string $storeName,
        public Collection $docs,
        public int $grandTotal,
    ) {
    }

    public function build()
    {
        return $this->subject('[LEEFRIENDS] 세금계산서 발행 확인 · '.$this->storeName)
            ->view('emails.tax-invoice-issued');
    }
}

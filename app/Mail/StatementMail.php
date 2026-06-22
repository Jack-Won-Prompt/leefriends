<?php

namespace App\Mail;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 거래명세서 PDF 첨부 메일 (본사 → 매장).
 */
class StatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Store $store,
        public array $lines,
        public int $total,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        return $this->subject('[LEEFRIENDS] 거래명세서 · '.$this->store->name)
            ->view('emails.statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}

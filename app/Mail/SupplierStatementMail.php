<?php

namespace App\Mail;

use App\Models\SupplierStatement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 공급처 거래명세서 PDF 첨부 메일 (공급처 → 본사).
 */
class SupplierStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupplierStatement $statement,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        return $this->subject('[LEEFRIENDS] 거래명세서 · '.$this->statement->supplier_name)
            ->view('emails.supplier-statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}

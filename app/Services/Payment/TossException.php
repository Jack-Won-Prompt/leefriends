<?php

namespace App\Services\Payment;

class TossException extends \RuntimeException
{
    public function __construct(string $message, public ?string $tossCode = null, public array $body = [])
    {
        parent::__construct($message);
    }
}

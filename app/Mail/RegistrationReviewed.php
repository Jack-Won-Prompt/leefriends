<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 회원가입 승인/반려 결과 안내 메일.
 */
class RegistrationReviewed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public bool $approved,
        public ?string $reason,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->approved ? '승인' : '반려';

        return new Envelope(
            subject: "[망고정] 회원가입 {$status} 안내",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-reviewed',
            with: [
                'loginUrl' => route('portal.login'),
            ],
        );
    }
}

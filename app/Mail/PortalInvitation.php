<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $orgName    초대 대상 조직명 (매장명/공급처명)
     * @param  string  $roleLabel  역할 (매장/공급처)
     * @param  string  $inviteUrl  비밀번호 설정 링크
     */
    public function __construct(
        public string $orgName,
        public string $roleLabel,
        public string $inviteUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[망고정] {$this->roleLabel} 포털 초대 안내",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.portal-invitation',
        );
    }
}

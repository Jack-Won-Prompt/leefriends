<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** 발주포털 비밀번호 재설정 안내 메일 (한국어) */
class PortalPasswordReset extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('portal.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expire = config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 60);

        return (new MailMessage)
            ->subject('[망고정] 발주포털 비밀번호 재설정 안내')
            ->greeting('비밀번호 재설정')
            ->line('발주포털 비밀번호 재설정 요청을 받았습니다. 아래 버튼을 눌러 새 비밀번호를 설정해 주세요.')
            ->action('비밀번호 재설정하기', $url)
            ->line("이 링크는 {$expire}분 후 만료됩니다.")
            ->line('본인이 요청하지 않았다면 이 메일을 무시하셔도 됩니다.')
            ->salutation('— 망고정 발주포털');
    }
}

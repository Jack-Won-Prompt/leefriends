<?php

namespace App\Services\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Fcm\FcmService;
use Illuminate\Http\UploadedFile;

/**
 * 채팅 메시지 전송 — 저장 + 대화방 메타 갱신 + 브로드캐스트 + 수신자 FCM.
 * 웹/모바일 양쪽에서 공유.
 */
class ChatService
{
    public function __construct(private FcmService $fcm)
    {
    }

    public function send(Conversation $conversation, User $sender, ?string $body, ?UploadedFile $file = null): Message
    {
        $attrs = [
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'sender_role' => $sender->role,
            'sender_name' => $sender->name,
            'body' => $body,
        ];

        if ($file) {
            $attrs['attachment_path'] = $file->store('chat/'.$conversation->id, 'public');
            $attrs['attachment_name'] = $file->getClientOriginalName();
            $attrs['attachment_mime'] = $file->getMimeType();
            $attrs['attachment_size'] = $file->getSize();
        }

        $message = Message::create($attrs);

        $preview = $message->body ?: '📎 '.$message->attachment_name;
        $conversation->forceFill([
            'last_message' => mb_strimwidth($preview, 0, 60, '…'),
            'last_message_at' => $message->created_at,
        ]);
        if ($sender->role === 'hq') {
            $conversation->party_unread = $conversation->party_unread + 1;
        } else {
            $conversation->hq_unread = $conversation->hq_unread + 1;
        }
        $conversation->save();

        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            report($e);
        }

        $this->notifyRecipients($conversation, $sender, $preview);
        $this->pushFcm($conversation, $sender, $preview);

        return $message;
    }

    /** 수신자에게 웹 토스트 알림 (벨 카운트는 증가시키지 않음 — 채팅 자체 미읽음으로 관리) */
    private function notifyRecipients(Conversation $conversation, User $sender, string $preview): void
    {
        $recipients = $sender->role === 'hq'
            ? $this->partyUsers($conversation)
            : User::where('role', 'hq')->get();

        foreach ($recipients as $u) {
            try {
                broadcast(new \App\Events\PortalToast(
                    $u->id,
                    '💬 '.$sender->name,
                    $preview,
                    'chat',
                    ['conversation_id' => $conversation->id],
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** 수신자에게 채팅 FCM (인앱 알림 센터에는 적재하지 않음 — 채팅 자체 미읽음으로 관리) */
    private function pushFcm(Conversation $conversation, User $sender, string $preview): void
    {
        $recipients = $sender->role === 'hq'
            ? $this->partyUsers($conversation)
            : User::where('role', 'hq')->get();

        $tokens = [];
        foreach ($recipients as $u) {
            $tokens = array_merge($tokens, $u->deviceTokens()->pluck('token')->all());
        }
        if (empty($tokens)) {
            return;
        }

        $this->fcm->sendToTokens(
            $tokens,
            '💬 '.$sender->name,
            $preview,
            [
                'type' => 'chat',
                'conversation_id' => (string) $conversation->id,
            ],
        );
    }

    private function partyUsers(Conversation $conversation)
    {
        if ($conversation->party_type === 'store') {
            return User::where('role', 'store')->where('store_id', $conversation->party_id)->get();
        }

        return User::where('role', 'supplier')->where('supplier_id', $conversation->party_id)->get();
    }
}

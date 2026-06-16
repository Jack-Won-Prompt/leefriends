<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'user_id', 'sender_role', 'sender_name', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 브로드캐스트/프런트 표시용 페이로드 */
    public function toBroadcast(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'sender_role' => $this->sender_role,
            'sender_name' => $this->sender_name,
            'body' => $this->body,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'time' => optional($this->created_at)->format('H:i'),
        ];
    }
}

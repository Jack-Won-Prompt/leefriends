<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'user_id', 'sender_role', 'sender_name', 'body', 'read_at',
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'attachment_size' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->attachment_mime && Str::startsWith($this->attachment_mime, 'image/');
    }

    public function getAttachmentSizeLabelAttribute(): ?string
    {
        if (! $this->attachment_size) {
            return null;
        }
        $kb = $this->attachment_size / 1024;

        return $kb >= 1024 ? round($kb / 1024, 1).' MB' : round($kb).' KB';
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
            'attachment_url' => $this->attachment_url,
            'attachment_name' => $this->attachment_name,
            'attachment_is_image' => $this->is_image,
            'attachment_size_label' => $this->attachment_size_label,
        ];
    }
}

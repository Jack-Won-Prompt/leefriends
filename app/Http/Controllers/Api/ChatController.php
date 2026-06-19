<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 채팅 — 본사 ↔ 매장 / 본사 ↔ 공급처.
 *  - 매장/공급처: 본사와의 단일 대화방
 *  - 본사: 매장·공급처 전체 목록 + 각 대화방
 */
class ChatController extends Controller
{
    /**
     * GET /api/v1/chat/conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'hq') {
            $conv = Conversation::forUser($user);
            if (! $conv) {
                return response()->json(['data' => [], 'meta' => ['mode' => 'single']]);
            }

            return response()->json([
                'data' => [[
                    'id' => $conv->id,
                    'party_type' => $conv->party_type,
                    'label' => '본사',
                    'name' => '본사',
                    'last_message' => $conv->last_message,
                    'last_at' => $conv->last_message_at?->format('Y-m-d H:i'),
                    'unread' => (int) $conv->party_unread,
                ]],
                'meta' => ['mode' => 'single'],
            ]);
        }

        // 본사: 매장 + 공급처 목록 (대화방 메타 병합)
        $convs = Conversation::all()->keyBy(fn ($c) => $c->party_type.':'.$c->party_id);
        $rows = collect();
        foreach (Store::where('is_active', true)->orderBy('name')->get() as $s) {
            $rows->push($this->hqRow('store', $s->id, $s->name, '매장', $convs->get('store:'.$s->id)));
        }
        foreach (Supplier::where('is_active', true)->orderBy('name')->get() as $s) {
            $rows->push($this->hqRow('supplier', $s->id, $s->name, '공급처', $convs->get('supplier:'.$s->id)));
        }
        $rows = $rows->sortByDesc(fn ($r) => $r['last_ts'])->values()
            ->map(function ($r) {
                unset($r['last_ts']);

                return $r;
            });

        return response()->json(['data' => $rows, 'meta' => ['mode' => 'hq']]);
    }

    /**
     * GET /api/v1/chat/open?type=store|supplier&id=123  (본사용 — 대화방 생성/조회)
     */
    public function open(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'hq', 403);
        $data = $request->validate([
            'type' => ['required', 'in:store,supplier'],
            'id' => ['required', 'integer'],
        ]);
        $conv = Conversation::findOrCreateFor($data['type'], (int) $data['id']);

        return response()->json(['data' => ['id' => $conv->id]]);
    }

    /**
     * GET /api/v1/chat/conversations/{conversation}/messages?after=
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->accessibleBy($user), 403);

        $messages = $conversation->messages()
            ->when($request->query('after'), fn ($q, $after) => $q->where('id', '>', (int) $after))
            ->orderBy('id')->limit(200)->get()
            ->map(fn ($m) => $m->toBroadcast());

        $this->markRead($conversation, $user);

        return response()->json([
            'data' => $messages,
            'meta' => ['me' => $user->id],
        ]);
    }

    /**
     * POST /api/v1/chat/conversations/{conversation}/messages
     * body: { body }
     */
    public function send(Request $request, Conversation $conversation, ChatService $chat): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->accessibleBy($user), 403);

        $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip'],
        ]);

        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            return response()->json(['message' => '메시지 또는 파일을 입력하세요.'], 422);
        }

        $message = $chat->send(
            $conversation,
            $user,
            $request->input('body'),
            $request->file('attachment'),
        );

        return response()->json(['data' => $message->toBroadcast()], 201);
    }

    /**
     * GET /api/v1/chat/unread  — 채팅 미읽음 합계 (배지용)
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'hq') {
            $total = (int) Conversation::sum('hq_unread');
        } else {
            $conv = Conversation::forUser($user);
            $total = $conv ? (int) $conv->party_unread : 0;
        }

        return response()->json(['unread' => $total]);
    }

    private function hqRow(string $type, int $id, string $name, string $label, ?Conversation $conv): array
    {
        return [
            'id' => $conv?->id,
            'party_type' => $type,
            'party_id' => $id,
            'name' => $name,
            'label' => $label,
            'last_message' => $conv?->last_message,
            'last_at' => $conv?->last_message_at?->format('Y-m-d H:i'),
            'unread' => (int) ($conv?->hq_unread ?? 0),
            'last_ts' => $conv?->last_message_at?->timestamp ?? 0,
        ];
    }

    private function markRead(Conversation $conversation, $user): void
    {
        if ($user->role === 'hq') {
            $conversation->update(['hq_unread' => 0]);
        } else {
            $conversation->update(['party_unread' => 0]);
        }
    }
}

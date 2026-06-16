<?php

namespace App\Http\Controllers\Portal;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 본사 ↔ 매장 / 본사 ↔ 공급처 실시간 채팅.
 */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 매장/공급처: 본인-본사 대화방 단일 스레드
        if ($user->role !== 'hq') {
            $conversation = Conversation::forUser($user);
            abort_unless($conversation, 403, '채팅을 사용할 수 없는 계정입니다.');
            $this->markRead($conversation, $user);

            return view('portal.chat.index', [
                'mode' => 'single',
                'conversation' => $conversation,
                'messages' => $conversation->messages()->orderBy('id')->get(),
                'conversations' => collect(),
                'partnerName' => '본사',
            ]);
        }

        // 본사: 매장·공급처 전체 목록 + 선택된 대화방
        $conversations = $this->hqConversationList();

        $active = null;
        if ($open = $request->query('open')) {
            [$type, $id] = array_pad(explode(':', $open), 2, null);
            if (in_array($type, ['store', 'supplier'], true) && $id) {
                $active = Conversation::findOrCreateFor($type, (int) $id);
            }
        } elseif ($c = $request->query('c')) {
            $active = Conversation::find($c);
        }

        if ($active) {
            $this->markRead($active, $user);
            $active->loadMissing([]);
        }

        return view('portal.chat.index', [
            'mode' => 'hq',
            'conversations' => $conversations,
            'conversation' => $active,
            'messages' => $active ? $active->messages()->orderBy('id')->get() : collect(),
            'partnerName' => $active ? $active->party_name : null,
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        abort_unless($conversation->accessibleBy($user), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'sender_role' => $user->role,
            'sender_name' => $user->name,
            'body' => $data['body'],
        ]);

        // 대화방 메타 갱신 + 상대측 미읽음 증가
        $conversation->forceFill([
            'last_message' => mb_strimwidth($message->body, 0, 60, '…'),
            'last_message_at' => $message->created_at,
        ]);
        if ($user->role === 'hq') {
            $conversation->party_unread = $conversation->party_unread + 1;
        } else {
            $conversation->hq_unread = $conversation->hq_unread + 1;
        }
        $conversation->save();

        broadcast(new MessageSent($message));

        return response()->json(['message' => $message->toBroadcast()]);
    }

    /** 폴백/재동기화용 메시지 조회 (afterId 이후) */
    public function poll(Request $request, Conversation $conversation)
    {
        $user = Auth::user();
        abort_unless($conversation->accessibleBy($user), 403);

        $messages = $conversation->messages()
            ->when($request->query('after'), fn ($q, $after) => $q->where('id', '>', (int) $after))
            ->orderBy('id')->limit(100)->get()
            ->map(fn ($m) => $m->toBroadcast());

        $this->markRead($conversation, $user);

        return response()->json(['messages' => $messages]);
    }

    /** 본사용 대화 목록: 활성 매장 + 공급처 (대화방 메타 병합) */
    private function hqConversationList()
    {
        $convs = Conversation::all()->keyBy(fn ($c) => $c->party_type.':'.$c->party_id);

        $rows = collect();
        foreach (Store::where('is_active', true)->orderBy('name')->get() as $s) {
            $conv = $convs->get('store:'.$s->id);
            $rows->push($this->row('store', $s->id, $s->name, $conv));
        }
        foreach (Supplier::where('is_active', true)->orderBy('name')->get() as $s) {
            $conv = $convs->get('supplier:'.$s->id);
            $rows->push($this->row('supplier', $s->id, $s->name, $conv));
        }

        // 최근 메시지 순 → 미사용 대화방은 이름순
        return $rows->sortByDesc(fn ($r) => $r['last_at'] ? $r['last_at']->timestamp : 0)->values();
    }

    private function row(string $type, int $id, string $name, ?Conversation $conv): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'label' => $type === 'supplier' ? '공급처' : '매장',
            'conversation_id' => $conv?->id,
            'last_message' => $conv?->last_message,
            'last_at' => $conv?->last_message_at,
            'unread' => (int) ($conv?->hq_unread ?? 0),
            'open_param' => $type.':'.$id,
        ];
    }

    /** 해당 사용자 측 미읽음 0으로 */
    private function markRead(Conversation $conversation, $user): void
    {
        if ($user->role === 'hq' && $conversation->hq_unread > 0) {
            $conversation->update(['hq_unread' => 0]);
        } elseif ($user->role !== 'hq' && $conversation->party_unread > 0) {
            $conversation->update(['party_unread' => 0]);
        }
    }
}

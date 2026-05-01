<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = ChatConversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'participants:id,name,email',
                // Pas de select() partiel ici : latestOfMany() construit un INNER JOIN
                // sur subquery qui rend chat_conversation_id ambigu côté MySQL.
                'lastMessage',
            ])
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        $unread = $user->unreadNotifications()->count();

        return view('messages.index', compact('conversations', 'unread'));
    }

    public function show(Request $request, ChatConversation $conversation)
    {
        $this->authorizeMember($conversation, $request->user());

        $conversation->participants()
            ->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'conversation' => $conversation->only(['id', 'type', 'title', 'context']),
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender?->name ?? 'Système',
                'type' => $m->type,
                'body' => $m->body,
                'payload' => $m->payload,
                'created_at' => $m->created_at->toIso8601String(),
                'mine' => $m->sender_id === $request->user()->id,
            ])->all(),
        ]);
    }

    public function send(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeMember($conversation, $request->user());

        $data = $request->validate([
            'body' => 'required|string|max:4000',
        ]);

        $message = DB::transaction(function () use ($conversation, $request, $data) {
            $msg = ChatMessage::create([
                'chat_conversation_id' => $conversation->id,
                'sender_id' => $request->user()->id,
                'type' => 'text',
                'body' => $data['body'],
            ]);
            $conversation->update(['last_message_at' => now()]);
            return $msg;
        });

        return response()->json([
            'id' => $message->id,
            'body' => $message->body,
            'sender_name' => $request->user()->name,
            'mine' => true,
            'created_at' => $message->created_at->toIso8601String(),
        ], 201);
    }

    public function startDm(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        if ($data['user_id'] === $request->user()->id) {
            return response()->json(['error' => 'Cannot DM yourself'], 422);
        }

        $existing = ChatConversation::where('type', 'dm')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $request->user()->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $data['user_id']))
            ->first();

        if ($existing) {
            return response()->json(['conversation_id' => $existing->id]);
        }

        $convo = DB::transaction(function () use ($request, $data) {
            $c = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
            $c->participants()->attach([$request->user()->id, $data['user_id']]);
            return $c;
        });

        return response()->json(['conversation_id' => $convo->id], 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifs = $request->user()->unreadNotifications()->latest()->limit(20)->get();
        return response()->json([
            'unread_count' => $notifs->count(),
            'items' => $notifs->map(fn ($n) => [
                'id' => $n->id,
                'data' => $n->data,
                'created_at' => $n->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $request->user()->unreadNotifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $q = $request->validate(['q' => 'required|string|min:2|max:100'])['q'];
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('is_active', true)
            ->where(fn ($qb) => $qb->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->limit(10)
            ->get(['id', 'name', 'email']);
        return response()->json(['users' => $users]);
    }

    private function authorizeMember(ChatConversation $conversation, User $user): void
    {
        $isMember = $conversation->participants()->where('user_id', $user->id)->exists();
        abort_unless($isMember, 403, 'Vous n\'êtes pas membre de cette conversation.');
    }
}

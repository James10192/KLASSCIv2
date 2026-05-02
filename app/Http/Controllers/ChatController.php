<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\ChatActionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(private readonly ChatActionResolver $resolver = new ChatActionResolver())
    {
    }

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

        $viewer = $request->user();
        $maps = $this->resolver->preload($messages);

        return response()->json([
            'conversation' => $conversation->only(['id', 'type', 'title', 'context']),
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender?->name ?? 'Système',
                'type' => $m->type,
                'body' => $m->body,
                'payload' => $m->payload,
                'cta' => $m->type === 'action_card' ? $this->resolver->resolveCta($m, $viewer, $maps) : null,
                'created_at' => $m->created_at->toIso8601String(),
                'mine' => $m->sender_id === $viewer->id,
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
        $convo = $this->findOrCreateDm($request->user(), (int) $data['user_id']);
        return response()->json(['conversation_id' => $convo->id], $convo->wasRecentlyCreated ? 201 : 200);
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

    /**
     * Partager une inscription dans une conversation (existante ou nouveau DM).
     */
    public function shareInscription(Request $request, ESBTPInscription $inscription): JsonResponse
    {
        abort_unless($request->user()->can('inscriptions.view'), 403);

        $data = $request->validate([
            'recipient_id' => 'required_without:conversation_id|exists:users,id',
            'conversation_id' => 'required_without:recipient_id|exists:chat_conversations,id',
            'note' => 'nullable|string|max:500',
        ]);

        $payload = $this->resolver->snapshotInscription($inscription);

        return $this->dispatchActionCard($request, $data, $payload);
    }

    /**
     * Partager un paiement dans une conversation (existante ou nouveau DM).
     */
    public function sharePaiement(Request $request, ESBTPPaiement $paiement): JsonResponse
    {
        abort_unless($request->user()->can('paiements.view'), 403);

        $data = $request->validate([
            'recipient_id' => 'required_without:conversation_id|exists:users,id',
            'conversation_id' => 'required_without:recipient_id|exists:chat_conversations,id',
            'note' => 'nullable|string|max:500',
        ]);

        $payload = $this->resolver->snapshotPaiement($paiement);

        return $this->dispatchActionCard($request, $data, $payload);
    }

    /**
     * Picker : recherche d'inscriptions à partager (autocomplete par étudiant/matricule).
     */
    public function pickerInscriptions(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('inscriptions.view'), 403);

        $q = trim($request->validate(['q' => 'nullable|string|max:100'])['q'] ?? '');

        $rows = ESBTPInscription::query()
            ->select(['id', 'etudiant_id', 'classe_id', 'workflow_step', 'status', 'annee_universitaire_id'])
            ->with([
                'etudiant:id,nom,prenoms,matricule',
                'classe:id,name',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->whereHas('etudiant', function ($eq) use ($q) {
                    $eq->where('nom', 'like', "%{$q}%")
                       ->orWhere('prenoms', 'like', "%{$q}%")
                       ->orWhere('matricule', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->limit(15)
            ->get();

        return response()->json([
            'items' => $rows->map(fn (ESBTPInscription $i) => [
                'id' => $i->id,
                'etudiant' => trim(($i->etudiant?->nom ?? '') . ' ' . ($i->etudiant?->prenoms ?? '')) ?: '—',
                'matricule' => $i->etudiant?->matricule,
                'classe' => $i->classe?->name ?? '—',
                'workflow_step' => $i->workflow_step,
                'workflow_label' => ChatActionResolver::workflowLabel($i->workflow_step),
                'status' => $i->status,
            ])->all(),
        ]);
    }

    /**
     * Picker : recherche de paiements à partager.
     */
    public function pickerPaiements(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('paiements.view'), 403);

        $q = trim($request->validate(['q' => 'nullable|string|max:100'])['q'] ?? '');

        $rows = ESBTPPaiement::query()
            ->select(['id', 'inscription_id', 'etudiant_id', 'montant', 'status', 'validateur_id', 'date_paiement', 'mode_paiement', 'reference_paiement'])
            ->with(['etudiant:id,nom,prenoms,matricule'])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->whereHas('etudiant', function ($eq) use ($q) {
                    $eq->where('nom', 'like', "%{$q}%")
                       ->orWhere('prenoms', 'like', "%{$q}%")
                       ->orWhere('matricule', 'like', "%{$q}%");
                })->orWhere('reference_paiement', 'like', "%{$q}%");
            })
            ->latest('id')
            ->limit(15)
            ->get();

        return response()->json([
            'items' => $rows->map(fn (ESBTPPaiement $p) => [
                'id' => $p->id,
                'etudiant' => trim(($p->etudiant?->nom ?? '') . ' ' . ($p->etudiant?->prenoms ?? '')) ?: '—',
                'matricule' => $p->etudiant?->matricule,
                'montant' => (float) $p->montant,
                'statut' => $p->status,
                'is_validated' => $p->status === 'validé',
                'reference' => $p->reference_paiement,
                'date' => optional($p->date_paiement)->toDateString(),
            ])->all(),
        ]);
    }

    /**
     * Pipeline interne : trouve/crée la conv + insère le message action_card + (optionnel) note texte.
     *
     * @param array{recipient_id?: int, conversation_id?: int, note?: string} $data
     */
    private function dispatchActionCard(Request $request, array $data, array $payload): JsonResponse
    {
        $sender = $request->user();

        $result = DB::transaction(function () use ($sender, $data, $payload) {
            $conversation = $this->findOrCreateConversation($sender, $data);

            $card = ChatMessage::create([
                'chat_conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'type' => 'action_card',
                'body' => null,
                'payload' => $payload,
            ]);

            if (!empty($data['note'])) {
                ChatMessage::create([
                    'chat_conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'type' => 'text',
                    'body' => $data['note'],
                ]);
            }

            $conversation->update(['last_message_at' => now()]);

            return [$conversation, $card];
        });

        [$conversation, $card] = $result;

        return response()->json([
            'conversation_id' => $conversation->id,
            'message_id' => $card->id,
            'redirect' => route('chat.index') . '?conversation=' . $conversation->id,
        ], 201);
    }

    private function findOrCreateConversation(User $sender, array $data): ChatConversation
    {
        if (!empty($data['conversation_id'])) {
            $conversation = ChatConversation::findOrFail($data['conversation_id']);
            $this->authorizeMember($conversation, $sender);
            return $conversation;
        }
        return $this->findOrCreateDm($sender, (int) $data['recipient_id']);
    }

    private function findOrCreateDm(User $sender, int $recipientId): ChatConversation
    {
        abort_if($recipientId === $sender->id, 422, 'Vous ne pouvez pas vous envoyer un message à vous-même.');

        $existing = ChatConversation::where('type', 'dm')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $sender->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipientId))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($sender, $recipientId) {
            $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
            $conversation->participants()->attach([$sender->id, $recipientId]);
            return $conversation;
        });
    }
}

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
                'participants:id,name,email,last_seen_at',
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
        $viewer = $request->user();

        $conversation->participants()
            ->updateExistingPivot($viewer->id, ['last_read_at' => now()]);

        // On veut les 50 derniers messages, dans l'ordre chronologique ASC pour l'affichage.
        // sortBy après get() pour éviter le piège ORDER BY DESC + reverse() qui peut ne pas
        // s'appliquer dans certaines configurations Eloquent.
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->sortBy('created_at')
            ->values();

        $maps = $this->resolver->preload($messages);

        // Pour les read receipts : on calcule le min(last_read_at) des autres participants.
        $conversation->loadMissing('participants:id,name,email,last_seen_at');
        $others = $conversation->participants->where('id', '!=', $viewer->id);
        $minOtherReadAt = $others->map(fn ($p) => $p->pivot->last_read_at)->filter()->min();

        return response()->json([
            'conversation' => array_merge($conversation->only(['id', 'type', 'title', 'context']), [
                'participants' => $others->map(fn ($p) => $this->mapParticipant($p))->values(),
            ]),
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
                'read_by_others' => $m->sender_id === $viewer->id
                    && $minOtherReadAt
                    && $m->created_at->lte($minOtherReadAt),
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
        $this->assertCanReceiveMessages((int) $data['user_id']);
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

    /**
     * Liste JSON des conversations du user pour polling live de la sidebar.
     */
    public function conversationsList(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = ChatConversation::query()
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with(['participants:id,name,email,last_seen_at', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        return response()->json([
            'items' => $conversations->map(function (ChatConversation $c) use ($user) {
                $last = $c->lastMessage;
                $kind = is_array($last->payload ?? null) ? ($last->payload['kind'] ?? null) : null;
                $preview = $last ? match ($last->type) {
                    'action_card' => '📎 ' . match ($kind) {
                        'inscription' => 'Inscription partagée',
                        'paiement' => 'Paiement partagé',
                        default => 'Card partagée',
                    },
                    'system' => $last->body ?? 'Notification',
                    default => $last->body ?? '',
                } : null;

                $lastReadAt = $c->participants->firstWhere('id', $user->id)?->pivot?->last_read_at;
                $unreadCount = $last && (!$lastReadAt || $last->created_at->gt($lastReadAt))
                    ? $c->messages()->where('sender_id', '!=', $user->id)
                        ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
                        ->count()
                    : 0;

                return [
                    'id' => $c->id,
                    'type' => $c->type,
                    'title' => $c->title,
                    'last_message_at' => $c->last_message_at?->toIso8601String(),
                    'last_message' => $last ? [
                        'id' => $last->id,
                        'body' => $last->body,
                        'type' => $last->type,
                        'preview' => $preview,
                        'sender_id' => $last->sender_id,
                    ] : null,
                    'unread_count' => $unreadCount,
                    'participants' => $c->participants->where('id', '!=', $user->id)
                        ->map(fn ($p) => $this->mapParticipant($p))->values(),
                ];
            })->all(),
        ]);
    }

    /**
     * Sérialise un participant avec présence (en ligne / dernière connexion).
     * En ligne = last_seen_at < 2 minutes.
     */
    private function mapParticipant($p): array
    {
        $lastSeen = $p->last_seen_at;
        $isOnline = $lastSeen && $lastSeen->gt(now()->subMinutes(2));
        return [
            'id' => $p->id,
            'name' => $p->name,
            'is_online' => (bool) $isOnline,
            'last_seen_at' => $lastSeen?->toIso8601String(),
        ];
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

        // Issue #315 : isolation étudiants — on n'expose pas les comptes étudiants
        // dans le picker du chat staff. Les étudiants ne peuvent pas recevoir de DM
        // (ils consultent leurs annonces via /esbtp/mes-messages). On exclut donc
        // explicitement les users qui ont le rôle `etudiant` ET on conserve les
        // checks existants (auto-exclu, actifs).
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'etudiant'))
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

        // Issue #315 : double-check au niveau du repository d'écriture. Toute conversation
        // existante (legacy) reste accessible — on bloque uniquement la *création* de
        // nouvelles conversations vers un destinataire qui ne peut pas recevoir de DM.
        $existing = ChatConversation::where('type', 'dm')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $sender->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $recipientId))
            ->first();

        if ($existing) {
            return $existing;
        }

        $this->assertCanReceiveMessages($recipientId);

        return DB::transaction(function () use ($sender, $recipientId) {
            $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
            $conversation->participants()->attach([$sender->id, $recipientId]);
            return $conversation;
        });
    }

    /**
     * Garde-fou : un destinataire de DM doit pouvoir *recevoir* des messages directs.
     *
     * Les étudiants (rôle `etudiant`) ont seulement `messages.receive` au sens
     * « recevoir des annonces », pas du DM staff. La permission canonique pour
     * être éligible à un DM est `messages.send` (staff actif). On vérifie donc
     * que le destinataire dispose de cette permission, ce qui exclut nativement
     * les étudiants et tout user désactivé/sans rôle.
     *
     * Issue #315 : isoler les étudiants du chat user-to-user.
     */
    private function assertCanReceiveMessages(int $userId): void
    {
        $recipient = User::find($userId);
        abort_if(!$recipient, 422, 'Destinataire introuvable.');
        abort_if(!$recipient->is_active, 422, 'Le destinataire est inactif.');
        abort_if(
            !$recipient->can('messages.send'),
            422,
            'Le destinataire ne peut pas recevoir de messages directs (probablement un étudiant — utilisez les annonces).'
        );
    }
}

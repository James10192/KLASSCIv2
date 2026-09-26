<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Notifications\WorkflowNextStepNotification;
use App\Services\ChatActionResolver;
use App\Services\ChatMessagePreview;
use App\Services\Messages\ConversationEntityLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class MessageHubController extends Controller
{
    public function __construct(
        private readonly ConversationEntityLinkService $links,
        private readonly ChatActionResolver $actionResolver,
    ) {
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $conversations = $this->conversationQuery($viewer)->get();

        $inbox = $conversations
            ->reject(fn (ChatConversation $conversation) => $conversation->type === 'workflow')
            ->map(fn (ChatConversation $conversation) => $this->conversationSummary($conversation, $viewer))
            ->values();

        $actions = collect()
            ->concat($this->persistentActions($viewer))
            ->concat($this->legacyNotificationActions($viewer))
            ->concat($this->legacyWorkflowConversationActions($conversations, $viewer))
            ->sortByDesc(fn (array $action) => $action['updated_at'] ?? $action['created_at'] ?? '')
            ->values();

        return response()->json([
            'conversations' => $inbox,
            'actions' => $actions,
            'counts' => [
                'conversations' => $inbox->count(),
                'actions_open' => $actions->whereNotIn('status', ['done', 'rejected'])->count(),
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function conversation(Request $request, ChatConversation $conversation): JsonResponse
    {
        $viewer = $request->user();
        $this->authorizeMember($conversation, $viewer);

        $conversation->participants()->updateExistingPivot($viewer->id, ['last_read_at' => now()]);
        $conversation->loadMissing([
            'participants:id,name,email,last_seen_at,position,department,is_active',
            'participants.roles:id,name',
            'entityLinks',
        ]);

        $messages = $conversation->messages()
            ->with(['sender:id,name,email,position,department', 'sender.roles:id,name'])
            ->orderByDesc('created_at')
            ->limit(80)
            ->get()
            ->sortBy('created_at')
            ->values();

        // Older data may predate the explicit link table. Create only typed links
        // from kind+id; never infer from a participant name.
        foreach ($messages->where('type', 'action_card') as $message) {
            $this->links->ensureForMessage($message);
        }
        $conversation->unsetRelation('entityLinks');
        $conversation->load('entityLinks');

        $otherReadAt = $conversation->participants
            ->where('id', '!=', $viewer->id)
            ->map(fn (User $participant) => $participant->pivot->last_read_at)
            ->filter()
            ->min();

        $linkedEntities = $this->links->forViewer($conversation, $viewer, 'conversation_open');
        $projectedMessages = $messages->map(function (ChatMessage $message) use ($viewer, $otherReadAt) {
            $businessCard = $message->type === 'action_card'
                ? $this->links->businessCard($message, $viewer)
                : null;

            $cta = null;
            if ($businessCard) {
                if (($businessCard['sensitive_actions_allowed'] ?? false) === true) {
                    $cta = $this->actionResolver->resolveCta($message, $viewer);
                } elseif (! empty($businessCard['open_url'])) {
                    $cta = [
                        'label' => 'Ouvrir le dossier lié',
                        'url' => $businessCard['open_url'],
                        'variant' => 'ghost',
                        'icon' => 'fa-arrow-up-right-from-square',
                        'view_only' => true,
                    ];
                }
            }

            return [
                'id' => $message->id,
                'client_id' => null,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name ?? 'Système',
                'sender_roles' => $message->sender?->getRoleNames()->values()->all() ?? [],
                'type' => $message->type,
                'body' => $message->body,
                'business_card' => $businessCard,
                'cta' => $cta,
                'created_at' => $message->created_at?->toIso8601String(),
                'direction' => $message->sender_id === $viewer->id ? 'outgoing' : 'incoming',
                'mine' => $message->sender_id === $viewer->id,
                'read_by_others' => $message->sender_id === $viewer->id
                    && $otherReadAt
                    && $message->created_at?->lte($otherReadAt),
            ];
        })->all();

        $relatedActions = WorkflowAction::query()
            ->where('chat_conversation_id', $conversation->id)
            ->with(['activities.user:id,name'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->filter(fn (WorkflowAction $action) => $this->canViewAction($viewer, $action))
            ->map(fn (WorkflowAction $action) => $this->actionDetail($action))
            ->values();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $this->conversationTitle($conversation, $viewer),
                'participants' => $conversation->participants
                    ->where('id', '!=', $viewer->id)
                    ->map(fn (User $participant) => $this->participant($participant))
                    ->values()
                    ->all(),
                'state' => $this->viewerState($conversation, $viewer),
                'has_ambiguous_link' => $linkedEntities->contains(fn (array $link) => ! ($link['relation_verified'] ?? false)),
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ],
            'messages' => $projectedMessages,
            'linked_entities' => $linkedEntities->all(),
            'action_history' => $relatedActions->all(),
        ]);
    }

    public function updateConversationState(Request $request, ChatConversation $conversation): JsonResponse
    {
        $viewer = $request->user();
        $this->authorizeMember($conversation, $viewer);
        $data = $request->validate([
            'important' => 'sometimes|boolean',
            'archived' => 'sometimes|boolean',
            'pinned' => 'sometimes|boolean',
        ]);

        $updates = [];
        foreach (['important' => 'important_at', 'archived' => 'archived_at', 'pinned' => 'pinned_at'] as $key => $column) {
            if (array_key_exists($key, $data)) {
                $updates[$column] = $data[$key] ? now() : null;
            }
        }
        if ($updates !== []) {
            $conversation->participants()->updateExistingPivot($viewer->id, $updates);
        }
        $conversation->unsetRelation('participants');
        $conversation->load(['participants' => fn ($query) => $query->where('users.id', $viewer->id)]);

        return response()->json(['state' => $this->viewerState($conversation, $viewer)]);
    }

    public function markLegacyActionRead(Request $request, string $notification): JsonResponse
    {
        $row = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        abort_unless($row->type === WorkflowNextStepNotification::class, 404);
        $row->markAsRead();

        return response()->json(['ok' => true, 'status' => 'done']);
    }

    private function conversationQuery(User $viewer)
    {
        return ChatConversation::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $viewer->id))
            ->with([
                'participants:id,name,email,last_seen_at,position,department,is_active',
                'participants.roles:id,name',
                'lastMessage',
            ])
            ->select('chat_conversations.*')
            ->selectRaw(
                '(SELECT COUNT(*) FROM chat_messages cm
                  INNER JOIN chat_conversation_participants ccp
                    ON ccp.chat_conversation_id = cm.chat_conversation_id AND ccp.user_id = ?
                  WHERE cm.chat_conversation_id = chat_conversations.id
                    AND cm.sender_id != ?
                    AND (ccp.last_read_at IS NULL OR cm.created_at > ccp.last_read_at)) as unread_count',
                [$viewer->id, $viewer->id]
            )
            ->orderByDesc('last_message_at')
            ->limit(100);
    }

    private function conversationSummary(ChatConversation $conversation, User $viewer): array
    {
        $participant = $conversation->participants->firstWhere('id', $viewer->id);
        $others = $conversation->participants->where('id', '!=', $viewer->id)->values();
        $title = $this->conversationTitle($conversation, $viewer);
        $preview = ChatMessagePreview::forMessage($conversation->lastMessage);

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'title' => $title,
            'initials' => $this->initials($title),
            'subtitle' => $conversation->type === 'group'
                ? $others->pluck('name')->take(3)->implode(', ')
                : ($others->first()?->position ?: $others->first()?->getRoleNames()->first()),
            'participants' => $others->map(fn (User $user) => $this->participant($user))->all(),
            'last_message_preview' => $preview,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => (int) ($conversation->unread_count ?? 0),
            'state' => [
                'important' => (bool) $participant?->pivot?->important_at,
                'archived' => (bool) $participant?->pivot?->archived_at,
                'pinned' => (bool) $participant?->pivot?->pinned_at,
            ],
        ];
    }

    private function participant(User $user): array
    {
        $roles = $user->getRoleNames()->values();
        $accountType = $roles->contains('etudiant')
            ? 'student'
            : ($roles->contains(fn (string $role) => str_contains(mb_strtolower($role), 'parent')) ? 'parent' : 'staff');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_names' => $roles->all(),
            'role_label' => $user->position ?: $roles->first() ?: 'Personnel de l’école',
            'account_type' => $accountType,
            'department' => $user->department,
            'is_online' => $user->isOnline(),
            'last_seen_at' => $user->last_seen_at?->toIso8601String(),
            'relevant_permissions' => [
                'messages_send' => $user->can('messages.send'),
            ],
        ];
    }

    private function viewerState(ChatConversation $conversation, User $viewer): array
    {
        $participant = $conversation->participants->firstWhere('id', $viewer->id)
            ?? $conversation->participants()->where('user_id', $viewer->id)->first();

        return [
            'important' => (bool) $participant?->pivot?->important_at,
            'archived' => (bool) $participant?->pivot?->archived_at,
            'pinned' => (bool) $participant?->pivot?->pinned_at,
        ];
    }

    /** @return Collection<int, array<string,mixed>> */
    private function persistentActions(User $viewer): Collection
    {
        return WorkflowAction::query()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->latest('updated_at')
            ->limit(100)
            ->get()
            ->filter(fn (WorkflowAction $action) => $this->canViewAction($viewer, $action))
            ->map(fn (WorkflowAction $action) => [
                'id' => 'action:' . $action->id,
                'numeric_id' => $action->id,
                'source' => 'workflow_action',
                'type' => $action->action_type,
                'title' => $action->title,
                'subject' => $this->actionSubject($action),
                'description' => $action->description,
                'service' => $action->service ?: 'Interne',
                'priority' => $action->priority,
                'status' => $action->status,
                'assigned_to' => $action->assigned_to,
                'assignee' => $action->assignee?->name,
                'creator' => $action->creator?->name,
                'due_at' => $action->due_at?->toIso8601String(),
                'created_at' => $action->created_at?->toIso8601String(),
                'updated_at' => $action->updated_at?->toIso8601String(),
                'conversation_id' => $action->chat_conversation_id,
                'editable' => $this->canEditAction($viewer, $action),
            ]);
    }

    /** @return Collection<int, array<string,mixed>> */
    private function legacyNotificationActions(User $viewer): Collection
    {
        return $viewer->notifications()
            ->where('type', WorkflowNextStepNotification::class)
            ->latest()
            ->limit(100)
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($viewer) {
                $permission = $notification->data['next_perm'] ?? null;
                return ! $permission || $viewer->can($permission);
            })
            ->map(function (DatabaseNotification $notification) use ($viewer) {
                $data = $notification->data;
                [$type, $entityId] = $this->legacyEntityReference($data['context'] ?? []);
                $entity = $type && $entityId
                    ? $this->links->describeEntity($type, $entityId, $viewer)
                    : ['label' => 'Lien à vérifier', 'url' => null];

                return [
                    'id' => 'legacy:' . $notification->id,
                    'legacy_notification_id' => $notification->id,
                    'source' => 'legacy_notification',
                    'type' => $data['type'] ?? 'legacy_workflow',
                    'title' => $data['next_label'] ?? $this->legacyTitle($data['type'] ?? ''),
                    'subject' => $entity['label'] ?? 'Lien à vérifier',
                    'description' => 'Action historique issue du workflow KLASSCI.',
                    'service' => $this->legacyService($data['type'] ?? ''),
                    'priority' => str_starts_with((string) ($data['type'] ?? ''), 'paiement.') ? 'high' : 'normal',
                    'status' => $notification->read_at ? 'done' : 'todo',
                    'assigned_to' => $viewer->id,
                    'assignee' => $viewer->name,
                    'creator' => $data['actor_name'] ?? null,
                    'due_at' => null,
                    'created_at' => ($data['created_at'] ?? $notification->created_at?->toIso8601String()),
                    'updated_at' => $notification->updated_at?->toIso8601String(),
                    'conversation_id' => null,
                    'open_url' => $data['next_url'] ?? $entity['url'] ?? null,
                    'editable' => false,
                ];
            });
    }

    /** @return Collection<int, array<string,mixed>> */
    private function legacyWorkflowConversationActions(Collection $conversations, User $viewer): Collection
    {
        return $conversations
            ->where('type', 'workflow')
            ->map(function (ChatConversation $conversation) use ($viewer) {
                $last = $conversation->lastMessage;
                $payload = is_array($last?->payload) ? $last->payload : [];
                $kind = $payload['kind'] ?? null;
                $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);
                $entity = $kind && $id
                    ? $this->links->describeEntity($kind, (int) $id, $viewer)
                    : ['label' => 'Lien à vérifier'];

                return [
                    'id' => 'legacy-conversation:' . $conversation->id,
                    'source' => 'legacy_workflow_conversation',
                    'type' => $kind ?: 'legacy_workflow',
                    'title' => $conversation->title ?: 'Action historique à vérifier',
                    'subject' => $entity['label'] ?? 'Lien à vérifier',
                    'description' => 'Conversation workflow historique conservée sans rapprochement automatique.',
                    'service' => 'Interne',
                    'priority' => 'normal',
                    'status' => 'todo',
                    'assigned_to' => null,
                    'assignee' => null,
                    'creator' => null,
                    'due_at' => null,
                    'created_at' => $conversation->created_at?->toIso8601String(),
                    'updated_at' => $conversation->last_message_at?->toIso8601String(),
                    'conversation_id' => $conversation->id,
                    'editable' => false,
                ];
            });
    }

    private function actionDetail(WorkflowAction $action): array
    {
        return [
            'id' => $action->id,
            'title' => $action->title,
            'status' => $action->status,
            'priority' => $action->priority,
            'activities' => $action->activities->map(fn ($activity) => [
                'event' => $activity->event,
                'comment' => $activity->comment,
                'user' => $activity->user?->name,
                'created_at' => $activity->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    private function canViewAction(User $viewer, WorkflowAction $action): bool
    {
        if ($action->created_by === $viewer->id || $action->assigned_to === $viewer->id || $viewer->can('admin.access')) {
            return true;
        }
        if ($action->chat_conversation_id && ChatConversation::whereKey($action->chat_conversation_id)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $viewer->id))->exists()) {
            return true;
        }
        if ($action->assigned_to !== null) {
            return false;
        }

        return match ($action->action_type) {
            'paiement', 'payment' => $viewer->can('paiements.view'),
            'inscription', 'registration' => $viewer->can('inscriptions.view'),
            default => $viewer->can('messages.send'),
        };
    }

    private function canEditAction(User $viewer, WorkflowAction $action): bool
    {
        return $action->created_by === $viewer->id
            || $action->assigned_to === $viewer->id
            || $viewer->can('admin.access');
    }

    private function actionSubject(WorkflowAction $action): string
    {
        $data = is_array($action->context_data) ? $action->context_data : [];
        return $data['entity_label'] ?? $data['subject'] ?? 'Demande interne';
    }

    private function legacyEntityReference(array $context): array
    {
        if (! empty($context['paiement'])) {
            return ['paiement', (int) $context['paiement']];
        }
        $inscription = $context['inscription_id'] ?? $context['inscription'] ?? null;
        if ($inscription) {
            return ['inscription', (int) $inscription];
        }
        return [null, null];
    }

    private function legacyTitle(string $type): string
    {
        return match ($type) {
            'inscription.created' => 'Paiement à enregistrer',
            'paiement.created' => 'Paiement à valider',
            'paiement.validated' => 'Inscription à valider',
            default => 'Action historique à traiter',
        };
    }

    private function legacyService(string $type): string
    {
        return str_starts_with($type, 'paiement.') ? 'Caisse / Comptabilité' : 'Scolarité';
    }

    private function conversationTitle(ChatConversation $conversation, User $viewer): string
    {
        if ($conversation->title) {
            return $conversation->title;
        }
        return $conversation->participants->where('id', '!=', $viewer->id)->first()?->name ?? 'Conversation';
    }

    private function initials(string $label): string
    {
        return collect(preg_split('/\s+/u', trim($label)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'K';
    }

    private function authorizeMember(ChatConversation $conversation, User $viewer): void
    {
        abort_unless($conversation->participants()->where('user_id', $viewer->id)->exists(), 403);
    }
}

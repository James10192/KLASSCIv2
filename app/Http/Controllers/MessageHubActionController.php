<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowActionActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MessageHubActionController extends Controller
{
    private const TYPES = [
        'internal_request',
        'verification',
        'inscription',
        'paiement',
        'reclamation',
        'grade',
        'absence',
        'document',
    ];

    private const STATUSES = ['todo', 'in_progress', 'waiting_info', 'done', 'rejected'];
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();
        $data = $request->validate([
            'action_type' => ['required', Rule::in(self::TYPES)],
            'title' => 'required|string|min:3|max:180',
            'description' => 'nullable|string|max:4000',
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'service' => 'nullable|string|max:120',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'due_at' => 'nullable|date',
            'chat_conversation_id' => 'nullable|integer|exists:chat_conversations,id',
            'context_type' => 'nullable|string|max:120',
            'context_id' => 'nullable|integer|min:1',
            'context_data' => 'nullable|array',
            'context_data.entity_label' => 'nullable|string|max:255',
            'context_data.subject' => 'nullable|string|max:255',
        ]);

        $this->assertConversationAccess($actor, $data['chat_conversation_id'] ?? null);
        $this->assertCanCreateType($actor, $data['action_type']);
        $assignee = $this->eligibleAssignee($data['assigned_to'] ?? null);

        $action = DB::transaction(function () use ($data, $actor, $assignee) {
            $action = WorkflowAction::create([
                'action_type' => $data['action_type'],
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'],
                'status' => 'todo',
                'service' => $data['service'] ?? 'Interne',
                'assigned_to' => $assignee?->id,
                'created_by' => $actor->id,
                'chat_conversation_id' => $data['chat_conversation_id'] ?? null,
                'context_type' => $data['context_type'] ?? null,
                'context_id' => $data['context_id'] ?? null,
                'context_data' => $data['context_data'] ?? null,
                'due_at' => $data['due_at'] ?? null,
            ]);

            WorkflowActionActivity::create([
                'workflow_action_id' => $action->id,
                'user_id' => $actor->id,
                'event' => 'created',
                'comment' => 'Action créée.',
                'metadata' => [
                    'priority' => $action->priority,
                    'assigned_to' => $action->assigned_to,
                    'status' => $action->status,
                ],
            ]);

            if ($assignee) {
                WorkflowActionActivity::create([
                    'workflow_action_id' => $action->id,
                    'user_id' => $actor->id,
                    'event' => 'assigned',
                    'comment' => 'Action affectée à ' . $assignee->name . '.',
                    'metadata' => ['assigned_to' => $assignee->id],
                ]);
            }

            return $action;
        });

        return response()->json(['action' => $this->serialize($action->fresh(['assignee', 'creator', 'activities.user']))], 201);
    }

    public function show(Request $request, WorkflowAction $action): JsonResponse
    {
        $this->authorizeAction($request->user(), $action, false);
        $action->load(['assignee:id,name', 'creator:id,name', 'activities.user:id,name']);

        return response()->json(['action' => $this->serialize($action)]);
    }

    public function update(Request $request, WorkflowAction $action): JsonResponse
    {
        $actor = $request->user();
        $this->authorizeAction($actor, $action, true);

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(self::STATUSES)],
            'priority' => ['sometimes', Rule::in(self::PRIORITIES)],
            'assigned_to' => 'sometimes|nullable|integer|exists:users,id',
            'due_at' => 'sometimes|nullable|date',
            'description' => 'sometimes|nullable|string|max:4000',
            'service' => 'sometimes|nullable|string|max:120',
            'comment' => 'nullable|string|max:1000',
        ]);

        $assignee = array_key_exists('assigned_to', $data)
            ? $this->eligibleAssignee($data['assigned_to'])
            : null;

        DB::transaction(function () use ($action, $actor, $data, $assignee) {
            $before = $action->only(['status', 'priority', 'assigned_to', 'due_at', 'description', 'service']);
            $updates = collect($data)->only(['status', 'priority', 'due_at', 'description', 'service'])->all();
            if (array_key_exists('assigned_to', $data)) {
                $updates['assigned_to'] = $assignee?->id;
            }
            if (($updates['status'] ?? null) === 'done') {
                $updates['completed_at'] = now();
            } elseif (isset($updates['status']) && $updates['status'] !== 'done') {
                $updates['completed_at'] = null;
            }
            $action->update($updates);

            $after = $action->fresh()->only(['status', 'priority', 'assigned_to', 'due_at', 'description', 'service']);
            $changes = [];
            foreach ($after as $key => $value) {
                $old = $before[$key] ?? null;
                $new = $value;
                if ((string) $old !== (string) $new) {
                    $changes[$key] = ['from' => $old, 'to' => $new];
                }
            }

            WorkflowActionActivity::create([
                'workflow_action_id' => $action->id,
                'user_id' => $actor->id,
                'event' => isset($changes['status']) ? 'status_changed' : 'updated',
                'comment' => $data['comment'] ?? null,
                'metadata' => ['changes' => $changes],
            ]);
        });

        return response()->json(['action' => $this->serialize($action->fresh(['assignee', 'creator', 'activities.user']))]);
    }

    public function assignees(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $users = User::permission('messages.send')
            ->where('is_active', true)
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email', 'position', 'department']);

        return response()->json(['users' => $users]);
    }

    private function eligibleAssignee(?int $id): ?User
    {
        if ($id === null) {
            return null;
        }
        $user = User::findOrFail($id);
        abort_unless($user->is_active && $user->can('messages.send'), 422, 'Cette personne ne peut pas recevoir une action interne.');
        return $user;
    }

    private function assertConversationAccess(User $actor, ?int $conversationId): void
    {
        if (! $conversationId) {
            return;
        }
        abort_unless(
            ChatConversation::whereKey($conversationId)
                ->whereHas('participants', fn ($query) => $query->where('user_id', $actor->id))
                ->exists(),
            403,
            'Vous ne pouvez pas créer une action sur cette conversation.'
        );
    }

    private function assertCanCreateType(User $actor, string $type): void
    {
        $allowed = match ($type) {
            'paiement' => $actor->can('paiements.view'),
            'inscription' => $actor->can('inscriptions.view'),
            default => $actor->can('messages.send'),
        };
        abort_unless($allowed, 403, 'Vous n’avez pas les droits nécessaires pour ce type d’action.');
    }

    private function authorizeAction(User $actor, WorkflowAction $action, bool $write): void
    {
        $own = $action->created_by === $actor->id || $action->assigned_to === $actor->id;
        $admin = $actor->can('admin.access');
        $conversationMember = $action->chat_conversation_id
            && ChatConversation::whereKey($action->chat_conversation_id)
                ->whereHas('participants', fn ($query) => $query->where('user_id', $actor->id))
                ->exists();

        if ($write) {
            abort_unless($own || $admin, 403, 'Cette action ne vous est pas affectée.');
            return;
        }

        $queueAccess = $action->assigned_to === null && match ($action->action_type) {
            'paiement' => $actor->can('paiements.view'),
            'inscription' => $actor->can('inscriptions.view'),
            default => $actor->can('messages.send'),
        };
        abort_unless($own || $admin || $conversationMember || $queueAccess, 403);
    }

    private function serialize(WorkflowAction $action): array
    {
        $data = is_array($action->context_data) ? $action->context_data : [];
        return [
            'id' => $action->id,
            'action_type' => $action->action_type,
            'title' => $action->title,
            'description' => $action->description,
            'priority' => $action->priority,
            'status' => $action->status,
            'service' => $action->service,
            'assigned_to' => $action->assigned_to,
            'assignee' => $action->assignee?->name,
            'creator' => $action->creator?->name,
            'chat_conversation_id' => $action->chat_conversation_id,
            'context_type' => $action->context_type,
            'context_id' => $action->context_id,
            'subject' => $data['entity_label'] ?? $data['subject'] ?? 'Demande interne',
            'due_at' => $action->due_at?->toIso8601String(),
            'created_at' => $action->created_at?->toIso8601String(),
            'updated_at' => $action->updated_at?->toIso8601String(),
            'activities' => $action->relationLoaded('activities')
                ? $action->activities->map(fn (WorkflowActionActivity $activity) => [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'comment' => $activity->comment,
                    'user' => $activity->user?->name,
                    'metadata' => $activity->metadata,
                    'created_at' => $activity->created_at?->toIso8601String(),
                ])->values()->all()
                : [],
        ];
    }
}

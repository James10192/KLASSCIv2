<?php

namespace App\Support\Messages;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Collection;

final class MessageHubProjection
{
    /**
     * Transitional projection for the 2026 message hub redesign.
     *
     * Legacy `workflow` conversations remain untouched in storage so existing
     * notifications, deep-links and action cards keep working. The UI projects
     * them as explicit workflowAction objects until the workflow domain gets its
     * own persistence layer.
     */
    public function project(Collection $conversations, User $viewer): array
    {
        $inbox = [];
        $actions = [];

        foreach ($conversations as $conversation) {
            if ($conversation->type === 'workflow') {
                $actions[] = $this->workflowAction($conversation, $viewer);
                continue;
            }

            $inbox[] = $this->conversation($conversation, $viewer);
        }

        return [
            'conversation' => $inbox,
            'workflowAction' => $actions,
            'contextEntity' => null,
            'announcement' => null,
            'aiSuggestion' => $this->aiSuggestions(),
        ];
    }

    private function conversation(ChatConversation $conversation, User $viewer): array
    {
        $others = $conversation->participants->where('id', '!=', $viewer->id)->values();
        $primary = $others->first();
        $title = $conversation->title ?: ($primary?->name ?? 'Conversation');
        $last = $conversation->lastMessage;

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'title' => $title,
            'subtitle' => $conversation->type === 'group'
                ? $others->pluck('name')->take(3)->implode(', ')
                : ($primary?->email ?? null),
            'initials' => $this->initials($title),
            'preview' => $last?->body ?: ($last?->type === 'action_card' ? 'Donnée métier partagée' : 'Aucun message'),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'context' => $conversation->context ?: [],
            'participants' => $others->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->all(),
        ];
    }

    private function workflowAction(ChatConversation $conversation, User $viewer): array
    {
        $last = $conversation->lastMessage;
        $payload = is_array($last?->payload) ? $last->payload : [];
        $context = is_array($conversation->context) ? $conversation->context : [];
        $kind = $payload['kind'] ?? $payload['type'] ?? $context['kind'] ?? 'demande_interne';
        $student = $payload['etudiant'] ?? $payload['student_name'] ?? $context['etudiant'] ?? null;

        return [
            'id' => $conversation->id,
            'conversation_id' => $conversation->id,
            'type' => $kind,
            'title' => $conversation->title ?: $this->actionTitle($kind),
            'subject' => $student ?: 'Dossier KLASSCI',
            'service' => $context['service'] ?? $this->serviceFor($kind),
            'priority' => $context['priority'] ?? 'normal',
            'status' => $context['status'] ?? 'todo',
            'assignee' => $context['assignee'] ?? null,
            'due_at' => $context['due_at'] ?? null,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'updated_at' => $conversation->last_message_at?->toIso8601String(),
            'summary' => $last?->body ?: $this->actionSummary($kind, $payload),
            'payload' => $payload,
            'context' => $context,
        ];
    }

    private function aiSuggestions(): array
    {
        return [
            ['id' => 'summary', 'label' => 'Résumer la conversation', 'icon' => 'fa-wand-magic-sparkles'],
            ['id' => 'reply', 'label' => 'Proposer une réponse', 'icon' => 'fa-reply'],
            ['id' => 'polish', 'label' => 'Reformuler le message', 'icon' => 'fa-pen'],
            ['id' => 'task', 'label' => 'Transformer en action', 'icon' => 'fa-list-check'],
        ];
    }

    private function actionTitle(string $kind): string
    {
        return match ($kind) {
            'paiement', 'payment' => 'Paiement à vérifier',
            'inscription', 'registration' => 'Inscription à traiter',
            'reclamation', 'complaint' => 'Réclamation à traiter',
            'note', 'grade' => 'Correction pédagogique',
            default => 'Demande interne à traiter',
        };
    }

    private function actionSummary(string $kind, array $payload): string
    {
        return match ($kind) {
            'paiement', 'payment' => 'Vérifier le paiement et ouvrir le dossier pour prendre une décision.',
            'inscription', 'registration' => 'Vérifier le dossier d’inscription et les pièces disponibles.',
            default => 'Une action métier nécessite votre attention.',
        };
    }

    private function serviceFor(string $kind): string
    {
        return match ($kind) {
            'paiement', 'payment' => 'Caisse / Comptabilité',
            'inscription', 'registration' => 'Scolarité',
            'note', 'grade' => 'Pédagogie',
            default => 'Interne',
        };
    }

    private function initials(string $label): string
    {
        return collect(preg_split('/\s+/u', trim($label)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'K';
    }
}

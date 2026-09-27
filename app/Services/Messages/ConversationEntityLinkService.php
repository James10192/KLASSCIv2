<?php

namespace App\Services\Messages;

use App\Models\ChatContextAccessLog;
use App\Models\ChatConversation;
use App\Models\ChatConversationEntityLink;
use App\Models\ChatMessage;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Source of truth for business items shared inside a conversation.
 *
 * A shared item is not evidence of a personal relationship between the sender
 * and the student/payment/document. The sender is simply the author of the
 * share. Business actions are gated by the viewer permissions, never by a
 * guessed participant <-> student relationship.
 */
class ConversationEntityLinkService
{
    public const SHARE_PURPOSES = [
        'validate' => 'À valider',
        'information' => 'Pour information',
        'follow_up' => 'Suivi demandé',
        'question' => 'Question',
        'check_document' => 'Pièce à contrôler',
    ];

    public function ensureForMessage(ChatMessage $message): ?ChatConversationEntityLink
    {
        if ($message->type !== 'action_card' || ! is_array($message->payload)) {
            return null;
        }

        $kind = $message->payload['kind'] ?? null;
        $id = filter_var($message->payload['id'] ?? null, FILTER_VALIDATE_INT);
        if (! in_array($kind, ['inscription', 'paiement'], true) || ! $id) {
            return null;
        }

        $resolved = $this->resolveEntity($kind, (int) $id);
        $purpose = $message->payload['share_purpose'] ?? null;
        $purpose = array_key_exists((string) $purpose, self::SHARE_PURPOSES) ? (string) $purpose : null;

        $link = ChatConversationEntityLink::firstOrCreate(
            [
                'source_message_id' => $message->id,
                'entity_type' => $kind,
                'entity_id' => (int) $id,
            ],
            [
                'chat_conversation_id' => $message->chat_conversation_id,
                'entity_label' => $resolved['label'],
                // Kept only for backwards-compatible storage. The UI/API no longer
                // asks users to verify a personal relationship for a shared card.
                'relation_type' => 'shared_by',
                'confidence' => 'verified',
                'source_type' => 'action_card',
                'related_user_id' => null,
                'required_view_permission' => $resolved['view_permission'],
                'required_detail_permission' => $resolved['detail_permission'],
                'created_by' => $message->sender_id,
                'verified_by' => null,
                'verified_at' => $message->created_at ?? now(),
                'metadata' => [
                    'entity_found' => $resolved['found'],
                    'source' => 'typed_action_card',
                    'share_purpose' => $purpose,
                ],
            ]
        );

        // Existing links created by the previous relation-verification model are
        // presented as shares without destructive data migration. Preserve their
        // audit history, but enrich purpose when the source payload now has one.
        if ($purpose && data_get($link->metadata, 'share_purpose') !== $purpose) {
            $metadata = $link->metadata ?: [];
            $metadata['share_purpose'] = $purpose;
            $link->forceFill(['metadata' => $metadata])->save();
        }

        return $link;
    }

    /** @return Collection<int, array<string,mixed>> */
    public function forViewer(ChatConversation $conversation, User $viewer, string $purpose = 'message_context'): Collection
    {
        $conversation->loadMissing('entityLinks.sourceMessage.sender.roles');

        return $conversation->entityLinks
            ->map(fn (ChatConversationEntityLink $link) => $this->present($link, $viewer, $purpose))
            ->values();
    }

    public function businessCard(ChatMessage $message, User $viewer): ?array
    {
        $link = $this->ensureForMessage($message);
        if (! $link) {
            return [
                'type' => 'unknown',
                'label' => 'Données indisponibles',
                'entity_id' => null,
                'entity_label' => 'Données indisponibles',
                'shared_item' => true,
                'shared_by' => $this->sharedBy($message->sender),
                'share_purpose' => null,
                'share_purpose_label' => null,
                'can_view' => false,
                'can_view_details' => false,
                'can_act' => false,
                'details' => [],
                'open_url' => null,
                'sensitive_actions_allowed' => false,
            ];
        }

        $link->setRelation('sourceMessage', $message->loadMissing('sender.roles'));

        return $this->present($link, $viewer, 'business_card');
    }

    public function present(ChatConversationEntityLink $link, User $viewer, string $purpose): array
    {
        $viewAllowed = $this->allowed($viewer, $link->required_view_permission);
        $detailAllowed = $viewAllowed && $this->allowed($viewer, $link->required_detail_permission);
        $canAct = $viewAllowed && $this->canAct($viewer, $link->entity_type);

        $this->logAccess($viewer, $link, $purpose, $viewAllowed, $link->required_view_permission);

        $sourceMessage = $link->relationLoaded('sourceMessage')
            ? $link->sourceMessage
            : $link->sourceMessage()->with('sender.roles')->first();
        $sharePurpose = data_get($link->metadata, 'share_purpose')
            ?: data_get($sourceMessage?->payload, 'share_purpose');
        $sharePurpose = array_key_exists((string) $sharePurpose, self::SHARE_PURPOSES) ? (string) $sharePurpose : null;

        $base = [
            'id' => $link->id,
            'type' => $link->entity_type,
            'entity_id' => $link->entity_id,
            'shared_item' => true,
            'shared_by' => $this->sharedByForViewer($link, $sourceMessage, $viewer),
            'share_purpose' => $sharePurpose,
            'share_purpose_label' => $sharePurpose ? self::SHARE_PURPOSES[$sharePurpose] : null,
            'source_message_id' => $link->source_message_id,
            'source_type' => $link->source_type,
        ];

        if (! $viewAllowed) {
            return $base + [
                'entity_label' => 'Données indisponibles',
                'label' => 'Données indisponibles',
                'can_view' => false,
                'can_view_details' => false,
                'can_act' => false,
                'details' => [],
                'open_url' => null,
                'sensitive_actions_allowed' => false,
            ];
        }

        $entity = $this->resolveEntity($link->entity_type, (int) $link->entity_id, $detailAllowed);

        return $base + [
            'entity_label' => $entity['label'],
            'label' => $entity['label'],
            'can_view' => true,
            'can_view_details' => $detailAllowed,
            'can_act' => $canAct,
            'details' => $detailAllowed ? $entity['details'] : $this->nonSensitiveDetails($entity['details']),
            'open_url' => $entity['url'],
            // This permission applies to the shared business item itself. It never
            // means the sender is the student, debtor or academic subject.
            'sensitive_actions_allowed' => $detailAllowed && $canAct,
        ];
    }

    public function describeEntity(string $type, int $id, User $viewer, string $purpose = 'legacy_workflow'): array
    {
        $permission = match ($type) {
            'inscription' => 'inscriptions.view',
            'paiement' => 'paiements.view',
            default => null,
        };
        if (! $this->allowed($viewer, $permission)) {
            return ['found' => false, 'label' => 'Données indisponibles', 'details' => [], 'url' => null];
        }

        return $this->resolveEntity($type, $id, false);
    }

    private function resolveEntity(string $type, int $id, bool $includeSensitive = true): array
    {
        return match ($type) {
            'inscription' => $this->resolveInscription($id, $includeSensitive),
            'paiement' => $this->resolvePaiement($id, $includeSensitive),
            default => [
                'found' => false,
                'label' => ucfirst(str_replace('_', ' ', $type)) . " #{$id} — Données indisponibles",
                'details' => [], 'url' => null, 'view_permission' => null, 'detail_permission' => null,
            ],
        };
    }

    private function resolveInscription(int $id, bool $includeSensitive): array
    {
        $inscription = ESBTPInscription::with([
            'etudiant:id,nom,prenoms,matricule', 'classe:id,name', 'anneeUniversitaire:id,name,libelle',
        ])->find($id);

        if (! $inscription) {
            return [
                'found' => false, 'label' => "Inscription #{$id} — Données indisponibles",
                'details' => ['status_label' => 'Données indisponibles'], 'url' => null,
                'view_permission' => 'inscriptions.view', 'detail_permission' => 'finances.etudiants.voir',
            ];
        }

        $name = trim(($inscription->etudiant?->nom ?? '') . ' ' . ($inscription->etudiant?->prenoms ?? ''));
        $details = [
            'student_name' => $name !== '' ? $name : 'Données indisponibles',
            'matricule' => $inscription->etudiant?->matricule,
            'classe' => $inscription->classe?->name,
            'annee' => $inscription->anneeUniversitaire?->libelle ?? $inscription->anneeUniversitaire?->name,
            'status' => $inscription->status,
            'workflow_step' => $inscription->workflow_step,
        ];
        if ($includeSensitive) $details['finance_available'] = true;

        return [
            'found' => true,
            'label' => 'Inscription — ' . ($name !== '' ? $name : 'Données indisponibles'),
            'details' => $details,
            'url' => route('esbtp.inscriptions.show', $id),
            'view_permission' => 'inscriptions.view',
            'detail_permission' => 'finances.etudiants.voir',
        ];
    }

    private function resolvePaiement(int $id, bool $includeSensitive): array
    {
        $paiement = ESBTPPaiement::with([
            'etudiant:id,nom,prenoms,matricule', 'inscription:id,etudiant_id,classe_id', 'inscription.classe:id,name',
        ])->find($id);

        if (! $paiement) {
            return [
                'found' => false, 'label' => "Paiement #{$id} — Données indisponibles",
                'details' => ['status_label' => 'Données indisponibles'], 'url' => null,
                'view_permission' => 'paiements.view', 'detail_permission' => 'finances.etudiants.voir',
            ];
        }

        $name = trim(($paiement->etudiant?->nom ?? '') . ' ' . ($paiement->etudiant?->prenoms ?? ''));
        $details = [
            'student_name' => $name !== '' ? $name : 'Données indisponibles',
            'matricule' => $paiement->etudiant?->matricule,
            'classe' => $paiement->inscription?->classe?->name,
            'status' => $paiement->status,
            'reference' => $paiement->reference_paiement ?? $paiement->numero_recu,
            'inscription_id' => $paiement->inscription_id,
        ];
        if ($includeSensitive) {
            $details['amount'] = (float) $paiement->montant;
            $details['payment_mode'] = $paiement->mode_paiement;
        }

        return [
            'found' => true,
            'label' => 'Paiement — ' . ($name !== '' ? $name : 'Données indisponibles'),
            'details' => $details,
            'url' => route('esbtp.paiements.show', $id),
            'view_permission' => 'paiements.view',
            'detail_permission' => 'finances.etudiants.voir',
        ];
    }

    private function sharedBy(?User $user): ?array
    {
        if (! $user) return null;
        $roles = $user->getRoleNames()->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'role_label' => $user->position ?: $roles->first() ?: 'Personnel de l’école',
            'department' => $user->department,
        ];
    }

    private function sharedByForViewer(ChatConversationEntityLink $link, ?ChatMessage $sourceMessage, User $viewer): ?array
    {
        $author = $sourceMessage?->sender;

        if ($author && $author->id !== $viewer->id) {
            return $this->sharedBy($author);
        }

        $conversation = $link->relationLoaded('conversation')
            ? $link->conversation
            : $link->conversation()->with('participants.roles')->first();
        $peer = $conversation?->participants
            ->first(fn (User $participant) => $participant->id !== $viewer->id);

        return $this->sharedBy($peer) ?: $this->sharedBy($author);
    }

    private function nonSensitiveDetails(array $details): array
    {
        return collect($details)->except(['amount', 'payment_mode', 'finance_available'])->all();
    }

    private function allowed(User $viewer, ?string $permission): bool
    {
        return $permission === null || $permission === '' || $viewer->can($permission);
    }

    private function canAct(User $viewer, string $entityType): bool
    {
        if ($viewer->can('admin.access')) return true;

        return match ($entityType) {
            'inscription' => $viewer->can('inscriptions.validate'),
            'paiement' => $viewer->can('paiements.validate'),
            default => false,
        };
    }

    private function logAccess(User $viewer, ChatConversationEntityLink $link, string $purpose, bool $allowed, ?string $permission): void
    {
        ChatContextAccessLog::create([
            'user_id' => $viewer->id,
            'chat_conversation_id' => $link->chat_conversation_id,
            'entity_type' => $link->entity_type,
            'entity_id' => $link->entity_id,
            'purpose' => $purpose,
            'allowed' => $allowed,
            'permission_checked' => $permission,
            'created_at' => now(),
        ]);
    }
}

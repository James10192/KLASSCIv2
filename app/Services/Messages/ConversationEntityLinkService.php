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
 * Source of truth for business entities linked to a conversation.
 *
 * A participant is NEVER used as a substitute for the linked entity. Legacy
 * action cards are linked only through their typed `kind` + numeric `id`.
 * Any link for which a relationship with a participant was not explicitly
 * verified remains `unknown_verify` and financial/enrolment actions stay locked.
 */
class ConversationEntityLinkService
{
    public const RELATIONS = [
        'concerns',
        'shared_by',
        'responsible_for',
        'parent_of',
        'administrative_contact',
        'unknown_verify',
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

        return ChatConversationEntityLink::firstOrCreate(
            [
                'source_message_id' => $message->id,
                'entity_type' => $kind,
                'entity_id' => (int) $id,
            ],
            [
                'chat_conversation_id' => $message->chat_conversation_id,
                'entity_label' => $resolved['label'],
                'relation_type' => 'unknown_verify',
                'confidence' => 'unknown',
                'source_type' => 'action_card',
                'related_user_id' => null,
                'required_view_permission' => $resolved['view_permission'],
                'required_detail_permission' => $resolved['detail_permission'],
                'created_by' => $message->sender_id,
                'metadata' => [
                    'entity_found' => $resolved['found'],
                    'source' => 'typed_action_card',
                ],
            ]
        );
    }

    /** @return Collection<int, array<string,mixed>> */
    public function forViewer(ChatConversation $conversation, User $viewer, string $purpose = 'message_context'): Collection
    {
        $conversation->loadMissing('entityLinks');

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
                'relation_label' => 'Lien à vérifier',
                'relation_verified' => false,
                'confidence' => 'unknown',
                'details' => [],
                'open_url' => null,
                'sensitive_actions_allowed' => false,
            ];
        }

        return $this->present($link, $viewer, 'business_card');
    }

    public function present(ChatConversationEntityLink $link, User $viewer, string $purpose): array
    {
        $viewAllowed = $this->allowed($viewer, $link->required_view_permission);
        $detailAllowed = $viewAllowed && $this->allowed($viewer, $link->required_detail_permission);

        $this->logAccess($viewer, $link, $purpose, $viewAllowed, $link->required_view_permission);

        if (! $viewAllowed) {
            return [
                'id' => $link->id,
                'type' => $link->entity_type,
                'entity_id' => $link->entity_id,
                'entity_label' => 'Données indisponibles',
                'label' => 'Données indisponibles',
                'relation' => $link->relation_type,
                'relation_label' => $this->relationLabel($link->relation_type),
                'relation_verified' => $link->isVerified(),
                'confidence' => $link->confidence,
                'can_view' => false,
                'can_view_details' => false,
                'details' => [],
                'open_url' => null,
                'sensitive_actions_allowed' => false,
            ];
        }

        $entity = $this->resolveEntity($link->entity_type, (int) $link->entity_id, $detailAllowed);
        $verified = $link->isVerified();

        return [
            'id' => $link->id,
            'type' => $link->entity_type,
            'entity_id' => $link->entity_id,
            'entity_label' => $entity['label'],
            'label' => $entity['label'],
            'relation' => $link->relation_type,
            'relation_label' => $this->relationLabel($link->relation_type),
            'relation_verified' => $verified,
            'confidence' => $link->confidence,
            'source_type' => $link->source_type,
            'related_user_id' => $link->related_user_id,
            'can_view' => true,
            'can_view_details' => $detailAllowed,
            'details' => $detailAllowed ? $entity['details'] : $this->nonSensitiveDetails($entity['details']),
            'open_url' => $entity['url'],
            'sensitive_actions_allowed' => $verified && $detailAllowed,
        ];
    }

    public function verify(
        ChatConversationEntityLink $link,
        User $actor,
        string $relation,
        ?int $relatedUserId = null
    ): ChatConversationEntityLink {
        if (! in_array($relation, self::RELATIONS, true) || $relation === 'unknown_verify') {
            throw new \InvalidArgumentException('Relation invalide pour une liaison vérifiée.');
        }

        $link->update([
            'relation_type' => $relation,
            'related_user_id' => $relatedUserId,
            'confidence' => 'verified',
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        return $link->fresh();
    }

    private function resolveEntity(string $type, int $id, bool $includeSensitive = true): array
    {
        return match ($type) {
            'inscription' => $this->resolveInscription($id, $includeSensitive),
            'paiement' => $this->resolvePaiement($id, $includeSensitive),
            default => [
                'found' => false,
                'label' => ucfirst(str_replace('_', ' ', $type)) . " #{$id} — Données indisponibles",
                'details' => [],
                'url' => null,
                'view_permission' => null,
                'detail_permission' => null,
            ],
        };
    }

    private function resolveInscription(int $id, bool $includeSensitive): array
    {
        $inscription = ESBTPInscription::with([
            'etudiant:id,nom,prenoms,matricule',
            'classe:id,name',
            'anneeUniversitaire:id,name,libelle',
        ])->find($id);

        if (! $inscription) {
            return [
                'found' => false,
                'label' => "Inscription #{$id} — Données indisponibles",
                'details' => ['status_label' => 'Données indisponibles'],
                'url' => null,
                'view_permission' => 'inscriptions.view',
                'detail_permission' => 'finances.etudiants.voir',
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

        if ($includeSensitive) {
            $details['finance_available'] = true;
        }

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
            'etudiant:id,nom,prenoms,matricule',
            'inscription:id,etudiant_id,classe_id',
            'inscription.classe:id,name',
        ])->find($id);

        if (! $paiement) {
            return [
                'found' => false,
                'label' => "Paiement #{$id} — Données indisponibles",
                'details' => ['status_label' => 'Données indisponibles'],
                'url' => null,
                'view_permission' => 'paiements.view',
                'detail_permission' => 'finances.etudiants.voir',
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

    private function nonSensitiveDetails(array $details): array
    {
        return collect($details)
            ->except(['amount', 'payment_mode', 'finance_available'])
            ->all();
    }

    private function allowed(User $viewer, ?string $permission): bool
    {
        return $permission === null || $permission === '' || $viewer->can($permission);
    }

    private function logAccess(
        User $viewer,
        ChatConversationEntityLink $link,
        string $purpose,
        bool $allowed,
        ?string $permission
    ): void {
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

    public function relationLabel(string $relation): string
    {
        return match ($relation) {
            'concerns' => 'Concerne cet interlocuteur',
            'shared_by' => 'Partagé par cet interlocuteur',
            'responsible_for' => 'Responsable du dossier',
            'parent_of' => 'Parent de l’étudiant',
            'administrative_contact' => 'Contact administratif',
            default => 'Relation à vérifier',
        };
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Résout l'étape suivante d'un workflow inscription.
 *
 * Mapping event → permission requise pour l'étape d'après. Anti-self-notif :
 * si l'acteur courant a déjà la permission, le système le rend visible côté UI
 * (modal "tu peux maintenant ...") au lieu d'envoyer une notif.
 */
class WorkflowNextStepResolver
{
    /**
     * Étapes suivantes par type d'event de workflow.
     *
     * Forme : [
     *     <event_type> => [
     *         'permission' => string|null,  // permission requise pour l'étape suivante
     *         'label'      => string|null,  // label humain
     *         'route'      => string|null,  // nom de route Laravel
     *         'param_key'  => string|null,  // clé recherchée dans le context (avec fallback sans `_id`)
     *     ],
     * ]
     */
    private const NEXT_STEPS = [
        'inscription.created' => [
            'permission' => 'paiements.create',
            'label'      => 'Encaisser un paiement pour cette inscription',
            'route'      => 'esbtp.paiements.create',
            'param_key'  => 'inscription_id',
        ],
        'paiement.created' => [
            'permission' => 'paiements.validate',
            'label'      => 'Valider ce paiement',
            'route'      => 'esbtp.paiements.show',
            'param_key'  => 'paiement',
        ],
        'paiement.validated' => [
            'permission' => 'inscriptions.validate',
            'label'      => 'Valider l\'inscription',
            'route'      => 'esbtp.inscriptions.show',
            'param_key'  => 'inscription',
        ],
        'inscription.validated' => [
            'permission' => null,
            'label'      => null,
            'route'      => null,
            'param_key'  => null,
        ],
    ];

    /**
     * Permission requise pour l'étape suivante du workflow donné.
     */
    public function nextPermission(string $eventType): ?string
    {
        return self::NEXT_STEPS[$eventType]['permission'] ?? null;
    }

    /**
     * Label humain de l'étape suivante.
     */
    public function nextLabel(string $eventType): ?string
    {
        return self::NEXT_STEPS[$eventType]['label'] ?? null;
    }

    /**
     * URL absolue de l'action suivante (déjà préfixée avec le contexte).
     */
    public function nextActionUrl(string $eventType, array $context): ?string
    {
        $step = self::NEXT_STEPS[$eventType] ?? null;
        if (!$step || !$step['route'] || !$step['param_key']) {
            return null;
        }

        $key = $step['param_key'];
        $fallbackKey = str_replace('_id', '', $key);
        $value = $context[$key] ?? $context[$fallbackKey] ?? null;
        if ($value === null) {
            return null;
        }

        return route($step['route'], [$key => $value]);
    }

    /**
     * Liste des destinataires de la notif (users avec la permission, hors actor).
     *
     * @return Collection<int, User>
     */
    public function recipients(string $eventType, ?int $actorId): Collection
    {
        $perm = $this->nextPermission($eventType);
        if (!$perm) {
            return collect();
        }

        return User::permission($perm)
            ->where('is_active', true)
            ->when($actorId, fn ($q) => $q->where('id', '!=', $actorId))
            ->get();
    }

    /**
     * True si l'acteur a lui-même la permission de l'étape suivante.
     * Dans ce cas le FE doit afficher un modal "tu peux maintenant X" plutôt
     * que d'envoyer une notif (anti-self-notif).
     */
    public function actorCanDoNextStep(string $eventType, User $actor): bool
    {
        $perm = $this->nextPermission($eventType);
        return $perm !== null && $actor->can($perm);
    }
}

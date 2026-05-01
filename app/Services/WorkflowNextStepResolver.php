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
     * Permission cible par type d'event de workflow.
     */
    private const NEXT_STEP_PERMISSION = [
        'inscription.created'        => 'paiements.create',     // créer/associer paiement
        'paiement.created'           => 'paiements.validate',   // valider paiement
        'paiement.validated'         => 'inscriptions.validate', // valider inscription
        'inscription.validated'      => null,                    // fin du chain
    ];

    private const NEXT_STEP_LABEL = [
        'inscription.created'   => 'Encaisser un paiement pour cette inscription',
        'paiement.created'      => 'Valider ce paiement',
        'paiement.validated'    => 'Valider l\'inscription',
        'inscription.validated' => null,
    ];

    private const NEXT_STEP_ROUTE = [
        'inscription.created'   => ['name' => 'esbtp.paiements.create', 'param_key' => 'inscription_id'],
        'paiement.created'      => ['name' => 'esbtp.paiements.show',  'param_key' => 'paiement'],
        'paiement.validated'    => ['name' => 'esbtp.inscriptions.show', 'param_key' => 'inscription'],
        'inscription.validated' => null,
    ];

    /**
     * Permission requise pour l'étape suivante du workflow donné.
     */
    public function nextPermission(string $eventType): ?string
    {
        return self::NEXT_STEP_PERMISSION[$eventType] ?? null;
    }

    /**
     * Label humain de l'étape suivante.
     */
    public function nextLabel(string $eventType): ?string
    {
        return self::NEXT_STEP_LABEL[$eventType] ?? null;
    }

    /**
     * URL absolue de l'action suivante (déjà préfixée avec le contexte).
     */
    public function nextActionUrl(string $eventType, array $context): ?string
    {
        $route = self::NEXT_STEP_ROUTE[$eventType] ?? null;
        if (!$route) {
            return null;
        }
        $key = $route['param_key'];
        if (!isset($context[$key]) && !isset($context[str_replace('_id', '', $key)])) {
            return null;
        }
        $value = $context[$key] ?? $context[str_replace('_id', '', $key)];
        return route($route['name'], [$route['param_key'] === 'inscription_id'
            ? 'inscription_id' : ($route['param_key'])  => $value]);
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

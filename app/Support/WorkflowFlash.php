<?php

namespace App\Support;

use App\Events\WorkflowStepCompleted;
use App\Services\WorkflowNextStepResolver;

/**
 * Helper pour fire l'event workflow + flash un payload "next step" en session
 * QUAND l'acteur courant a déjà la permission de l'étape suivante (anti-self-notif).
 *
 * Usage : WorkflowFlash::dispatch('inscription.created', $user, ['inscription_id' => $i->id]);
 *
 * Le layout app.blade.php peut lire session('workflow_next_step') pour afficher
 * un modal "Tu peux maintenant X → [bouton CTA]" sans envoyer de notif au user.
 */
class WorkflowFlash
{
    public static function dispatch(string $type, $actor, array $context = []): void
    {
        WorkflowStepCompleted::dispatch($type, $actor, $context);

        $resolver = app(WorkflowNextStepResolver::class);
        if ($resolver->actorCanDoNextStep($type, $actor)) {
            session()->flash('workflow_next_step', [
                'type'  => $type,
                'label' => $resolver->nextLabel($type),
                'url'   => $resolver->nextActionUrl($type, $context),
            ]);
        }
    }
}

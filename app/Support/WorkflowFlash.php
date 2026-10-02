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
 * Le layout le lit (layouts/partials/etape-suivante) : un simple bandeau quand
 * l'etape se fait sur la page ou l'on arrive, sinon une fenetre qui mene a la
 * bonne page. Ni l'un ni l'autre n'a l'allure d'une confirmation : on a cru,
 * en cliquant « Valider ce paiement » dans l'ancienne fenetre, avoir valide.
 */
class WorkflowFlash
{
    public static function dispatch(string $type, $actor, array $context = []): void
    {
        WorkflowStepCompleted::dispatch($type, $actor, $context);

        $resolver = app(WorkflowNextStepResolver::class);

        // Une action faite sans recharger la page (AJAX, application mobile)
        // ne laisse rien en session : le message ressurgirait plus tard, sur
        // une autre page, a propos d'une etape deja passee.
        $requete = request();
        if ($requete && ($requete->expectsJson() || $requete->ajax())) {
            return;
        }

        if ($resolver->actorCanDoNextStep($type, $actor)) {
            session()->flash('workflow_next_step', [
                'type'  => $type,
                'label' => $resolver->nextLabel($type),
                'url'   => $resolver->nextActionUrl($type, $context),
            ]);
        }
    }
}

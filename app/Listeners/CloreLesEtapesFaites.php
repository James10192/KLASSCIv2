<?php

namespace App\Listeners;

use App\Events\WorkflowStepCompleted;
use App\Notifications\WorkflowNextStepNotification;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Une étape faite clôt l'avis qui la demandait, chez tout le monde.
 *
 * Les avis « étape suivante » (WorkflowNextStepNotification) restaient « à
 * faire » dans /messages, et dans le badge du menu, jusqu'à ce que chacun les
 * ouvre — y compris quand un collègue avait déjà fait l'étape. On croyait
 * avoir un paiement à valider qui l'était déjà.
 *
 * Synchrone : le badge doit être juste dès la page suivante.
 */
class CloreLesEtapesFaites
{
    /**
     * Quand l'étape de gauche arrive, les avis de droite sont clos, si leur
     * contexte désigne le même objet. Les clés de contexte sont celles que
     * posent les contrôleurs (« inscription » et « inscription_id » coexistent).
     *
     * @var array<string, array<int, array{0:string, 1:string, 2:string}>>
     *      événement => [type d'avis clos, clé dans l'avis, clé dans l'événement]
     */
    private const CLOTURES = [
        'paiement.created' => [['inscription.created', 'inscription_id', 'inscription_id']],
        'paiement.validated' => [['paiement.created', 'paiement', 'paiement']],
        'inscription.validated' => [
            ['paiement.validated', 'inscription', 'inscription'],
            ['inscription.created', 'inscription_id', 'inscription'],
        ],
    ];

    public function handle(WorkflowStepCompleted $event): void
    {
        foreach (self::CLOTURES[$event->type] ?? [] as [$typeClos, $cleAvis, $cleEvenement]) {
            $objet = $event->context[$cleEvenement] ?? null;
            if ($objet === null) {
                continue;
            }

            DatabaseNotification::query()
                ->where('type', WorkflowNextStepNotification::class)
                ->whereNull('read_at')
                ->where('data->type', $typeClos)
                ->where('data->context->'.$cleAvis, (int) $objet)
                ->update(['read_at' => now()]);
        }
    }
}

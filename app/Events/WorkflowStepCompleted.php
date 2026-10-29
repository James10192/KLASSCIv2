<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event générique pour chaque étape complétée du workflow inscription→paiement.
 *
 * Type values: 'inscription.created', 'paiement.created', 'paiement.validated',
 * 'inscription.validated'.
 *
 * Context: {inscription_id?, paiement?, etudiant_id?, ...} — sérialisé en
 * payload de la notif et utilisé par WorkflowNextStepResolver pour construire
 * l'URL de l'action suivante.
 */
class WorkflowStepCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly User $actor,
        public readonly array $context = [],
    ) {
    }
}

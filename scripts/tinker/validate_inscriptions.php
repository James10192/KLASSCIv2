<?php

use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionWorkflowHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Validation de masse des inscriptions issues de l'import legacy.
 *
 * - Passe toutes les inscriptions en statut "active" + workflow_step "etudiant_cree"
 * - Ajoute une entrée d'historique "creation_etudiant" si elle n'existe pas encore
 *
 * À exécuter via :
 * php artisan tinker --execute="require 'scripts/tinker/validate_inscriptions.php';"
 */

DB::transaction(function (): void {
    $userId = User::query()->orderBy('id')->value('id');

    if (!$userId) {
        throw new RuntimeException("Aucun utilisateur trouvé pour historiser le workflow.");
    }

    $now = now();

    ESBTPInscription::chunkById(200, function ($inscriptions) use ($userId, $now): void {
        foreach ($inscriptions as $inscription) {
            $previousStep = $inscription->workflow_step;

            $inscription->forceFill([
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'date_validation' => $inscription->date_validation ?: $now->toDateString(),
                'validated_by' => $inscription->validated_by ?: $userId,
            ])->save();

            if (!ESBTPInscriptionWorkflowHistory::where('inscription_id', $inscription->id)
                ->where('action', 'creation_etudiant')
                ->exists()) {
                ESBTPInscriptionWorkflowHistory::create([
                    'inscription_id' => $inscription->id,
                    'etape_from' => $previousStep,
                    'etape_to' => 'etudiant_cree',
                    'action' => 'creation_etudiant',
                    'user_id' => $userId,
                    'action_timestamp' => now(),
                    'commentaires' => 'Validation automatique post-import legacy',
                    'metadata' => ['script' => 'legacy-import'],
                    'ip_address' => null,
                    'user_agent' => 'tinker-script',
                ]);
            }
        }
    });
});


<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ESBTPInscriptionWorkflowHistory;
use Carbon\Carbon;

class ESBTPInscriptionWorkflowSeeder extends Seeder
{
    public function run()
    {
        // Exemple d'utilisation :
        // $inscription = ...; $userId = ...;
        // $steps = ['creation', 'documents_complets', ...];
        //
        // foreach ($steps as $index => $step) { ... }

        // À adapter selon votre logique métier réelle
        $inscriptionId = 1; // À remplacer par l'ID réel
        $userId = 1; // À remplacer par l'ID réel
        $steps = ['creation', 'documents_complets', 'en_validation', 'valide', 'etudiant_cree'];
        $actions = [
            'creation' => 'creation',
            'documents_complets' => 'documents_soumis',
            'en_validation' => 'mise_en_validation',
            'valide' => 'validation',
            'etudiant_cree' => 'creation_etudiant',
        ];
        $etapeFrom = null;
        foreach ($steps as $index => $step) {
            $actionDate = Carbon::now()->addMinutes($index);
            ESBTPInscriptionWorkflowHistory::create([
                'inscription_id' => $inscriptionId,
                'etape_from' => $etapeFrom,
                'etape_to' => $step,
                'action' => $actions[$step],
                'user_id' => $userId,
                'action_timestamp' => $actionDate,
                'commentaires' => 'Progression automatique vers: ' . $step,
                'metadata' => [
                    'seeded' => true,
                    'step_number' => $index + 1,
                    'total_steps' => count($steps),
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder Script',
            ]);
            $etapeFrom = $step;
        }
    }
}

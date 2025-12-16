<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST RELIQUATS - AVANT REINSCRIPTION ===\n\n";

try {
    // 1. Prendre un étudiant qui a fait sa réinscription récemment
    $etudiant = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function($q) {
        $q->where('type_inscription', 'reinscription');
    })->with(['inscriptions.anneeUniversitaire'])->first();

    if (!$etudiant) {
        echo "❌ Aucun étudiant avec réinscription trouvé\n";
        exit;
    }

    echo "👤 Étudiant testé: {$etudiant->nom} {$etudiant->prenoms} (ID: {$etudiant->id})\n\n";

    // 2. Récupérer ses inscriptions
    $inscriptions = $etudiant->inscriptions()->with('anneeUniversitaire')->orderBy('created_at')->get();

    echo "📚 INSCRIPTIONS TROUVÉES:\n";
    foreach ($inscriptions as $inscription) {
        echo "  - ID: {$inscription->id} | Année: {$inscription->anneeUniversitaire->name} | Type: {$inscription->type_inscription}\n";
    }
    echo "\n";

    // 3. Pour chaque inscription, vérifier les frais souscrits
    foreach ($inscriptions as $inscription) {
        echo "🔍 ANALYSE INSCRIPTION {$inscription->id} ({$inscription->anneeUniversitaire->name}):\n";

        // Frais souscrits
        $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->with('fraisConfiguration')
            ->get();

        echo "  📋 Frais souscrits: " . $fraisSouscrits->count() . "\n";

        foreach ($fraisSouscrits as $frais) {
            $fraisName = isset($frais->fraisConfiguration->name) ? $frais->fraisConfiguration->name : 'N/A';
            echo "    - {$fraisName}: {$frais->amount} FCFA\n";
        }

        // Paiements
        $paiements = \App\Models\ESBTPPaiement::where('inscription_id', $inscription->id)
            ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
            ->get();

        echo "  💰 Paiements validés: " . $paiements->count() . " (Total: " . $paiements->sum('montant') . " FCFA)\n";

        foreach ($paiements as $paiement) {
            echo "    - {$paiement->montant} FCFA | Status: {$paiement->status}\n";
        }

        // Reliquats VERS cette inscription
        $reliquats = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)->get();
        echo "  🔄 Reliquats reçus: " . $reliquats->count() . " (Total: " . $reliquats->sum('montant_reliquat') . " FCFA)\n";

        echo "\n";
    }

    // 4. Test spécifique: Que se passerait-il pour les reliquats?
    if ($inscriptions->count() >= 2) {
        $inscriptionSource = $inscriptions->first(); // Plus ancienne
        $inscriptionDest = $inscriptions->last();   // Plus récente

        echo "🧪 SIMULATION RELIQUATS:\n";
        echo "  Source: {$inscriptionSource->anneeUniversitaire->name} (ID: {$inscriptionSource->id})\n";
        echo "  Destination: {$inscriptionDest->anneeUniversitaire->name} (ID: {$inscriptionDest->id})\n\n";

        $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionSource->id)
            ->where('is_active', true)
            ->get();

        if ($fraisSouscrits->count() == 0) {
            echo "  ❌ PROBLÈME: Aucun frais souscrit trouvé pour l'inscription source!\n";
            echo "  ➡️  C'est pourquoi aucun reliquat n'a été créé.\n\n";

            // Suggérer des solutions
            echo "  🔧 SOLUTIONS:\n";
            echo "  1. Vérifier si des frais existent dans ESBTPFrais pour cette inscription\n";
            echo "  2. Créer les ESBTPFraisSubscription manquantes\n";
            echo "  3. Ou modifier le code pour utiliser ESBTPFrais directement\n\n";
        } else {
            echo "  ✅ Frais souscrits trouvés: {$fraisSouscrits->count()}\n";

            foreach ($fraisSouscrits as $frais) {
                $montantPaye = \App\Models\ESBTPPaiement::where('inscription_id', $inscriptionSource->id)
                    ->where('frais_category_id', $frais->frais_category_id)
                    ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
                    ->sum('montant');

                $reliquat = $frais->amount - $montantPaye;

                echo "    - Frais: {$frais->amount} | Payé: {$montantPaye} | Reliquat: {$reliquat} FCFA\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
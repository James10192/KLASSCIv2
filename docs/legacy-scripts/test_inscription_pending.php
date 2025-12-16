<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST INSCRIPTIONS EN ATTENTE ===\n\n";

try {
    // 1. Chercher des inscriptions en attente
    $inscriptionsPending = \App\Models\ESBTPInscription::where('status', 'pending')
        ->with(['etudiant', 'anneeUniversitaire', 'classe'])
        ->take(3)
        ->get();

    echo "📚 Inscriptions en attente trouvées: {$inscriptionsPending->count()}\n\n";

    foreach ($inscriptionsPending as $inscription) {
        echo "🔍 INSCRIPTION ID: {$inscription->id}\n";
        echo "  👤 Étudiant: {$inscription->etudiant->nom} {$inscription->etudiant->prenoms}\n";
        echo "  📅 Année: {$inscription->anneeUniversitaire->name}\n";
        echo "  📚 Classe: {$inscription->classe->name}\n";

        // Vérifier les frais souscrits
        $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->get();
        echo "  💰 Frais souscrits: {$fraisSouscrits->count()}\n";

        if ($fraisSouscrits->count() == 0) {
            echo "  ❌ PROBLÈME: Aucun frais souscrit!\n";

            // Vérifier les frais obligatoires qui devraient être créés
            $fraisObligatoires = \App\Models\ESBTPFraisCategory::where('is_mandatory', true)
                ->where('is_active', true)
                ->get();

            echo "  📋 Frais obligatoires configurés: {$fraisObligatoires->count()}\n";
            foreach ($fraisObligatoires as $frais) {
                echo "    - {$frais->name} (obligatoire)\n";
            }

            // Vérifier s'il y a des configurations pour cette classe
            $classe = $inscription->classe;
            if ($classe) {
                $configs = \App\Models\ESBTPFraisConfiguration::where('filiere_id', $classe->filiere_id)
                    ->where('niveau_id', $classe->niveau_etude_id)
                    ->where('is_active', true)
                    ->get();

                echo "  ⚙️ Configurations de frais pour cette classe: {$configs->count()}\n";
            }
        } else {
            echo "  ✅ Frais souscrits trouvés:\n";
            foreach ($fraisSouscrits as $frais) {
                echo "    - {$frais->amount} FCFA (Category ID: {$frais->frais_category_id})\n";
            }
        }

        echo "\n";
    }

    // 2. Test spécifique pour une inscription sans frais
    $inscriptionSansFrais = $inscriptionsPending->first(function($i) {
        return \App\Models\ESBTPFraisSubscription::where('inscription_id', $i->id)->count() == 0;
    });

    if ($inscriptionSansFrais) {
        echo "🧪 TEST: Simulation création des frais manquants\n";
        echo "  📋 Inscription ID: {$inscriptionSansFrais->id}\n";

        $service = app(\App\Services\ESBTPInscriptionService::class);
        $fees = $service->generateFeesForInscription($inscriptionSansFrais, [], 'affecté');

        echo "  🔧 Frais générés par le service: " . count($fees) . "\n";
        foreach ($fees as $fee) {
            echo "    - {$fee['description']}: {$fee['amount']} FCFA ({$fee['type']})\n";
        }

        // Vérifier si maintenant il y a des souscriptions
        $nouvelleSouscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionSansFrais->id)->count();
        echo "  📊 Souscriptions créées après generateFeesForInscription: {$nouvelleSouscriptions}\n";

        if ($nouvelleSouscriptions == 0) {
            echo "  ❌ CONFIRMATION: generateFeesForInscription ne crée PAS les ESBTPFraisSubscription pour les frais obligatoires!\n";
        } else {
            echo "  ✅ Des souscriptions ont été créées\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";
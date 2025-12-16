<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSTIC COMPLET FRAIS ===\n\n";

try {
    // 1. Prendre une inscription récente
    $inscription = \App\Models\ESBTPInscription::with(['etudiant', 'anneeUniversitaire', 'classe'])->latest()->first();

    if (!$inscription) {
        echo "❌ Aucune inscription trouvée\n";
        exit;
    }

    echo "🔍 INSCRIPTION TESTÉE:\n";
    echo "  ID: {$inscription->id}\n";
    echo "  Étudiant: {$inscription->etudiant->nom} {$inscription->etudiant->prenoms}\n";
    echo "  Année: {$inscription->anneeUniversitaire->name}\n";
    echo "  Classe: {$inscription->classe->name}\n";
    echo "  Status: {$inscription->status}\n\n";

    // 2. Vérifier les frais souscrits
    $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->get();
    echo "💰 FRAIS SOUSCRITS: {$fraisSouscrits->count()}\n";

    $totalSouscrit = 0;
    foreach ($fraisSouscrits as $frais) {
        $category = \App\Models\ESBTPFraisCategory::find($frais->frais_category_id);
        $categoryName = $category ? $category->name : "Catégorie inconnue";
        echo "  - {$categoryName}: {$frais->amount} FCFA\n";
        $totalSouscrit += $frais->amount;
    }
    echo "  TOTAL SOUSCRIT: {$totalSouscrit} FCFA\n\n";

    // 3. Vérifier les frais obligatoires qui devraient être là
    $fraisObligatoires = \App\Models\ESBTPFraisCategory::where('is_mandatory', true)
        ->where('is_active', true)
        ->get();

    echo "📋 FRAIS OBLIGATOIRES CONFIGURÉS: {$fraisObligatoires->count()}\n";
    foreach ($fraisObligatoires as $frais) {
        $souscrit = $fraisSouscrits->where('frais_category_id', $frais->id)->first();
        $status = $souscrit ? "✅ SOUSCRIT ({$souscrit->amount} FCFA)" : "❌ MANQUANT";
        echo "  - {$frais->name}: {$status}\n";
    }
    echo "\n";

    // 4. Test de generateFeesForInscription
    echo "🧪 TEST generateFeesForInscription:\n";
    $service = app(\App\Services\ESBTPInscriptionService::class);

    $beforeCount = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
    echo "  Souscriptions AVANT: {$beforeCount}\n";

    $fees = $service->generateFeesForInscription($inscription, [], 'affecté');

    $afterCount = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
    echo "  Souscriptions APRÈS: {$afterCount}\n";
    echo "  Frais générés (retour): " . count($fees) . "\n";

    foreach ($fees as $fee) {
        echo "    - {$fee['description']}: {$fee['amount']} FCFA (Type: {$fee['type']})\n";
    }

    // 5. Identifier le problème
    echo "\n🐛 DIAGNOSTIC:\n";
    $mandatoryMissing = $fraisObligatoires->filter(function($cat) use ($fraisSouscrits) {
        return !$fraisSouscrits->where('frais_category_id', $cat->id)->first();
    });

    if ($mandatoryMissing->count() > 0) {
        echo "  ❌ PROBLÈME: {$mandatoryMissing->count()} frais obligatoires manquants\n";
        echo "  ➡️  La méthode generateFeesForInscription génère les frais mais ne crée PAS les ESBTPFraisSubscription pour les obligatoires\n\n";

        echo "  🔧 SOLUTION NÉCESSAIRE:\n";
        echo "  1. Modifier generateFeesForInscription pour créer aussi les souscriptions obligatoires\n";
        echo "  2. Ou exécuter un script de correction pour les inscriptions existantes\n";
    } else {
        echo "  ✅ Tous les frais obligatoires sont souscrits\n";
    }

} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
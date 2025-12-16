<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST DU FIX DES FRAIS D'INSCRIPTION ===\n\n";

try {
    // Prendre une inscription sans frais souscrits
    // Prendre l'inscription récente sans frais
    $inscription = \App\Models\ESBTPInscription::where('id', 2467)
        ->with(['etudiant', 'anneeUniversitaire', 'classe'])
        ->first();

    if (!$inscription) {
        echo "❌ Aucune inscription sans frais trouvée\n";
        exit;
    }

    echo "🔍 INSCRIPTION À TESTER:\n";
    echo "  ID: {$inscription->id}\n";
    echo "  Étudiant: {$inscription->etudiant->nom} {$inscription->etudiant->prenoms}\n";
    echo "  Année: {$inscription->anneeUniversitaire->name}\n";
    echo "  Classe: {$inscription->classe->name}\n";
    echo "  Status: {$inscription->status}\n\n";

    // 1. Vérifier l'état AVANT
    $fraisAvant = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
    echo "💰 FRAIS SOUSCRITS AVANT: {$fraisAvant}\n\n";

    // 2. Exécuter la méthode corrigée
    echo "🔧 EXÉCUTION DU FIX:\n";
    $service = app(\App\Services\ESBTPInscriptionService::class);
    $generatedFees = $service->generateFeesForInscription($inscription, [], 'affecté');

    echo "  📋 Frais générés: " . count($generatedFees) . "\n";
    foreach ($generatedFees as $fee) {
        echo "    - {$fee['description']}: {$fee['amount']} FCFA ({$fee['type']})\n";
    }

    // 3. Maintenant appeler saveGeneratedFeesAsSubscriptions directement pour tester
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('saveGeneratedFeesAsSubscriptions');
    $method->setAccessible(true);
    $method->invoke($service, $inscription, $generatedFees);

    // 4. Vérifier l'état APRÈS
    $fraisApres = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->count();
    echo "\n💰 FRAIS SOUSCRITS APRÈS: {$fraisApres}\n";

    $souscriptions = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)->get();
    $totalSouscrit = 0;
    echo "  📋 DÉTAIL DES SOUSCRIPTIONS:\n";
    foreach ($souscriptions as $souscription) {
        $category = \App\Models\ESBTPFraisCategory::find($souscription->frais_category_id);
        $categoryName = $category ? $category->name : "Catégorie inconnue";
        echo "    - {$categoryName}: {$souscription->amount} FCFA\n";
        $totalSouscrit += $souscription->amount;
    }
    echo "  💰 TOTAL SOUSCRIT: {$totalSouscrit} FCFA\n\n";

    // 5. Test de ce qui sera affiché dans inscriptions.show
    echo "🖥️ CE QUI SERA AFFICHÉ DANS inscriptions.show:\n";
    $subscriptions = \App\Models\ESBTPFraisSubscription::getActiveSubscriptions($inscription->id);
    echo "  📊 Souscriptions actives récupérées: {$subscriptions->count()}\n";

    // 6. Test de ce qui sera affiché dans paiement.index
    echo "\n💳 CE QUI SERA AFFICHÉ DANS paiement.index:\n";
    $paiementSubscriptions = \App\Models\ESBTPFraisSubscription::where('is_active', true)
        ->where('inscription_id', $inscription->id)
        ->get();
    echo "  📊 Souscriptions pour paiements: {$paiementSubscriptions->count()}\n";

    // 7. Résultat
    echo "\n✅ RÉSULTAT DU TEST:\n";
    if ($fraisApres > $fraisAvant) {
        $nouvelles = $fraisApres - $fraisAvant;
        echo "  🎉 FIX RÉUSSI ! {$nouvelles} nouvelles souscriptions créées\n";
        echo "  ➡️  Maintenant les nouvelles inscriptions auront automatiquement leurs frais souscrits\n";
        echo "  ➡️  Les reliquats fonctionneront correctement lors des réinscriptions\n";
    } else {
        echo "  ❌ Le fix n'a pas fonctionné ou pas de frais à créer\n";
    }

} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
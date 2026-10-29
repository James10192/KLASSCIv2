<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSTIC RELIQUATS SIMPLE ===\n\n";

try {
    // 1. Vérifier un étudiant avec réinscription
    $etudiant = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function($q) {
        $q->where('type_inscription', 'reinscription');
    })->first();

    if (!$etudiant) {
        echo "❌ Aucun étudiant avec réinscription trouvé\n";
        exit;
    }

    echo "👤 Étudiant: {$etudiant->nom} {$etudiant->prenoms} (ID: {$etudiant->id})\n\n";

    // 2. Ses inscriptions
    $inscriptions = $etudiant->inscriptions()->with('anneeUniversitaire')->orderBy('created_at')->get();

    echo "📚 Inscriptions ({$inscriptions->count()}):\n";
    foreach ($inscriptions as $inscription) {
        echo "  - ID: {$inscription->id} | Année: {$inscription->anneeUniversitaire->name} | Type: {$inscription->type_inscription}\n";
    }
    echo "\n";

    if ($inscriptions->count() >= 2) {
        $inscriptionAncienne = $inscriptions->first();
        $inscriptionRecente = $inscriptions->last();

        echo "🔍 ANALYSE DE L'ANCIENNE INSCRIPTION (ID: {$inscriptionAncienne->id}):\n\n";

        // Vérifier ESBTPFraisSubscription
        $count1 = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionAncienne->id)->count();
        echo "  📋 ESBTPFraisSubscription: {$count1} entrées\n";

        // Vérifier les frais en détail
        $fraisDetails = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscriptionAncienne->id)->get();
        $totalFrais = $fraisDetails->sum('amount');
        echo "  💰 Total frais souscrits: {$totalFrais} FCFA\n";

        // Vérifier les paiements
        $count3 = \App\Models\ESBTPPaiement::where('inscription_id', $inscriptionAncienne->id)->count();
        $sumPaiements = \App\Models\ESBTPPaiement::where('inscription_id', $inscriptionAncienne->id)
            ->whereIn('status', ['validé', 'validated', 'valide', 'confirmé', 'confirmed'])
            ->sum('montant');
        echo "  💳 Paiements: {$count3} entrées (Total validé: {$sumPaiements} FCFA)\n";

        // Vérifier les reliquats créés
        $count4 = \App\Models\ESBTPReliquatDetail::where('inscription_source_id', $inscriptionAncienne->id)->count();
        echo "  🔄 Reliquats créés DEPUIS cette inscription: {$count4}\n";

        $count5 = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscriptionRecente->id)->count();
        echo "  🔄 Reliquats reçus PAR la nouvelle inscription: {$count5}\n\n";

        // Le problème probable
        echo "🐛 DIAGNOSTIC:\n";
        if ($count1 == 0) {
            echo "  ❌ PROBLÈME MAJEUR: Aucun frais souscrit trouvé pour l'inscription {$inscriptionAncienne->id}\n";
            echo "  ➡️  C'est pourquoi aucun reliquat n'a pu être calculé!\n\n";

            echo "  🔧 SOLUTIONS:\n";
            echo "  1. Créer les frais manquants pour cette inscription\n";
            echo "  2. Modifier le code de reliquats pour utiliser une autre source\n\n";
        } else {
            echo "  ✅ Des frais existent, le problème est ailleurs\n\n";
        }

        // Vérifier la configuration des frais pour cette classe/année
        $classe = $inscriptionAncienne->classe;
        if ($classe) {
            $fraisConfigs = \App\Models\ESBTPFraisConfiguration::where('classe_id', $classe->id)
                ->orWhere('niveau_id', $classe->niveau_id)
                ->orWhere('filiere_id', $classe->filiere_id)
                ->count();
            echo "  ⚙️  Configurations de frais pour cette classe/niveau/filière: {$fraisConfigs}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
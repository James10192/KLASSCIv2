<?php

/**
 * Script de test pour vérifier la création de reliquats lors de la réinscription
 * Usage: php test_reliquats_creation.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;

// Charger l'application Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test de création de reliquats ===\n\n";

// Rechercher l'étudiant ABOUANOU KOUAME SIESMO MELCHISEDECK
$etudiant = ESBTPEtudiant::where('matricule', 'MESBTP24-0260')->first();

if (!$etudiant) {
    echo "❌ Étudiant avec matricule MESBTP24-0260 non trouvé\n";
    exit(1);
}

echo "✅ Étudiant trouvé: {$etudiant->nom} {$etudiant->prenoms} (ID: {$etudiant->id})\n\n";

// Lister ses inscriptions
$inscriptions = $etudiant->inscriptions()->with('anneeUniversitaire')->orderBy('created_at', 'desc')->get();
echo "📋 Inscriptions de l'étudiant:\n";
foreach ($inscriptions as $inscription) {
    echo "  - ID {$inscription->id}: {$inscription->anneeUniversitaire->name} (Status: {$inscription->status})\n";
}

// Vérifier s'il y a des reliquats existants
$reliquatsEntrants = ESBTPReliquatDetail::whereIn('inscription_destination_id', $inscriptions->pluck('id'))->get();
$reliquatsSortants = ESBTPReliquatDetail::whereIn('inscription_source_id', $inscriptions->pluck('id'))->get();

echo "\n📊 Reliquats existants:\n";
echo "  - Reliquats entrants: " . $reliquatsEntrants->count() . "\n";
echo "  - Reliquats sortants: " . $reliquatsSortants->count() . "\n";

if ($reliquatsEntrants->count() > 0) {
    echo "\n📥 Détail des reliquats entrants:\n";
    foreach ($reliquatsEntrants as $reliquat) {
        echo "  - ID {$reliquat->id}: {$reliquat->montant_reliquat} FCFA (Statut: {$reliquat->statut})\n";
        echo "    Source: Inscription {$reliquat->inscription_source_id}\n";
        echo "    Destination: Inscription {$reliquat->inscription_destination_id}\n";
    }
}

if ($reliquatsSortants->count() > 0) {
    echo "\n📤 Détail des reliquats sortants:\n";
    foreach ($reliquatsSortants as $reliquat) {
        echo "  - ID {$reliquat->id}: {$reliquat->montant_reliquat} FCFA (Statut: {$reliquat->statut})\n";
        echo "    Source: Inscription {$reliquat->inscription_source_id}\n";
        echo "    Destination: Inscription {$reliquat->inscription_destination_id}\n";
    }
}

// Analyser la situation financière de la dernière inscription
if ($inscriptions->count() > 0) {
    $derniereInscription = $inscriptions->first();
    echo "\n💰 Analyse financière de la dernière inscription (ID {$derniereInscription->id}):\n";

    // Frais souscrits
    $fraisSouscrits = ESBTPFraisSubscription::where('inscription_id', $derniereInscription->id)
        ->where('is_active', true)
        ->with('fraisConfiguration')
        ->get();

    echo "  - Frais souscrits: " . $fraisSouscrits->count() . "\n";

    $totalAttendu = 0;
    $totalPaye = 0;

    foreach ($fraisSouscrits as $frais) {
        $montantAttendu = $frais->amount;
        $montantPaye = ESBTPPaiement::where('inscription_id', $derniereInscription->id)
            ->where('frais_category_id', $frais->frais_category_id)
            ->where('status', 'validé')
            ->sum('montant');

        $solde = $montantAttendu - $montantPaye;

        echo "    • {$frais->fraisConfiguration->name}: {$montantAttendu} FCFA attendu, {$montantPaye} FCFA payé, {$solde} FCFA de solde\n";

        $totalAttendu += $montantAttendu;
        $totalPaye += $montantPaye;
    }

    $soldeTotal = $totalAttendu - $totalPaye;
    echo "  - TOTAL: {$totalAttendu} FCFA attendu, {$totalPaye} FCFA payé, {$soldeTotal} FCFA de solde\n";

    if ($soldeTotal > 0) {
        echo "  ⚠️  Il y a {$soldeTotal} FCFA d'impayés qui devraient créer un reliquat lors de la réinscription\n";
    } else {
        echo "  ✅ Aucun impayé sur cette inscription\n";
    }
}

echo "\n=== Fin du test ===\n";
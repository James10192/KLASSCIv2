<?php

/**
 * Script de Diagnostic Matricules KLASSCI
 *
 * Utilisation sur serveur distant :
 * php artisan tinker < diagnose-matricule.php > matricule-diagnostic-report.txt
 *
 * OU directement :
 * php diagnose-matricule.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatriculeConfig;
use App\Models\ESBTPEtablissement;
use App\Models\ESBTPNiveauEtude;
use App\Support\MatriculeGenerator;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║          DIAGNOSTIC MATRICULES - KLASSCI ESBTP ABIDJAN          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// 1. VÉRIFICATION CONFIGURATION MATRICULE
// ============================================================================
echo "📋 ÉTAPE 1 : VÉRIFICATION CONFIGURATION MATRICULE\n";
echo str_repeat("=", 70) . "\n";

$etablissement = ESBTPEtablissement::where('code', 'ESBTP-ABIDJAN')->first();

if (!$etablissement) {
    echo "❌ ERREUR CRITIQUE : Établissement ESBTP-ABIDJAN introuvable !\n";
    exit(1);
}

echo "✅ Établissement trouvé : {$etablissement->nom} (ID: {$etablissement->id})\n\n";

$configs = ESBTPMatriculeConfig::where('etablissement_id', $etablissement->id)->get();

if ($configs->isEmpty()) {
    echo "❌ ERREUR CRITIQUE : Aucune configuration matricule trouvée !\n";
    echo "   Exécutez : php artisan db:seed --class=ESBTPAbidjanMatriculeConfigSeeder\n";
    exit(1);
}

echo "Configuration(s) active(s) :\n";
foreach ($configs as $config) {
    echo "  • Niveau : {$config->niveau_etude_code} ({$config->niveau_etude_name})\n";
    echo "    Pattern : {$config->pattern}\n";
    echo "    Préfixe : " . ($config->prefixe ?: 'Aucun') . "\n";
    echo "    Année format : {$config->annee_format} chiffres\n";
    echo "    Numéro digits : {$config->numero_digits} chiffres\n";
    echo "    Exemples :\n";
    $exemples = $config->genererExemples();
    echo "      - Masculin : {$exemples['masculin']}\n";
    echo "      - Féminin  : {$exemples['feminin']}\n";
    echo "    Actif : " . ($config->is_active ? 'Oui' : 'Non') . "\n";
    echo "\n";
}

// ============================================================================
// 2. ANALYSE DES MATRICULES EXISTANTS
// ============================================================================
echo "\n📊 ÉTAPE 2 : ANALYSE MATRICULES EXISTANTS\n";
echo str_repeat("=", 70) . "\n";

$anneeActuelle = date('Y');
$annee2chiffres = substr($anneeActuelle, -2);
$annee4chiffres = $anneeActuelle;

// Pour chaque configuration
foreach ($configs as $config) {
    echo "\n🔍 Niveau : {$config->niveau_etude_code}\n";
    echo str_repeat("-", 70) . "\n";

    $anneeFormatee = $config->annee_format == 2 ? $annee2chiffres : $annee4chiffres;

    // Patterns à chercher
    $patternMasculin = 'M' . ($config->prefixe ?: '') . $config->etablissement_code . $anneeFormatee . '-%';
    $patternFeminin = 'F' . ($config->prefixe ?: '') . $config->etablissement_code . $anneeFormatee . '-%';

    echo "Patterns recherchés :\n";
    echo "  • Masculin : {$patternMasculin}\n";
    echo "  • Féminin  : {$patternFeminin}\n\n";

    // Compter les étudiants par genre
    $countMasculin = ESBTPEtudiant::where('matricule', 'LIKE', $patternMasculin)
        ->whereNull('deleted_at')
        ->count();
    $countFeminin = ESBTPEtudiant::where('matricule', 'LIKE', $patternFeminin)
        ->whereNull('deleted_at')
        ->count();
    $countSoftDeleted = ESBTPEtudiant::where(function($q) use ($patternMasculin, $patternFeminin) {
        $q->where('matricule', 'LIKE', $patternMasculin)
          ->orWhere('matricule', 'LIKE', $patternFeminin);
    })->whereNotNull('deleted_at')->count();

    echo "Statistiques :\n";
    echo "  • Masculins actifs  : {$countMasculin}\n";
    echo "  • Féminins actifs   : {$countFeminin}\n";
    echo "  • Soft deleted      : {$countSoftDeleted}\n";
    echo "  • Total actifs      : " . ($countMasculin + $countFeminin) . "\n";

    // Derniers matricules par genre
    $dernierMasculin = ESBTPEtudiant::where('matricule', 'LIKE', $patternMasculin)
        ->whereNull('deleted_at')
        ->orderByRaw("CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED) DESC")
        ->first();
    $dernierFeminin = ESBTPEtudiant::where('matricule', 'LIKE', $patternFeminin)
        ->whereNull('deleted_at')
        ->orderByRaw("CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED) DESC")
        ->first();

    echo "\nDerniers matricules :\n";
    if ($dernierMasculin) {
        echo "  • Masculin : {$dernierMasculin->matricule} (Numéro max: " .
             intval(explode('-', $dernierMasculin->matricule)[1]) . ")\n";
    } else {
        echo "  • Masculin : Aucun\n";
    }
    if ($dernierFeminin) {
        echo "  • Féminin  : {$dernierFeminin->matricule} (Numéro max: " .
             intval(explode('-', $dernierFeminin->matricule)[1]) . ")\n";
    } else {
        echo "  • Féminin  : Aucun\n";
    }
}

// ============================================================================
// 3. DÉTECTION DOUBLONS
// ============================================================================
echo "\n\n🔍 ÉTAPE 3 : DÉTECTION DOUBLONS\n";
echo str_repeat("=", 70) . "\n";

$doublons = DB::table('esbtp_etudiants')
    ->select('matricule', DB::raw('COUNT(*) as count'))
    ->whereNull('deleted_at')
    ->groupBy('matricule')
    ->having('count', '>', 1)
    ->get();

if ($doublons->isEmpty()) {
    echo "✅ Aucun doublon détecté dans les matricules actifs.\n";
} else {
    echo "❌ DOUBLONS DÉTECTÉS : {$doublons->count()} matricules dupliqués !\n\n";
    foreach ($doublons as $doublon) {
        echo "  ⚠️  Matricule : {$doublon->matricule} (présent {$doublon->count} fois)\n";

        $etudiants = ESBTPEtudiant::where('matricule', $doublon->matricule)
            ->whereNull('deleted_at')
            ->get();

        foreach ($etudiants as $etudiant) {
            echo "      - ID {$etudiant->id}: {$etudiant->nom} {$etudiant->prenoms} (créé le {$etudiant->created_at})\n";
        }
    }
    echo "\n🔧 SOLUTION : Supprimer les doublons manuellement ou lancer un script de nettoyage.\n";
}

// Vérifier aussi les soft deleted
$doublonsSoftDeleted = DB::table('esbtp_etudiants')
    ->select('matricule', DB::raw('COUNT(*) as count'))
    ->whereNotNull('deleted_at')
    ->groupBy('matricule')
    ->having('count', '>', 1)
    ->get();

if (!$doublonsSoftDeleted->isEmpty()) {
    echo "\n⚠️  Doublons dans soft deleted : {$doublonsSoftDeleted->count()}\n";
}

// ============================================================================
// 4. DÉTECTION TROUS DANS LA SÉQUENCE
// ============================================================================
echo "\n\n🕳️  ÉTAPE 4 : DÉTECTION TROUS DANS LA SÉQUENCE\n";
echo str_repeat("=", 70) . "\n";

foreach ($configs as $config) {
    echo "\n🔍 Niveau : {$config->niveau_etude_code}\n";
    echo str_repeat("-", 70) . "\n";

    $anneeFormatee = $config->annee_format == 2 ? $annee2chiffres : $annee4chiffres;

    foreach (['M', 'F'] as $genre) {
        $pattern = $genre . ($config->prefixe ?: '') . $config->etablissement_code . $anneeFormatee . '-%';

        $dernier = ESBTPEtudiant::where('matricule', 'LIKE', $pattern)
            ->whereNull('deleted_at')
            ->orderByRaw("CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED) DESC")
            ->first();

        if (!$dernier) {
            echo "  • Genre {$genre} : Aucun étudiant\n";
            continue;
        }

        $maxNumero = intval(explode('-', $dernier->matricule)[1]);
        $trous = [];

        // Vérifier les 100 derniers numéros
        $searchStart = max(1, $maxNumero - 99);

        for ($i = $searchStart; $i <= $maxNumero; $i++) {
            $numeroFormate = str_pad($i, $config->numero_digits, '0', STR_PAD_LEFT);
            $testMatricule = $genre . ($config->prefixe ?: '') .
                           $config->etablissement_code . $anneeFormatee . '-' .
                           $numeroFormate;

            $exists = ESBTPEtudiant::where('matricule', $testMatricule)
                ->whereNull('deleted_at')
                ->exists();

            if (!$exists) {
                $trous[] = $testMatricule;
            }
        }

        if (empty($trous)) {
            echo "  • Genre {$genre} : Aucun trou détecté dans les 100 derniers (max: {$maxNumero})\n";
        } else {
            echo "  • Genre {$genre} : " . count($trous) . " trou(s) détecté(s) dans les 100 derniers\n";
            echo "    Premier trou disponible : {$trous[0]}\n";
            if (count($trous) > 1) {
                echo "    Trous : " . implode(', ', array_slice($trous, 0, 5));
                if (count($trous) > 5) echo " ... +" . (count($trous) - 5) . " autres";
                echo "\n";
            }
        }
    }
}

// ============================================================================
// 5. TEST GÉNÉRATION MATRICULE
// ============================================================================
echo "\n\n🧪 ÉTAPE 5 : TEST GÉNÉRATION MATRICULE\n";
echo str_repeat("=", 70) . "\n";

$generator = new MatriculeGenerator();

// Récupérer les niveaux
$niveaux = ESBTPNiveauEtude::whereIn('code', ['BTS', 'LICENCE'])->get();

foreach ($niveaux as $niveau) {
    echo "\n🔍 Test pour niveau : {$niveau->name} (code: {$niveau->code})\n";
    echo str_repeat("-", 70) . "\n";

    foreach (['M', 'F'] as $genre) {
        try {
            $context = [
                'genre' => $genre,
                'niveau_id' => $niveau->id,
                'annee_universitaire_id' => null // Année actuelle par défaut
            ];

            $matriculeGenere = $generator->generate($context);

            // Vérifier si ce matricule existe déjà
            $existeDeja = ESBTPEtudiant::where('matricule', $matriculeGenere)
                ->whereNull('deleted_at')
                ->exists();

            if ($existeDeja) {
                echo "  ❌ Genre {$genre} : {$matriculeGenere} (EXISTE DÉJÀ ! ⚠️)\n";

                // Trouver l'étudiant qui a ce matricule
                $etudiantExistant = ESBTPEtudiant::where('matricule', $matriculeGenere)->first();
                if ($etudiantExistant) {
                    echo "      Possédé par : {$etudiantExistant->nom} {$etudiantExistant->prenoms}\n";
                    echo "      ID: {$etudiantExistant->id}, Créé le: {$etudiantExistant->created_at}\n";
                }
            } else {
                echo "  ✅ Genre {$genre} : {$matriculeGenere} (disponible)\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Genre {$genre} : ERREUR - {$e->getMessage()}\n";
        }
    }
}

// ============================================================================
// 6. RECOMMANDATIONS
// ============================================================================
echo "\n\n💡 ÉTAPE 6 : RECOMMANDATIONS\n";
echo str_repeat("=", 70) . "\n";

$hasDoublons = !$doublons->isEmpty();
$hasErrors = false;

// Vérifier si le matricule généré existe déjà
foreach ($niveaux as $niveau) {
    foreach (['M', 'F'] as $genre) {
        $context = ['genre' => $genre, 'niveau_id' => $niveau->id];
        $matricule = $generator->generate($context);
        if (ESBTPEtudiant::where('matricule', $matricule)->whereNull('deleted_at')->exists()) {
            $hasErrors = true;
            break 2;
        }
    }
}

if ($hasDoublons) {
    echo "\n⚠️  ACTION URGENTE REQUISE : Doublons détectés\n";
    echo "   1. Identifier les doublons (voir ÉTAPE 3)\n";
    echo "   2. Supprimer les doublons en gardant le plus ancien (ou le plus récent selon contexte)\n";
    echo "   3. Vérifier que les inscriptions/paiements associés sont corrects\n";
}

if ($hasErrors) {
    echo "\n⚠️  ACTION URGENTE REQUISE : Matricule généré existe déjà\n";
    echo "   Causes possibles :\n";
    echo "   1. Race condition : Deux utilisateurs créent un étudiant simultanément\n";
    echo "   2. Cache Laravel : Essayez php artisan cache:clear\n";
    echo "   3. Logique de génération : Le matricule retourné est déjà pris\n";
    echo "\n   Solutions :\n";
    echo "   - Ajouter une contrainte UNIQUE sur matricule en BDD\n";
    echo "   - Implémenter un système de lock (DB transactions)\n";
    echo "   - Vérifier la logique de recherche de trous (méthode getProchainNumero)\n";
}

if (!$hasDoublons && !$hasErrors) {
    echo "\n✅ Système de génération matricule semble fonctionnel !\n";
    echo "   Vérifications supplémentaires :\n";
    echo "   - Tester la création d'un étudiant en interface web\n";
    echo "   - Monitorer les logs Laravel pour détecter des erreurs\n";
    echo "   - Vérifier que la contrainte UNIQUE sur matricule existe bien\n";
}

// Vérifier contrainte UNIQUE
echo "\n\n🔒 Vérification contrainte UNIQUE sur matricule :\n";
$constraintExists = DB::select("
    SELECT CONSTRAINT_NAME
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'esbtp_etudiants'
    AND CONSTRAINT_TYPE = 'UNIQUE'
    AND CONSTRAINT_NAME LIKE '%matricule%'
");

if (empty($constraintExists)) {
    echo "   ❌ Aucune contrainte UNIQUE détectée sur 'matricule'\n";
    echo "   📝 Recommandation : Ajouter une migration pour contrainte UNIQUE\n";
    echo "      ALTER TABLE esbtp_etudiants ADD UNIQUE KEY unique_matricule (matricule);\n";
} else {
    echo "   ✅ Contrainte UNIQUE existe : " . $constraintExists[0]->CONSTRAINT_NAME . "\n";
}

// ============================================================================
// 7. RÉSUMÉ FINAL
// ============================================================================
echo "\n\n📊 RÉSUMÉ FINAL\n";
echo str_repeat("=", 70) . "\n";

$totalEtudiants = ESBTPEtudiant::whereNull('deleted_at')->count();
$totalSoftDeleted = ESBTPEtudiant::whereNotNull('deleted_at')->count();

echo "Total étudiants actifs       : {$totalEtudiants}\n";
echo "Total étudiants soft deleted : {$totalSoftDeleted}\n";
echo "Doublons détectés            : " . ($hasDoublons ? "OUI ({$doublons->count()})" : "NON") . "\n";
echo "Matricule généré existe déjà : " . ($hasErrors ? "OUI ⚠️" : "NON") . "\n";
echo "Contrainte UNIQUE matricule  : " . (empty($constraintExists) ? "NON ⚠️" : "OUI") . "\n";

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                    FIN DU DIAGNOSTIC                             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📝 Ce rapport a été généré le : " . date('Y-m-d H:i:s') . "\n";
echo "🖥️  Base de données : " . config('database.connections.mysql.database') . "\n";
echo "🌐 Application URL : " . config('app.url') . "\n";
echo "\n";

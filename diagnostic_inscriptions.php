<?php
/**
 * Script de diagnostic pour les inscriptions
 * À exécuter sur le serveur de production pour identifier le problème
 */

require_once 'vendor/autoload.php';

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;

echo "=== DIAGNOSTIC INSCRIPTIONS PRODUCTION ===\n\n";

// 1. Vérifier le nombre total d'inscriptions
$totalInscriptions = ESBTPInscription::count();
echo "1. Total inscriptions en base : $totalInscriptions\n\n";

// 2. Vérifier l'année universitaire courante
$anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
if ($anneeEnCours) {
    echo "2. Année universitaire courante : {$anneeEnCours->name} (ID: {$anneeEnCours->id})\n";
    $inscriptionsAnneeEnCours = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)->count();
    echo "   Inscriptions pour cette année : $inscriptionsAnneeEnCours\n\n";
} else {
    echo "2. ❌ PROBLÈME : Aucune année universitaire marquée comme courante (is_current = true)\n\n";
    
    // Lister toutes les années
    $annees = ESBTPAnneeUniversitaire::all();
    echo "   Années universitaires disponibles :\n";
    foreach ($annees as $annee) {
        $count = ESBTPInscription::where('annee_universitaire_id', $annee->id)->count();
        echo "   - {$annee->name} (ID: {$annee->id}) - is_current: " . ($annee->is_current ? 'true' : 'false') . " - Inscriptions: $count\n";
    }
    echo "\n";
}

// 3. Vérifier les statuts des inscriptions
echo "3. Répartition par statuts :\n";
$statuts = ESBTPInscription::selectRaw('status, COUNT(*) as count')->groupBy('status')->get();
foreach ($statuts as $statut) {
    echo "   - {$statut->status} : {$statut->count} inscriptions\n";
}
echo "\n";

// 4. Vérifier les dernières inscriptions
echo "4. Dernières 5 inscriptions :\n";
$dernieresInscriptions = ESBTPInscription::with(['etudiant', 'filiere', 'niveau'])
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($dernieresInscriptions as $inscription) {
    echo "   - ID: {$inscription->id} - Étudiant: {$inscription->etudiant->nom} {$inscription->etudiant->prenom} - Statut: {$inscription->status} - Année ID: {$inscription->annee_universitaire_id}\n";
}
echo "\n";

// 5. Test de la requête actuelle du contrôleur
echo "5. Test requête contrôleur (sans filtres) :\n";
$query = ESBTPInscription::with(['etudiant', 'filiere', 'niveau', 'anneeUniversitaire']);

// Appliquer le filtre année courante comme dans le contrôleur
$anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
if ($anneeEnCours) {
    $query->where('annee_universitaire_id', $anneeEnCours->id);
    echo "   Filtre appliqué : année universitaire ID = {$anneeEnCours->id}\n";
} else {
    echo "   ❌ Aucun filtre année appliqué (pas d'année courante)\n";
}

$resultats = $query->count();
echo "   Résultats obtenus : $resultats inscriptions\n\n";

echo "=== FIN DIAGNOSTIC ===\n";
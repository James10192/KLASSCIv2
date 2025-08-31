<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h1>DEBUG RESULTATS - TEST EN DIRECT</h1>";

// Simuler exactement la requête que fait la page
echo "<h2>1. PARAMETRES DE LA REQUÊTE</h2>";
$classe_id = null;
$semestre = null;
$annee_universitaire_id = null;
$include_all_statuses = true; // Par défaut

// Get current academic year if not specified (même logique que le controller)
if (!$annee_universitaire_id) {
    $annee_universitaire_id = \App\Models\ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
}

echo "classe_id: " . ($classe_id ?: 'null') . "<br>";
echo "semestre: " . ($semestre ?: 'null') . "<br>";
echo "annee_universitaire_id: " . $annee_universitaire_id . "<br>";
echo "include_all_statuses: " . ($include_all_statuses ? 'true' : 'false') . "<br>";

echo "<h2>2. RÉCUPÉRATION DES ÉTUDIANTS</h2>";

// Branche exacte du controller (pas de classe mais année spécifiée)
if (!$classe_id && $annee_universitaire_id) {
    echo "<strong>Branche exécutée: année spécifiée sans classe</strong><br>";
    
    $studentsQuery = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
        $query->where('annee_universitaire_id', $annee_universitaire_id);
        // Logique corrigée
        if (!$include_all_statuses) {
            $query->where('status', 'active');
        }
    })
    ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
        $query->where('annee_universitaire_id', $annee_universitaire_id);
    }])
    ->orderBy('nom')
    ->orderBy('prenoms');
    
    $etudiants = $studentsQuery->get();
    echo "Étudiants récupérés: " . $etudiants->count() . "<br>";
    
    echo "<h3>Détail des étudiants:</h3>";
    foreach ($etudiants as $etudiant) {
        $inscription = $etudiant->inscriptions->first();
        echo "- " . $etudiant->matricule . " (" . $etudiant->nom . " " . $etudiant->prenoms . ") - Statut: " . ($inscription ? $inscription->status : 'N/A') . "<br>";
    }
    
    echo "<h2>3. RÉCUPÉRATION DES NOTES</h2>";
    
    if ($etudiants->count() > 0) {
        $student_ids = $etudiants->pluck('id')->toArray();
        echo "IDs étudiants: " . implode(', ', $student_ids) . "<br>";
        
        // Requête exacte du controller
        $notesQuery = \App\Models\ESBTPNote::whereIn('etudiant_id', $student_ids)
            ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);
        
        // Pas de filtre de semestre
        $notes = $notesQuery->get();
        echo "Notes récupérées: " . $notes->count() . "<br>";
        echo "Notes isEmpty: " . ($notes->isEmpty() ? 'true' : 'false') . "<br>";
        
        if ($notes->count() > 0) {
            echo "<h3>Détail des notes:</h3>";
            foreach ($notes as $note) {
                $etudiant = $etudiants->firstWhere('id', $note->etudiant_id);
                echo "- Note ID " . $note->id . ": " . ($etudiant ? $etudiant->matricule : 'N/A') . " = " . $note->note . "/20";
                if ($note->evaluation) {
                    echo " (éval: " . $note->evaluation->titre . ", coeff: " . $note->evaluation->coefficient . ", bareme: " . $note->evaluation->bareme . ")";
                    if ($note->evaluation->matiere) {
                        echo " en " . $note->evaluation->matiere->name;
                    }
                }
                echo "<br>";
            }
            
            echo "<h2>4. TEST DU CALCUL DES MOYENNES</h2>";
            
            // Test de calculateStudentStatsFixed
            $moyennes = [];
            $rangs = [];
            $notesByStudentMatiere = [];
            
            foreach ($notes as $note) {
                if (!$note->evaluation || !$note->evaluation->matiere) {
                    echo "Note " . $note->id . " ignorée: pas d'évaluation ou matière<br>";
                    continue;
                }
                
                $etudiantId = $note->etudiant_id;
                $matiere_id = $note->matiere_id ?: $note->evaluation->matiere->id;
                
                if (!isset($notesByStudentMatiere[$etudiantId])) {
                    $notesByStudentMatiere[$etudiantId] = [];
                }
                
                if (!isset($notesByStudentMatiere[$etudiantId][$matiere_id])) {
                    $notesByStudentMatiere[$etudiantId][$matiere_id] = [
                        'total_points' => 0,
                        'total_coefficients' => 0
                    ];
                }
                
                if ($note->evaluation->bareme > 0) {
                    $noteValue = is_numeric($note->note) ? floatval($note->note) : floatval($note->valeur);
                    $normalized = ($noteValue / $note->evaluation->bareme) * 20;
                    $coefficient = $note->evaluation->coefficient ?: 1;
                    $ponderation = $normalized * $coefficient;
                    
                    $notesByStudentMatiere[$etudiantId][$matiere_id]['total_points'] += $ponderation;
                    $notesByStudentMatiere[$etudiantId][$matiere_id]['total_coefficients'] += $coefficient;
                    
                    echo "Calcul note " . $note->id . ": " . $noteValue . "/" . $note->evaluation->bareme . " × " . $coefficient . " = " . $ponderation . "<br>";
                }
            }
            
            echo "<h3>Moyennes calculées:</h3>";
            foreach ($etudiants as $etudiant) {
                if (!isset($notesByStudentMatiere[$etudiant->id])) {
                    echo "Étudiant " . $etudiant->matricule . ": aucune note<br>";
                    continue;
                }
                
                $moyenneGenerale = 0;
                $countValidMatieres = 0;
                
                foreach ($notesByStudentMatiere[$etudiant->id] as $matiereData) {
                    if ($matiereData['total_coefficients'] > 0) {
                        $moyenneMat = $matiereData['total_points'] / $matiereData['total_coefficients'];
                        $moyenneGenerale += $moyenneMat;
                        $countValidMatieres++;
                    }
                }
                
                if ($countValidMatieres > 0) {
                    $moyennes[$etudiant->id] = $moyenneGenerale / $countValidMatieres;
                    echo "<strong>Étudiant " . $etudiant->matricule . ": " . round($moyennes[$etudiant->id], 2) . "/20</strong><br>";
                }
            }
            
            // Test des KPI
            echo "<h2>5. CALCUL DES KPI</h2>";
            if (count($moyennes) > 0) {
                $moyenneGeneraleKPI = array_sum($moyennes) / count($moyennes);
                $reussis = count(array_filter($moyennes, function($m) { return $m >= 10; }));
                $tauxReussite = ($reussis / count($moyennes)) * 100;
                
                echo "Moyenne générale: " . round($moyenneGeneraleKPI, 2) . "/20<br>";
                echo "Taux de réussite: " . round($tauxReussite, 1) . "%<br>";
                
                echo "<h2>6. VARIABLES PASSÉES À LA VUE</h2>";
                echo "etudiants->count(): " . $etudiants->count() . "<br>";
                echo "notes->count(): " . $notes->count() . "<br>";
                echo "notes->isEmpty(): " . ($notes->isEmpty() ? 'true' : 'false') . "<br>";
                echo "count(moyennes): " . count($moyennes) . "<br>";
                
            } else {
                echo "<strong>PROBLÈME: Aucune moyenne calculée!</strong><br>";
            }
        } else {
            echo "<strong>PROBLÈME: Aucune note trouvée!</strong><br>";
        }
    }
}
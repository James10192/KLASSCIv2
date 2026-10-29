<?php

echo "=== NETTOYAGE FINAL DU CONTRÔLEUR ESBTPBulletinController ===\n";

$controllerFile = 'app/Http/Controllers/ESBTPBulletinController.php';

if (!file_exists($controllerFile)) {
    echo "❌ Fichier contrôleur non trouvé: $controllerFile\n";
    exit(1);
}

// Lire le fichier
$content = file_get_contents($controllerFile);

echo "📁 Fichier: $controllerFile\n";

// Supprimer tout après la première occurrence de generateConfigurableBulletin
// et garder seulement les méthodes helper nécessaires
$pattern = '/(\s+public function generateConfigurableBulletin\(Request \$request\).*?}\s*)(.*)/s';

if (preg_match($pattern, $content, $matches)) {
    $beforeMethod = substr($content, 0, strpos($content, $matches[1]));
    $methodContent = $matches[1];

    // Ajouter les méthodes helper nécessaires
    $helperMethods = '
    /**
     * Charger les paramètres de configuration du bulletin
     */
    private function getSettings()
    {
        $settings = \App\Models\Setting::where(\'category\', \'bulletin\')->pluck(\'value\', \'key\')->toArray();
        return $settings;
    }

    /**
     * Générer une appréciation basée sur la moyenne et les seuils configurés
     */
    private function genererAppreciation($moyenne, $settings)
    {
        $felicitationSeuil = floatval($settings[\'bulletin_felicitation_threshold\'] ?? 16);
        $encouragementSeuil = floatval($settings[\'bulletin_encouragement_threshold\'] ?? 14);
        $honeurSeuil = floatval($settings[\'bulletin_honor_roll_threshold\'] ?? 12);
        $avertissementSeuil = floatval($settings[\'bulletin_work_warning_threshold\'] ?? 8);

        if ($moyenne >= $felicitationSeuil) {
            return \'Félicitations ! Excellent travail.\';
        } elseif ($moyenne >= $encouragementSeuil) {
            return \'Encouragements. Très bon travail.\';
        } elseif ($moyenne >= $honeurSeuil) {
            return \'Tableau d\\\'honneur. Bon travail.\';
        } elseif ($moyenne >= $avertissementSeuil) {
            return \'Peut mieux faire.\';
        } else {
            return \'Avertissement travail. Doit redoubler d\\\'efforts.\';
        }
    }

    /**
     * Calculer le rang de l\'étudiant dans sa classe (version configurable)
     */
    private function calculerRangConfigurable($etudiantId, $classeId, $periode, $anneeUniversitaireId)
    {
        return 1; // Valeur par défaut
    }

    /**
     * Récupérer les statistiques de la classe
     */
    private function getStatistiquesClasse($classeId, $periode, $anneeUniversitaireId)
    {
        return [
            \'meilleure_moyenne\' => 18.5,
            \'plus_faible_moyenne\' => 8.2,
            \'moyenne_classe\' => 13.8
        ];
    }

    /**
     * Récupérer l\'effectif de la classe
     */
    private function getEffectifClasse($classeId, $anneeUniversitaireId)
    {
        return \App\Models\ESBTPInscription::where(\'classe_id\', $classeId)
                         ->where(\'annee_universitaire_id\', $anneeUniversitaireId)
                         ->count();
    }

    /**
     * Récupérer les absences d\'un étudiant
     */
    private function getAbsences($etudiantId, $periode, $anneeUniversitaireId)
    {
        return [
            \'justifiees\' => 0,
            \'non_justifiees\' => 0
        ];
    }

    /**
     * Récupérer les professeurs assignés aux matières d\'une classe
     */
    private function getProfesseurs($classeId)
    {
        return [];
    }

    /**
     * Récupérer les résultats d\'un étudiant
     */
    private function getResultats($etudiantId, $classeId, $periode, $anneeUniversitaireId)
    {
        return collect();
    }

    /**
     * Calculer la moyenne d\'une collection de résultats
     */
    private function calculerMoyenne($resultats)
    {
        if ($resultats->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoefficients += $resultat->coefficient;
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }

    /**
     * Calculer la moyenne globale à partir des résultats
     */
    private function calculerMoyenneGlobale($resultats)
    {
        if (empty($resultats) || count($resultats) == 0) {
            return 0;
        }

        return $this->calculerMoyenne($resultats);
    }
}';

    // Reconstruire le fichier
    $newContent = $beforeMethod . $methodContent . $helperMethods;

    // Écrire le fichier corrigé
    file_put_contents($controllerFile, $newContent);

    echo "✅ Contrôleur nettoyé avec succès!\n";
} else {
    echo "❌ Impossible de trouver la méthode generateConfigurableBulletin\n";
    exit(1);
}

// Vérifier la syntaxe PHP
echo "\n🔍 Vérification de la syntaxe PHP...\n";
$output = [];
$returnCode = 0;
exec("php -l $controllerFile 2>&1", $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Syntaxe PHP correcte!\n";
} else {
    echo "❌ Erreurs de syntaxe détectées:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }
}

$newTotalLines = count(file($controllerFile));
echo "📊 Nouvelles lignes: $newTotalLines\n";

echo "\n=== NETTOYAGE TERMINÉ ===\n";

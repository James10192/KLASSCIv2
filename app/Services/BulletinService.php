<?php

namespace App\Services;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BulletinService
{
    private $absenceService;

    private array $coefficientCache = [];

    private array $classeCache = [];

    public function __construct(ESBTPAbsenceService $absenceService)
    {
        $this->absenceService = $absenceService;
    }

    /**
     * Génère les données complètes pour un bulletin (utilisé par preview et PDF)
     */
    public function genererDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1')
    {
        // Récupérer les entités de base
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
        $classe = ESBTPClasse::with(['filiere', 'niveauEtude'])->findOrFail($classeId);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // Récupérer le bulletin pour obtenir les professeurs configurés
        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->first();

        // VÉRIFICATION OBLIGATOIRE : S'assurer que la configuration existe
        if (! $bulletin || ! $bulletin->config_matieres || ! $bulletin->professeurs) {
            throw new \Exception('Configuration bulletin manquante. Veuillez d\'abord configurer les matières et les professeurs.');
        }

        // Vérifier que la configuration n'est pas vide
        $configMatieres = json_decode($bulletin->config_matieres, true);
        $professeursConfigures = json_decode($bulletin->professeurs, true);

        if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
            throw new \Exception('Aucune matière configurée dans le bulletin.');
        }

        // Récupérer les notes avec évaluations pour la période spécifiée
        $notesAvecEvaluations = ESBTPNote::where('etudiant_id', $etudiant->id)
            ->with(['evaluation.matiere'])
            ->whereHas('evaluation', function ($q) use ($anneeUniversitaire, $periode) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->where('status', '!=', 'cancelled')
                    ->where('periode', $periode);
            })
            ->get();

        // Créer des résultats par matière avec évaluations
        $resultatsParMatiere = [];
        $professeurs = [];

        foreach ($notesAvecEvaluations as $note) {
            if ($note->evaluation && $note->evaluation->matiere) {
                $matiere = $note->evaluation->matiere;
                $matiereId = $matiere->id;

                if (! isset($resultatsParMatiere[$matiereId])) {
                    // Déterminer le type de formation selon la configuration du bulletin
                    if (in_array($matiereId, $configMatieres['generales'] ?? [])) {
                        $typeFormation = 'generale';
                    } elseif (in_array($matiereId, $configMatieres['techniques'] ?? [])) {
                        $typeFormation = 'technologique_professionnelle';
                    } else {
                        $typeFormation = 'generale';
                    }

                    $resultatsParMatiere[$matiereId] = (object) [
                        'id' => $matiereId,
                        'matiere_id' => $matiereId,
                        'matiere' => $matiere,
                        'notes' => [],
                        'moyenne' => 0,
                        'coefficient' => $this->getCoefficientForCombination($matiereId, $classe, $anneeUniversitaireId),
                        'rang' => '-',
                        'appreciation' => '',
                        'type_formation' => $typeFormation,
                    ];
                }

                $resultatsParMatiere[$matiereId]->notes[] = [
                    'note' => $note->note,
                    'coefficient' => $note->evaluation->coefficient,
                ];

                // Utiliser uniquement les professeurs configurés
                $professeurs[$matiereId] = $professeursConfigures[$matiereId] ?? '';
            }
        }

        // Calculer les moyennes pondérées pour chaque matière (automatiques)
        foreach ($resultatsParMatiere as $matiereId => $resultat) {
            $totalPoints = 0;
            $totalCoeffs = 0;

            foreach ($resultat->notes as $noteData) {
                $totalPoints += $noteData['note'] * $noteData['coefficient'];
                $totalCoeffs += $noteData['coefficient'];
            }

            $resultat->moyenne = $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
            $resultat->appreciation = $this->getAppreciation($resultat->moyenne);
        }

        // INTÉGRER LES MOYENNES MANUELLES (priorité Manuel l'emporte)
        $resultats = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $periode)
            ->with('matiere')
            ->get();

        // Ajouter les matières qui ont seulement des moyennes manuelles (sans évaluations)
        foreach ($resultats as $resultatManuel) {
            $matiereId = $resultatManuel->matiere_id;

            if ($resultatManuel->matiere) {
                // Si la matière n'existe pas encore dans les résultats, l'ajouter
                if (! isset($resultatsParMatiere[$matiereId])) {
                    // Déterminer le type selon la configuration du bulletin
                    if (in_array($matiereId, $configMatieres['generales'] ?? [])) {
                        $typeFormation = 'generale';
                    } elseif (in_array($matiereId, $configMatieres['techniques'] ?? [])) {
                        $typeFormation = 'technologique_professionnelle';
                    } else {
                        $typeFormation = 'generale';
                    }

                    $resultatsParMatiere[$matiereId] = (object) [
                        'id' => $matiereId,
                        'matiere_id' => $matiereId,
                        'matiere' => $resultatManuel->matiere,
                        'notes' => [],
                        'moyenne' => $resultatManuel->moyenne,
                        'coefficient' => $this->getCoefficientForCombination($matiereId, $classe, $anneeUniversitaireId),
                        'rang' => '-',
                        'appreciation' => $resultatManuel->appreciation ?: $this->getAppreciation($resultatManuel->moyenne),
                        'type_formation' => $typeFormation,
                    ];

                    // Configurer le professeur si disponible
                    if (! isset($professeurs[$matiereId])) {
                        $professeurs[$matiereId] = $professeursConfigures[$matiereId] ?? '';
                    }
                } else {
                    // Écraser avec les moyennes manuelles (elles l'emportent toujours)
                    $resultatsParMatiere[$matiereId]->moyenne = $resultatManuel->moyenne;
                    $resultatsParMatiere[$matiereId]->appreciation = $resultatManuel->appreciation ?: $this->getAppreciation($resultatManuel->moyenne);
                    $resultatsParMatiere[$matiereId]->coefficient = $this->getCoefficientForCombination($matiereId, $classe, $anneeUniversitaireId);
                }
            }
        }

        $periodeNormalized = $this->normalizePeriode($periode);
        $this->persistResultats(
            $resultatsParMatiere,
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $periodeNormalized
        );

        // Séparer par type d'enseignement
        $resultatsGeneraux = collect($resultatsParMatiere)->filter(function ($resultat) {
            return $resultat->type_formation == 'generale';
        });

        $resultatsTechniques = collect($resultatsParMatiere)->filter(function ($resultat) {
            return $resultat->type_formation == 'technologique_professionnelle';
        });

        // Calculer les moyennes par section
        $moyenneGenerale = $this->calculerMoyennePonderee($resultatsGeneraux);
        $moyenneTechnique = $this->calculerMoyennePonderee($resultatsTechniques);
        $moyenneGlobale = $this->calculerMoyennePonderee(collect($resultatsParMatiere));

        // Calcul des absences et note d'assiduité
        $absences = $this->absenceService->calculerDetailAbsences(
            $etudiant->id,
            $classe->id,
            $anneeUniversitaire->date_debut,
            $anneeUniversitaire->date_fin
        );
        // Calculer la note d'assiduité seulement si l'affichage est activé
        $afficherNoteAssiduite = SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
        $noteAssiduite = $afficherNoteAssiduite ? $this->calculerNoteAssiduite($absences['justifiees'], $absences['non_justifiees']) : 0;
        $moyenneAvecAssiduite = $moyenneGlobale + $noteAssiduite;

        // Rang de l'étudiant (simplifié)
        $rang = '1';

        // Effectif de la classe
        $effectif = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classe, $anneeUniversitaire) {
            $q->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id);
        })->count();

        // Calculer les vraies statistiques de classe
        $statsClasse = $this->calculerStatistiquesClasse($classe->id, $anneeUniversitaire->id, $periode);

        // Calculer les rangs par matière - simplification pour un seul étudiant
        foreach ($resultatsGeneraux as $resultat) {
            $resultat->rang = 1; // Premier et seul étudiant avec cette configuration
        }
        foreach ($resultatsTechniques as $resultat) {
            $resultat->rang = 1; // Premier et seul étudiant avec cette configuration
        }

        // Déterminer l'appréciation selon la moyenne
        $appreciation = $this->getAppreciation($moyenneGlobale);

        // Préparer la configuration PDF
        $settings = $this->getPDFConfig();

        $semesterWeights = $this->getSemesterWeights();
        $moyenneSemestre1 = $this->getSemesterAverageFromBulletins(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            'semestre1',
            $periode,
            $moyenneAvecAssiduite
        );
        $moyenneSemestre2 = $this->getSemesterAverageFromBulletins(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            'semestre2',
            $periode,
            $moyenneAvecAssiduite
        );
        $moyenneAnnuelle = $this->calculateAnnualAverage($moyenneSemestre1, $moyenneSemestre2, $semesterWeights);

        // Préparer la photo de l'étudiant en base64 pour le PDF
        $photoEtudiantBase64 = $this->preparePhotoEtudiantBase64($etudiant);

        return [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'resultatsGeneraux' => $resultatsGeneraux,
            'resultatsTechniques' => $resultatsTechniques,
            'moyenneGenerale' => $moyenneGenerale,
            'moyenneTechnique' => $moyenneTechnique,
            'moyenneGlobale' => $moyenneGlobale,
            'moyenneAvecAssiduite' => $moyenneAvecAssiduite,
            'noteAssiduite' => $noteAssiduite,
            'note_assiduite' => $noteAssiduite, // Alias pour compatibilité template
            'rang' => $rang,
            'effectif' => $effectif,
            'meilleure_moyenne' => $statsClasse['meilleure_moyenne'],
            'plus_faible_moyenne' => $statsClasse['plus_faible_moyenne'],
            'moyenne_classe' => $statsClasse['moyenne_classe'],
            'appreciation' => $appreciation,
            'absences' => $absences,
            'absencesJustifiees' => $absences['justifiees'] ?? 0,
            'absencesNonJustifiees' => $absences['non_justifiees'] ?? 0,
            'absences_justifiees' => $absences['justifiees'] ?? 0, // Alias pour compatibilité
            'absences_non_justifiees' => $absences['non_justifiees'] ?? 0, // Alias pour compatibilité
            'professeurs' => $professeurs,
            'date_edition' => date('d/m/Y'),
            'settings' => $settings,
            'photoEtudiantBase64' => $photoEtudiantBase64,
            'moyenneSemestre1' => $moyenneSemestre1,
            'moyenneSemestre2' => $moyenneSemestre2,
            'moyenneAnnuelle' => $moyenneAnnuelle,
            'semesterWeights' => $semesterWeights,
        ];
    }

    public function getSemesterWeights(): array
    {
        $semester1 = floatval(SettingsHelper::get('bulletin_semester1_weight', '50'));
        $semester2 = floatval(SettingsHelper::get('bulletin_semester2_weight', '50'));

        if ($semester1 < 0) {
            $semester1 = 0;
        }
        if ($semester2 < 0) {
            $semester2 = 0;
        }

        if (($semester1 + $semester2) <= 0) {
            $semester1 = 50;
            $semester2 = 50;
        }

        return [
            'semester1' => $semester1,
            'semester2' => $semester2,
        ];
    }

    private function getSemesterAverageFromBulletins(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        string $currentPeriode,
        float $currentAverage
    ): ?float {
        if ($periode === $currentPeriode) {
            return $currentAverage;
        }

        $periodeOptions = [$periode];
        if ($periode === 'semestre1') {
            $periodeOptions[] = '1';
        } elseif ($periode === 'semestre2') {
            $periodeOptions[] = '2';
        } elseif ($periode === '1') {
            $periodeOptions[] = 'semestre1';
        } elseif ($periode === '2') {
            $periodeOptions[] = 'semestre2';
        }

        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', array_unique($periodeOptions))
            ->first();

        if (! $bulletin || $bulletin->moyenne_generale === null || $bulletin->moyenne_generale <= 0) {
            return null;
        }

        return floatval($bulletin->moyenne_generale + ($bulletin->note_assiduite ?? 0));
    }

    public function calculateAnnualAverage(?float $semester1, ?float $semester2, array $weights): ?float
    {
        if ($semester1 === null || $semester2 === null) {
            return null;
        }

        $total = $weights['semester1'] + $weights['semester2'];
        if ($total <= 0) {
            return null;
        }

        return (($semester1 * $weights['semester1']) + ($semester2 * $weights['semester2'])) / $total;
    }

    private function normalizePeriode(string $periode): string
    {
        if ($periode === '1') {
            return 'semestre1';
        }
        if ($periode === '2') {
            return 'semestre2';
        }

        return $periode ?: 'semestre1';
    }

    private function persistResultats(array $resultatsParMatiere, int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): void
    {
        $userId = Auth::id();

        foreach ($resultatsParMatiere as $resultat) {
            if (! isset($resultat->matiere_id)) {
                continue;
            }

            if ($resultat->moyenne === null) {
                continue;
            }

            ESBTPResultat::updateOrCreate(
                [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'matiere_id' => $resultat->matiere_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $anneeUniversitaireId,
                ],
                [
                    'moyenne' => $resultat->moyenne,
                    'coefficient' => $resultat->coefficient ?? 1,
                    'appreciation' => $resultat->appreciation ?? $this->getAppreciation($resultat->moyenne),
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ]
            );
        }
    }

    /**
     * Calcule le coefficient d'une matière pour une combinaison filiere + niveau + année
     */
    private function getCoefficientForCombination(int $matiereId, ESBTPClasse $classe, int $anneeUniversitaireId): float
    {
        $cacheKey = $matiereId.'|'.$classe->id.'|'.$anneeUniversitaireId;

        if (isset($this->coefficientCache[$cacheKey])) {
            return $this->coefficientCache[$cacheKey];
        }

        if (! isset($this->classeCache[$classe->id])) {
            $this->classeCache[$classe->id] = $classe->fresh();
        }

        $classeCourante = $this->classeCache[$classe->id];
        $classeCourante->loadMissing(['filiere', 'niveauEtude']);

        if (! $classeCourante || ! $classeCourante->filiere_id || ! $classeCourante->niveau_etude_id) {
            throw new \RuntimeException('Classe invalide pour le calcul du coefficient.');
        }

        $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
            ->where('filiere_id', $classeCourante->filiere_id)
            ->where('niveau_etude_id', $classeCourante->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->value('coefficient');

        if ($coefficient === null) {
            $matiere = ESBTPMatiere::find($matiereId);
            $inFiliere = false;
            $inNiveau = false;

            if ($matiere) {
                $inFiliere = $matiere->filieres()
                    ->where('esbtp_filieres.id', $classeCourante->filiere_id)
                    ->exists();
                $inNiveau = $matiere->niveaux()
                    ->where('esbtp_niveau_etudes.id', $classeCourante->niveau_etude_id)
                    ->exists();

                if (! $inFiliere && $matiere->filiere_id) {
                    $inFiliere = (int) $matiere->filiere_id === (int) $classeCourante->filiere_id;
                }
                if (! $inNiveau && $matiere->niveau_etude_id) {
                    $inNiveau = (int) $matiere->niveau_etude_id === (int) $classeCourante->niveau_etude_id;
                }
            }

            $reason = 'coefficient_missing';
            if (! $matiere) {
                $reason = 'matiere_introuvable';
            } elseif (! $inFiliere || ! $inNiveau) {
                $reason = 'matiere_hors_combinaison';
            }

            $context = [
                'reason' => $reason,
                'matiere' => $matiere
                    ? [
                        'id' => $matiere->id,
                        'name' => $matiere->name,
                        'code' => $matiere->code,
                    ]
                    : [
                        'id' => $matiereId,
                    ],
                'classe' => [
                    'id' => $classeCourante->id,
                    'name' => $classeCourante->name,
                    'filiere_id' => $classeCourante->filiere_id,
                    'niveau_etude_id' => $classeCourante->niveau_etude_id,
                    'filiere_name' => $classeCourante->filiere->name ?? null,
                    'niveau_name' => $classeCourante->niveauEtude->name ?? null,
                ],
                'annee_universitaire_id' => $anneeUniversitaireId,
            ];

            throw new CoefficientMissingException('Coefficient manquant pour la matière sélectionnée.', $context);
        }

        $this->coefficientCache[$cacheKey] = (float) $coefficient;

        return $this->coefficientCache[$cacheKey];
    }

    /**
     * Détermine l'appréciation selon la moyenne
     */
    private function getAppreciation($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Excellent';
        }
        if ($moyenne >= 14) {
            return 'Très Bien';
        }
        if ($moyenne >= 12) {
            return 'Bien';
        }
        if ($moyenne >= 10) {
            return 'Assez Bien';
        }
        if ($moyenne >= 8) {
            return 'Passable';
        }

        return 'Insuffisant';
    }

    /**
     * Calcule la moyenne pondérée d'une collection de résultats
     */
    public function calculerMoyennePonderee($resultats)
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
     * Calcule la note d'assiduité
     */
    public function calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees)
    {
        // Logique exacte du contrôleur : bonus/malus selon les absences non justifiées
        switch (true) {
            case $absencesNonJustifiees == 0:
                return 0.13; // Bonus pour aucune absence non justifiée
                break;
            case $absencesNonJustifiees == 1:
                return 0;
                break;
            case $absencesNonJustifiees == 2:
                return -0.13;
                break;
            case $absencesNonJustifiees == 3:
            case $absencesNonJustifiees == 4:
                return -0.39;
                break;
            default: // 5 ou plus
                return -0.5;
        }
    }

    /**
     * Calcule les statistiques de la classe
     */
    private function calculerStatistiquesClasse($classeId, $anneeUniversitaireId, $periode = 'semestre1')
    {
        // Récupérer tous les étudiants de la classe
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $anneeUniversitaireId) {
            $q->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeUniversitaireId);
        })->get();

        if ($etudiants->isEmpty()) {
            return [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0,
            ];
        }

        $moyennes = [];

        foreach ($etudiants as $etudiant) {
            try {
                // Calculer la moyenne globale de cet étudiant
                $moyenneEtudiant = $this->calculerMoyenneGlobaleEtudiant($etudiant->id, $classeId, $anneeUniversitaireId, $periode);

                // Ajouter la note d'assiduité seulement si l'affichage est activé et si la moyenne est valide
                $afficherNoteAssiduite = SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
                if ($afficherNoteAssiduite && $moyenneEtudiant > 0) {
                    $anneeUniv = \App\Models\ESBTPAnneeUniversitaire::find($anneeUniversitaireId);
                    $absencesEtudiant = $this->absenceService->calculerDetailAbsences(
                        $etudiant->id,
                        $classeId,
                        $anneeUniv->date_debut ?? null,
                        $anneeUniv->date_fin ?? null
                    );
                    $noteAssiduite = $this->calculerNoteAssiduite($absencesEtudiant['justifiees'], $absencesEtudiant['non_justifiees']);
                    $moyenneEtudiant += $noteAssiduite;
                }

                if ($moyenneEtudiant <= 0) {
                    continue;
                }

                $moyennes[] = $moyenneEtudiant;

            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($moyennes)) {
            return [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0,
            ];
        }

        return [
            'meilleure_moyenne' => max($moyennes),
            'plus_faible_moyenne' => min($moyennes),
            'moyenne_classe' => array_sum($moyennes) / count($moyennes),
        ];
    }

    /**
     * Calculer la moyenne globale d'un étudiant (utilisé pour les statistiques)
     */
    private function calculerMoyenneGlobaleEtudiant($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1')
    {
        $classe = ESBTPClasse::find($classeId);
        if (! $classe) {
            throw new \RuntimeException('Classe introuvable pour le calcul de la moyenne.');
        }

        $periodeOptions = [$periode];
        if ($periode === 'semestre1') {
            $periodeOptions[] = '1';
        } elseif ($periode === 'semestre2') {
            $periodeOptions[] = '2';
        } elseif ($periode === '1') {
            $periodeOptions[] = 'semestre1';
        } elseif ($periode === '2') {
            $periodeOptions[] = 'semestre2';
        }

        // Logique simplifiée pour éviter la récursion
        $resultatsParMatiere = [];

        // Récupérer les moyennes manuelles seulement
        $resultats = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', array_unique($periodeOptions))
            ->with('matiere')
            ->get();

        if ($resultats->isEmpty()) {
            return $this->calculerMoyenneDepuisNotes($etudiantId, $classe, $anneeUniversitaireId, $periodeOptions);
        }

        foreach ($resultats as $resultat) {
            if ($resultat->matiere) {
                try {
                    $coefficient = $this->getCoefficientForCombination($resultat->matiere_id, $classe, $anneeUniversitaireId);
                } catch (\RuntimeException $e) {
                    $coefficient = 1;
                }

                $resultatsParMatiere[] = (object) [
                    'moyenne' => $resultat->moyenne,
                    'coefficient' => $coefficient,
                ];
            }
        }

        if (empty($resultatsParMatiere)) {
            return 0;
        }

        return $this->calculerMoyennePonderee(collect($resultatsParMatiere));
    }

    private function calculerMoyenneDepuisNotes(int $etudiantId, ESBTPClasse $classe, int $anneeUniversitaireId, array $periodeOptions): float
    {
        $notes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation', 'evaluation.matiere'])
            ->byClasse($classe->id)
            ->byAnneeUniversitaire($anneeUniversitaireId)
            ->where(function ($query) use ($periodeOptions) {
                $query->whereIn('semestre', $periodeOptions)
                    ->orWhereHas('evaluation', function ($subQuery) use ($periodeOptions) {
                        $subQuery->whereIn('periode', $periodeOptions);
                    });
            })
            ->get();

        if ($notes->isEmpty()) {
            return 0;
        }

        $notesByMatiere = [];

        foreach ($notes as $note) {
            if (! $note->evaluation || ! $note->evaluation->matiere) {
                continue;
            }

            $matiereId = $note->matiere_id ?: $note->evaluation->matiere->id;
            if (! $matiereId) {
                continue;
            }

            if (! isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'total_points' => 0,
                    'total_coefficients' => 0,
                ];
            }

            if ($note->is_absent) {
                $noteValue = 0;
            } else {
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
            }

            $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;
            $normalized = $bareme > 0 ? ($noteValue / $bareme) * 20 : 0;
            $evalCoeff = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

            $notesByMatiere[$matiereId]['total_points'] += $normalized * $evalCoeff;
            $notesByMatiere[$matiereId]['total_coefficients'] += $evalCoeff;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($notesByMatiere as $matiereId => $matiereData) {
            if ($matiereData['total_coefficients'] <= 0) {
                continue;
            }

            $moyenneMatiere = $matiereData['total_points'] / $matiereData['total_coefficients'];

            try {
                $coefficient = $this->getCoefficientForCombination($matiereId, $classe, $anneeUniversitaireId);
            } catch (\RuntimeException $e) {
                $coefficient = 1;
            }

            $totalPoints += $moyenneMatiere * $coefficient;
            $totalCoefficients += $coefficient;
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }

    /**
     * Récupère la configuration PDF
     */
    private function getPDFConfig()
    {
        return [
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI'),
            'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'school_logo' => \App\Helpers\SettingsHelper::get('school_logo', ''),
            'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '11'),

            // Configuration de l'en-tête
            'bulletin_show_header' => \App\Helpers\SettingsHelper::get('bulletin_show_header', '1'),
            'bulletin_show_republic_info' => \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1'),
            'bulletin_republic_text' => \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
            'bulletin_union_text' => \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail'),
            'bulletin_show_ministry_info' => \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1'),
            'bulletin_ministry_text' => \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur'),
            'bulletin_show_logo' => \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1'),
            'bulletin_show_school_info' => \App\Helpers\SettingsHelper::get('bulletin_show_school_info', '1'),
            'bulletin_show_edition_date' => \App\Helpers\SettingsHelper::get('bulletin_show_edition_date', '1'),
            'bulletin_show_cycle_info' => \App\Helpers\SettingsHelper::get('bulletin_show_cycle_info', '1'),
            'bulletin_cycle_text' => \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur'),
            'bulletin_cycle_abbreviation' => \App\Helpers\SettingsHelper::get('bulletin_cycle_abbreviation', 'BTS'),

            // Configuration des informations étudiant
            'bulletin_show_student_info' => \App\Helpers\SettingsHelper::get('bulletin_show_student_info', '1'),

            // Toutes les autres configurations du bulletin...
            'bulletin_show_matricule' => \App\Helpers\SettingsHelper::get('bulletin_show_matricule', '1'),
            'bulletin_show_birth_date' => \App\Helpers\SettingsHelper::get('bulletin_show_birth_date', '1'),
            'bulletin_show_redoublant' => \App\Helpers\SettingsHelper::get('bulletin_show_redoublant', '1'),
            'bulletin_show_class_info' => \App\Helpers\SettingsHelper::get('bulletin_show_class_info', '1'),
            'bulletin_show_effectif' => \App\Helpers\SettingsHelper::get('bulletin_show_effectif', '1'),
            'bulletin_show_subjects_table' => \App\Helpers\SettingsHelper::get('bulletin_show_subjects_table', '1'),
            'bulletin_show_subject_average' => \App\Helpers\SettingsHelper::get('bulletin_show_subject_average', '1'),
            'bulletin_show_coefficient' => \App\Helpers\SettingsHelper::get('bulletin_show_coefficient', '1'),
            'bulletin_show_weighted_average' => \App\Helpers\SettingsHelper::get('bulletin_show_weighted_average', '1'),
            'bulletin_show_rank_per_subject' => \App\Helpers\SettingsHelper::get('bulletin_show_rank_per_subject', '1'),
            'bulletin_show_teachers' => \App\Helpers\SettingsHelper::get('bulletin_show_teachers', '1'),
            'bulletin_show_appreciations' => \App\Helpers\SettingsHelper::get('bulletin_show_appreciations', '1'),
            'bulletin_show_general_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_general_subjects', '1'),
            'bulletin_show_technical_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_technical_subjects', '1'),
            'bulletin_show_section_averages' => \App\Helpers\SettingsHelper::get('bulletin_show_section_averages', '1'),
            'bulletin_show_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_absences', '1'),
            'bulletin_show_justified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_justified_absences', '1'),
            'bulletin_show_unjustified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_unjustified_absences', '1'),
            'bulletin_show_results_section' => \App\Helpers\SettingsHelper::get('bulletin_show_results_section', '1'),
            'bulletin_show_raw_average' => \App\Helpers\SettingsHelper::get('bulletin_show_raw_average', '1'),
            'bulletin_show_attendance_note' => \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1'),
            'bulletin_show_semester_average' => \App\Helpers\SettingsHelper::get('bulletin_show_semester_average', '1'),
            'bulletin_show_student_rank' => \App\Helpers\SettingsHelper::get('bulletin_show_student_rank', '1'),
            'bulletin_show_mentions' => \App\Helpers\SettingsHelper::get('bulletin_show_mentions', '1'),
            'bulletin_show_felicitation' => \App\Helpers\SettingsHelper::get('bulletin_show_felicitation', '1'),
            'bulletin_show_encouragement' => \App\Helpers\SettingsHelper::get('bulletin_show_encouragement', '1'),
            'bulletin_show_honor_roll' => \App\Helpers\SettingsHelper::get('bulletin_show_honor_roll', '1'),
            'bulletin_show_work_warning' => \App\Helpers\SettingsHelper::get('bulletin_show_work_warning', '1'),
            'bulletin_show_conduct_blame' => \App\Helpers\SettingsHelper::get('bulletin_show_conduct_blame', '1'),
            'bulletin_auto_calculate_mention' => \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1'),
            'bulletin_felicitation_threshold' => \App\Helpers\SettingsHelper::get('bulletin_felicitation_threshold', '16'),
            'bulletin_encouragement_threshold' => \App\Helpers\SettingsHelper::get('bulletin_encouragement_threshold', '14'),
            'bulletin_honor_roll_threshold' => \App\Helpers\SettingsHelper::get('bulletin_honor_roll_threshold', '12'),
            'bulletin_work_warning_threshold' => \App\Helpers\SettingsHelper::get('bulletin_work_warning_threshold', '8'),
            'bulletin_show_statistics' => \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1'),
            'bulletin_show_highest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1'),
            'bulletin_show_lowest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1'),
            'bulletin_show_class_average' => \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1'),
            'bulletin_show_class_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_class_council_decision', '1'),
            'bulletin_show_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_council_decision', '1'),
            'bulletin_show_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_signature', '1'),
            'bulletin_show_director_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_director_signature', '1'),
            'bulletin_include_attendance_in_stats' => \App\Helpers\SettingsHelper::get('bulletin_include_attendance_in_stats', '1'),
            'director_title' => \App\Helpers\SettingsHelper::get('director_title', 'Directeur'),
            'director_name' => \App\Helpers\SettingsHelper::get('director_name', ''),
        ];
    }

    /**
     * Calcule les rangs par matière pour une liste de résultats
     */
    private function calculerRangsParMatiere($resultats, $classeId, $anneeUniversitaireId, $typeFormation)
    {
        if (! $resultats || $resultats->isEmpty()) {
            return;
        }

        // Récupérer tous les étudiants de la classe avec leurs bulletins configurés
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $anneeUniversitaireId) {
            $q->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeUniversitaireId);
        })->get();

        // Pour chaque matière dans les résultats
        foreach ($resultats as $resultat) {
            $matiereId = $resultat->matiere_id;
            $moyennesMatiere = [];

            // Récupérer les moyennes de tous les étudiants pour cette matière
            foreach ($etudiants as $etudiant) {
                try {
                    // Récupérer le bulletin de l'étudiant
                    $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                        ->where('classe_id', $classeId)
                        ->where('periode', $periode)
                        ->where('annee_universitaire_id', $anneeUniversitaireId)
                        ->first();

                    if (! $bulletin || ! $bulletin->config_matieres) {
                        continue;
                    }

                    $configMatieres = json_decode($bulletin->config_matieres, true);
                    $matiereConfig = null;

                    // Chercher la matière dans la configuration
                    foreach (['generales', 'techniques'] as $type) {
                        if (isset($configMatieres[$type])) {
                            foreach ($configMatieres[$type] as $config) {
                                if ($config['matiere_id'] == $matiereId) {
                                    $matiereConfig = $config;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (! $matiereConfig) {
                        continue;
                    }

                    // Calculer la moyenne pour cette matière
                    $moyenneMatiere = $this->calculerMoyenneMatiere($etudiant->id, $matiereId, $matiereConfig);

                    if ($moyenneMatiere > 0) {
                        $moyennesMatiere[$etudiant->id] = $moyenneMatiere;
                    }
                } catch (\Exception $e) {
                    // Ignorer les étudiants sans configuration
                    continue;
                }
            }

            // Trier les moyennes par ordre décroissant
            arsort($moyennesMatiere);

            // Attribuer les rangs
            $rang = 1;
            $previousMoyenne = null;
            $previousRang = null;

            foreach ($moyennesMatiere as $etudiantId => $moyenne) {
                if ($previousMoyenne !== null && $moyenne == $previousMoyenne) {
                    // Même moyenne, même rang
                    $currentRang = $previousRang;
                } else {
                    // Moyenne différente, nouveau rang
                    $currentRang = $rang;
                    $previousRang = $currentRang;
                }

                // Debug temporaire
                \Log::info("Comparaison rang : etudiant {$etudiantId} vs resultat etudiant {$resultat->etudiant_id}");

                // Si c'est notre étudiant, assigner le rang
                if ($etudiantId == $resultat->etudiant_id) {
                    $resultat->rang = $currentRang;
                    \Log::info("Rang assigné : {$currentRang} pour matière {$matiereId}");
                    break;
                }

                $rang++;
                $previousMoyenne = $moyenne;
            }

            // Si le rang n'a pas été trouvé, mettre un tiret
            if (! isset($resultat->rang)) {
                $resultat->rang = '-';
            }
        }
    }

    /**
     * Prépare la photo de l'étudiant en base64 pour l'affichage dans le PDF
     */
    private function preparePhotoEtudiantBase64($etudiant)
    {
        if (! $etudiant->photo) {
            return null;
        }

        $photo = $etudiant->photo;
        $photoCandidates = [
            storage_path('app/public/' . $photo),
            storage_path('app/public/photos/etudiants/' . basename($photo)),
            public_path('storage/' . $photo),
            public_path('storage/photos/etudiants/' . basename($photo)),
        ];

        $photoPath = null;
        foreach ($photoCandidates as $candidate) {
            if (file_exists($candidate)) {
                $photoPath = $candidate;
                break;
            }
        }

        if (! $photoPath) {
            return null;
        }

        // Convertir en JPEG truecolor pour compatibilité DomPDF
        // DomPDF ne supporte pas les PNG indexés (palette 8-bit)
        if (! function_exists('imagecreatefromstring')) {
            $mime = mime_content_type($photoPath) ?: 'image/jpeg';
            return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($photoPath));
        }

        try {
            $rawData = file_get_contents($photoPath);
            $src = @imagecreatefromstring($rawData);
            if (! $src) {
                $mime = mime_content_type($photoPath) ?: 'image/jpeg';
                return 'data:'.$mime.';base64,'.base64_encode($rawData);
            }

            $w = imagesx($src);
            $h = imagesy($src);
            $dst = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);

            ob_start();
            imagejpeg($dst, null, 85);
            $jpegData = ob_get_clean();
            imagedestroy($dst);

            return 'data:image/jpeg;base64,'.base64_encode($jpegData);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la préparation de la photo étudiant: '.$e->getMessage());

            return null;
        }
    }
}

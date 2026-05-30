<?php

namespace App\Services;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Support\InscriptionWorkflowAlertPresenter;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEvaluation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BulletinService
{
    private const ATTENDANCE_NOTE_DEFAULTS = [
        'attendance_note_zero_unjustified' => '0.13',
        'attendance_note_one_unjustified' => '0.00',
        'attendance_note_two_or_more_unjustified' => '-0.13',
    ];

    private $absenceService;

    private array $coefficientCache = [];

    private array $classeCache = [];

    public function __construct(ESBTPAbsenceService $absenceService)
    {
        $this->absenceService = $absenceService;
    }

    public function isAttendanceNoteEnabled(): bool
    {
        return SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
    }

    public function getAttendanceNoteSettings(): array
    {
        return [
            'zero_unjustified' => (float) SettingsHelper::get(
                'attendance_note_zero_unjustified',
                self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_zero_unjustified']
            ),
            'one_unjustified' => (float) SettingsHelper::get(
                'attendance_note_one_unjustified',
                self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_one_unjustified']
            ),
            'two_or_more_unjustified' => (float) SettingsHelper::get(
                'attendance_note_two_or_more_unjustified',
                self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_two_or_more_unjustified']
            ),
        ];
    }

    public function resolveAttendanceNote($absencesJustifiees, $absencesNonJustifiees): float
    {
        if (! $this->isAttendanceNoteEnabled()) {
            return 0.0;
        }

        $bareme = $this->getAttendanceNoteSettings();
        $absencesNonJustifiees = (float) $absencesNonJustifiees;

        if ($absencesNonJustifiees <= 0.0) {
            return $bareme['zero_unjustified'];
        }

        if ($absencesNonJustifiees < 2.0) {
            return $bareme['one_unjustified'];
        }

        return $bareme['two_or_more_unjustified'];
    }

    public function calculateEffectiveAttendanceNoteForStudent(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode = 'annuel'
    ): float {
        if (! $this->isAttendanceNoteEnabled()) {
            return 0.0;
        }

        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($anneeUniversitaireId);
        if (! $anneeUniversitaire) {
            return 0.0;
        }

        $absences = $this->absenceService->calculerDetailAbsences(
            $etudiantId,
            $classeId,
            $anneeUniversitaire->date_debut ?? null,
            $anneeUniversitaire->date_fin ?? null,
            $anneeUniversitaireId,
            $periode
        );

        return $this->resolveAttendanceNote(
            $absences['justifiees'] ?? 0,
            $absences['non_justifiees'] ?? 0
        );
    }

    public function getEffectiveBulletinAttendanceNote(?ESBTPBulletin $bulletin): float
    {
        if (! $bulletin || ! $this->isAttendanceNoteEnabled()) {
            return 0.0;
        }

        return (float) ($bulletin->note_assiduite ?? 0);
    }

    public function getEffectiveBulletinAverage(?ESBTPBulletin $bulletin): ?float
    {
        if (! $bulletin || $bulletin->moyenne_generale === null) {
            return null;
        }

        return (float) $bulletin->moyenne_generale + $this->getEffectiveBulletinAttendanceNote($bulletin);
    }

    public function getAlignedBulletinAverageForPeriode(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        string $currentPeriode,
        float $currentAverage,
        ?float $currentNoteAssiduite = null
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

        if ($bulletin && $bulletin->moyenne_generale !== null && $bulletin->moyenne_generale > 0) {
            return $this->getEffectiveBulletinAverage($bulletin);
        }

        $rawAvg = $this->calculateStudentAverageForPeriode($etudiantId, $classeId, $anneeUniversitaireId, $periode);
        if ($rawAvg === null) {
            return null;
        }

        $attendanceNote = $currentNoteAssiduite;
        if ($attendanceNote === null) {
            $attendancePeriode = $currentPeriode === 'annuel' ? 'annuel' : $periode;
            $attendanceNote = $this->calculateEffectiveAttendanceNoteForStudent(
                $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $attendancePeriode
            );
        }

        return $rawAvg + $attendanceNote;
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
        $inscription = $etudiant->inscriptions()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->first();
        $inscriptionWorkflowAlert = InscriptionWorkflowAlertPresenter::fromInscription($inscription, $anneeUniversitaire);

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
                        'coefficient' => $this->getCoefficientForCombination($matiereId, $classe->id, $anneeUniversitaireId),
                        'rang' => '-',
                        'appreciation' => '',
                        'type_formation' => $typeFormation,
                    ];
                }

                // BUG FIX : on capture aussi `bareme` et `is_absent` pour permettre la normalisation /20
                // et l'exclusion des absences dans le calcul de moyenne par matière (cf. computeMoyenneFromNotesData).
                $resultatsParMatiere[$matiereId]->notes[] = [
                    'note' => $note->note,
                    'coefficient' => $note->evaluation->coefficient,
                    'bareme' => $note->evaluation->bareme ?: 20,
                    'is_absent' => (bool) $note->is_absent,
                ];

                // Utiliser uniquement les professeurs configurés
                $professeurs[$matiereId] = $professeursConfigures[$matiereId] ?? '';
            }
        }

        // Calculer les moyennes pondérées pour chaque matière (automatiques)
        // BUG FIX : on normalise CHAQUE note par son barème avant pondération.
        // Avant : note brute (15/30 + 10/20)/2 = 12.5 au lieu de (10 + 10)/2 = 10.
        foreach ($resultatsParMatiere as $matiereId => $resultat) {
            $resultat->moyenne = $this->computeMoyenneFromNotesData($resultat->notes);
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
                        'coefficient' => $this->getCoefficientForCombination($matiereId, $classe->id, $anneeUniversitaireId),
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
                    $resultatsParMatiere[$matiereId]->coefficient = $this->getCoefficientForCombination($matiereId, $classe->id, $anneeUniversitaireId);
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
        // (priorité à la saisie manuelle par matière si disponible pour cette année/période)
        $absences = $this->absenceService->calculerDetailAbsences(
            $etudiant->id,
            $classe->id,
            $anneeUniversitaire->date_debut,
            $anneeUniversitaire->date_fin,
            $anneeUniversitaire->id,
            $periode
        );
        // Calculer la note d'assiduité seulement si l'affichage est activé
        $afficherNoteAssiduite = SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
        $noteAssiduite = $afficherNoteAssiduite ? $this->calculerNoteAssiduite($absences['justifiees'], $absences['non_justifiees']) : 0;
        $moyenneAvecAssiduite = $moyenneGlobale + $noteAssiduite;

        // Effectif de la classe aligné sur classes.show: inscriptions validées uniquement.
        $effectif = $this->countValidatedClassStudents($classe->id, $anneeUniversitaire->id);

        // Persister la moyenne BRUTE (sans assiduité) dans le bulletin.
        // L'assiduité est stockée séparément dans note_assiduite.
        // getBulletinAverageForPeriode() additionne les deux.
        $bulletin->moyenne_generale = $moyenneGlobale;
        $bulletin->note_assiduite = $noteAssiduite;
        $bulletin->effectif_classe = $effectif;
        $bulletin->save();

        // Calculer le vrai rang (basé sur tous les bulletins de la classe/période)
        $this->calculerRang($bulletin);
        $bulletin->refresh();
        $rang = $bulletin->rang ?? 1;

        // Calculer les vraies statistiques de classe
        $statsClasse = $this->calculerStatistiquesClasse($classe->id, $anneeUniversitaire->id, $periode);

        // Calculer les rangs par matière via ESBTPResultat (batch-fetch pour éviter N+1)
        $allMatiereIds = collect($resultatsParMatiere)->pluck('matiere_id')->filter()->unique()->values()->all();
        $rangsParMatiere = $this->calculerRangsParMatiereBatch($allMatiereIds, $etudiantId, $classeId, $anneeUniversitaireId, $periodeNormalized);
        foreach ($resultatsGeneraux as $resultat) {
            $resultat->rang = $rangsParMatiere[$resultat->matiere_id] ?? '-';
        }
        foreach ($resultatsTechniques as $resultat) {
            $resultat->rang = $rangsParMatiere[$resultat->matiere_id] ?? '-';
        }

        // Déterminer l'appréciation selon la moyenne
        $appreciation = $this->getAppreciation($moyenneGlobale);

        // Préparer la configuration PDF
        $settings = $this->getPDFConfig();

        $semesterWeights = $this->getSemesterWeights();
        $warnings = [];

        // Vérifier si le bulletin de l'autre semestre existe en base
        $otherPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
        $otherBulletinExists = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $otherPeriode)
            ->where('moyenne_generale', '>', 0)
            ->exists();

        // Tronc commun : si l'inscription est une spécialisation, chercher S1 dans la classe d'origine
        $classeIdS1 = $classeId;
        $classeTroncCommun = null;
        $inscription = \App\Models\ESBTPInscription::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('status', ['active', 'terminée'])
            ->first();

        if ($inscription && $inscription->isSpecialisation()
            && \App\Helpers\SettingsHelper::get('tronc_commun_mga_include_s1', true)) {
            $origine = $inscription->inscriptionOrigine;
            if ($origine && $origine->classe_id) {
                $classeIdS1 = $origine->classe_id;
                $classeTroncCommun = $origine->classe;
            }
        }

        $moyenneSemestre1 = $this->getAlignedBulletinAverageForPeriode(
            $etudiantId,
            $classeIdS1, // Peut être la classe tronc commun si spécialisation
            $anneeUniversitaireId,
            'semestre1',
            $periode,
            $moyenneAvecAssiduite,
            $noteAssiduite
        );
        $moyenneSemestre2 = $this->getAlignedBulletinAverageForPeriode(
            $etudiantId,
            $classeId, // Toujours la classe actuelle pour S2
            $anneeUniversitaireId,
            'semestre2',
            $periode,
            $moyenneAvecAssiduite,
            $noteAssiduite
        );
        $moyenneAnnuelle = $this->calculateAnnualAverage($moyenneSemestre1, $moyenneSemestre2, $semesterWeights);

        // Warning si le bulletin de l'autre semestre n'a pas été généré officiellement
        if (! $otherBulletinExists && ($periode === 'semestre2' && $moyenneSemestre1 !== null)) {
            $warnings[] = [
                'type' => 'fallback_calcul',
                'message' => 'La moyenne du Semestre 1 a été calculée à la volée car aucun bulletin S1 officiel n\'a été généré. Pour des résultats plus fiables, générez d\'abord le bulletin du Semestre 1.',
            ];
        }
        if (! $otherBulletinExists && ($periode === 'semestre1' && $moyenneSemestre2 !== null)) {
            $warnings[] = [
                'type' => 'fallback_calcul',
                'message' => 'La moyenne du Semestre 2 a été calculée à la volée car aucun bulletin S2 officiel n\'a été généré. Pour des résultats plus fiables, générez d\'abord le bulletin du Semestre 2.',
            ];
        }

        // Préparer la photo de l'étudiant en base64 pour le PDF
        $photoEtudiantBase64 = $this->preparePhotoEtudiantBase64($etudiant);

        // Note de conduite (absences par matière)
        $conduiteEnabled = SettingsHelper::get('bulletin_conduite_enabled', '0') === '1';
        $absencesParMatiere = [];
        $noteConduite = null;
        $mentionConduite = '';
        $totalHeuresAbsencesParMatiere = 0;

        if ($conduiteEnabled) {
            $absencesParMatiereData = $this->absenceService->calculerAbsencesParMatiere(
                $etudiant->id,
                $classe->id,
                $anneeUniversitaire->date_debut,
                $anneeUniversitaire->date_fin,
                $anneeUniversitaire->id,
                $periode
            );
            $absencesParMatiere = $absencesParMatiereData['par_matiere'] ?? [];
            $totalHeuresAbsencesParMatiere = $absencesParMatiereData['total_heures'] ?? 0;
            $noteConduite = $this->calculerNoteConduite($totalHeuresAbsencesParMatiere);
            $mentionConduite = $this->getMentionConduite($noteConduite);
        }

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
            'note_assiduite' => $noteAssiduite,
            'rang' => $rang,
            'effectif' => $effectif,
            'meilleure_moyenne' => $statsClasse['meilleure_moyenne'],
            'plus_faible_moyenne' => $statsClasse['plus_faible_moyenne'],
            'moyenne_classe' => $statsClasse['moyenne_classe'],
            'appreciation' => $appreciation,
            'absences' => $absences,
            'absencesJustifiees' => $absences['justifiees'] ?? 0,
            'absencesNonJustifiees' => $absences['non_justifiees'] ?? 0,
            'absences_justifiees' => $absences['justifiees'] ?? 0,
            'absences_non_justifiees' => $absences['non_justifiees'] ?? 0,
            'professeurs' => $professeurs,
            'date_edition' => date('d/m/Y'),
            'settings' => $settings,
            'photoEtudiantBase64' => $photoEtudiantBase64,
            'moyenneSemestre1' => $moyenneSemestre1,
            'moyenneSemestre2' => $moyenneSemestre2,
            'moyenneAnnuelle' => $moyenneAnnuelle,
            'semesterWeights' => $semesterWeights,
            'warnings' => $warnings,
            'noteConduite' => $noteConduite,
            'mentionConduite' => $mentionConduite,
            'absencesParMatiere' => $absencesParMatiere,
            'totalHeuresAbsencesParMatiere' => $totalHeuresAbsencesParMatiere,
            'classeTroncCommun' => $classeTroncCommun,
            'isSpecialisation' => $classeTroncCommun !== null,
            'inscriptionWorkflowAlert' => $inscriptionWorkflowAlert,
        ];
    }

    public function getBulletinTemplateView(): string
    {
        $style = SettingsHelper::get('bulletin_style', 'yakro');
        return $style === 'abidjan'
            ? 'esbtp.bulletins.pdf-configurable-abidjan'
            : 'esbtp.bulletins.pdf-configurable';
    }

    public function getBulletinPreviewView(): string
    {
        $style = SettingsHelper::get('bulletin_style', 'yakro');
        return $style === 'abidjan'
            ? 'esbtp.bulletins.preview-configurable-abidjan'
            : 'esbtp.bulletins.preview-configurable';
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

    public function normalizePeriode(string $periode): string
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
     * Calcule la moyenne pondérée d'une matière à partir d'un tableau de notes brutes.
     *
     * Algorithme officiel KLASSCI (mai 2026) :
     *  1. Exclure les notes marquées absentes (is_absent = true).
     *  2. Ignorer les notes dont le barème est invalide (<= 0) — garde-fou silencieux.
     *  3. Normaliser chaque note sur 20 : (note / barème) * 20.
     *  4. Pondérer par le coefficient de l'évaluation, faire la moyenne arithmétique pondérée.
     *  5. Arrondir à 2 décimales (cohérence avec l'affichage UI).
     *
     * Pure function : aucun accès DB, aucun side-effect → testable unitairement.
     *
     * @param array<int, array{note: float|int|string, coefficient: float|int, bareme?: float|int|null, is_absent?: bool}> $notes
     */
    public function computeMoyenneFromNotesData(array $notes): float
    {
        $totalPoints = 0.0;
        $totalCoeffs = 0.0;

        foreach ($notes as $noteData) {
            if (! empty($noteData['is_absent'])) {
                continue;
            }

            $bareme = (float) ($noteData['bareme'] ?? 20);
            if ($bareme <= 0) {
                continue;
            }

            $coefficient = (float) ($noteData['coefficient'] ?? 1);
            $noteValue = is_numeric($noteData['note'] ?? null) ? (float) $noteData['note'] : 0.0;
            $normalized = ($noteValue / $bareme) * 20;

            $totalPoints += $normalized * $coefficient;
            $totalCoeffs += $coefficient;
        }

        if ($totalCoeffs <= 0) {
            return 0.0;
        }

        return round($totalPoints / $totalCoeffs, 2);
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
            case $absencesNonJustifiees == 1:
                return 0;
            case $absencesNonJustifiees == 2:
                return -0.13;
            case $absencesNonJustifiees == 3:
            case $absencesNonJustifiees == 4:
                return -0.39;
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
        $afficherNoteAssiduite = SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
        $anneeUniv = $afficherNoteAssiduite ? \App\Models\ESBTPAnneeUniversitaire::find($anneeUniversitaireId) : null;

        foreach ($etudiants as $etudiant) {
            try {
                // Calculer la moyenne globale de cet étudiant
                $moyenneEtudiant = $this->calculerMoyenneGlobaleEtudiant($etudiant->id, $classeId, $anneeUniversitaireId, $periode);

                // Ajouter la note d'assiduité seulement si l'affichage est activé et si la moyenne est valide
                if ($afficherNoteAssiduite && $moyenneEtudiant > 0) {
                    $absencesEtudiant = $this->absenceService->calculerDetailAbsences(
                        $etudiant->id,
                        $classeId,
                        $anneeUniv->date_debut ?? null,
                        $anneeUniv->date_fin ?? null,
                        $anneeUniversitaireId,
                        $periode
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
                    $coefficient = $this->getCoefficientForCombination($resultat->matiere_id, $classe->id, $anneeUniversitaireId);
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
                $coefficient = $this->getCoefficientForCombination($matiereId, $classe->id, $anneeUniversitaireId);
            } catch (\RuntimeException $e) {
                $coefficient = 1;
            }

            $totalPoints += $moyenneMatiere * $coefficient;
            $totalCoefficients += $coefficient;
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }

    /**
     * Récupère la configuration PDF (méthode canonique — utilisée par le contrôleur et la vue)
     */
    public function getPDFConfig(): array
    {
        return [
            // Informations de l'établissement
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'school_website' => \App\Helpers\SettingsHelper::get('school_website', ''),
            'school_country' => \App\Helpers\SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
            'school_logo' => \App\Helpers\SettingsHelper::get('school_logo', ''),
            'director_name' => \App\Helpers\SettingsHelper::get('director_name', ''),
            'director_title' => \App\Helpers\SettingsHelper::get('director_title', 'Directeur'),

            // Configuration PDF
            'pdf_margin_top' => \App\Helpers\SettingsHelper::get('pdf.margin_top', 15),
            'pdf_margin_bottom' => \App\Helpers\SettingsHelper::get('pdf.margin_bottom', 15),
            'pdf_margin_left' => \App\Helpers\SettingsHelper::get('pdf.margin_left', 10),
            'pdf_margin_right' => \App\Helpers\SettingsHelper::get('pdf.margin_right', 10),
            'pdf_font_size' => \App\Helpers\SettingsHelper::get('pdf.font_size', 12),
            'pdf_header_font_size' => \App\Helpers\SettingsHelper::get('pdf.header_font_size', 14),
            'pdf_title_font_size' => \App\Helpers\SettingsHelper::get('pdf.title_font_size', 16),
            'pdf_show_watermark' => \App\Helpers\SettingsHelper::get('pdf.show_watermark', false),
            'pdf_watermark_text' => \App\Helpers\SettingsHelper::get('pdf.watermark_text', 'CONFIDENTIEL'),
            'pdf_show_signature' => \App\Helpers\SettingsHelper::get('pdf.show_signature', true),
            'pdf_header_text' => \App\Helpers\SettingsHelper::get('pdf.header_text', ''),
            'pdf_footer_text' => \App\Helpers\SettingsHelper::get('pdf.footer_text', ''),

            // En-tête bulletin
            'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
            'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '11'),
            'bulletin_show_header' => \App\Helpers\SettingsHelper::get('bulletin_show_header', '1'),
            'bulletin_show_logo' => \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1'),
            'bulletin_show_republic_info' => \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1'),
            'bulletin_republic_text' => \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
            'bulletin_union_text' => \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail'),
            'bulletin_show_ministry_info' => \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1'),
            'bulletin_ministry_text' => \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur'),
            'bulletin_show_school_info' => \App\Helpers\SettingsHelper::get('bulletin_show_school_info', '1'),
            'bulletin_show_edition_date' => \App\Helpers\SettingsHelper::get('bulletin_show_edition_date', '1'),
            'bulletin_show_cycle_info' => \App\Helpers\SettingsHelper::get('bulletin_show_cycle_info', '1'),
            'bulletin_cycle_text' => \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur'),
            'bulletin_cycle_abbreviation' => \App\Helpers\SettingsHelper::get('bulletin_cycle_abbreviation', 'BTS'),

            // Informations étudiant
            'bulletin_show_student_info' => \App\Helpers\SettingsHelper::get('bulletin_show_student_info', '1'),
            'bulletin_show_matricule' => \App\Helpers\SettingsHelper::get('bulletin_show_matricule', '1'),
            'bulletin_show_birth_date' => \App\Helpers\SettingsHelper::get('bulletin_show_birth_date', '1'),
            'bulletin_show_redoublant' => \App\Helpers\SettingsHelper::get('bulletin_show_redoublant', '1'),
            'bulletin_show_class_info' => \App\Helpers\SettingsHelper::get('bulletin_show_class_info', '1'),
            'bulletin_show_effectif' => \App\Helpers\SettingsHelper::get('bulletin_show_effectif', '1'),

            // Tableau des matières
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

            // Absences
            'bulletin_show_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_absences', '1'),
            'bulletin_show_justified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_justified_absences', '1'),
            'bulletin_show_unjustified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_unjustified_absences', '1'),

            // Section résultats
            'bulletin_show_results_section' => \App\Helpers\SettingsHelper::get('bulletin_show_results_section', '1'),
            'bulletin_show_raw_average' => \App\Helpers\SettingsHelper::get('bulletin_show_raw_average', '1'),
            'bulletin_show_attendance_note' => \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1'),
            'bulletin_show_semester_average' => \App\Helpers\SettingsHelper::get('bulletin_show_semester_average', '1'),
            'bulletin_show_student_rank' => \App\Helpers\SettingsHelper::get('bulletin_show_student_rank', '1'),
            'bulletin_show_attendance' => \App\Helpers\SettingsHelper::get('bulletin_show_attendance', '1'),
            'bulletin_show_general_average' => \App\Helpers\SettingsHelper::get('bulletin_show_general_average', '1'),
            'bulletin_show_technical_average' => \App\Helpers\SettingsHelper::get('bulletin_show_technical_average', '1'),
            'bulletin_show_global_average' => \App\Helpers\SettingsHelper::get('bulletin_show_global_average', '1'),
            'bulletin_show_class_rank' => \App\Helpers\SettingsHelper::get('bulletin_show_class_rank', '1'),
            'bulletin_show_class_size' => \App\Helpers\SettingsHelper::get('bulletin_show_class_size', '1'),

            // Mentions
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

            // Statistiques
            'bulletin_show_statistics' => \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1'),
            'bulletin_show_highest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1'),
            'bulletin_show_lowest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1'),
            'bulletin_show_class_average' => \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1'),
            'bulletin_include_attendance_in_stats' => \App\Helpers\SettingsHelper::get('bulletin_include_attendance_in_stats', '1'),

            // Note de conduite
            'bulletin_conduite_enabled' => \App\Helpers\SettingsHelper::get('bulletin_conduite_enabled', '0'),
            'conduite_note_defaut' => \App\Helpers\SettingsHelper::get('conduite_note_defaut', '16'),
            'conduite_heures_par_point' => \App\Helpers\SettingsHelper::get('conduite_heures_par_point', '4'),
            'bulletin_show_absences_par_matiere' => \App\Helpers\SettingsHelper::get('bulletin_show_absences_par_matiere', '1'),

            // Tronc commun
            'tronc_commun_bulletin_show_origin' => \App\Helpers\SettingsHelper::get('tronc_commun_bulletin_show_origin', '1'),

            // Décision et signatures
            'bulletin_show_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_council_decision', '1'),
            'bulletin_show_class_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_class_council_decision', '1'),
            'bulletin_show_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_signature', '1'),
            'bulletin_show_signatures' => \App\Helpers\SettingsHelper::get('bulletin_show_signatures', '1'),
            'bulletin_show_director_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_director_signature', '1'),
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

    public function calculateMoyennesForStudent($etudiantId, $classeId, $periode, $anneeUniversitaireId, $matieres)
    {
        // Normaliser la période
        $periodePourBDD = $periode;
        if ($periode == '1') {
            $periodePourBDD = 'semestre1';
        } elseif ($periode == '2') {
            $periodePourBDD = 'semestre2';
        }

        // Récupérer toutes les notes de l'étudiant
        $notesQuery = ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere', 'matiere']);

        // Filtrer par période (semestre)
        if ($periodePourBDD) {
            $notesQuery->where(function ($q) use ($periodePourBDD) {
                $q->where('semestre', $periodePourBDD)
                    ->orWhereHas('evaluation', function ($query) use ($periodePourBDD) {
                        $query->where('periode', $periodePourBDD);
                    });
            });
        }

        // Filtrer par classe
        $notesQuery->byClasse($classeId);

        // Filtrer par année universitaire (avec année précédente)
        $notesQuery->byAnneeUniversitaireWithPrevious($anneeUniversitaireId);

        $notes = $notesQuery->get();

        // Organiser les notes par matière
        $notesByMatiere = [];
        foreach ($notes as $note) {
            if (! $note->evaluation || ! $note->evaluation->matiere) {
                continue;
            }

            $matiereId = $note->evaluation->matiere->id;
            if (! isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                ];
            }

            $notesByMatiere[$matiereId]['notes'][] = $note;
        }

        // Calculer la moyenne pour chaque matière
        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($matiereData['notes'] as $note) {
                if ($note->evaluation && $note->evaluation->bareme > 0) {
                    $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                    $bareme = floatval($note->evaluation->bareme);
                    $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                    $normalized = ($noteValue / $bareme) * 20;
                    $totalPoints += $normalized * $coefficient;
                    $totalCoefficients += $coefficient;
                }
            }

            $matiereData['total_points'] = $totalPoints;
            $matiereData['total_coefficients'] = $totalCoefficients;
            $matiereData['moyenne'] = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : null;
        }

        // Retourner les moyennes calculées indexées par matiere_id
        $result = [];
        foreach ($matieres as $matiere) {
            $result[$matiere->id] = [
                'moyenne' => isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null,
                'source' => isset($notesByMatiere[$matiere->id]) && $notesByMatiere[$matiere->id]['moyenne'] !== null ? 'calculee' : 'manuelle',
            ];
        }

        return $result;
    }


    public function calculateStudentAverageForPeriode(int $etudiantId, ?int $classeId, ?int $anneeUniversitaireId, string $periode): ?float
    {
        if ($classeId && $anneeUniversitaireId && in_array($periode, ['semestre1', 'semestre2'], true)) {
            $snapshot = app(\App\Services\ESBTP\BtsCurrentResultSnapshotService::class)
                ->getSemesterSnapshot($etudiantId, $classeId, $anneeUniversitaireId, $periode);

            return $snapshot['raw_total'] ?? null;
        }

        $semestre = $periode === 'semestre2' ? '2' : '1';

        $notesQuery = ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation', 'evaluation.matiere']);

        $notesQuery->where(function ($q) use ($semestre, $periode) {
            $q->where('semestre', $semestre)
                ->orWhereHas('evaluation', function ($query) use ($semestre, $periode) {
                    $query->where('periode', $periode)
                        ->orWhere('periode', $semestre);
                });
        });

        $notes = $notesQuery->get();

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
                    'moyenne' => 0,
                ];
            }

            if ($note->evaluation->bareme > 0) {
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;
                $normalized = ($noteValue / $bareme) * 20;
                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                $notesByMatiere[$matiereId]['total_points'] += $normalized * $coefficient;
                $notesByMatiere[$matiereId]['total_coefficients'] += $coefficient;
            }
        }

        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            if ($matiereData['total_coefficients'] > 0) {
                $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];
            }
        }

        $resultats = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->when($classeId, function ($query) use ($classeId) {
                return $query->where('classe_id', $classeId);
            })
            ->when($anneeUniversitaireId, function ($query) use ($anneeUniversitaireId) {
                return $query->where('annee_universitaire_id', $anneeUniversitaireId);
            })
            ->where('periode', $periode)
            ->with('matiere')
            ->get();

        foreach ($resultats as $resultat) {
            if (! $resultat->matiere) {
                continue;
            }

            $matiereId = $resultat->matiere_id;
            if (! isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                ];
            }

            $notesByMatiere[$matiereId]['moyenne'] = $resultat->moyenne;
        }

        // Pondérer par coefficients matière (cohérent avec calculerMoyennePonderee)
        $sommePoints = 0;
        $sommeCoefs = 0;

        foreach ($notesByMatiere as $matiereId => $matiereData) {
            if ($matiereData['moyenne'] > 0) {
                $coeff = $this->getCoefficientForCombination($matiereId, $classeId ?? 0, $anneeUniversitaireId ?? 0);
                $sommePoints += $matiereData['moyenne'] * $coeff;
                $sommeCoefs += $coeff;
            }
        }

        if ($sommeCoefs <= 0) {
            return null;
        }

        return $sommePoints / $sommeCoefs;
    }


    public function prepareLogoBase64($logoPath)
    {
        // Essayer d'abord le chemin depuis storage (logos uploadés)
        if ($logoPath) {
            $storagePath = storage_path('app/public/'.$logoPath);
            if (file_exists($storagePath)) {
                $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($storagePath);
                Log::info('Logo uploadé chargé avec succès depuis: '.$storagePath);

                return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
            }

            // Essayer aussi dans public/ pour compatibilité
            $publicPath = public_path($logoPath);
            if (file_exists($publicPath)) {
                $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($publicPath);
                Log::info('Logo public chargé avec succès depuis: '.$publicPath);

                return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
            }
        }

        // Essayer les chemins alternatifs
        $alternativePaths = [
            'images/esbtp_logo.png',
            'images/logo.jpeg',
            'images/esbtp_logo_white.png',
            'storage/logos/'.basename($logoPath),
        ];

        foreach ($alternativePaths as $altPath) {
            $fullPath = public_path($altPath);
            if (file_exists($fullPath)) {
                $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($fullPath);
                Log::info('Logo alternatif chargé avec succès depuis: '.$fullPath);

                return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
            }
        }

        Log::warning('Aucun logo trouvé pour le chemin: '.$logoPath.'. Chemins testés: storage et public + alternatives');

        return null;
    }


    public function calculerMoyenneGenerale(ESBTPBulletin $bulletin)
    {
        Log::info('Calcul de la moyenne générale pour le bulletin '.$bulletin->id);

        try {
            $resultats = $bulletin->resultats;
            Log::info('Nombre de résultats trouvés: '.$resultats->count());

            if ($resultats->isEmpty()) {
                Log::info('Aucun résultat trouvé pour le bulletin '.$bulletin->id);
                $bulletin->moyenne_generale = null;
                $bulletin->save();

                return;
            }

            $sommePoints = 0;
            $sommeCoefficients = 0;

            foreach ($resultats as $resultat) {
                if ($resultat->moyenne !== null) {
                    Log::info('Résultat pour matière '.$resultat->matiere_id.': moyenne='.$resultat->moyenne.', coefficient='.$resultat->coefficient);
                    $sommePoints += $resultat->moyenne * $resultat->coefficient;
                    $sommeCoefficients += $resultat->coefficient;
                } else {
                    Log::info('Résultat ignoré pour matière '.$resultat->matiere_id.' (moyenne null)');
                }
            }

            Log::info('Somme des points: '.$sommePoints.', Somme des coefficients: '.$sommeCoefficients);
            $moyenneGenerale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
            Log::info('Moyenne générale calculée: '.$moyenneGenerale);

            $bulletin->moyenne_generale = $moyenneGenerale;
            $bulletin->save();
            Log::info('Moyenne générale enregistrée pour le bulletin '.$bulletin->id);

            // Calculer le rang si la moyenne a changé
            $this->calculerRang($bulletin);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul de la moyenne générale: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());
            throw $e;
        }
    }


    public function calculerRang($bulletin)
    {
        $base = ESBTPBulletin::where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('periode', $bulletin->periode)
            ->whereNotNull('moyenne_generale');

        $bulletin->effectif_classe = $this->countValidatedClassStudents(
            $bulletin->classe_id,
            $bulletin->annee_universitaire_id
        );
        $bulletin->rang = (clone $base)
            ->where('moyenne_generale', '>', $bulletin->moyenne_generale ?? 0)
            ->count() + 1;

        $bulletin->save();
    }

    private function countValidatedClassStudents(int $classeId, int $anneeUniversitaireId): int
    {
        return ESBTPInscription::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->distinct('etudiant_id')
            ->count('etudiant_id');
    }

    /**
     * Calcule les rangs d'un étudiant pour plusieurs matières en une seule requête.
     * Retourne un tableau [matiere_id => rang_string].
     */
    private function calculerRangsParMatiereBatch(array $matiereIds, int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        if (empty($matiereIds)) {
            return [];
        }

        $resultats = ESBTPResultat::whereIn('matiere_id', $matiereIds)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $periode)
            ->whereNotNull('moyenne')
            ->orderByDesc('moyenne')
            ->get()
            ->groupBy('matiere_id');

        $rangs = [];
        foreach ($matiereIds as $matiereId) {
            $matiereResultats = $resultats->get($matiereId);
            if (! $matiereResultats || $matiereResultats->isEmpty()) {
                $rangs[$matiereId] = '-';
                continue;
            }

            $rang = 1;
            $prevMoyenne = null;
            $prevRang = 1;
            $found = false;

            foreach ($matiereResultats->sortByDesc('moyenne')->values() as $r) {
                if ($prevMoyenne !== null && $r->moyenne < $prevMoyenne) {
                    $prevRang = $rang;
                }
                if ($r->etudiant_id == $etudiantId) {
                    $rangs[$matiereId] = (string) $prevRang;
                    $found = true;
                    break;
                }
                $prevMoyenne = $r->moyenne;
                $rang++;
            }

            if (! $found) {
                $rangs[$matiereId] = '-';
            }
        }

        return $rangs;
    }


    public function calculerAbsencesDetailees($bulletin)
    {
        try {
            \Log::info('Début du calcul des absences détaillées pour le bulletin #'.$bulletin->id);

            // Vérifier que les relations nécessaires sont chargées
            if (! $bulletin->etudiant || ! $bulletin->classe || ! $bulletin->anneeUniversitaire) {
                \Log::error('Relations essentielles manquantes pour le calcul des absences du bulletin #'.$bulletin->id);
                throw new \Exception("Données incomplètes pour calculer les absences. Veuillez vérifier que l'étudiant, la classe et l'année universitaire sont correctement définis.");
            }

            // Vérifier que les dates de l'année universitaire sont définies
            if (! $bulletin->anneeUniversitaire->date_debut || ! $bulletin->anneeUniversitaire->date_fin) {
                \Log::error('Dates de l\'année universitaire non définies pour le bulletin #'.$bulletin->id);
                throw new \Exception("Les dates de début et de fin de l'année universitaire ne sont pas définies.");
            }

            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin,
                $bulletin->annee_universitaire_id,
                $bulletin->periode
            );

            \Log::info('Absences détaillées calculées avec succès pour le bulletin #'.$bulletin->id, $absences);

            return $absences;

        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des absences détaillées: '.$e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'etudiant_id' => $bulletin->etudiant_id ?? 'non défini',
                'classe_id' => $bulletin->classe_id ?? 'non défini',
                'trace' => $e->getTraceAsString(),
            ]);

            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0,
                'detail' => [
                    'justifiees' => [],
                    'non_justifiees' => [],
                ],
            ];
        }
    }


    public function computeResultatsKpis(Collection $studentIds, $classe_id, $annee_universitaire_id, $semestre): array
    {
        $kpis = [
            'total_etudiants' => $studentIds->count(),
            'moyenne_generale' => null,
            'taux_reussite' => null,
            'bulletins_count' => 0,
        ];

        if ($studentIds->isEmpty()) {
            return $kpis;
        }

        $moyennes = [];
        $rangs = [];

        $this->getPreCalculatedResults(
            $studentIds->map(function ($id) {
                return (object) ['id' => $id];
            })->all(),
            $classe_id,
            $annee_universitaire_id,
            $semestre,
            $moyennes,
            $rangs
        );

        if (empty($moyennes)) {
            $students = ESBTPEtudiant::whereIn('id', $studentIds)->get();

            if ($students->isNotEmpty()) {
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $studentIds)
                    ->with(['evaluation', 'evaluation.classe', 'evaluation.matiere']);

                if ($classe_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($classe_id) {
                        $query->where('classe_id', $classe_id);
                    });
                }

                if ($annee_universitaire_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    });
                }

                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                $this->calculateStudentStatsFixed($students, $notes, $moyennes, $rangs, $classe_id, $annee_universitaire_id);
            }
        }

        if (! empty($moyennes)) {
            $values = array_values($moyennes);
            $kpis['moyenne_generale'] = round(array_sum($values) / max(count($values), 1), 2);

            $reussites = array_filter($values, function ($moyenne) {
                return $moyenne >= 10;
            });

            $kpis['taux_reussite'] = count($values) > 0
                ? round((count($reussites) / count($values)) * 100, 1)
                : null;
        }

        $bulletinsQuery = ESBTPBulletin::whereIn('etudiant_id', $studentIds);

        if ($classe_id) {
            $bulletinsQuery->where('classe_id', $classe_id);
        }

        if ($annee_universitaire_id) {
            $bulletinsQuery->where('annee_universitaire_id', $annee_universitaire_id);
        }

        if ($semestre) {
            $bulletinsQuery->where('periode', 'semestre'.$semestre);
        }

        $kpis['bulletins_count'] = $bulletinsQuery->count();

        return $kpis;
    }


    public function buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses)
    {
        if ($classe_id) {
            // Get students through inscriptions for the selected class and year
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                if (! $include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
                ->with(['user', 'inscriptions.classe.filiere', 'inscriptions.classe.niveau'])
                ->orderBy('nom')
                ->orderBy('prenoms');

        } elseif ($annee_universitaire_id) {
            // If no class selected but academic year is set, get all students enrolled in that year
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);

                if (! $include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
                ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
                    $query->where('annee_universitaire_id', $annee_universitaire_id);
                }])
                ->orderBy('nom')
                ->orderBy('prenoms');

        } else {
            // If no filters are applied, get all students
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($include_all_statuses) {
                if (! $include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
                ->with(['user', 'inscriptions'])
                ->orderBy('nom')
                ->orderBy('prenoms');
        }
    }


    public function getPreCalculatedResults($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$moyennes, &$rangs)
    {
        \Log::info('Tentative de récupération des résultats pré-calculés', [
            'etudiants_count' => count($etudiants),
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id,
            'semestre' => $semestre,
        ]);

        $student_ids = collect($etudiants)->pluck('id')->toArray();

        // Récupérer les résultats pré-calculés de la table ESBTPResultat
        $resultatsQuery = \App\Models\ESBTPResultat::whereIn('etudiant_id', $student_ids);

        if ($classe_id) {
            $resultatsQuery->where('classe_id', $classe_id);
        }

        if ($annee_universitaire_id) {
            $resultatsQuery->where('annee_universitaire_id', $annee_universitaire_id);
        }

        if ($semestre) {
            $resultatsQuery->where('periode', 'semestre'.$semestre);
        }

        $resultats = $resultatsQuery->get();

        \Log::info('Résultats pré-calculés trouvés', [
            'resultats_count' => $resultats->count(),
        ]);

        // Extraire les moyennes et rangs
        foreach ($resultats as $resultat) {
            if ($resultat->moyenne !== null) {
                $moyennes[$resultat->etudiant_id] = $resultat->moyenne;
            }

            if ($resultat->rang !== null) {
                $rangs[$resultat->etudiant_id] = $resultat->rang;
            }
        }

        // Si pas de rangs pré-calculés mais on a des moyennes, calculer les rangs
        if (empty($rangs) && ! empty($moyennes)) {
            arsort($moyennes);
            $rank = 1;
            foreach (array_keys($moyennes) as $etudiantId) {
                $rangs[$etudiantId] = $rank++;
            }
        }

        \Log::info('Résultats pré-calculés récupérés', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs),
        ]);
    }


    public function calculateStudentStatsFixed($etudiants, $notes, &$moyennes, &$rangs, $classeId = null, $anneeUniversitaireId = null, $periode = null)
    {
        \Log::info('Calcul des statistiques étudiants - Étudiants: '.count($etudiants).', Notes: '.count($notes));
        \Log::info('Début du calcul des moyennes (logique corrigée) pour '.count($etudiants).' étudiants avec '.count($notes).' notes');

        // Group notes by student and matière - using the same logic as resultatEtudiant
        $notesByStudentMatiere = [];

        foreach ($notes as $note) {
            if (! $note->evaluation || ! $note->evaluation->matiere) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);

                continue;
            }

            $etudiantId = $note->etudiant_id;

            // CORRECTION: Use matiere_id from note directly, then from evaluation as fallback (same as resultatEtudiant)
            $matiere_id = $note->matiere_id;
            if (! $matiere_id && $note->evaluation && $note->evaluation->matiere) {
                $matiere_id = $note->evaluation->matiere->id;
            }

            if (! $matiere_id) {
                \Log::warning('Cannot determine matiere_id for note', ['note_id' => $note->id]);

                continue;
            }

            // Initialize student if not exists
            if (! isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            // Initialize matière for this student if not exists (same structure as resultatEtudiant)
            if (! isset($notesByStudentMatiere[$etudiantId][$matiere_id])) {
                $notesByStudentMatiere[$etudiantId][$matiere_id] = [
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                ];
            }

            // Calculate weighted note using EXACT same logic as resultatEtudiant
            if ($note->evaluation->bareme > 0) {
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;

                if ($noteValue === 'Absent' || ! is_numeric($noteValue)) {
                    $normalized = 0;
                } else {
                    $normalized = ($noteValue / $bareme) * 20;
                }

                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
                $ponderation = $normalized * $coefficient;

                $notesByStudentMatiere[$etudiantId][$matiere_id]['total_points'] += $ponderation;
                $notesByStudentMatiere[$etudiantId][$matiere_id]['total_coefficients'] += $coefficient;
            }
        }

        // Integrate ESBTPResultat (manual grade overrides) — same logic as calculateStudentAverageForPeriode
        if ($classeId && $anneeUniversitaireId) {
            $etudiantIds = $etudiants->pluck('id')->toArray();
            $resultatsQuery = \App\Models\ESBTPResultat::whereIn('etudiant_id', $etudiantIds)
                ->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeUniversitaireId);

            // Filtrer par période si spécifiée (évite de mélanger S1 et S2)
            if ($periode) {
                $resultatsQuery->where(function ($q) use ($periode) {
                    $q->where('periode', $periode)
                        ->orWhere('periode', 'semestre' . $periode);
                });
            }

            $resultatsManuel = $resultatsQuery->get()->groupBy('etudiant_id');

            foreach ($resultatsManuel as $etudiantId => $resultats) {
                if (! isset($notesByStudentMatiere[$etudiantId])) {
                    $notesByStudentMatiere[$etudiantId] = [];
                }
                foreach ($resultats as $resultat) {
                    $matiereId = $resultat->matiere_id;
                    if (! isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                        $notesByStudentMatiere[$etudiantId][$matiereId] = ['total_points' => 0, 'total_coefficients' => 0, 'moyenne' => 0];
                    }
                    // Manual moyenne overrides the note-computed value
                    $notesByStudentMatiere[$etudiantId][$matiereId]['total_points'] = $resultat->moyenne;
                    $notesByStudentMatiere[$etudiantId][$matiereId]['total_coefficients'] = 1;
                }
            }
        }

        // Calculate averages for each student using EXACT same logic as resultatEtudiant
        foreach ($etudiants as $etudiant) {
            if (! isset($notesByStudentMatiere[$etudiant->id])) {
                continue;
            }

            $moyenneGenerale = 0;
            $countValidMatieres = 0;

            // Calculate average for each matière (same as resultatEtudiant)
            foreach ($notesByStudentMatiere[$etudiant->id] as $matiere_id => &$matiereData) {
                if ($matiereData['total_coefficients'] > 0) {
                    $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];
                    // For overall average, treat each matière equally (same as resultatEtudiant)
                    $moyenneGenerale += $matiereData['moyenne'];
                    $countValidMatieres++;
                }
            }

            // Calculate the overall moyenne générale (same as resultatEtudiant)
            if ($countValidMatieres > 0) {
                $moyennes[$etudiant->id] = $moyenneGenerale / $countValidMatieres;
                \Log::debug('Moyenne calculée pour étudiant '.$etudiant->matricule, [
                    'etudiant_id' => $etudiant->id,
                    'moyenne' => $moyennes[$etudiant->id],
                    'matieres_count' => $countValidMatieres,
                ]);
            }
        }

        // Sort by average to calculate ranks
        if (count($moyennes) > 0) {
            arsort($moyennes);
            $rank = 1;
            foreach (array_keys($moyennes) as $etudiantId) {
                $rangs[$etudiantId] = $rank++;
            }
        }

        \Log::info('Calcul des moyennes terminé (logique corrigée):', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs),
        ]);
    }



    public function getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$bulletins)
    {
        $periodeMap = [
            '1' => 'semestre1',
            '2' => 'semestre2',
        ];

        // Si le semestre est spécifié, on récupère seulement ce semestre
        // Sinon, on récupère tous les semestres
        $periodes = [];
        if ($semestre && isset($periodeMap[$semestre])) {
            $periodes[] = $periodeMap[$semestre];
        } else {
            // Si aucun semestre n'est spécifié, on récupère tous les semestres
            $periodes = array_values($periodeMap);
        }

        \Log::info('Récupération des bulletins pour '.count($etudiants).' étudiants', [
            'annee_universitaire_id' => $annee_universitaire_id,
            'semestre' => $semestre,
            'periodes' => $periodes,
        ]);

        foreach ($etudiants as $etudiant) {
            // If no specific class is provided, get the student's class from inscriptions
            $studentClasseId = $classe_id;
            if (! $studentClasseId) {
                $inscription = $etudiant->inscriptions
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active')
                    ->first();
                $studentClasseId = $inscription ? $inscription->classe_id : null;
            }

            if ($studentClasseId && $annee_universitaire_id && ! empty($periodes)) {
                $query = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                    ->where('classe_id', $studentClasseId)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // Si on a des périodes spécifiques, on les utilise
                // Sinon, on récupère tous les bulletins pour cet étudiant dans cette classe et cette année
                if (count($periodes) == 1) {
                    $query->where('periode', $periodes[0]);
                } else {
                    $query->whereIn('periode', $periodes);
                }

                $bulletin = $query->first();

                if ($bulletin) {
                    $bulletins[$etudiant->id] = $bulletin->id;
                    \Log::debug('Bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'bulletin_id' => $bulletin->id,
                        'classe_id' => $studentClasseId,
                        'periode' => $bulletin->periode,
                    ]);
                } else {
                    \Log::warning('Aucun bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $studentClasseId,
                        'periodes' => $periodes,
                    ]);
                }
            } else {
                \Log::warning('Données insuffisantes pour récupérer le bulletin', [
                    'etudiant_id' => $etudiant->id,
                    'studentClasseId' => $studentClasseId,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'periodes' => $periodes,
                ]);
            }
        }
    }


    public function getCoefficientForCombination(int $matiereId, int $classeId, int $anneeUniversitaireId): float
    {
        $cacheKey = $matiereId.'|'.$classeId.'|'.$anneeUniversitaireId;

        if (isset($this->coefficientCache[$cacheKey])) {
            return $this->coefficientCache[$cacheKey];
        }

        if (! isset($this->classeCache[$classeId])) {
            $this->classeCache[$classeId] = ESBTPClasse::find($classeId);
        }

        $classe = $this->classeCache[$classeId];

        if (! $classe || ! $classe->filiere_id || ! $classe->niveau_etude_id) {
            throw new \RuntimeException('Classe invalide pour le calcul du coefficient.');
        }

        $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->value('coefficient');

        if ($coefficient === null) {
            throw new \RuntimeException('Coefficient manquant pour la matière sélectionnée.');
        }

        $this->coefficientCache[$cacheKey] = (float) $coefficient;

        return $this->coefficientCache[$cacheKey];
    }


    public function getAppreciation($moyenne)
    {
        $useCustomAppreciations = SettingsHelper::get('bulletin_conduite_enabled', '0') === '1';

        if ($useCustomAppreciations) {
            if ($moyenne >= 18) return 'Excellent';
            if ($moyenne >= 16) return 'Très Bien';
            if ($moyenne >= 14) return 'Bien';
            if ($moyenne >= 12) return 'Assez-bien';
            if ($moyenne >= 9.99) return 'Passable';
            if ($moyenne >= 7) return 'Insuffisant';
            if ($moyenne >= 1) return 'Médiocre';
            return 'Nul';
        }

        if ($moyenne >= 16) return 'Excellent';
        if ($moyenne >= 14) return 'Très Bien';
        if ($moyenne >= 12) return 'Bien';
        if ($moyenne >= 10) return 'Assez Bien';
        if ($moyenne >= 8) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Calcule la note de conduite basée sur les absences totales
     * Note par défaut - (total_heures_absences / heures_par_point)
     */
    public function calculerNoteConduite($totalHeuresAbsences)
    {
        $noteDefaut = floatval(SettingsHelper::get('conduite_note_defaut', '16'));
        $heuresParPoint = floatval(SettingsHelper::get('conduite_heures_par_point', '4'));

        if ($heuresParPoint <= 0) {
            $heuresParPoint = 4;
        }

        $deduction = floor($totalHeuresAbsences / $heuresParPoint);
        $noteConduite = max(0, $noteDefaut - $deduction);

        return round($noteConduite, 2);
    }

    /**
     * Retourne la mention conduite selon la note
     */
    public function getMentionConduite($noteConduite)
    {
        if ($noteConduite <= 0) {
            return 'Blâme';
        }
        if ($noteConduite <= 10) {
            return 'Avertissement';
        }
        return '';
    }



    public function getMention($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Félicitation';
        } elseif ($moyenne >= 14) {
            return 'Tableau d\'honneur';
        } elseif ($moyenne >= 12) {
            return 'Encouragement';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } elseif ($moyenne >= 8) {
            return 'Avertissement (Travail)';
        } else {
            return 'Blâme (Conduite)';
        }
    }



    public function calculerMoyenneEtudiant($etudiant_id, $classe_id, $periode, $annee_universitaire_id)
    {
        $periodesCompatibles = [$periode];
        if ($periode === 'semestre1') {
            $periodesCompatibles[] = '1';
        } elseif ($periode === 'semestre2') {
            $periodesCompatibles[] = '2';
        } elseif ($periode === '1') {
            $periodesCompatibles[] = 'semestre1';
        } elseif ($periode === '2') {
            $periodesCompatibles[] = 'semestre2';
        }

        // Récupérer les résultats de l'étudiant pour les paramètres spécifiés
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant_id)
            ->where('classe_id', $classe_id)
            ->whereIn('periode', array_unique($periodesCompatibles))
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->get();

        // Si aucun résultat n'est trouvé, retourner 0
        if ($resultats->isEmpty()) {
            return 0;
        }

        // Calculer la moyenne pondérée en utilisant la méthode existante
        return $this->calculerMoyennePonderee($resultats);
    }


    public function integrerAbsencesAuBulletin($bulletin, $donneeAbsences)
    {
        \Log::info('Intégration des absences au bulletin ID: '.$bulletin->id, $donneeAbsences);

        // Mettre à jour les champs d'absences du bulletin
        $bulletin->absences_justifiees = $donneeAbsences['justifiees'];
        $bulletin->absences_non_justifiees = $donneeAbsences['non_justifiees'];
        $bulletin->total_absences = $donneeAbsences['total'];

        // Calculer et définir la note d'assiduité
        $bulletin->note_assiduite = $this->calculerNoteAssiduite(
            $donneeAbsences['justifiees'],
            $donneeAbsences['non_justifiees']
        );

        $bulletin->save();

        \Log::info('Absences intégrées avec succès au bulletin ID: '.$bulletin->id);

        return $bulletin;
    }


    public function getBulletinAverageForPeriode(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        string $currentPeriode,
        float $currentAverage,
        float $currentNoteAssiduite = 0
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

        $bulletin = \App\Models\ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', array_unique($periodeOptions))
            ->first();

        if (! $bulletin || $bulletin->moyenne_generale === null || $bulletin->moyenne_generale <= 0) {
            // Fallback: calculer à la volée depuis les notes/résultats + ajouter assiduité
            $rawAvg = $this->calculateStudentAverageForPeriode($etudiantId, $classeId, $anneeUniversitaireId, $periode);
            if ($rawAvg === null) {
                return null;
            }
            // L'assiduité est par année (pas par semestre), on utilise celle du semestre courant
            return $rawAvg + $currentNoteAssiduite;
        }

        return floatval($bulletin->moyenne_generale + ($bulletin->note_assiduite ?? 0));
    }


    public function convertImageToJpegBase64(string $photoPath): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            // GD non disponible : fallback sur file_get_contents brut
            $mime = mime_content_type($photoPath) ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($photoPath));
        }

        try {
            $rawData = file_get_contents($photoPath);
            $src = @imagecreatefromstring($rawData);
            if (! $src) {
                // Impossible de lire l'image avec GD : fallback brut
                $mime = mime_content_type($photoPath) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode($rawData);
            }

            $w = imagesx($src);
            $h = imagesy($src);

            // Créer une image truecolor avec fond blanc (pour gérer la transparence PNG)
            $dst = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
            imagedestroy($src);

            ob_start();
            imagejpeg($dst, null, 85);
            $jpegData = ob_get_clean();
            imagedestroy($dst);

            return 'data:image/jpeg;base64,' . base64_encode($jpegData);
        } catch (\Exception $e) {
            \Log::error('Erreur conversion image pour PDF: ' . $e->getMessage());
            return null;
        }
    }

}

<?php

namespace App\Services;

use App\Exceptions\BulletinConfigurationException;
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
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPResultat;
use App\Models\ESBTPResultatMatiere;
use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinCohortResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Domain\BtsTroncCommun\BulletinSubjectRowsCompleter;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Support\Attendance\AttendanceNoteRule;
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
    // Barème assiduité 5 paliers (Marcel 03/06/2026 — étendu de 3 à 5).
    // Rétrocompat : 'two_or_more' reste lisible comme fallback pour les bulletins
    // historiques saisis avant l'extension.
    private const ATTENDANCE_NOTE_DEFAULTS = [
        'attendance_note_zero_unjustified' => '0.13',
        'attendance_note_one_unjustified' => '0.00',
        'attendance_note_two_unjustified' => '-0.13',
        'attendance_note_three_to_four_unjustified' => '-0.39',
        'attendance_note_five_or_more_unjustified' => '-0.50',
        // Rétrocompat : settings legacy 3-paliers
        'attendance_note_two_or_more_unjustified' => '-0.13',
    ];

    private $absenceService;

    private BtsAnnualClassMapResolver $classMapResolver;

    private BtsBulletinCohortResolver $cohortResolver;

    private BtsClassCohortCounter $classCohortCounter;

    private ClasseOuvertureResolver $ouvertureResolver;

    private BulletinSubjectOrder $subjectOrder;

    private BulletinSubjectRowsCompleter $rowsCompleter;

    private array $coefficientCache = [];

    private array $classeCache = [];

    // Caches request-scoped d'invariants de classe : accélèrent l'export groupé (~40
    // bulletins d'une même classe) en calculant une seule fois ce qui est identique pour
    // tous les étudiants (config PDF, stats de classe, logo, effectif). Aucun impact sur
    // le rendu : ces valeurs ne dépendent pas de l'étudiant et ne mutent pas pendant l'export.
    private ?array $pdfConfigCache = null;

    private array $classStatsCache = [];


    private array $logoBase64Cache = [];

    private array $effectifCache = [];

    public function __construct(
        ESBTPAbsenceService $absenceService,
        BtsAnnualClassMapResolver $classMapResolver,
        BtsBulletinCohortResolver $cohortResolver,
        BtsClassCohortCounter $classCohortCounter,
        ClasseOuvertureResolver $ouvertureResolver,
        BulletinSubjectOrder $subjectOrder,
        BulletinSubjectRowsCompleter $rowsCompleter
    ) {
        $this->absenceService = $absenceService;
        $this->classMapResolver = $classMapResolver;
        $this->cohortResolver = $cohortResolver;
        $this->classCohortCounter = $classCohortCounter;
        $this->ouvertureResolver = $ouvertureResolver;
        $this->subjectOrder = $subjectOrder;
        $this->rowsCompleter = $rowsCompleter;
    }

    public function forgetPDFConfigCache(): void
    {
        $this->pdfConfigCache = null;
    }

    public function isAttendanceNoteEnabled(): bool
    {
        return SettingsHelper::get('bulletin_show_attendance_note', '1') === '1';
    }

    public function getAttendanceNoteSettings(): array
    {
        // Si setting palier "2" pas défini, utiliser le legacy "two_or_more" en fallback
        // (cas tenants qui n'ont pas encore l'extension 5-paliers configurée).
        $legacyTwoOrMore = (float) SettingsHelper::get(
            'attendance_note_two_or_more_unjustified',
            self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_two_or_more_unjustified']
        );

        return [
            'zero_unjustified' => (float) SettingsHelper::get(
                'attendance_note_zero_unjustified',
                self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_zero_unjustified']
            ),
            'one_unjustified' => (float) SettingsHelper::get(
                'attendance_note_one_unjustified',
                self::ATTENDANCE_NOTE_DEFAULTS['attendance_note_one_unjustified']
            ),
            'two_unjustified' => (float) SettingsHelper::get(
                'attendance_note_two_unjustified',
                $legacyTwoOrMore // fallback legacy si nouveau palier pas saisi
            ),
            'three_to_four_unjustified' => (float) SettingsHelper::get(
                'attendance_note_three_to_four_unjustified',
                $legacyTwoOrMore // fallback legacy
            ),
            'five_or_more_unjustified' => (float) SettingsHelper::get(
                'attendance_note_five_or_more_unjustified',
                $legacyTwoOrMore // fallback legacy
            ),
            // Rétrocompat pour code qui lit encore ce nom
            'two_or_more_unjustified' => $legacyTwoOrMore,
        ];
    }

    public function resolveAttendanceNote($absencesJustifiees, $absencesNonJustifiees): float
    {
        if (! $this->isAttendanceNoteEnabled()) {
            return 0.0;
        }

        return $this->getAttendanceNoteRule()->resolve(
            (float) $absencesJustifiees,
            (float) $absencesNonJustifiees
        );
    }

    /**
     * Règle d'assiduité configurable du tenant : tranches d'heures dynamiques
     * (justifié + non justifié). Lue depuis le setting JSON `attendance_note_rules`,
     * avec fallback automatique sur les 5 clés legacy si absent/invalide — aucune
     * fenêtre de régression au déploiement.
     */
    public function getAttendanceNoteRule(): AttendanceNoteRule
    {
        $raw = SettingsHelper::get('attendance_note_rules', null);

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && AttendanceNoteRule::validationErrors($decoded) === []) {
                return AttendanceNoteRule::fromArray($decoded);
            }
        } elseif (is_array($raw) && AttendanceNoteRule::validationErrors($raw) === []) {
            return AttendanceNoteRule::fromArray($raw);
        }

        // Fallback legacy : construit la règle depuis les 5 clés paliers du tenant.
        $legacy = $this->getAttendanceNoteSettings();

        return AttendanceNoteRule::fromLegacySettings([
            'zero_unjustified' => $legacy['zero_unjustified'],
            'one_unjustified' => $legacy['one_unjustified'],
            'two_unjustified' => $legacy['two_unjustified'],
            'three_to_four_unjustified' => $legacy['three_to_four_unjustified'],
            'five_or_more_unjustified' => $legacy['five_or_more_unjustified'],
        ]);
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

        $attendanceNote = $this->calculateEffectiveAttendanceNoteForStudent(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $periode
        );

        return $rawAvg + $attendanceNote;
    }

    /**
     * Génère les données complètes pour un bulletin (utilisé par preview et PDF)
     */
    public function genererDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1')
    {
        return $this->buildDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode, true);
    }

    public function genererDonneesBulletinPreview($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1')
    {
        return $this->buildDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode, false);
    }

    private function buildDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1', bool $persistOfficial = true)
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
        [$configMatieres, $professeursConfigures] = $this->resolveConfiguredBulletinContext(
            $bulletin,
            (int) $classeId,
            (int) $anneeUniversitaireId,
            (string) $periode,
            (int) $etudiantId
        );

        if ($persistOfficial && ! $bulletin) {
            throw new BulletinConfigurationException(
                'Aucun bulletin officiel existant a mettre a jour.',
                $this->bulletinConfigurationContext((int) $classeId, (int) $anneeUniversitaireId, (string) $periode, (int) $etudiantId)
            );
        }

        if ($persistOfficial && $bulletin) {
            $this->syncConfiguredBulletinContext($bulletin, $configMatieres, $professeursConfigures);
        }

        // Récupérer les notes avec évaluations pour la période spécifiée.
        // On tolère les alias legacy ('1'/'2') exactement comme le snapshot de
        // pré-contrôle, pour que la génération réelle voie les mêmes notes.
        $periodeAliases = $this->periodeAliases((string) $periode);

        // Classes de specialite pas encore ouvertes a ce semestre. Une note
        // prise dans l'une d'elles ne peut pas appartenir a ce bulletin :
        // l'etudiant etait encore en tronc commun. Sans cette exclusion, une
        // evaluation mal datee remontait sur le bulletin de tronc commun
        // (cas « Securite » a l'ESBTP Yamoussoukro).
        $classesPasEncoreOuvertes = $this->classesPasEncoreOuvertes((string) $periode);
        $classesDuBulletin = $this->evaluationClassIdsForBulletin(
            (int) $etudiantId,
            (int) $classeId,
            (int) $anneeUniversitaireId,
            (string) $periode
        );

        $notesAvecEvaluations = ESBTPNote::where('etudiant_id', $etudiant->id)
            ->with(['evaluation.matiere', 'evaluation.enseignant'])
            ->whereHas('evaluation', function ($q) use ($anneeUniversitaire, $periodeAliases, $classesPasEncoreOuvertes, $classesDuBulletin) {
                $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereIn('periode', $periodeAliases)
                    ->whereIn('classe_id', $classesDuBulletin);

                if ($classesPasEncoreOuvertes !== []) {
                    $q->whereNotIn('classe_id', $classesPasEncoreOuvertes);
                }
            })
            ->get();

        // Créer des résultats par matière avec évaluations
        $resultatsParMatiere = [];
        $professeurs = [];

        foreach ($notesAvecEvaluations as $note) {
            if ($note->evaluation && $note->evaluation->matiere) {
                $matiere = $note->evaluation->matiere;
                $matiereId = $matiere->id;

                if (! CoherenceSystemeAcademique::matiereRetenue($matiere, $classe, 'bulletin/evaluation')) {
                    continue;
                }

                if (! isset($resultatsParMatiere[$matiereId])) {
                    // Type de formation : résolution canonique (ConfigMatiere → bulletin JSON → matiere globale)
                    $typeFormation = $this->resolveMatiereTypeFormation(
                        $matiereId,
                        $classe->id,
                        $periode,
                        $anneeUniversitaireId,
                        $bulletin
                    );

                    $resultatsParMatiere[$matiereId] = (object) [
                        'id' => $matiereId,
                        'matiere_id' => $matiereId,
                        'matiere' => $matiere,
                        'notes' => [],
                        'moyenne' => 0,
                        'coefficient' => $this->coefficientOrDefault(
                            $matiereId,
                            $classe->id,
                            $anneeUniversitaireId,
                            (string) $periode,
                            (int) $etudiantId
                        ),
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

                // L'affectation du bulletin reste prioritaire. Sinon, tous les
                // évaluateurs réels de la matière sont affichés dans un ordre
                // stable, y compris les enseignants externes.
                $professeurConfigure = trim((string) ($professeursConfigures[$matiereId] ?? ''));
                $professeurEvaluation = trim((string) (
                    $note->evaluation->enseignant?->name
                    ?? $note->evaluation->enseignant_externe_nom
                    ?? ''
                ));

                if ($professeurConfigure !== '') {
                    $professeurs[$matiereId] = $professeurConfigure;
                } elseif ($professeurEvaluation !== '') {
                    $noms = array_filter(
                        array_map('trim', explode(' / ', (string) ($professeurs[$matiereId] ?? '')))
                    );
                    $noms[] = $professeurEvaluation;
                    $noms = array_values(array_unique($noms));
                    sort($noms, SORT_NATURAL | SORT_FLAG_CASE);
                    $professeurs[$matiereId] = implode(' / ', $noms);
                } elseif (! isset($professeurs[$matiereId])) {
                    $professeurs[$matiereId] = '';
                }
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

            if ($resultatManuel->matiere
                && ! CoherenceSystemeAcademique::matiereRetenue($resultatManuel->matiere, $classe, 'bulletin/moyenne manuelle')) {
                continue;
            }

            if ($resultatManuel->matiere) {
                // Si la matière n'existe pas encore dans les résultats, l'ajouter
                if (! isset($resultatsParMatiere[$matiereId])) {
                    // Type de formation : résolution canonique (ConfigMatiere → bulletin JSON → matiere globale)
                    $typeFormation = $this->resolveMatiereTypeFormation(
                        $matiereId,
                        $classe->id,
                        $periode,
                        $anneeUniversitaireId,
                        $bulletin
                    );

                    $resultatsParMatiere[$matiereId] = (object) [
                        'id' => $matiereId,
                        'matiere_id' => $matiereId,
                        'matiere' => $resultatManuel->matiere,
                        'notes' => [],
                        'moyenne' => $resultatManuel->moyenne,
                        'coefficient' => $this->coefficientOrDefault(
                            $matiereId,
                            $classe->id,
                            $anneeUniversitaireId,
                            (string) $periode,
                            (int) $etudiantId
                        ),
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
                    $resultatsParMatiere[$matiereId]->coefficient = $this->coefficientOrDefault(
                        $matiereId,
                        $classe->id,
                        $anneeUniversitaireId,
                        (string) $periode,
                        (int) $etudiantId
                    );
                }
            }
        }

        // Ordre du bulletin. Jusqu'ici les matieres sortaient dans l'ordre
        // d'arrivee des notes : deux etudiants d'une meme classe recevaient des
        // bulletins ordonnes differemment. `sort()` rend la collection
        // INCHANGEE tant qu'aucun rang n'est defini pour la classe, et preserve
        // les cles (le tableau reste indexe par matiere_id).
        $resultatsParMatiere = $this->subjectOrder
            ->sort(collect($resultatsParMatiere), $this->subjectOrder->rankMapForClasse(
                $classe,
                \App\Domain\BtsTroncCommun\BulletinSubjectRowsCompleter::semestreDe($periode)
            ))
            ->all();

        $periodeNormalized = $this->normalizePeriode($periode);

        // Composition du bulletin : les matieres prevues par la maquette qui
        // n'ont pas de note y figurent avec le symbole de trou, et celles dont
        // l'etudiant est dispense le disent. Sans maquette renseignee et sans
        // dispense, cette etape ne change rien.
        $resultatsParMatiere = $this->rowsCompleter
            ->completer(
                collect($resultatsParMatiere),
                $classe,
                (int) $etudiantId,
                (int) $anneeUniversitaireId,
                BulletinSubjectRowsCompleter::semestreDe($periodeNormalized),
                fn (int $matiereId): array => [
                    'coefficient' => $this->coefficientOrDefault(
                        $matiereId,
                        $classe->id,
                        $anneeUniversitaireId,
                        (string) $periode,
                        (int) $etudiantId
                    ),
                    'type_formation' => $this->resolveMatiereTypeFormation(
                        $matiereId,
                        $classe->id,
                        $periode,
                        $anneeUniversitaireId,
                        $bulletin
                    ),
                ]
            )
            ->all();
        if ($persistOfficial) {
            $this->persistResultats(
                $resultatsParMatiere,
                $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $periodeNormalized
            );
        }

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
        $moyenneGlobale = $this->composerLaMoyenneDuSemestre(
            collect($resultatsParMatiere),
            $resultatsGeneraux,
            $resultatsTechniques,
            $moyenneGenerale,
            $moyenneTechnique
        );

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

        // Effectif de la classe: inscriptions validées présentes dans cette classe pour la période.
        $effectif = $this->getValidatedClassStudentCount($classe->id, $anneeUniversitaire->id, $periode);

        $rang = null;
        $rangAnnuel = null;

        if ($persistOfficial && $bulletin) {
            // Persister la moyenne BRUTE (sans assiduite) dans le bulletin.
            // L'assiduite est stockee separement dans note_assiduite.
            // getBulletinAverageForPeriode() additionne les deux.
            $bulletin->moyenne_generale = $moyenneGlobale;
            $bulletin->note_assiduite = $noteAssiduite;
            $bulletin->effectif_classe = $effectif;
            // Persister aussi les compteurs d'absences (live, saisie manuelle incluse) :
            // sinon les colonnes restent figees a 0 et la page bulletins.show / le PDF
            // affichent 0 h malgre des heures saisies (bug absences manuelles invisibles).
            $bulletin->absences_justifiees = $absences['justifiees'] ?? 0;
            $bulletin->absences_non_justifiees = $absences['non_justifiees'] ?? 0;
            $bulletin->total_absences = $absences['total'] ?? (($absences['justifiees'] ?? 0) + ($absences['non_justifiees'] ?? 0));
            $bulletin->save();

            // Recalculer toute la classe: un rang séquentiel contre un set incomplet
            // donnait 1 à tout le monde.
            $this->calculerRangsPourClasse($classe->id, $anneeUniversitaire->id, $periode);
            $bulletin->refresh();
            $rang = $bulletin->rang;
        } else {
            $rang = $this->rankAmongAverages($this->collectSemesterAveragesForClasse(
                (int) $classe->id,
                (int) $anneeUniversitaire->id,
                (string) $periode
            ), $moyenneAvecAssiduite);
        }

        // Calculer les vraies statistiques de classe.
        // On n'autorise le cache que hors génération officielle : en génération
        // ($persistOfficial), persistResultats() vient d'écrire les esbtp_resultats et
        // les stats (qui les lisent) peuvent évoluer d'un étudiant à l'autre — on préserve
        // donc le calcul par étudiant. En export/preview (persist=false), les resultats
        // ne bougent pas → cache sûr et gros gain sur l'export groupé.
        $statsClasse = $this->calculerStatistiquesClasse(
            $classe->id,
            $anneeUniversitaire->id,
            $periode,
            ! $persistOfficial
        );

        // Calculer les rangs par matière via ESBTPResultat (batch-fetch pour éviter N+1)
        // Seules les matieres reellement notees ont un rang : une matiere
        // dispensee ou non notee ne se classe pas, elle garde le tiret pose
        // par le `?? '-'` ci-dessous.
        $allMatiereIds = collect($resultatsParMatiere)
            ->filter(fn ($resultat) => $this->ligneEstNotee($resultat))
            ->pluck('matiere_id')->filter()->unique()->values()->all();
        $rangsParMatiere = $this->calculerRangsParMatierePourEtudiant(
            $allMatiereIds,
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $periodeNormalized
        );
        foreach ($resultatsGeneraux as $resultat) {
            $resultat->rang = $rangsParMatiere[$resultat->matiere_id] ?? '-';
        }
        foreach ($resultatsTechniques as $resultat) {
            $resultat->rang = $rangsParMatiere[$resultat->matiere_id] ?? '-';
        }

        if ($persistOfficial && $bulletin) {
            $this->persistOfficialSubjectRows($bulletin, $resultatsParMatiere);
        }

        // Préparer la configuration PDF
        $settings = $this->getPDFConfig();

        $semesterWeights = $this->getSemesterWeights($classe);
        $warnings = [];

        // Vérifier si le bulletin de l'autre semestre existe en base
        $otherPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
        $otherBulletinExists = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $otherPeriode)
            ->where('moyenne_generale', '>', 0)
            ->exists();

        // Tronc commun : resoudre la classe portant les notes du S1 via le class-map
        // resolveur stateless (modele phases ET legacy). CLASS MAP only.
        $classeIdS1 = $classeId;
        $classeTroncCommun = null;
        if (\App\Helpers\SettingsHelper::get('tronc_commun_mga_include_s1', true)) {
            $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
            $resolvedS1ClasseId = $classMap['semestre1_classe_id'] ?? $classeId;
            if ($resolvedS1ClasseId && (int) $resolvedS1ClasseId !== (int) $classeId) {
                $classeIdS1 = (int) $resolvedS1ClasseId;
                $classeTroncCommun = ESBTPClasse::with('filiere')->find($resolvedS1ClasseId);
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
        $rangAnnuel = $this->calculerRangAnnuel(
            (int) $etudiantId,
            (int) $classe->id,
            (int) $anneeUniversitaire->id,
            $moyenneAnnuelle
        );
        $levelYear = $this->classeLevelYear($classe);
        $automaticCouncilDecision = $this->automaticCouncilDecision(
            $classe,
            $periode,
            $moyenneSemestre2,
            $moyenneAnnuelle,
            $levelYear
        );
        $decisionConseil = BtsBulletinPolicy::displayCouncilDecision(
            $automaticCouncilDecision,
            $bulletin?->decision_conseil
        );

        // Quand la moyenne de decision manque, la case reste vide et rien ne le
        // dit a l'utilisateur. Le pourquoi, et pourquoi un `$warnings[]` de
        // plus n'y changerait rien, est sur `automaticCouncilDecision()`.
        $councilDecision = [
            'title' => $this->councilDecisionTitle($classe, $periode),
            'text' => (string) ($decisionConseil ?? ''),
            'mode' => $this->councilDecisionMode($levelYear),
        ];
        $appreciation = $this->getAppreciation($moyenneAvecAssiduite);
        // N'ecrire que ce qu'on a. Ecraser par `null` faisait disparaitre la
        // decision saisie a la main chaque fois que la politique ne repondait
        // pas — c'est-a-dire en mode manuel, et desormais aussi quand la
        // moyenne annuelle manque parce qu'un semestre n'a pas de notes.
        if ($persistOfficial && $bulletin && $councilDecision['text'] !== '') {
            $bulletin->decision_conseil = $councilDecision['text'];
            $bulletin->save();
        }

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
            // L'inscription de CETTE classe et de CETTE annee. Les gabarits
            // lisaient auparavant $etudiant->inscriptions->first(), c'est-a-dire
            // la toute premiere inscription de l'etudiant, jamais celle de la
            // periode affichee : la mention « Redoublant » etait donc toujours
            // fausse. Cette ligne est deja calculee plus haut, triee.
            'inscription' => $inscription,
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
            'rangAnnuel' => $rangAnnuel,
            'councilDecision' => $councilDecision,
            'effectif' => $effectif,
            'meilleure_moyenne' => $statsClasse['meilleure_moyenne'],
            'plus_faible_moyenne' => $statsClasse['plus_faible_moyenne'],
            'moyenne_classe' => $statsClasse['moyenne_classe'],
            'appreciation' => $appreciation,
            'decisionConseil' => $decisionConseil,
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

    /**
     * Resolve class/period bulletin configuration without forcing a bulletin row
     * to exist. Preview reads class configuration only; official generation syncs
     * that configuration on the persisted bulletin before calculations.
     *
     * @return array{0: array{generales: list<int>, techniques: list<int>}, 1: array<int|string, mixed>}
     */
    private function resolveConfiguredBulletinContext(
        ?ESBTPBulletin $bulletin,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        ?int $etudiantId = null
    ): array {
        $configMatieres = $this->decodeJsonToArray($bulletin?->config_matieres);

        if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
            $configMatieres = $this->configMatieresPayloadForBulletin($classeId, $anneeUniversitaireId, $periode);
        }

        if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
            throw new BulletinConfigurationException(
                'Configuration bulletin manquante : configurez les matieres du bulletin avant de generer le PDF.',
                $this->bulletinConfigurationContext($classeId, $anneeUniversitaireId, $periode, $etudiantId)
            );
        }

        $professeurs = array_replace(
            $this->professeursPayloadForBulletin($classeId, $anneeUniversitaireId, $periode),
            array_filter(
                $this->decodeJsonToArray($bulletin?->professeurs ?? null),
                fn ($value) => trim((string) $value) !== ''
            )
        );

        return [$configMatieres, $professeurs];
    }

    private function syncConfiguredBulletinContext(ESBTPBulletin $bulletin, array $configMatieres, array $professeurs): void
    {
        $currentConfig = $this->decodeJsonToArray($bulletin->config_matieres);
        if (empty($currentConfig['generales']) && empty($currentConfig['techniques'])) {
            $bulletin->config_matieres = $configMatieres;
        }

        if (trim((string) $bulletin->professeurs) === '') {
            $bulletin->professeurs = json_encode($professeurs);
        }
    }

    private function bulletinConfigurationContext(int $classeId, int $anneeUniversitaireId, string $periode, ?int $etudiantId = null): array
    {
        $periode = $this->normalizePeriode($periode);
        $params = [
            'classe_id' => $classeId,
            'periode' => $periode,
            'annee_universitaire_id' => $anneeUniversitaireId,
        ];

        if ($etudiantId) {
            $params['bulletin'] = $etudiantId;
            $params['etudiant_id'] = $etudiantId;
        }

        return [
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeUniversitaireId,
            'periode' => $periode,
            'configuration_url' => route('esbtp.bulletins.config-matieres', $params),
        ];
    }

    private function configMatieresPayloadForBulletin(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $payload = ['generales' => [], 'techniques' => []];

        $rows = ESBTPConfigMatiere::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->configPeriodsForBulletin($periode))
            ->get(['matiere_id', 'config']);

        foreach ($rows as $row) {
            $config = is_array($row->config) ? $row->config : $this->decodeJsonToArray($row->config);
            $type = $config['type'] ?? null;

            if (in_array($type, ['general', 'generale'], true)) {
                $payload['generales'][] = (int) $row->matiere_id;
            }

            if (in_array($type, ['technique', 'technologique_professionnelle'], true)) {
                $payload['techniques'][] = (int) $row->matiere_id;
            }
        }

        $payload['generales'] = array_values(array_unique($payload['generales']));
        $payload['techniques'] = array_values(array_unique($payload['techniques']));

        return $payload;
    }

    private function professeursPayloadForBulletin(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $professeurs = $this->professeursFromAcademicPlanning($classeId, $anneeUniversitaireId, $periode);

        $historique = ESBTPBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->configPeriodsForBulletin($periode))
            ->whereNotNull('professeurs')
            ->where('professeurs', '!=', '')
            ->where('professeurs', '!=', '{}')
            ->latest('updated_at')
            ->value('professeurs');

        $historique = array_filter(
            $this->decodeJsonToArray($historique),
            fn ($value) => trim((string) $value) !== ''
        );
        $professeurs = array_replace($professeurs, $historique);

        foreach ($this->configPeriodsForBulletin($periode) as $targetPeriode) {
            $raw = SettingsHelper::get($this->professeursTemplateKey($classeId, $anneeUniversitaireId, $targetPeriode), null);
            $template = is_string($raw) ? $this->decodeJsonToArray($raw) : (array) $raw;
            $template = array_filter($template, fn ($value) => trim((string) $value) !== '');

            if ($template !== []) {
                $professeurs = array_replace($professeurs, $template);
            }
        }

        return $professeurs;
    }

    /**
     * Retourne les enseignants principaux planifiés pour les matières de la classe.
     * Les saisies manuelles du bulletin restent prioritaires dans le payload appelant.
     *
     * @return array<int, string>
     */
    private function professeursFromAcademicPlanning(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $classe = ESBTPClasse::with('filiere:id,parent_id')->find($classeId);
        if (! $classe) {
            return [];
        }

        $filiereIds = $classe->filiere?->troncCommunUnionFiliereIds() ?? [$classe->filiere_id];
        $semestres = match ($this->normalizePeriode($periode)) {
            'semestre1' => [1],
            'semestre2' => [2],
            default => [1, 2],
        };

        $planifications = ESBTPPlanificationAcademique::query()
            ->with('enseignantPrincipal:id,name')
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('filiere_id', $filiereIds)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereIn('semestre', $semestres)
            ->where('is_active', true)
            ->whereNotNull('enseignant_principal_id')
            ->orderByRaw('filiere_id = ? desc', [$classe->filiere_id])
            ->get(['matiere_id', 'enseignant_principal_id']);

        $professeurs = [];
        foreach ($planifications as $planification) {
            $nom = trim((string) ($planification->enseignantPrincipal?->name ?? ''));
            if ($nom !== '' && ! isset($professeurs[$planification->matiere_id])) {
                $professeurs[(int) $planification->matiere_id] = $nom;
            }
        }

        return $professeurs;
    }

    private function professeursTemplateKey(int $classeId, int $anneeUniversitaireId, string $periode): string
    {
        return "bulletin_professeurs_template.{$classeId}.{$anneeUniversitaireId}.{$periode}";
    }

    private function configPeriodsForBulletin(string $periode): array
    {
        $periode = $this->normalizePeriode($periode);

        return $periode === 'annuel' ? ['semestre1', 'semestre2'] : [$periode];
    }

    /**
     * Résolution canonique du type de formation d'une matière pour un bulletin.
     *
     * Priorité de résolution :
     *   1. ESBTPConfigMatiere (table per matiere+classe+période+année) — override saisi via /config-matieres
     *   2. $bulletin->config_matieres JSON (arrays generales[]/techniques[])
     *   3. $matiere->type_formation (fallback global)
     *
     * @return string 'generale' | 'technologique_professionnelle'
     */
    public function resolveMatiereTypeFormation(
        int $matiereId,
        int $classeId,
        string $periode,
        int $anneeUniversitaireId,
        ?ESBTPBulletin $bulletin = null
    ): string {
        // 1. ESBTPConfigMatiere (source canonique : override par classe/période)
        $config = ESBTPConfigMatiere::where('matiere_id', $matiereId)
            ->where('classe_id', $classeId)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->first();

        if ($config) {
            $cfg = is_string($config->config) ? json_decode($config->config, true) : ($config->config ?? []);
            $type = is_array($cfg) ? ($cfg['type'] ?? null) : null;
            if ($type === 'general' || $type === 'generale') {
                return 'generale';
            }
            if ($type === 'technique' || $type === 'technologique_professionnelle') {
                return 'technologique_professionnelle';
            }
        }

        // 2. Bulletin JSON (legacy : arrays generales[]/techniques[] saisi via /config-matieres)
        if ($bulletin && $bulletin->config_matieres) {
            $bulletinConfig = is_string($bulletin->config_matieres)
                ? (json_decode($bulletin->config_matieres, true) ?: [])
                : (is_array($bulletin->config_matieres) ? $bulletin->config_matieres : []);

            if (in_array($matiereId, $bulletinConfig['generales'] ?? [], false)) {
                return 'generale';
            }
            if (in_array($matiereId, $bulletinConfig['techniques'] ?? [], false)) {
                return 'technologique_professionnelle';
            }
        }

        // 3. Fallback : type global de la matière
        $matiere = ESBTPMatiere::find($matiereId);
        $globalType = $matiere?->type_formation;
        if ($globalType === 'technique' || $globalType === 'technologique_professionnelle') {
            return 'technologique_professionnelle';
        }

        return 'generale';
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
            ? 'esbtp.bulletins.preview-abidjan'
            : 'esbtp.bulletins.preview';
    }

    public function getSemesterWeights(?ESBTPClasse $classe = null): array
    {
        $semester1 = floatval(SettingsHelper::get('bulletin_semester1_weight', '1'));
        $semester2 = floatval(SettingsHelper::get('bulletin_semester2_weight', '1'));

        if ($semester1 < 0) {
            $semester1 = 0;
        }
        if ($semester2 < 0) {
            $semester2 = 0;
        }

        if (($semester1 + $semester2) <= 0) {
            $semester1 = 1;
            $semester2 = 1;
        }

        $fallback = [
            'semester1' => $semester1,
            'semester2' => $semester2,
        ];

        if ($classe === null) {
            return $fallback;
        }

        $levelYear = $this->classeLevelYear($classe);
        $settings = [];
        foreach ([1, 2] as $year) {
            foreach (['semester1_weight', 'semester2_weight'] as $key) {
                $settingKey = "bulletin_bts{$year}_{$key}";
                $settings[$settingKey] = SettingsHelper::get($settingKey);
            }
        }

        $weights = BtsBulletinPolicy::annualWeights($classe->isBTS(), $levelYear, $settings, $fallback);
        $weights['year'] = $levelYear;

        return $weights;
    }

    public function recalculerRangsClasse(int $classeId, int $anneeUniversitaireId, string $periode): void
    {
        $this->calculerRangsPourClasse($classeId, $anneeUniversitaireId, $periode);
    }

    /**
     * La decision du conseil telle que la politique de l'instance la calcule.
     *
     * Rend `null` dans deux cas qu'il n'est pas utile de distinguer ICI : le
     * mode manuel, ou la moyenne de decision absente. Dans les deux, l'appelant
     * garde la decision saisie a la main. Une version anterieure rendait un
     * couple pour porter la seconde raison jusqu'a un avertissement — supprime,
     * faute de lecteur (voir la note dans `genererDonneesBulletin`).
     */
    private function automaticCouncilDecision(
        ESBTPClasse $classe,
        string $periode,
        ?float $semester2Average,
        ?float $annualAverage,
        ?int $levelYear = null,
    ): ?string
    {
        $levelYear ??= $this->classeLevelYear($classe);
        $settings = [];
        foreach ([1, 2] as $year) {
            $prefix = "bulletin_bts{$year}_council_";
            foreach (['mode', 'threshold', 'below_text', 'at_or_above_text', 'fixed_text', 'average_source'] as $key) {
                $settings[$prefix . $key] = SettingsHelper::get($prefix . $key);
            }
        }

        $source = BtsBulletinPolicy::councilAverageSource($levelYear, $settings);
        $decisionAverage = BtsBulletinPolicy::decisionAverage($source, $semester2Average, $annualAverage);

        return BtsBulletinPolicy::councilDecision(
            $classe->isBTS(),
            $levelYear,
            $this->normalizePeriode($periode),
            $decisionAverage,
            $settings,
        );
    }


    public function councilDecisionTitle(?ESBTPClasse $classe, string $periode): string
    {
        $periode = $this->normalizePeriode($periode);
        $levelYear = $classe ? $this->classeLevelYear($classe) : null;
        $defaultTitle = 'Décision du conseil de classe';

        if ($classe && $classe->isBTS() && $levelYear === 1 && $periode === 'semestre1') {
            $title = trim((string) SettingsHelper::get('bulletin_bts1_s1_council_title', $defaultTitle));

            return $title !== '' ? $title : $defaultTitle;
        }

        return $defaultTitle;
    }

    private function councilDecisionMode(?int $levelYear): string
    {
        if (! in_array($levelYear, [1, 2], true)) {
            return 'manual';
        }

        return (string) (SettingsHelper::get("bulletin_bts{$levelYear}_council_mode", 'manual') ?: 'manual');
    }
    private function classeLevelYear(ESBTPClasse $classe): ?int
    {
        return $classe->relationLoaded('niveau')
            ? $classe->niveau?->year
            : $classe->niveau()->value('year');
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

    /**
     * Aliases légaux d'une période, pour les requêtes qui doivent tolérer les
     * données legacy stockées sous '1'/'2' en plus de 'semestre1'/'semestre2'.
     * Source de vérité unique réutilisée par le snapshot et la génération.
     *
     * @return list<string>
     */
    public function periodeAliases(string $periode): array
    {
        return match ($this->normalizePeriode($periode)) {
            'semestre1' => ['semestre1', '1'],
            'semestre2' => ['semestre2', '2'],
            default => [$this->normalizePeriode($periode)],
        };
    }

    /**
     * Classes dont les notes ont le droit d'entrer sur ce bulletin.
     *
     * Sans ce perimetre, une note d'Hydrologie prise dans une autre classe
     * (TP, transfert) etait reecrite sur le bulletin 1BTS GBAT B a chaque
     * generation : le pre-controle demandait de supprimer la moyenne, le
     * recalcul la restaurait via persistResultats().
     *
     * @return list<int>
     */
    private function evaluationClassIdsForBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $ids = [(int) $classeId];
        $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
        $normalized = $this->normalizePeriode($periode);

        if (in_array($normalized, ['semestre1', 'annuel'], true) && ! empty($classMap['semestre1_classe_id'])) {
            $ids[] = (int) $classMap['semestre1_classe_id'];
        }

        if (in_array($normalized, ['semestre2', 'annuel'], true) && ! empty($classMap['semestre2_classe_id'])) {
            $ids[] = (int) $classMap['semestre2_classe_id'];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Classes de specialite qui ne s'ouvrent qu'apres le semestre demande.
     *
     * Une note prise dans une telle classe ne peut pas figurer sur un bulletin
     * d'un semestre anterieur : l'etudiant y etait encore en tronc commun.
     *
     * Pour une periode annuelle, aucune exclusion : le bulletin annuel doit
     * couvrir les deux classes du parcours.
     *
     * @return list<int>
     */
    private function classesPasEncoreOuvertes(string $periode): array
    {
        $semestre = match ($this->normalizePeriode($periode)) {
            'semestre1' => 1,
            'semestre2' => 2,
            default => null,
        };

        return $semestre === null
            ? []
            : $this->ouvertureResolver->classesNonOuvertesAu($semestre);
    }

    /**
     * Matieres dont une moyenne est enregistree pour cet etudiant sur cette
     * periode alors qu'aucune note ne la porte plus.
     *
     * Une evaluation deplacee d'un semestre a l'autre laisse sa ligne derriere
     * elle. Le calcul « courant » la reprend -- il fusionne les moyennes
     * enregistrees -- donc l'officiel et le courant affichent la meme valeur
     * perimee et aucun ecart n'est signale. Seule cette liste le dit.
     *
     * @return array<int, array{matiere_id: int, matiere: string, moyenne: string|null}>
     */
    public function moyennesSansNotePourEtudiant(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $lignes = ESBTPResultat::query()
            ->sansNoteSurLaPeriode($classeId, $anneeUniversitaireId, $this->periodeAliases($this->normalizePeriode($periode)))
            ->where('etudiant_id', $etudiantId)
            ->get();

        if ($lignes->isEmpty()) {
            return [];
        }

        // withTrashed : la matiere peut etre en corbeille, son nom doit rester
        // lisible -- un identifiant nu n'atteint jamais un ecran.
        $noms = ESBTPMatiere::withTrashed()
            ->whereIn('id', $lignes->pluck('matiere_id'))
            ->pluck('name', 'id');

        return $lignes
            ->map(fn (ESBTPResultat $r) => [
                'matiere_id' => (int) $r->matiere_id,
                'matiere' => $noms[$r->matiere_id] ?? 'Matiere #'.$r->matiere_id,
                'moyenne' => $r->moyenne,
            ])
            ->all();
    }

    private function persistResultats(array $resultatsParMatiere, int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): void
    {
        $userId = Auth::id();

        foreach ($resultatsParMatiere as $resultat) {
            if (! isset($resultat->matiere_id)) {
                continue;
            }

            // Une dispense retire la matiere du dossier de l'etudiant : il n'a
            // pas de resultat dans une matiere dont il est dispense, et la
            // ligne agregee doit donc partir. Suppression douce : une
            // revocation la fera revivre a la generation suivante.
            //
            // CE QUE CELA NE FAIT PAS, et il faut le savoir : le rang par
            // matiere de ses camarades ne bouge pas. Il se calcule par
            // preferRicherSubjectRank(), qui retient le MAXIMUM entre le rang
            // stocke et un rang recalcule en direct sur `esbtp_notes` — ou les
            // notes de l'etudiant dispense sont toujours la. Exclure vraiment
            // un dispense du classement d'une matiere demanderait de filtrer
            // aussi ce calcul en direct ; ce n'est pas fait.
            if (($resultat->statut ?? ESBTPResultatMatiere::STATUT_NOTE) === ESBTPResultatMatiere::STATUT_DISPENSE) {
                ESBTPResultat::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classeId)
                    ->where('matiere_id', $resultat->matiere_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->delete();

                continue;
            }

            if ($resultat->moyenne === null) {
                continue;
            }

            // withTrashed + sans le scope d'archivage : une ligne supprimee ou
            // archivee occupe la cle unique mais reste invisible au
            // updateOrCreate par defaut, qui tentait alors un INSERT sur le
            // meme quintuple -- `Duplicate entry`, 500 definitif sur cet
            // etudiant des qu'une note revenait apres une suppression.
            $ligne = ESBTPResultat::withTrashed()
                ->withoutGlobalScope('not_archived')
                ->updateOrCreate(
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

            // Une note qui revient rend la moyenne legitime : la ligne revit.
            if ($ligne->trashed()) {
                $ligne->restore();
            }
        }
    }

    private function persistOfficialSubjectRows(ESBTPBulletin $bulletin, array $resultatsParMatiere): void
    {
        $userId = Auth::id();
        $keptMatiereIds = [];

        foreach ($resultatsParMatiere as $resultat) {
            if (! isset($resultat->matiere_id)) {
                continue;
            }

            $statut = $resultat->statut ?? ESBTPResultatMatiere::STATUT_NOTE;
            $estNotee = $this->ligneEstNotee($resultat);

            // Une ligne sans note ET sans etat declare n'a rien a dire : c'est
            // l'ancien cas « pas de moyenne », qu'on continue d'ignorer.
            if (! $estNotee && $statut === ESBTPResultatMatiere::STATUT_NOTE) {
                continue;
            }

            $matiereId = (int) $resultat->matiere_id;
            $keptMatiereIds[] = $matiereId;
            $rang = $estNotee && is_numeric($resultat->rang ?? null) ? (int) $resultat->rang : null;

            // `poserSurLeBulletin` et non `updateOrCreate` : la boucle se termine
            // par un soft-delete des matieres non retenues, et la cle unique ne
            // porte pas `deleted_at`. Une matiere retiree puis remise rendait
            // donc le bulletin DEFINITIVEMENT ingenerable — « Duplicate entry ».
            ESBTPResultatMatiere::poserSurLeBulletin(
                (int) $bulletin->id,
                $matiereId,
                [
                    'moyenne' => $estNotee ? $resultat->moyenne : null,
                    'statut' => $statut,
                    'motif_dispense' => $resultat->motif_dispense ?? null,
                    'dispense_id' => $resultat->dispense_id ?? null,
                    'coefficient' => $resultat->coefficient ?? 1,
                    'rang' => $rang,
                    'appreciation' => $estNotee
                        ? ($resultat->appreciation ?? $this->getAppreciation($resultat->moyenne))
                        : '',
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ]
            );
        }

        $query = ESBTPResultatMatiere::where('bulletin_id', $bulletin->id);
        if ($keptMatiereIds !== []) {
            $query->whereNotIn('matiere_id', $keptMatiereIds);
        }
        $query->delete();
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
    /**
     * La moyenne du semestre, selon ce que l'ecole a choisi.
     *
     * Deux compositions, et elles ne donnent pas le meme resultat :
     *
     * - « ponderee » (defaut, comportement historique) : une seule moyenne
     *   ponderee sur toutes les matieres. Une matiere de coefficient 4 pese
     *   quatre fois une matiere de coefficient 1, d'un bloc a l'autre.
     *
     * - « blocs » : chaque bloc fait sa moyenne ponderee chez lui, puis les
     *   deux sont combines selon LEURS coefficients. Chez ESBTP Abidjan, 1 et
     *   1 : deux matieres generales pesent autant que sept professionnelles.
     *   Sur un cas reel de leur bulletin, l'ecart est de 09.86 contre 10.01 —
     *   la difference entre passer et ne pas passer.
     *
     * Un bloc SANS aucune matiere notee sort du calcul, coefficient compris,
     * comme une matiere dispensee : sinon il vaudrait zero et tirerait la
     * moyenne vers le bas. Le test porte sur « une ligne notee existe », pas
     * sur « la moyenne vaut zero » : un bloc dont toutes les notes sont a zero
     * est une realite, pas une absence.
     *
     * @param  \Illuminate\Support\Collection  $toutes
     * @param  \Illuminate\Support\Collection  $generales
     * @param  \Illuminate\Support\Collection  $professionnelles
     */
    public function composerLaMoyenneDuSemestre(
        $toutes,
        $generales,
        $professionnelles,
        ?float $moyenneGenerale,
        ?float $moyenneProfessionnelle
    ): float {
        if (SettingsHelper::get('bulletin_moyenne_mode', 'ponderee') !== 'blocs') {
            return (float) $this->calculerMoyennePonderee($toutes);
        }

        // Une matiere notee qui n'est dans AUCUN des deux blocs ne serait
        // comptee nulle part : la composition par blocs l'oublierait en
        // silence, et la moyenne serait celle d'une partie du bulletin. Le
        // bloc d'une matiere vient de `type_formation` ; tant qu'une seule
        // note echappe au classement, on rend la ponderation classique, qui
        // elle n'oublie personne.
        $noteesEnBloc = $this->compterLesLignesNotees($generales) + $this->compterLesLignesNotees($professionnelles);
        if ($noteesEnBloc < $this->compterLesLignesNotees($toutes)) {
            return (float) $this->calculerMoyennePonderee($toutes);
        }

        $blocs = [
            [$moyenneGenerale, (float) SettingsHelper::get('bulletin_bloc_general_coef', 1), $noteesEnBloc > 0 && $this->blocEstNote($generales)],
            [$moyenneProfessionnelle, (float) SettingsHelper::get('bulletin_bloc_professionnel_coef', 1), $noteesEnBloc > 0 && $this->blocEstNote($professionnelles)],
        ];

        $points = 0.0;
        $coefficients = 0.0;
        foreach ($blocs as [$moyenne, $coefficient, $estNote]) {
            if (! $estNote || $moyenne === null || $coefficient <= 0) {
                continue;
            }
            $points += $moyenne * $coefficient;
            $coefficients += $coefficient;
        }

        // Aucun bloc exploitable : on rend ce que la ponderation classique
        // aurait rendu, plutot qu'un zero invente.
        if ($coefficients <= 0) {
            return (float) $this->calculerMoyennePonderee($toutes);
        }

        return $points / $coefficients;
    }

    /**
     * Combien de lignes reellement notees.
     *
     * @param  \Illuminate\Support\Collection  $resultats
     */
    private function compterLesLignesNotees($resultats): int
    {
        $total = 0;
        foreach ($resultats as $resultat) {
            if ($this->ligneEstNotee($resultat)) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Un bloc porte-t-il au moins une matiere reellement notee ?
     *
     * @param  \Illuminate\Support\Collection  $resultats
     */
    private function blocEstNote($resultats): bool
    {
        foreach ($resultats as $resultat) {
            if ($this->ligneEstNotee($resultat)) {
                return true;
            }
        }

        return false;
    }

    public function calculerMoyennePonderee($resultats)
    {
        if ($resultats->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            // Une matiere dispensee ou non notee sort du calcul ENTIEREMENT :
            // son coefficient ne doit pas non plus entrer au denominateur,
            // sinon elle vaudrait zero et diluerait la moyenne — un etudiant
            // dispense de deux matieres serait puni de l'avoir ete.
            if (! $this->ligneEstNotee($resultat)) {
                continue;
            }

            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoefficients += $resultat->coefficient;
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }

    /** Cette ligne de bulletin porte-t-elle une note qui compte ? */
    private function ligneEstNotee($resultat): bool
    {
        return ESBTPResultatMatiere::ligneNotee($resultat);
    }

    /**
     * Calcule la note d'assiduité
     */
    public function calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees)
    {
        return $this->resolveAttendanceNote($absencesJustifiees, $absencesNonJustifiees);
    }

    /**
     * Calcule les statistiques de la classe
     */
    private function calculerStatistiquesClasse($classeId, $anneeUniversitaireId, $periode = 'semestre1', bool $useCache = false)
    {
        // Memoize (uniquement si $useCache) : ces stats parcourent TOUS les étudiants de la
        // classe et sont identiques pour chaque bulletin. Sur l'export groupé (persist=false)
        // les esbtp_resultats ne mutent pas → cache sûr, l'export passe de O(N²) à O(N).
        // En génération, $useCache=false pour préserver le calcul par étudiant (cf. call site).
        $statsKey = $classeId.':'.$anneeUniversitaireId.':'.$periode;
        if ($useCache && isset($this->classStatsCache[$statsKey])) {
            return $this->classStatsCache[$statsKey];
        }

        // La cohorte vient du compteur de phases, pas de la classe courante.
        // Un etudiant passe en specialite au semestre 2 garde une seule
        // inscription, deplacee vers sa nouvelle classe. Interroger
        // inscriptions.classe_id revenait donc a chercher les eleves du
        // semestre 1 dans une classe qu ils ont quittee : la classe paraissait
        // vide et les statistiques tombaient a zero. Le rang, lui, utilisait
        // deja ce compteur : les deux portent desormais sur la meme population.
        $etudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode(
            (int) $classeId,
            (int) $anneeUniversitaireId,
            (string) $periode
        );

        $etudiants = $etudiantIds === []
            ? collect()
            : ESBTPEtudiant::whereIn('id', $etudiantIds)->get();

        if ($etudiants->isEmpty()) {
            return $this->cacheClassStats($statsKey, [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0,
            ], $useCache);
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
            return $this->cacheClassStats($statsKey, [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0,
            ], $useCache);
        }

        return $this->cacheClassStats($statsKey, [
            'meilleure_moyenne' => max($moyennes),
            'plus_faible_moyenne' => min($moyennes),
            'moyenne_classe' => array_sum($moyennes) / count($moyennes),
        ], $useCache);
    }

    /**
     * Écrit les stats de classe dans le cache request-scoped uniquement si autorisé
     * (export/preview), puis les renvoie. En génération, aucun cache n'est écrit ni lu.
     */
    private function cacheClassStats(string $statsKey, array $stats, bool $useCache): array
    {
        if ($useCache) {
            $this->classStatsCache[$statsKey] = $stats;
        }

        return $stats;
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

        foreach ($resultats as $resultat) {
            // MEME FILTRE QUE LE BULLETIN, et c'est le point : ces moyennes
            // alimentent `moyenne_classe`, `meilleure_moyenne` et
            // `plus_faible_moyenne`, qui s'impriment sur la MEME feuille que la
            // moyenne de l'eleve. Filtrer l'une sans l'autre faisait cohabiter,
            // sur un A4, une moyenne juste et des statistiques fausses — un
            // eleve pouvait depasser la « plus forte moyenne » de sa classe.
            if ($resultat->matiere
                && ! CoherenceSystemeAcademique::matiereRetenue($resultat->matiere, $classe, 'stats classe/moyenne manuelle')) {
                continue;
            }

            if ($resultat->matiere) {
                try {
                    $coefficient = $this->getCoefficientForCombination(
                        $resultat->matiere_id,
                        $classe->id,
                        $anneeUniversitaireId,
                        $this->normalizePeriode((string) ($resultat->periode ?: $periode)),
                        (int) $etudiantId
                    );
                } catch (\RuntimeException $e) {
                    $coefficient = 1;
                }

                $resultatsParMatiere[] = (object) [
                    'moyenne' => $resultat->moyenne,
                    'coefficient' => $coefficient,
                ];
            }
        }

        // LE REPLI SE DECIDE APRES LE FILTRE, ET NON AVANT.
        //
        // Ce test portait sur `$resultats->isEmpty()`, donc sur la requete
        // BRUTE, et il etait place au-dessus de la boucle. Le filtre de
        // coherence ajoute dans cette boucle a rendu atteignable un cas
        // ordinaire qui ne l'etait pas : un eleve dont les seules moyennes
        // manuelles de la periode portent sur une ECUE. Toutes ses lignes
        // etaient ecartees, `$resultatsParMatiere` restait vide, et la methode
        // rendait **0** — sans lire ses notes, qui sont pourtant la.
        //
        // Or `calculerStatistiquesClasse()` ecarte les moyennes <= 0 : l'eleve
        // DISPARAISSAIT des statistiques. Une saisie en masse posee par erreur
        // sur une ECUE — un seul envoi de `bulkUpdateMoyennes()` suffit — vidait
        // ainsi les trois statistiques de toute une classe, imprimees a zero sur
        // chaque bulletin, pendant que le journal ne parlait que de « matiere
        // ecartee ». C'est la moitie muette d'un rattrapage, le piege #12.
        //
        // Decider apres le filtre supprime la branche `return 0` : ou bien il
        // reste des moyennes retenues, ou bien on lit les notes — elles-memes
        // filtrees par `calculerMoyenneDepuisNotes()`.
        //
        // UN SECOND CAS CHANGE AU PASSAGE, et il vaut mieux le dire : une ligne
        // dont la `matiere_id` est pendante (FK cassee) rendait 0, elle mene
        // maintenant au meme repli. C'est meilleur — l'eleve garde ses notes —
        // mais ce n'est pas le defaut que ce correctif visait.
        if (empty($resultatsParMatiere)) {
            return $this->calculerMoyenneDepuisNotes(
                $etudiantId,
                $classe,
                $anneeUniversitaireId,
                $periodeOptions,
                $this->normalizePeriode((string) $periode)
            );
        }

        return $this->calculerMoyennePonderee(collect($resultatsParMatiere));
    }

    private function calculerMoyenneDepuisNotes(int $etudiantId, ESBTPClasse $classe, int $anneeUniversitaireId, array $periodeOptions, string $periode = 'semestre1'): float
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

            // Voir le commentaire jumeau dans `calculerMoyenneGlobaleEtudiant()`.
            if (! CoherenceSystemeAcademique::matiereRetenue($note->evaluation->matiere, $classe, 'stats classe/note')) {
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
                $coefficient = $this->getCoefficientForCombination(
                    $matiereId,
                    $classe->id,
                    $anneeUniversitaireId,
                    $this->normalizePeriode($periode),
                    $etudiantId
                );
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
        // Memoize : ~80 lectures de settings (chacune Cache::remember → I/O driver).
        // Identique pour tous les bulletins d'un export → calculé une seule fois.
        if ($this->pdfConfigCache !== null) {
            return $this->pdfConfigCache;
        }

        return $this->pdfConfigCache = [
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
            'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '13'),
            'bulletin_style' => \App\Helpers\SettingsHelper::get('bulletin_style', 'yakro'),
            'bulletin_bts1_s1_council_title' => \App\Helpers\SettingsHelper::get('bulletin_bts1_s1_council_title', 'Décision du conseil de classe'),
            'bulletin_show_header' => \App\Helpers\SettingsHelper::get('bulletin_show_header', '1'),
            'bulletin_show_logo' => \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1'),
            'bulletin_show_republic_info' => \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1'),
            'bulletin_republic_text' => \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
            'bulletin_union_text' => \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union - Discipline - Travail'),
            'bulletin_show_ministry_info' => \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1'),
            'bulletin_ministry_text' => \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur et de la Recherche Scientifique'),
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
            'bulletin_appreciation_plain' => \App\Helpers\SettingsHelper::get('bulletin_appreciation_plain', '0'),
            'bulletin_margin_vertical' => \App\Helpers\SettingsHelper::get('bulletin_margin_vertical', '5'),
            'bulletin_margin_horizontal' => \App\Helpers\SettingsHelper::get('bulletin_margin_horizontal', '5'),
            'bulletin_decision_min_height' => \App\Helpers\SettingsHelper::get('bulletin_decision_min_height', '84'),
            'bulletin_signature_height' => \App\Helpers\SettingsHelper::get('bulletin_signature_height', '44'),
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

            'bulletin_show_mentions' => \App\Helpers\SettingsHelper::get('bulletin_show_mentions', '1'),
            'bulletin_auto_calculate_mention' => \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1'),

            // Statistiques
            'bulletin_show_statistics' => \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1'),
            'bulletin_show_highest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1'),
            'bulletin_show_lowest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1'),
            'bulletin_show_class_average' => \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1'),
            'bulletin_include_attendance_in_stats' => \App\Helpers\SettingsHelper::get('bulletin_include_attendance_in_stats', '1'),

            // Regles BTS propres au tenant
            ...BtsBulletinPolicy::readSettings(
                fn (string $key, string $default) => \App\Helpers\SettingsHelper::get($key, $default)
            ),

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
     * Moyennes par matière d'un étudiant, calculées EN LIVE depuis les notes
     * (esbtp_notes) pour les périodes données. Repli du rang par matière quand la
     * table agrégat esbtp_resultats n'est pas encore peuplée (bulletin en aperçu ou
     * non généré officiellement). Réutilise l'algorithme de normalisation officiel :
     * note/barème*20 pondérée par le coefficient d'évaluation (cf. calculerMoyenneDepuisNotes).
     *
     * @param  array<int, string>  $periodeOptions
     * @return array<int, float> [matiere_id => moyenne]
     */
    private function moyennesParMatiereEtudiant(int $etudiantId, int $classeId, int $anneeUniversitaireId, array $periodeOptions): array
    {
        $notes = ESBTPNote::where('etudiant_id', $etudiantId)
            ->with('evaluation')
            ->byClasse($classeId)
            ->byAnneeUniversitaire($anneeUniversitaireId)
            ->where(function ($query) use ($periodeOptions) {
                $query->whereIn('semestre', $periodeOptions)
                    ->orWhereHas('evaluation', function ($subQuery) use ($periodeOptions) {
                        $subQuery->whereIn('periode', $periodeOptions);
                    });
            })
            ->get();

        $acc = [];
        foreach ($notes as $note) {
            if (! $note->evaluation) {
                continue;
            }
            $matiereId = $note->matiere_id ?: ($note->evaluation->matiere_id ?? null);
            if (! $matiereId) {
                continue;
            }

            $noteValue = $note->is_absent
                ? 0
                : (is_numeric($note->note) ? (float) $note->note : (is_numeric($note->valeur) ? (float) $note->valeur : 0));
            $bareme = $note->evaluation->bareme > 0 ? (float) $note->evaluation->bareme : 20;
            $normalized = $bareme > 0 ? ($noteValue / $bareme) * 20 : 0;
            $evalCoeff = $note->evaluation->coefficient ? (float) $note->evaluation->coefficient : 1;

            $acc[$matiereId] ??= ['points' => 0.0, 'coeffs' => 0.0];
            $acc[$matiereId]['points'] += $normalized * $evalCoeff;
            $acc[$matiereId]['coeffs'] += $evalCoeff;
        }

        $moyennes = [];
        foreach ($acc as $matiereId => $data) {
            if ($data['coeffs'] > 0) {
                $moyennes[(int) $matiereId] = round($data['points'] / $data['coeffs'], 2);
            }
        }

        return $moyennes;
    }

    /**
     * Rang par matière calculé EN LIVE (repli quand esbtp_resultats est vide) :
     * classe chaque étudiant de la classe sur sa moyenne matière issue des notes.
     * Égalités : même rang (rang de la première occurrence).
     *
     * @param  array<int, int>  $matiereIds
     * @return array<int, string> [matiere_id => rang|'-']
     */
    private function calculerRangsParMatiereLive(array $matiereIds, int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        if (empty($matiereIds)) {
            return [];
        }

        $periodeOptions = array_unique($this->periodeOptionsForRang($periode));
        $wanted = array_flip(array_map('intval', $matiereIds));

        $etudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, $periode);
        if ($etudiantIds === []) {
            $etudiantIds = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $anneeUniversitaireId) {
                $q->where('classe_id', $classeId)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            })->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        // moyennes[matiereId][etudiantId] = moyenne
        $moyennes = [];
        foreach ($etudiantIds as $sid) {
            $perMatiere = $this->moyennesParMatiereEtudiant((int) $sid, $classeId, $anneeUniversitaireId, $periodeOptions);
            foreach ($perMatiere as $matiereId => $moyenne) {
                if (isset($wanted[$matiereId])) {
                    $moyennes[$matiereId][(int) $sid] = $moyenne;
                }
            }
        }

        $rangs = [];
        foreach ($matiereIds as $matiereId) {
            $matiereId = (int) $matiereId;
            $classement = $moyennes[$matiereId] ?? [];
            if (empty($classement) || ! isset($classement[$etudiantId])) {
                $rangs[$matiereId] = '-';
                continue;
            }

            arsort($classement);
            $position = 0;
            $prevMoyenne = null;
            $prevRang = 1;
            $rang = '-';
            foreach ($classement as $sid => $moyenne) {
                $position++;
                if ($prevMoyenne === null || $moyenne < $prevMoyenne) {
                    $prevRang = $position;
                }
                if ($sid == $etudiantId) {
                    $rang = (string) $prevRang;
                    break;
                }
                $prevMoyenne = $moyenne;
            }
            $rangs[$matiereId] = $rang;
        }

        return $rangs;
    }

    /**
     * Variantes de période acceptées (numérique + libellé) pour les requêtes de rang.
     *
     * @return array<int, string>
     */
    private function periodeOptionsForRang(string $periode): array
    {
        $options = [$periode];
        $map = ['semestre1' => '1', 'semestre2' => '2', '1' => 'semestre1', '2' => 'semestre2'];
        if (isset($map[$periode])) {
            $options[] = $map[$periode];
        }

        return $options;
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
            // `evaluation.classe` : le filtre de coherence plus bas en a besoin.
            // Cette branche est celle de la periode « annuel », et son resultat
            // est ECRIT dans `esbtp_bulletins.moyenne_generale` par
            // `BulletinAverageBackfillService` — la moyenne officielle figee.
            ->with(['evaluation', 'evaluation.matiere', 'evaluation.classe']);

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

            // Cette branche a DEUX chemins d'ingestion et le second ECRASE le
            // premier (voir plus bas) : les filtrer tous les deux, ou aucun.
            $classeDeLaNote = $note->evaluation->classe;

            if ($classeDeLaNote
                && ! CoherenceSystemeAcademique::matiereRetenue($note->evaluation->matiere, $classeDeLaNote, 'moyenne periode/note')) {
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
            ->with(['matiere', 'classe'])
            ->get();

        foreach ($resultats as $resultat) {
            if (! $resultat->matiere) {
                continue;
            }

            if ($resultat->classe
                && ! CoherenceSystemeAcademique::matiereRetenue($resultat->matiere, $resultat->classe, 'moyenne periode/moyenne enregistree')) {
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
                // $periodePourBDD appartient a calculateMoyennesForStudent : ici
                // il n existe pas, et PHP levait « Undefined variable » des
                // qu une matiere avait une moyenne positive. La page de
                // resultats d un etudiant renvoyait alors une erreur serveur.
                $coeff = $this->getCoefficientForCombination(
                    $matiereId,
                    $classeId ?? 0,
                    $anneeUniversitaireId ?? 0,
                    $this->normalizePeriode($periode),
                    $etudiantId
                );
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
        // Memoize par chemin : le logo (lecture fichier + base64) est identique pour tous
        // les bulletins d'un export. Évite N lectures disque + N encodages base64.
        $logoKey = (string) $logoPath;
        if (array_key_exists($logoKey, $this->logoBase64Cache)) {
            return $this->logoBase64Cache[$logoKey];
        }

        return $this->logoBase64Cache[$logoKey] = $this->resolveLogoBase64($logoPath);
    }

    private function resolveLogoBase64($logoPath)
    {
        // Essayer d'abord le chemin depuis storage (logos uploadés)
        if ($logoPath) {
            $storagePath = storage_path('app/public/'.$logoPath);
            if (is_file($storagePath)) {
                $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($storagePath);
                Log::info('Logo uploadé chargé avec succès depuis: '.$storagePath);

                return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
            }

            // Essayer aussi dans public/ pour compatibilité
            $publicPath = public_path($logoPath);
            if (is_file($publicPath)) {
                $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($publicPath);
                Log::info('Logo public chargé avec succès depuis: '.$publicPath);

                return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
            }
        }

        // Essayer les chemins alternatifs
        // Repli generique uniquement : servir esbtp_logo a une autre ecole
        // lui imprimait le logo d'un concurrent sur ses propres bulletins.
        $alternativePaths = ['images/LOGO-KLASSCI-PNG.png'];

        // Sans logo configure, basename('') vaut '' et ce candidat devenait
        // « storage/logos/ », c'est-a-dire un DOSSIER. file_exists le validait,
        // puis file_get_contents levait « Is a directory » et interrompait
        // toute la generation du bulletin.
        $logoBasename = $logoPath ? basename($logoPath) : '';
        if ($logoBasename !== '') {
            array_unshift($alternativePaths, 'storage/logos/'.$logoBasename);
        }

        foreach ($alternativePaths as $altPath) {
            $fullPath = public_path($altPath);
            // is_file et non file_exists : un dossier existe aussi, et le lire
            // comme un fichier casse la generation au lieu de replier proprement.
            if (is_file($fullPath)) {
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
        $this->calculerRangsPourClasse(
            (int) $bulletin->classe_id,
            (int) $bulletin->annee_universitaire_id,
            (string) $bulletin->periode
        );
        $bulletin->refresh();
    }

    public function calculerRangsPourClasse(int $classeId, int $anneeUniversitaireId, string $periode): void
    {
        $periode = $this->normalizePeriode($periode);

        foreach ($this->rankCohortIdsForClasse($classeId, $anneeUniversitaireId, $periode) as $cohortId) {
            $this->recalculateRanksForCohort($cohortId, $anneeUniversitaireId, $periode);
        }
    }

    /**
     * Propose ranks for a class without writing persisted bulletin rows.
     *
     * @return array<int, array{id:int, moyenne:?float, rang_actuel:?int, rang_propose:?int}>
     */
    public function previewRanksForClasse(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $periode = $this->normalizePeriode($periode);
        $proposals = [];

        foreach ($this->rankCohortIdsForClasse($classeId, $anneeUniversitaireId, $periode) as $cohortId) {
            foreach ($this->proposedRanksForCohort((int) $cohortId, $anneeUniversitaireId, $periode) as $proposal) {
                $proposals[(int) $proposal['id']] = $proposal;
            }
        }

        return array_values($proposals);
    }

    private function recalculateRanksForCohort(int $cohortClasseId, int $anneeUniversitaireId, string $periode): void
    {
        $bulletins = $this->bulletinsInRankCohort($cohortClasseId, $anneeUniversitaireId, $periode);
        if ($bulletins->isEmpty()) {
            return;
        }

        $averages = [];
        foreach ($bulletins as $bulletin) {
            $average = $this->getEffectiveBulletinAverage($bulletin);
            if ($average === null) {
                continue;
            }
            $averages[(int) $bulletin->id] = $average;
        }

        $effectif = $this->getValidatedClassStudentCount($cohortClasseId, $anneeUniversitaireId, $periode);
        foreach ($bulletins as $bulletin) {
            $average = $averages[(int) $bulletin->id] ?? null;
            $bulletin->effectif_classe = $effectif;
            $bulletin->rang = $average === null ? null : $this->rankAmongAverages($averages, $average);
            $bulletin->save();
        }
    }

    /**
     * @return list<int>
     */
    private function rankCohortIdsForClasse(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $periode = $this->normalizePeriode($periode);
        $cohortIds = [$classeId];

        $classBulletins = ESBTPBulletin::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->periodeAliases($periode))
            ->whereNotNull('moyenne_generale')
            ->get();

        foreach ($classBulletins as $bulletin) {
            $cohortIds[] = $this->cohortResolver->resolveRankCohortClasseId($bulletin);
        }

        return array_values(array_unique(array_map('intval', $cohortIds)));
    }

    /**
     * @return array<int, array{id:int, moyenne:?float, rang_actuel:?int, rang_propose:?int}>
     */
    private function proposedRanksForCohort(int $cohortClasseId, int $anneeUniversitaireId, string $periode): array
    {
        $bulletins = $this->bulletinsInRankCohort($cohortClasseId, $anneeUniversitaireId, $periode);
        $averages = [];
        foreach ($bulletins as $bulletin) {
            $average = $this->getEffectiveBulletinAverage($bulletin);
            if ($average === null) {
                continue;
            }
            $averages[(int) $bulletin->id] = $average;
        }

        $proposals = [];
        foreach ($bulletins as $bulletin) {
            $average = $averages[(int) $bulletin->id] ?? null;
            $proposals[(int) $bulletin->id] = [
                'id' => (int) $bulletin->id,
                'moyenne' => $average === null ? null : round((float) $average, 2),
                'rang_actuel' => $bulletin->rang === null ? null : (int) $bulletin->rang,
                'rang_propose' => $average === null ? null : $this->rankAmongAverages($averages, $average),
            ];
        }

        return $proposals;
    }

    private function bulletinsInRankCohort(int $cohortClasseId, int $anneeUniversitaireId, string $periode)
    {
        $periodeAliases = $this->periodeAliases($periode);
        $direct = ESBTPBulletin::query()
            ->where('classe_id', $cohortClasseId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $periodeAliases)
            ->whereNotNull('moyenne_generale')
            ->get();

        if ($this->normalizePeriode($periode) !== 'semestre1') {
            return $direct;
        }

        $cohortEtudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode($cohortClasseId, $anneeUniversitaireId, $periode);
        $others = $cohortEtudiantIds === []
            ? collect()
            : ESBTPBulletin::query()
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->where('classe_id', '!=', $cohortClasseId)
                ->whereIn('etudiant_id', $cohortEtudiantIds)
                ->whereIn('periode', $periodeAliases)
                ->whereNotNull('moyenne_generale')
                ->get()
                ->filter(fn (ESBTPBulletin $bulletin): bool => $this->cohortResolver->resolveRankCohortClasseId($bulletin) === $cohortClasseId);

        return $direct->concat($others)->unique('id')->values();
    }

    /**
     * Décodage tolérant d'une valeur JSON : accepte un array déjà casté (colonnes
     * castées `json` sur le modèle) OU une chaîne brute, et renvoie toujours un array.
     * Source unique pour tous les consommateurs (controllers inclus).
     */
    public function decodeJsonToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getValidatedClassStudentCount(int $classeId, int $anneeUniversitaireId, string $periode = 'semestre1'): int
    {
        $periode = $this->normalizePeriode($periode);
        $effectifKey = $classeId.':'.$anneeUniversitaireId.':'.$periode;
        if (isset($this->effectifCache[$effectifKey])) {
            return $this->effectifCache[$effectifKey];
        }

        // Meme cohorte que le classement et que la generation : un etudiant
        // appartient a une seule classe par periode, et l'effectif compte
        // exactement ceux-la.
        return $this->effectifCache[$effectifKey] = $this->classCohortCounter->countPourPeriode(
            $classeId,
            $anneeUniversitaireId,
            $periode
        );
    }

    public function calculerRangAnnuel(int $etudiantId, int $classeId, int $anneeUniversitaireId, ?float $moyenneAnnuelle): ?int
    {
        if ($moyenneAnnuelle === null) {
            return null;
        }

        $averages = $this->collectAnnualAveragesForClasse($classeId, $anneeUniversitaireId);
        if ($averages === []) {
            return null;
        }

        return $this->rankAmongAverages($averages, $moyenneAnnuelle);
    }

    /**
     * @return array<int, float>
     */
    private function collectSemesterAveragesForClasse(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $periode = $this->normalizePeriode($periode);
        $etudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, $periode);
        $stored = $this->storedBulletinAveragesByEtudiant($etudiantIds, $classeId, $anneeUniversitaireId, $periode);

        $averages = [];
        foreach ($etudiantIds as $etudiantId) {
            $average = $stored[(int) $etudiantId] ?? $this->liveAverageForStudent(
                (int) $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $periode
            );
            if ($average === null) {
                continue;
            }
            $averages[(int) $etudiantId] = $average;
        }

        return $averages;
    }

    /**
     * @return array<int, float>
     */
    private function collectAnnualAveragesForClasse(int $classeId, int $anneeUniversitaireId): array
    {
        $classe = ESBTPClasse::with(['filiere', 'niveau', 'niveauEtude'])->find($classeId);
        $weights = $this->getSemesterWeights($classe);
        $averages = [];

        $etudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, 'semestre2');
        $s1Stored = [];
        $s2Stored = [];
        foreach ($etudiantIds as $etudiantId) {
            $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
            $classeIdS1 = (int) ($classMap['semestre1_classe_id'] ?? $classeId);
            $classeIdS2 = (int) ($classMap['semestre2_classe_id'] ?? $classeId);
            $s1Stored[$classeIdS1][] = (int) $etudiantId;
            $s2Stored[$classeIdS2][] = (int) $etudiantId;
        }

        $s1Averages = [];
        foreach ($s1Stored as $mappedClasseId => $mappedEtudiantIds) {
            $s1Averages[$mappedClasseId] = $this->storedBulletinAveragesByEtudiant(
                array_values(array_unique($mappedEtudiantIds)),
                (int) $mappedClasseId,
                $anneeUniversitaireId,
                'semestre1'
            );
        }

        $s2Averages = [];
        foreach ($s2Stored as $mappedClasseId => $mappedEtudiantIds) {
            $s2Averages[$mappedClasseId] = $this->storedBulletinAveragesByEtudiant(
                array_values(array_unique($mappedEtudiantIds)),
                (int) $mappedClasseId,
                $anneeUniversitaireId,
                'semestre2'
            );
        }

        foreach ($etudiantIds as $etudiantId) {
            $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
            $classeIdS1 = (int) ($classMap['semestre1_classe_id'] ?? $classeId);
            $classeIdS2 = (int) ($classMap['semestre2_classe_id'] ?? $classeId);
            $s1 = $s1Averages[$classeIdS1][(int) $etudiantId] ?? $this->liveAverageForStudent(
                (int) $etudiantId,
                $classeIdS1,
                $anneeUniversitaireId,
                'semestre1'
            );
            $s2 = $s2Averages[$classeIdS2][(int) $etudiantId] ?? $this->liveAverageForStudent(
                (int) $etudiantId,
                $classeIdS2,
                $anneeUniversitaireId,
                'semestre2'
            );
            $annual = $this->calculateAnnualAverage($s1, $s2, $weights);
            if ($annual === null) {
                continue;
            }
            $averages[(int) $etudiantId] = $annual;
        }

        return $averages;
    }

    private function averageFromStoredBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): ?float
    {
        $stored = $this->storedBulletinAveragesByEtudiant(
            [$etudiantId],
            $classeId,
            $anneeUniversitaireId,
            $periode
        );

        if (array_key_exists($etudiantId, $stored)) {
            return $stored[$etudiantId];
        }

        return $this->liveAverageForStudent($etudiantId, $classeId, $anneeUniversitaireId, $periode);
    }

    /**
     * @param list<int> $etudiantIds
     * @return array<int, float>
     */
    private function storedBulletinAveragesByEtudiant(array $etudiantIds, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        if ($etudiantIds === []) {
            return [];
        }

        $bulletins = ESBTPBulletin::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->periodeAliases($periode))
            ->get();

        $averages = [];
        foreach ($bulletins as $bulletin) {
            if ($bulletin->moyenne_generale === null || $bulletin->moyenne_generale <= 0) {
                continue;
            }

            $averages[(int) $bulletin->etudiant_id] = $this->getEffectiveBulletinAverage($bulletin);
        }

        return $averages;
    }

    private function liveAverageForStudent(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): ?float
    {
        $rawAvg = $this->calculateStudentAverageForPeriode($etudiantId, $classeId, $anneeUniversitaireId, $periode);
        if ($rawAvg === null) {
            return null;
        }

        return $rawAvg + $this->calculateEffectiveAttendanceNoteForStudent(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $periode
        );
    }

    /**
     * @param array<int, float> $averages
     */
    private function rankAmongAverages(array $averages, float $target): int
    {
        $rank = 1;
        foreach ($averages as $average) {
            if ($average > $target) {
                $rank++;
            }
        }

        return $rank;
    }

    /**
     * Calcule les rangs d'un étudiant pour plusieurs matières en une seule requête.
     * Retourne un tableau [matiere_id => rang_string].
     */
    public function calculerRangsParMatierePourEtudiant(array $matiereIds, int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        if (empty($matiereIds)) {
            return [];
        }

        $periode = $this->normalizePeriode($periode);
        $liveRanks = $this->calculerRangsParMatiereLive($matiereIds, $etudiantId, $classeId, $anneeUniversitaireId, $periode);
        $resultats = ESBTPResultat::query()
            ->whereIn('matiere_id', $matiereIds)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->periodeOptionsForRang($periode))
            ->whereNotNull('moyenne')
            ->orderByDesc('moyenne')
            ->get()
            ->groupBy('matiere_id');

        $rangs = [];
        foreach ($matiereIds as $matiereId) {
            $storedRank = $this->rankFromStoredSubjectResults($resultats->get($matiereId), $etudiantId);
            $liveRank = $liveRanks[(int) $matiereId] ?? '-';
            $rangs[(int) $matiereId] = $this->preferRicherSubjectRank($liveRank, $storedRank);
        }

        return $rangs;
    }

    /**
     * @param  array<int, float>  $matiereCoefficients
     */
    public function rankAmongMatiereSet(
        array $matiereCoefficients,
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        ?float $officialAverage = null
    ): ?int {
        $matiereIds = array_values(array_map('intval', array_keys($matiereCoefficients)));
        if ($matiereIds === []) {
            return null;
        }

        if (count($matiereIds) === 1) {
            $ranks = $this->calculerRangsParMatierePourEtudiant(
                $matiereIds,
                $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $periode
            );
            $rank = $ranks[$matiereIds[0]] ?? '-';

            return is_numeric($rank) ? (int) $rank : null;
        }

        $periode = $this->normalizePeriode($periode);
        $periodeOptions = array_unique($this->periodeOptionsForRang($periode));
        $etudiantIds = $this->classCohortCounter->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, $periode);
        if ($etudiantIds === []) {
            $etudiantIds = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $anneeUniversitaireId) {
                $q->where('classe_id', $classeId)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            })->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $averages = [];
        foreach ($etudiantIds as $sid) {
            $perMatiere = $this->moyennesParMatiereEtudiant((int) $sid, $classeId, $anneeUniversitaireId, $periodeOptions);
            $weighted = 0.0;
            $coef = 0.0;
            foreach ($matiereCoefficients as $matiereId => $coefficient) {
                $matiereId = (int) $matiereId;
                if (! isset($perMatiere[$matiereId]) || $coefficient <= 0) {
                    continue;
                }
                $weighted += $perMatiere[$matiereId] * (float) $coefficient;
                $coef += (float) $coefficient;
            }
            if ($coef > 0) {
                $averages[(int) $sid] = $weighted / $coef;
            }
        }

        if ($officialAverage !== null) {
            $averages[$etudiantId] = $officialAverage;
        }
        if (! isset($averages[$etudiantId])) {
            return null;
        }

        return $this->rankAmongAverages($averages, $averages[$etudiantId]);
    }

    public function recalculerRangsParMatierePourClasse(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $periode = $this->normalizePeriode($periode);
        $bulletins = ESBTPBulletin::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->periodeAliases($periode))
            ->get();

        $changed = 0;
        $unchanged = 0;
        $samples = [];

        foreach ($bulletins as $bulletin) {
            $rows = ESBTPResultatMatiere::query()
                ->where('bulletin_id', $bulletin->id)
                ->get();
            if ($rows->isEmpty()) {
                $unchanged++;
                continue;
            }

            // Une ligne dispensee ou non notee ne se classe pas. Le rang vient
            // aussi d'un calcul « live » sur les notes brutes : sans ce filtre,
            // un etudiant dispense recevrait un rang sur une matiere dont il
            // est precisement exempte.
            $ranks = $this->calculerRangsParMatierePourEtudiant(
                $rows->filter(fn ($row) => $row->estNotee())
                    ->pluck('matiere_id')->map(fn ($id) => (int) $id)->all(),
                (int) $bulletin->etudiant_id,
                $classeId,
                $anneeUniversitaireId,
                $periode
            );

            $bulletinChanged = false;
            foreach ($rows as $row) {
                $proposed = $row->estNotee() ? ($ranks[(int) $row->matiere_id] ?? '-') : '-';
                $proposedInt = is_numeric($proposed) ? (int) $proposed : null;
                $current = $row->rang === null ? null : (int) $row->rang;
                if ($current === $proposedInt) {
                    continue;
                }

                $row->rang = $proposedInt;
                $row->save();
                $bulletinChanged = true;
                if (count($samples) < 24) {
                    $samples[] = [
                        'bulletin_id' => (int) $bulletin->id,
                        'etudiant_id' => (int) $bulletin->etudiant_id,
                        'matiere_id' => (int) $row->matiere_id,
                        'rang_actuel' => $current,
                        'rang_propose' => $proposedInt,
                    ];
                }
            }

            if ($bulletinChanged) {
                $changed++;
            } else {
                $unchanged++;
            }
        }

        return [
            'classe_id' => $classeId,
            'periode' => $periode,
            'bulletins_lus' => $bulletins->count(),
            'bulletins_changes' => $changed,
            'bulletins_inchanges' => $unchanged,
            'echantillons' => $samples,
        ];
    }

    private function rankFromStoredSubjectResults($matiereResultats, int $etudiantId): string
    {
        if (! $matiereResultats || $matiereResultats->isEmpty()) {
            return '-';
        }

        $rang = 1;
        $prevMoyenne = null;
        $prevRang = 1;
        foreach ($matiereResultats->sortByDesc('moyenne')->values() as $resultat) {
            if ($prevMoyenne !== null && $resultat->moyenne < $prevMoyenne) {
                $prevRang = $rang;
            }
            if ((int) $resultat->etudiant_id === $etudiantId) {
                return (string) $prevRang;
            }
            $prevMoyenne = $resultat->moyenne;
            $rang++;
        }

        return '-';
    }

    private function preferRicherSubjectRank(string $liveRank, string $storedRank): string
    {
        $live = is_numeric($liveRank) ? (int) $liveRank : null;
        $stored = is_numeric($storedRank) ? (int) $storedRank : null;

        if ($live === null) {
            return $storedRank;
        }
        if ($stored === null) {
            return $liveRank;
        }

        return (string) max($live, $stored);
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


    public function buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses, string $periode)
    {
        if ($classe_id) {
            // UNE source : la cohorte de phases, qui applique la meme election
            // partout -- une classe par semestre. Seul le PERIMETRE varie avec
            // le bouton « inclure les inscriptions inactives » : toutes les
            // inscriptions de l'annee quand il est actif (l'orientation laisse
            // `terminee`, un abandon laisse `annulee`), les actives sinon.
            //
            // La liste a longtemps ajoute les inscriptions pointant sur la
            // classe, sans regard sur la periode. Pour un etudiant oriente,
            // cette source le rattachait a sa specialite DES LE SEMESTRE 1,
            // pendant que la cohorte le laissait au tronc commun : deux
            // classes au meme semestre, le signalement de Yamoussoukro sous
            // une autre forme. Un etudiant sans phase, lui, passe par le repli
            // de l'election (sa classe d'inscription) : il n'a rien perdu.
            //
            // Sur « annuel », union des deux semestres. Licite ICI et nulle
            // part ailleurs : cette methode construit une LISTE, aucune
            // generation ne suit. Le compteur, lui, reste exclusif, sans quoi
            // la generation annuelle lancee sur deux classes creerait deux
            // bulletins pour le meme etudiant.
            $normalisee = $this->normalizePeriode($periode);
            $periodesCohorte = $normalisee === 'annuel'
                ? ['semestre1', 'semestre2']
                : [$normalisee];

            $cohorteIds = collect($periodesCohorte)->flatMap(
                fn (string $p) => $include_all_statuses
                    ? $this->classCohortCounter->etudiantIdsToutesInscriptions((int) $classe_id, (int) $annee_universitaire_id, $p)
                    : $this->classCohortCounter->etudiantIdsInscriptionsActives((int) $classe_id, (int) $annee_universitaire_id, $p)
            );

            // Ces trois sources remplacent une union par les NOTES et les
            // RESULTATS persistes. Celle-ci ramenait les partis : une specialite
            // corrigee garde les notes prises avant la correction, et l'etudiant
            // reapparaissait dans son ANCIENNE classe en plus de la nouvelle.
            // Les notes disent ou l'on a evalue ; l'appartenance est une autre
            // question. Consequence assumee : une moyenne saisie a la main dans
            // une classe ou l'etudiant n'a ni inscription ni phase ne le fait
            // plus apparaitre -- c'est un etat incoherent, mieux vaut le voir.
            $allEtudiantIds = $cohorteIds
                ->unique()
                ->filter()
                ->values();

            return ESBTPEtudiant::whereIn('id', $allEtudiantIds)
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
        $resultatsQuery = \App\Models\ESBTPResultat::whereIn('etudiant_id', $student_ids)
            // Le filtre de coherence ci-dessous lit la matiere ET la classe de
            // chaque ligne. Sans ces deux eager-loads, c'est deux requetes par
            // ligne, sur toute une promotion.
            ->with(['matiere', 'classe']);

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

        // UNE LIGNE DE `esbtp_resultats` EST UNE MATIERE, PAS UN ELEVE.
        // Cette boucle ecrivait `$moyennes[$etudiantId] = $resultat->moyenne`
        // sur chaque ligne : la DERNIERE matiere lue devenait la « moyenne
        // generale » de l'eleve, sans ponderation ni ordre. La bande KPI de
        // `/esbtp/resultats` (Moyenne generale, Taux de reussite) affichait donc
        // la moyenne d'une matiere prise au hasard — et si cette matiere etait
        // une ECUE du LMD, elle affichait la note de l'ECUE.
        //
        // On agrege desormais par eleve, pondere par le coefficient de la ligne,
        // et on ecarte les matieres etrangeres au systeme academique de la
        // classe comme partout ailleurs.
        //
        // `rang` n'est PAS relu : c'est un rang PAR MATIERE, et le prendre pour
        // un rang de classe etait le meme defaut. Les rangs sont recalcules plus
        // bas a partir des moyennes corrigees.
        $cumuls = [];

        foreach ($resultats as $resultat) {
            if ($resultat->moyenne === null) {
                continue;
            }

            $classeDeLaLigne = $resultat->classe;

            if ($classeDeLaLigne && $resultat->matiere
                && ! CoherenceSystemeAcademique::matiereRetenue($resultat->matiere, $classeDeLaLigne, 'kpi resultats/moyenne enregistree')) {
                continue;
            }

            $coefficient = (float) ($resultat->coefficient ?: 1);

            if ($coefficient <= 0) {
                $coefficient = 1;
            }

            $etudiantId = $resultat->etudiant_id;
            $cumuls[$etudiantId]['points'] = ($cumuls[$etudiantId]['points'] ?? 0) + ((float) $resultat->moyenne * $coefficient);
            $cumuls[$etudiantId]['coefs'] = ($cumuls[$etudiantId]['coefs'] ?? 0) + $coefficient;
        }

        foreach ($cumuls as $etudiantId => $cumul) {
            if ($cumul['coefs'] > 0) {
                $moyennes[$etudiantId] = round($cumul['points'] / $cumul['coefs'], 2);
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

        // LA CLASSE SE RESOUT PAR NOTE, ET C'EST LA CORRECTION DE LA PASSE 12.
        // La version precedente affirmait « les deux appelants passent toujours
        // la classe ». C'etait faux : `/esbtp/resultats` porte un choix
        // « Toutes les classes » (`resultats/index.blade.php`, option de valeur
        // vide) et `ESBTPResultatController` transmet alors `classe_id = null`.
        // Le filtre etait donc inerte exactement dans le mode ou les eleves
        // viennent de plusieurs classes — le meme eleve lisait 9,00 la et 14,00
        // des qu'on selectionnait sa classe.
        //
        // La classe de la note est portee par son evaluation, et les deux
        // appelants l'eager-loadent deja (`evaluation.classe`) : aucune requete
        // de plus, et c'est plus juste que le parametre, qui ne decrit qu'un
        // filtre de recherche.
        $classeDuParametre = $classeId ? ESBTPClasse::find($classeId) : null;
        $sansClasseJournalisee = false;

        foreach ($notes as $note) {
            if (! $note->evaluation || ! $note->evaluation->matiere) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);

                continue;
            }

            // Meme filtre que le bulletin : ces moyennes et ces rangs
            // s'affichent sur `/esbtp/resultats`, a cote du bulletin PDF. Les
            // laisser diverger donnait deux chiffres differents pour le meme
            // eleve selon l'ecran consulte.
            $classeDeLaNote = $note->evaluation->classe ?? $classeDuParametre;

            if (! $classeDeLaNote) {
                // Ni l'evaluation ni le parametre ne nomment de classe : on ne
                // PEUT pas juger. On garde la note et on le dit une fois, plutot
                // que de filtrer au hasard ou de se taire.
                if (! $sansClasseJournalisee) {
                    $sansClasseJournalisee = true;
                    \Log::warning('Statistiques : note sans classe resolvable, coherence non verifiable.', [
                        'note_id' => $note->id,
                        'classe_id_parametre' => $classeId,
                    ]);
                }
            } elseif (! CoherenceSystemeAcademique::matiereRetenue($note->evaluation->matiere, $classeDeLaNote, 'stats resultats/note')) {
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
                // `with('matiere')` : le filtre de coherence ci-dessous lit la
                // matiere de chaque ligne. Sans l'eager-load, c'est une requete
                // par ligne, sur toute une classe.
                ->with('matiere')
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
                    // LE MEME FILTRE QU'AU CHEMIN DES NOTES, et c'est ici qu'il
                    // manquait. Cette methode a DEUX chemins d'ingestion, et
                    // celui-ci ne fait pas qu'ajouter : il ECRASE la valeur
                    // calculee depuis les notes, et RECREE l'entree que le
                    // `continue` du premier chemin venait d'ecarter. Filtrer un
                    // seul des deux revenait donc a ne filtrer aucun des deux
                    // des qu'une ligne heritee existe — c'est-a-dire dans le cas
                    // meme que ce correctif vise.
                    // Ce bloc n'est atteint que si `$classeId` est renseigne
                    // (voir le `if` qui l'englobe), donc `$classeDuParametre`
                    // repond forcement ici — contrairement au chemin des notes.
                    if ($classeDuParametre && $resultat->matiere
                        && ! CoherenceSystemeAcademique::matiereRetenue($resultat->matiere, $classeDuParametre, 'stats resultats/moyenne manuelle')) {
                        continue;
                    }

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


    /**
     * Coefficient de la matière, avec fallback à 1 si aucun coefficient n'est
     * configuré (au lieu de faire échouer tout le rendu du bulletin). Cohérent
     * avec le fallback déjà appliqué dans ESBTPBulletinController::buildBulletinPdf.
     * Un coefficient manquant ne doit jamais empêcher l'impression d'un bulletin.
     */
    public function coefficientOrDefault(int $matiereId, int $classeId, int $anneeUniversitaireId, ?string $periode = null, ?int $etudiantId = null): float
    {
        try {
            return $this->getCoefficientForCombination($matiereId, $classeId, $anneeUniversitaireId, $periode, $etudiantId);
        } catch (CoefficientMissingException $e) {
            \Illuminate\Support\Facades\Log::warning('Coefficient manquant — fallback à 1', [
                'matiere_id' => $matiereId,
                'classe_id' => $classeId,
                'annee_universitaire_id' => $anneeUniversitaireId,
                'periode' => $periode,
            ]);

            return 1.0;
        }
    }

    public function getCoefficientForCombination(int $matiereId, int $classeId, int $anneeUniversitaireId, ?string $periode = null, ?int $etudiantId = null): float
    {
        // Sous-lot α : coefficients désormais par-périodes. Si $periode null, on prend
        // n'importe quelle ligne (fallback semestre1) pour rétrocompat des appelants
        // qui n'ont pas encore intégré la dimension semestre.
        $periode = $periode ? $this->normalizePeriode($periode) : 'semestre1';
        if ($periode === 'annuel') {
            $periode = 'semestre1';
        }

        $cacheKey = $matiereId.'|'.$classeId.'|'.$anneeUniversitaireId.'|'.$periode.'|'.($etudiantId ?? '');

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
            ->where('periode', $periode)
            ->value('coefficient');

        // Fallback : si le palier $periode n'existe pas, regarder l'autre (rétrocompat pré-migration)
        if ($coefficient === null) {
            $altPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
            $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->where('periode', $altPeriode)
                ->value('coefficient');
        }

        // Fallback Tronc Commun (P1-a) : pour un etudiant orienté TC → spécialité, les
        // matières du Semestre 1 portent leur coefficient sur la classe TC (filière TC
        // parente), pas sur la classe de spécialité demandée. On résout la classe S1 via
        // le class-map resolver stateless et on cherche le coefficient là-bas.
        if ($coefficient === null && $etudiantId !== null) {
            $coefficient = $this->resolveTroncCommunCoefficient(
                $matiereId,
                $classeId,
                $anneeUniversitaireId,
                $periode,
                $etudiantId
            );
        }

        if ($coefficient === null) {
            // Typé (sous-classe de RuntimeException → rétrocompat des catch existants)
            // pour permettre à coefficientOrDefault() de le distinguer d'une "Classe invalide".
            throw new CoefficientMissingException('Coefficient manquant pour la matière sélectionnée.');
        }

        $this->coefficientCache[$cacheKey] = (float) $coefficient;

        return $this->coefficientCache[$cacheKey];
    }

    /**
     * Résout le coefficient d'une matière Tronc Commun pour un étudiant orienté
     * (TC → spécialité). Cherche le coefficient sur la classe S1 (TC) si elle diffère
     * de la classe de spécialité demandée. BTS uniquement.
     */
    private function resolveTroncCommunCoefficient(
        int $matiereId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode,
        int $etudiantId
    ): ?float {
        $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
        $resolvedS1ClasseId = $classMap['semestre1_classe_id'] ?? $classeId;

        if (! $resolvedS1ClasseId || (int) $resolvedS1ClasseId === (int) $classeId) {
            return null;
        }

        if (! isset($this->classeCache[$resolvedS1ClasseId])) {
            $this->classeCache[$resolvedS1ClasseId] = ESBTPClasse::find($resolvedS1ClasseId);
        }

        $classeTc = $this->classeCache[$resolvedS1ClasseId];

        if (! $classeTc || ! $classeTc->filiere_id || ! $classeTc->niveau_etude_id) {
            return null;
        }

        $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
            ->where('filiere_id', $classeTc->filiere_id)
            ->where('niveau_etude_id', $classeTc->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $periode)
            ->value('coefficient');

        // Fallback périodique sur la classe TC (rétrocompat pré-migration par-période).
        if ($coefficient === null) {
            $altPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1';
            $coefficient = ESBTPMatiereCoefficient::where('matiere_id', $matiereId)
                ->where('filiere_id', $classeTc->filiere_id)
                ->where('niveau_etude_id', $classeTc->niveau_etude_id)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->where('periode', $altPeriode)
                ->value('coefficient');
        }

        return $coefficient === null ? null : (float) $coefficient;
    }


    public function getAppreciation($moyenne)
    {
        return app(AppreciationScaleService::class)->labelFor(
            $moyenne === null ? null : (float) $moyenne,
            'bts',
            'Insuffisant'
        );
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
        return BulletinMentionResolver::stackedLabels(
            null,
            $noteConduite === null ? null : (float) $noteConduite,
            BulletinMentionResolver::SOURCE_CONDUCT
        );
    }

    public function getMention($moyenne)
    {
        return BulletinMentionResolver::stackedLabels(
            $moyenne === null ? null : (float) $moyenne,
            null,
            BulletinMentionResolver::SOURCE_AVERAGE
        );
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
            return $rawAvg + $this->calculateEffectiveAttendanceNoteForStudent(
                $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $periode
            );
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

<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetEntryStatus;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AcademicNoteCoverageService
{
    public function __construct(
        private readonly AcademicPeriodNormalizer $periods,
        private readonly ExpectedSubjectsResolver $expectedSubjects,
        private readonly \App\Domain\BtsTroncCommun\BtsClassCohortCounter $cohorte,
        private readonly CoverageTeacherContactResolver $contacts,
    ) {}

    public function summarize(
        ?int $yearId,
        string $period,
        ?string $system,
        ?int $classId,
        ?Collection $allowedClassIds = null,
    ): array {
        if ($refus = $this->refusAvantLaClasse($yearId, $classId, $allowedClassIds)) {
            return $refus;
        }

        $classe = ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->find($classId);

        if (! $classe) {
            return $this->empty('Classe introuvable.');
        }

        if ($refus = $this->refusSurLaClasse($classe, $system, $period)) {
            return $refus;
        }

        $attendu = $this->expectedSubjects->forClasse($classe, $period);
        $subjects = $attendu['subjects'];
        $students = $this->activeStudents($yearId, $classId, $period, $classe);
        $evaluations = $this->evaluations($yearId, $period, $classId);
        $entries = $this->resolvedEntries($evaluations->pluck('id'));

        $enseignants = $this->contacts->pourLaClasse($classe, $yearId, $attendu['semestre'], $subjects);

        return $this->buildPayload(
            $classe,
            $subjects,
            $students,
            $evaluations,
            $entries,
            $attendu,
            $enseignants,
            $this->cohortesParSemestre($classId, $yearId, $period, $classe),
        );
    }

    /**
     * Les compteurs du bandeau, a part parce qu'ils se lisent ensemble.
     *
     * @param  Collection<int, ESBTPMatiere>  $subjects
     * @param  Collection<int, array<string, mixed>>  $subjectRows
     * @param  Collection<int, array<string, mixed>>  $catalogSubjectRows
     * @param  array<string, mixed>  $attendu
     * @return array<string, mixed>
     */
    private function resume(
        Collection $subjects,
        Collection $studentIndex,
        Collection $subjectRows,
        Collection $catalogSubjectRows,
        Collection $evaluations,
        Collection $incompleteStudents,
        array $attendu,
    ): array {
        return [
                // Sans cet etat, une classe sans etudiant et une classe
                // entierement notee rendaient le meme « 0 resultat manquant » —
                // et l'ecran annoncait « toutes les notes sont recues » sur une
                // cohorte vide.
                'state' => $this->etatGlobal($subjects, $studentIndex, $catalogSubjectRows, $attendu),
                'subjects_total' => $subjects->count(),
                'subjects_evaluated' => $catalogSubjectRows->where('evaluations_count', '>', 0)->where('treated_count', '>', 0)->count(),
                'orphan_subjects' => $subjectRows->where('is_orphan', true)->count(),
                'evaluations_total' => $evaluations->count(),
                'students_expected' => $studentIndex->count(),
                // Le prevu ne compte QUE le referentiel. Y ajouter les matieres
                // hors referentiel melangeait deux perimetres : le ratio
                // « traite / prevu » pouvait depasser 100 %, ou faire croire
                // qu'il manque des notes sur des matieres qu'on n'attendait pas.
                'expected_results' => (int) $catalogSubjectRows->sum('expected_count'),
                'treated_results' => (int) $catalogSubjectRows->sum('treated_count'),
                'numeric_notes' => (int) $catalogSubjectRows->sum('numeric_count'),
                'missing_results' => (int) $catalogSubjectRows->sum('missing_count'),
                // Ce qui se passe hors referentiel reste visible, mais a part.
                'orphan_expected_results' => (int) $subjectRows->where('is_orphan', true)->sum('expected_count'),
                'orphan_treated_results' => (int) $subjectRows->where('is_orphan', true)->sum('treated_count'),
                'incomplete_students' => $incompleteStudents->count(),
                'actors_count' => $subjectRows->flatMap(fn (array $row) => $row['actor_ids'])->unique()->count(),
        ];
    }

    /**
     * Ce qui empeche de calculer avant meme de connaitre la classe.
     *
     * @return array<string, mixed>|null  Le constat vide, ou null si on peut continuer.
     */
    private function refusAvantLaClasse(?int $yearId, ?int $classId, ?Collection $allowedClassIds): ?array
    {
        if ($yearId === null) {
            return $this->empty('Aucune année universitaire sélectionnée.');
        }

        if ($classId === null) {
            return $this->empty('Sélectionnez une classe pour voir la couverture des notes.');
        }

        if ($allowedClassIds !== null && ! $allowedClassIds->contains($classId)) {
            return $this->empty('Classe hors périmètre.');
        }

        if (! $this->hasRequiredTables()) {
            return $this->empty('Données académiques indisponibles.');
        }

        return null;
    }

    /**
     * Ce qui empeche de calculer une fois la classe connue.
     *
     * @return array<string, mixed>|null  Le constat vide, ou null si on peut continuer.
     */
    private function refusSurLaClasse(ESBTPClasse $classe, ?string $system, string $period): ?array
    {
        if ($system && strtoupper((string) $classe->systeme_academique) !== strtoupper($system)) {
            return $this->empty('La classe ne correspond pas au système sélectionné.');
        }

        // Une periode que le normaliseur ne reconnait pas levait une exception
        // au premier acces aux evaluations, donc une erreur serveur sur le
        // tableau de bord entier. On la refuse ici, proprement, avant toute
        // requete.
        try {
            $this->periods->normalize($period);
        } catch (\InvalidArgumentException) {
            return $this->empty('Période académique non reconnue.');
        }

        return null;
    }

    /**
     * Le meme constat, sans les NOTES ni leurs AUTEURS.
     *
     * Le nom dit ce qui part, et rien de plus : `doublons_probables` survit,
     * avec les noms et les matricules des deux eleves. C'est voulu — le bandeau
     * les affiche, et c'est precisement ce qu'un enseignant doit voir pour
     * comprendre pourquoi une note « manque ». Une version anterieure de cette
     * methode s'appelait « sansLeDetailParEtudiant » et son en-tete promettait
     * qu'aucun nominatif ne survivait : c'etait faux, et le test cense le
     * garder passait sur une donnee d'essai irrealiste (des identifiants nus,
     * alors que la production met toujours nom et matricule).
     *
     * Le payload complet porte, pour CHAQUE evaluation et CHAQUE eleve, la
     * note chiffree, le nom de qui l'a saisie, celui de qui l'a corrigee et
     * les horodatages. Un seul ecran l'affiche : le tableau de bord du
     * pilotage, qui exige `academic_health.view`. Le bandeau de couverture,
     * lui, n'en lit pas une ligne — il compte.
     *
     * Or la route qui sert le bandeau accepte aussi `academic_health.view_own`,
     * pour que l'enseignant qui saisit voie ce qui manque. Il recevait donc,
     * dans une reponse dont son ecran n'affiche rien, les notes de tous les
     * eleves de la classe sur toutes les matieres, y compris celles qu'il
     * n'enseigne pas.
     *
     * Le retrait se fait A LA REPONSE, jamais avant la mise en cache : sinon
     * la premiere lecture par un enseignant servirait une version amputee a
     * tous les suivants.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sansLesNotesNiLeursAuteurs(array $payload): array
    {
        if (isset($payload['subjects']) && is_array($payload['subjects'])) {
            $payload['subjects'] = array_map(static function (array $matiere): array {
                unset(
                    $matiere['evaluations'],
                    $matiere['missing_students'],
                    $matiere['actors'],
                    $matiere['actor_ids'],
                );

                return $matiere;
            }, $payload['subjects']);
        }

        // Vide plutot qu'absent : une cle qui disparait casse un appelant qui
        // la parcourt, une cle vide non.
        if (array_key_exists('incomplete_students', $payload)) {
            $payload['incomplete_students'] = [];
        }

        return $payload;
    }

    /**
     * Les etudiants de la classe pour cette periode.
     *
     * Le filtre par `classe_id` brut se trompait sur le tronc commun : un
     * etudiant y est inscrit au semestre 1 puis passe en specialite au
     * semestre 2, et son inscription ne pointe pas forcement sur la classe
     * qu'on regarde. Des etudiants reellement concernes n'apparaissaient donc
     * jamais comme manquants — la couverture annoncait « tout est note » sur
     * une classe a moitie vide.
     *
     * `BtsClassCohortCounter` est la seule definition correcte, celle qui sert
     * deja a generer les bulletins et a calculer les rangs. Le LMD n'a pas de
     * phases : il garde la lecture directe.
     */
    private function activeStudents(int $yearId, int $classId, string $period, ESBTPClasse $classe): Collection
    {
        $requete = ESBTPInscription::query()
            ->where('annee_universitaire_id', $yearId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree');

        if (strtoupper((string) $classe->systeme_academique) !== 'LMD') {
            $etudiantIds = $this->cohorteIdsPourPeriode($classId, $yearId, $period);

            if ($etudiantIds === []) {
                return collect();
            }

            $requete->whereIn('etudiant_id', $etudiantIds);
        } else {
            $requete->where('classe_id', $classId);
        }

        return $requete
            ->with('etudiant:id,nom,prenoms,matricule')
            ->get(['id', 'etudiant_id', 'classe_id', 'annee_universitaire_id', 'status', 'workflow_step'])
            ->filter(fn (ESBTPInscription $inscription) => $inscription->etudiant !== null)
            ->unique('etudiant_id')
            ->values();
    }

    /**
     * Les identifiants de la cohorte pour cette periode.
     *
     * « Annuel » est le cas qui ne se devine pas. `BtsClassCohortCounter` y
     * rend la cohorte du SEUL semestre 2, et ce choix est deliberement le sien :
     * un bulletin annuel doit avoir un proprietaire unique, sinon un etudiant
     * oriente en cours d'annee en recoit deux. Mais ici on ne genere rien, on
     * COMPTE — et compter la vue annuelle d'une classe de tronc commun avec la
     * cohorte du semestre 2 rendait zero etudiant : l'ecran annoncait « aucun
     * etudiant sur cette periode » sur une classe dont tout le semestre 1 est
     * saisi. L'union est donc juste ici, et elle ne l'est nulle part ailleurs.
     *
     * Elle ne suffit pas seule : un etudiant present au seul semestre 1 serait
     * compte manquant sur les evaluations du semestre 2. C'est ce que
     * `cohortesParSemestre()` redresse, evaluation par evaluation.
     *
     * @return list<int>
     */
    private function cohorteIdsPourPeriode(int $classId, int $yearId, string $period): array
    {
        if ($this->periods->normalize($period) !== 'annuel') {
            return $this->cohorte->etudiantIdsPourPeriode($classId, $yearId, $period);
        }

        return array_values(array_unique(array_merge(
            $this->cohorte->etudiantIdsPourPeriode($classId, $yearId, 'semestre1'),
            $this->cohorte->etudiantIdsPourPeriode($classId, $yearId, 'semestre2'),
        )));
    }

    /**
     * Qui appartenait a la classe a CHAQUE semestre, quand la vue est annuelle.
     *
     * Rend un tableau vide partout ailleurs, et ce vide veut dire « aucune
     * restriction » : hors du cas annuel BTS, la cohorte ne bouge pas d'une
     * evaluation a l'autre.
     *
     * @return array<int, array<int, true>>  numero de semestre => ids en cle
     */
    private function cohortesParSemestre(int $classId, int $yearId, string $period, ESBTPClasse $classe): array
    {
        if (strtoupper((string) $classe->systeme_academique) === 'LMD') {
            return [];
        }

        if ($this->periods->normalize($period) !== 'annuel') {
            return [];
        }

        return [
            1 => array_fill_keys($this->cohorte->etudiantIdsPourPeriode($classId, $yearId, 'semestre1'), true),
            2 => array_fill_keys($this->cohorte->etudiantIdsPourPeriode($classId, $yearId, 'semestre2'), true),
        ];
    }

    private function evaluations(int $yearId, string $period, int $classId): Collection
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $classId)
            ->where('annee_universitaire_id', $yearId)
            ->when(
                $this->periods->normalize($period) !== 'annuel',
                fn ($query) => $query->whereIn('periode', $this->periods->databaseVariants($period)),
            )
            ->when(
                Schema::hasColumn('esbtp_evaluations', 'status'),
                fn ($query) => $query->where(function ($scope): void {
                    $scope->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
                }),
            )
            // Une evaluation qui n'a pas encore eu lieu ne peut pas avoir de
            // notes : `ESBTPNoteController::saisieRapide()` et `store()` la
            // REFUSENT tant que la date est future. La compter revenait a
            // reclamer ce que l'application interdit de saisir — et a passer
            // tout l'effectif en « manquant » des qu'une evaluation etait
            // programmee. Une evaluation sans date reste comptee : l'application
            // ne la bloque pas non plus.
            //
            // L'alignement n'est pas exact, et c'est assume : la saisie compare
            // des instants (`isFuture()`), ce filtre compare des JOURS. Une
            // evaluation prevue aujourd'hui a 14 h est refusee a la saisie a
            // 10 h mais reste comptee. L'ecart tient au jour meme et va dans le
            // bon sens — on compte trop plutot que pas assez.
            ->where(function ($scope): void {
                $scope->whereNull('date_evaluation')
                    ->orWhereDate('date_evaluation', '<=', now());
            })
            ->with([
                'matiere:id,name,code',
                'notes' => fn ($query) => $query
                    ->whereNull('archived_at')
                    ->with(['createdBy:id,name', 'updatedBy:id,name']),
            ])
            ->orderBy('matiere_id')
            ->orderBy('date_evaluation')
            ->get();
    }

    private function resolvedEntries(Collection $evaluationIds): Collection
    {
        if ($evaluationIds->isEmpty() || ! Schema::hasTable('esbtp_grade_sheets') || ! Schema::hasTable('esbtp_grade_sheet_entries')) {
            return collect();
        }

        return DB::table('esbtp_grade_sheet_entries as entries')
            ->join('esbtp_grade_sheets as sheets', 'sheets.id', '=', 'entries.grade_sheet_id')
            ->whereIn('sheets.evaluation_id', $evaluationIds)
            ->whereNull('sheets.deleted_at')
            ->whereIn('entries.status', [
                GradeSheetEntryStatus::ENTERED->value,
                GradeSheetEntryStatus::ABSENT->value,
                GradeSheetEntryStatus::EXEMPT->value,
                GradeSheetEntryStatus::NOT_APPLICABLE->value,
            ])
            ->select([
                'sheets.evaluation_id',
                'entries.etudiant_id',
                'entries.status',
                // `metadata` porte `cohort_active`, que `studentResultStatus()`
                // doit lire pour distinguer un retrait DECIDE par l'ecole d'un
                // simple « n'etait pas encore dans la classe ». Sans cette
                // colonne, la lecture rendait null sans rien signaler : la
                // ligne echouait par `$p->metadata` inexistant sur un stdClass,
                // le `??` avalait le diagnostic, et le garde etait inerte.
                'entries.metadata',
                'entries.entered_by',
                'entries.updated_at',
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->evaluation_id.'-'.(int) $row->etudiant_id);
    }

    /**
     * @param  array<int, array<int, true>>  $cohortesParSemestre  vide = aucune restriction
     */
    private function buildPayload(
        ESBTPClasse $classe,
        Collection $subjects,
        Collection $students,
        Collection $evaluations,
        Collection $entries,
        array $attendu,
        array $enseignants = [],
        array $cohortesParSemestre = [],
    ): array {
        $studentIndex = $this->studentIndex($students);

        // Qui etait attendu SUR CETTE EVALUATION-LA. Sur une vue annuelle, la
        // liste des etudiants est l'union des deux semestres ; sans ce filtre,
        // un etudiant parti apres le semestre 1 serait compte manquant sur
        // chaque evaluation du semestre 2.
        $indexPour = fn (ESBTPEvaluation $evaluation): Collection => $this->indexPourEvaluation(
            $evaluation,
            $studentIndex,
            $cohortesParSemestre,
        );

        $evaluationsBySubject = $evaluations->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id);
        $subjectRows = $subjects->map(fn (ESBTPMatiere $subject): array => $this->subjectRow(
            $subject,
            $evaluationsBySubject->get((int) $subject->id, collect()),
            $indexPour,
            $entries,
            false,
            $enseignants,
        ));

        $orphanRows = $evaluations
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id)
            ->map(fn (Collection $items): array => $this->subjectRow($items->first()->matiere ?? null, $items, $indexPour, $entries, true, $enseignants))
            ->values();

        $subjectRows = $subjectRows->concat($orphanRows)->values();
        $incompleteStudents = $this->incompleteStudents($studentIndex, $subjectRows);
        $catalogSubjectRows = $subjectRows->where('is_orphan', false);

        return [
            'ok' => true,
            'message' => null,
            'classe' => [
                'id' => (int) $classe->id,
                'name' => trim(($classe->code ? "{$classe->code} · " : '').$classe->name),
                'filiere_id' => $classe->filiere_id,
                'niveau_etude_id' => $classe->niveau_etude_id,
            ],
            'maquette' => [
                'renseignee' => (bool) $attendu['maquette_renseignee'],
                'etat' => (string) $attendu['maquette_etat'],
                'semestre' => $attendu['semestre'],
                'systeme' => (string) $attendu['systeme'],
            ],
            'summary' => $this->resume(
                $subjects,
                $studentIndex,
                $subjectRows,
                $catalogSubjectRows,
                $evaluations,
                $incompleteStudents,
                $attendu,
            ),
            'subjects' => $subjectRows->all(),
            'incomplete_students' => $incompleteStudents->values()->all(),
            'doublons_probables' => $this->doublonsProbables($studentIndex),
        ];
    }

    /**
     * L'etat global, celui que l'ecran doit annoncer en une phrase.
     *
     * @param  Collection<int, ESBTPMatiere>  $subjects
     * @param  array<string, mixed>  $attendu
     */
    private function etatGlobal(Collection $subjects, Collection $studentIndex, Collection $catalogRows, array $attendu): string
    {
        if ($subjects->isEmpty()) {
            return $attendu['maquette_renseignee'] ? 'aucune_matiere_ce_semestre' : 'referentiel_absent';
        }

        if ($studentIndex->isEmpty()) {
            return 'cohorte_vide';
        }

        if ((int) $catalogRows->sum('missing_count') > 0) {
            return 'incomplete';
        }

        // Aucune matiere evaluee du tout : ce n'est pas « complet », c'est
        // « rien n'a commence ».
        if ($catalogRows->where('evaluations_count', '>', 0)->isEmpty()) {
            return 'aucune_evaluation';
        }

        return 'complete';
    }

    /**
     * @param  array<int, array<string, mixed>>  $enseignants  matiere_id => contact
     */
    private function subjectRow(?ESBTPMatiere $subject, Collection $evaluations, \Closure $indexPour, Collection $entries, bool $orphan = false, array $enseignants = []): array
    {
        $evaluationRows = $evaluations->map(fn (ESBTPEvaluation $evaluation): array => $this->evaluationRow($evaluation, $indexPour($evaluation), $entries))->values();
        $missingByStudent = [];

        foreach ($evaluationRows as $row) {
            foreach ($row['missing_students'] as $student) {
                $missingByStudent[$student['id']] = $student;
            }
        }

        $actors = $evaluationRows->flatMap(fn (array $row) => $row['actors'])->unique('id')->values();
        $manquants = (int) $evaluationRows->sum('missing_count');

        // Quatre situations que l'ecran doit distinguer, parce qu'elles
        // appellent des gestes differents : relancer un enseignant qui n'a rien
        // rendu, finir une saisie commencee, ne rien faire, ou verifier une
        // matiere qui ne devrait pas etre la.
        $statut = match (true) {
            $orphan => 'hors_maquette',
            $evaluationRows->isEmpty() => 'non_evaluee',
            $manquants > 0 => 'partielle',
            default => 'complete',
        };

        return [
            'id' => $subject?->id,
            'name' => $subject?->name ?? 'Matière hors référentiel',
            'code' => $subject?->code,
            'is_orphan' => $orphan,
            'statut' => $statut,
            // Qui relancer. Vient du planning general, et ne sert QU'A CA :
            // ni les matieres attendues, ni le semestre, ni aucun calcul n'en
            // dependent — le planning n'entre pas dans le denominateur.
            'enseignant' => $subject ? ($enseignants[(int) $subject->id] ?? null) : null,
            'evaluations_count' => $evaluationRows->count(),
            'expected_count' => (int) $evaluationRows->sum('expected_count'),
            'treated_count' => (int) $evaluationRows->sum('treated_count'),
            'numeric_count' => (int) $evaluationRows->sum('numeric_count'),
            'absent_count' => (int) $evaluationRows->sum('absent_count'),
            'non_numeric_resolved_count' => (int) $evaluationRows->sum('non_numeric_resolved_count'),
            'missing_count' => (int) $evaluationRows->sum('missing_count'),
            'actor_ids' => $actors->pluck('id')->all(),
            'actors' => $actors->all(),
            'last_activity_at' => $evaluationRows->pluck('last_activity_at')->filter()->max(),
            'missing_students' => array_values($missingByStudent),
            'evaluations' => $evaluationRows->all(),
        ];
    }

    /**
     * L'index des etudiants attendus sur une evaluation donnee.
     *
     * Hors vue annuelle BTS, `$cohortes` est vide et l'index complet s'applique
     * tel quel. Une periode d'evaluation que le normaliseur ne reconnait pas ne
     * fait rien perdre : on retombe sur l'index complet plutot que de rendre
     * une liste vide, qui compterait toute la classe comme traitee.
     *
     * @param  array<int, array<int, true>>  $cohortes
     */
    private function indexPourEvaluation(ESBTPEvaluation $evaluation, Collection $index, array $cohortes): Collection
    {
        if ($cohortes === []) {
            return $index;
        }

        try {
            $semestre = $this->periods->semesterNumber((string) $evaluation->periode);
        } catch (\InvalidArgumentException) {
            return $index;
        }

        if ($semestre === null || ! isset($cohortes[$semestre])) {
            return $index;
        }

        return $index->filter(fn (array $student): bool => isset($cohortes[$semestre][$student['id']]));
    }

    private function evaluationRow(ESBTPEvaluation $evaluation, Collection $students, Collection $entries): array
    {
        $notes = $evaluation->notes
            ->whereIn('etudiant_id', $students->keys())
            ->keyBy('etudiant_id');

        $rows = $students->map(function (array $student) use ($evaluation, $notes, $entries): array {
            $note = $notes->get($student['id']);
            $entry = $entries->get((int) $evaluation->id.'-'.$student['id'])?->first();
            $status = $this->studentResultStatus($note, $entry);

            return [
                'student' => $student,
                'status' => $status,
                'note' => $note ? $this->noteValue($note) : null,
                'created_by' => $note?->createdBy?->name,
                'updated_by' => $note?->updatedBy?->name,
                'created_at' => optional($note?->created_at)->toIso8601String(),
                'updated_at' => optional($note?->updated_at)->toIso8601String(),
            ];
        });

        $treated = $rows->whereIn('status', ['numeric', 'absent', 'exempt', 'not_applicable']);
        $actors = $this->actorsForNotes($notes);

        return [
            'id' => (int) $evaluation->id,
            'title' => $evaluation->titre ?: ($evaluation->type ?: 'Évaluation'),
            'type' => $evaluation->type,
            'date' => optional($evaluation->date_evaluation)->toDateString(),
            'expected_count' => $students->count(),
            'treated_count' => $treated->count(),
            'numeric_count' => $rows->where('status', 'numeric')->count(),
            'absent_count' => $rows->where('status', 'absent')->count(),
            'non_numeric_resolved_count' => $rows->whereIn('status', ['exempt', 'not_applicable'])->count(),
            'missing_count' => $rows->where('status', 'missing')->count(),
            'actors' => $actors->values()->all(),
            'last_activity_at' => $notes->pluck('updated_at')->filter()->max()?->toIso8601String(),
            'missing_students' => $rows->where('status', 'missing')->pluck('student')->values()->all(),
            'students' => $rows->values()->all(),
        ];
    }

    private function studentResultStatus($note, $entry): string
    {
        if ($note) {
            if ((bool) $note->is_absent) {
                return 'absent';
            }

            return $this->noteValue($note) !== null ? 'numeric' : 'missing';
        }

        // « Dispense » et « n'est plus dans la classe » portent le MEME statut
        // `NOT_APPLICABLE` : `ExpectedGradeSheetEntrySynchronizer::deactivateMissing()`
        // s'en sert pour desactiver l'entree d'un eleve sorti de sa cohorte, et
        // le marque par `metadata.cohort_active = false`.
        //
        // Or les trois definitions de cohorte du depot ne coincident pas : le
        // synchroniseur filtre sur `classe_id` brut, la couverture sur les
        // phases. Un eleve present ICI a donc pu etre desactive LA-BAS. Sans ce
        // test, son entree comptait comme « traitee » et le bandeau pouvait
        // annoncer « toutes les notes sont recues » sur une classe incomplete —
        // le seul sens d'erreur qui fasse generer des bulletins a tort.
        $desactivee = $entry
            && $entry->status === GradeSheetEntryStatus::NOT_APPLICABLE->value
            && ($this->metadonnees($entry)['cohort_active'] ?? null) === false;

        if ($entry && ! $desactivee && in_array($entry->status, [
            GradeSheetEntryStatus::ABSENT->value,
            GradeSheetEntryStatus::EXEMPT->value,
            GradeSheetEntryStatus::NOT_APPLICABLE->value,
        ], true)) {
            return $entry->status;
        }

        if ($entry && $entry->status === GradeSheetEntryStatus::ENTERED->value) {
            return 'numeric';
        }

        return 'missing';
    }

    /**
     * Les metadonnees d'une entree de feuille de notes, en tableau.
     *
     * `resolvedEntries()` interroge la base par `DB::table()`, qui ne passe
     * AUCUN cast : `metadata` revient en chaine JSON, la ou le modele Eloquent
     * l'aurait rendue en tableau. Lire `$entry->metadata['cohort_active']`
     * directement y lisait donc un offset de chaine, et rendait `null` sans
     * lever la moindre erreur — un garde muet, indiscernable d'un garde qui
     * fonctionne. C'est exactement le piege #12 de la discipline de debogage.
     *
     * @return array<string, mixed>
     */
    private function metadonnees(object $entry): array
    {
        $brut = $entry->metadata ?? null;

        if (is_array($brut)) {
            return $brut;
        }

        if (! is_string($brut) || $brut === '') {
            return [];
        }

        $decode = json_decode($brut, true);

        return is_array($decode) ? $decode : [];
    }

    private function noteValue($note): mixed
    {
        return $note->note ?? $note->valeur ?? null;
    }

    private function actorsForNotes(Collection $notes): Collection
    {
        return $notes->flatMap(function ($note): array {
            $actors = [];
            if ($note->created_by) {
                $actors[] = ['id' => (int) $note->created_by, 'name' => $note->createdBy?->name ?? 'Auteur non identifié', 'role' => 'saisie'];
            }
            if ($note->updated_by && $note->updated_at && $note->created_at && $note->updated_at->gt($note->created_at)) {
                $actors[] = ['id' => (int) $note->updated_by, 'name' => $note->updatedBy?->name ?? 'Auteur non identifié', 'role' => 'correction'];
            }

            return $actors;
        })->unique(fn (array $actor): string => $actor['id'].'-'.$actor['role'])->values();
    }

    private function incompleteStudents(Collection $students, Collection $subjects): Collection
    {
        return $students->map(function (array $student) use ($subjects): array {
            $missingSubjects = $subjects
                ->filter(fn (array $subject) => collect($subject['missing_students'])->contains('id', $student['id']))
                ->map(fn (array $subject): array => [
                    'id' => $subject['id'],
                    'name' => $subject['name'],
                    'missing_count' => collect($subject['evaluations'])
                        ->filter(fn (array $evaluation) => collect($evaluation['missing_students'])->contains('id', $student['id']))
                        ->count(),
                ])
                ->values();

            return [
                ...$student,
                'missing_subjects_count' => $missingSubjects->count(),
                'missing_evaluations_count' => (int) $missingSubjects->sum('missing_count'),
                'missing_subjects' => $missingSubjects->all(),
            ];
        })->filter(fn (array $student) => $student['missing_subjects_count'] > 0)->values();
    }

    /**
     * Les paires d'eleves de la classe qui sont probablement la meme personne.
     *
     * Cherchees sur TOUTE la cohorte, pas seulement sur ceux a qui il manque
     * une note. Le doublon s'est fait reperer par un compteur faux, mais ce
     * n'est pas la son pire effet : deux dossiers entierement notes pour la
     * meme personne passent tous les controles et produisent DEUX bulletins,
     * que la cle unique laisse passer puisqu'elle porte l'etudiant.
     *
     * Aucune requete, et la comparaison est celle de `homonymesDansLaClasse()`.
     * Le cout est quadratique en la taille de la classe — quelques milliers de
     * comparaisons de chaines courtes pour une classe de soixante-dix.
     *
     * @return list<array{a: array<string, mixed>, b: array<string, mixed>}>
     */
    private function doublonsProbables(Collection $index): array
    {
        // Le nom comparable est calcule UNE FOIS par eleve. `nomComparable()`
        // passe par `Str::ascii()`, et le laisser dans la comparaison le faisait
        // tourner une fois par PAIRE — quadratique pour un resultat identique.
        $eleves = $index->values()
            ->map(fn (array $eleve): array => $eleve + ['_comparable' => $this->nomComparable((string) $eleve['name'])])
            ->all();
        $paires = [];

        foreach ($eleves as $position => $eleve) {
            // Seulement les SUIVANTS : la relation est symetrique, et la
            // parcourir dans les deux sens rendrait chaque paire deux fois.
            $suivants = new Collection(array_slice($eleves, $position + 1));

            foreach ($this->homonymesDansLaClasse($eleve, $suivants) as $autre) {
                $paires[] = [
                    'a' => [
                        'id' => $eleve['id'],
                        'name' => $eleve['name'],
                        'matricule' => $eleve['matricule'] ?? null,
                    ],
                    // `homonymesDansLaClasse()` ne rend que id / name /
                    // matricule : la cle de travail `_comparable` ne fuit pas
                    // dans la reponse.
                    'b' => $autre,
                ];
            }
        }

        return $paires;
    }

    /**
     * Les autres eleves de la MEME classe dont le nom est quasi identique.
     *
     * Cas fondateur, ESBTP Abidjan, 2BTS GBAT B : le bandeau annoncait
     * « 1 note manquante » sur Pathologie pour KOUASSI AFFOUE GRACE RUCHAMA, et
     * la direction affirmait que tout avait ete saisi. Les deux avaient raison.
     * La classe portait DEUX inscriptions actives :
     *
     *   1443  KOUASSI AFFOUE GRACE          FESBTP23-0322  — note 12,00
     *   1444  KOUASSI AFFOUE GRACE RUCHAMA  FESBTP24-0022  — rien
     *
     * Cinq matieres portaient meme des notes identiques sur les deux dossiers.
     * Le compteur etait juste ; ce qu'il revelait n'etait pas un oubli de
     * saisie, c'etait un doublon. Sans ce rapprochement, l'information se lit
     * comme une accusation et personne ne trouve la cause.
     *
     * Aucune requete : la comparaison se fait sur la cohorte deja chargee. On
     * ne rapproche que dans la classe — un homonyme a l'autre bout de l'ecole
     * n'explique rien et ferait du bruit.
     *
     * @param  array<string, mixed>  $student
     * @param  Collection<int, array<string, mixed>>  $students
     * @return list<array<string, mixed>>
     */
    private function homonymesDansLaClasse(array $student, Collection $students): array
    {
        // `_comparable` est pose par `doublonsProbables()`, qui normalise une
        // fois par eleve. Le repli sert les appels directs — dont les tests.
        $reference = $student['_comparable'] ?? $this->nomComparable((string) $student['name']);

        if ($reference === '') {
            return [];
        }

        return $students
            ->reject(fn (array $autre): bool => (int) $autre['id'] === (int) $student['id'])
            ->filter(function (array $autre) use ($reference): bool {
                $candidat = $autre['_comparable'] ?? $this->nomComparable((string) $autre['name']);

                if ($candidat === '' || $candidat === $reference) {
                    return $candidat !== '';
                }

                // Un prenom supplementaire suffit a creer le doublon : on
                // rapproche donc aussi quand un nom prolonge l'autre, a la
                // frontiere d'un mot. « GRACE » et « GRACES » ne se
                // rapprochent pas ; « GRACE » et « GRACE RUCHAMA », si.
                $court = min(strlen($reference), strlen($candidat));
                // Le PLUS LONG des deux, par la longueur — pas par l'ordre
                // alphabetique, que `>` sur deux chaines aurait compare.
                $long = strlen($reference) >= strlen($candidat) ? $reference : $candidat;
                $prefixe = substr($reference, 0, $court) === substr($candidat, 0, $court);

                return $prefixe && substr($long, $court, 1) === ' ';
            })
            ->map(fn (array $autre): array => [
                'id' => $autre['id'],
                'name' => $autre['name'],
                'matricule' => $autre['matricule'],
            ])
            ->values()
            ->all();
    }

    /**
     * Le nom reduit a ce qui se compare : sans accent, sans casse, sans
     * espaces superflus. « RUCHÂMA » et « RUCHAMA » sont la meme personne.
     */
    private function nomComparable(string $nom): string
    {
        $sansAccent = Str::ascii($nom);
        $majuscules = mb_strtoupper($sansAccent, 'UTF-8');
        $lettresSeules = preg_replace('/[^A-Z0-9 ]/', ' ', $majuscules) ?? '';

        return trim(preg_replace('/\s+/', ' ', $lettresSeules) ?? '');
    }

    private function studentIndex(Collection $inscriptions): Collection
    {
        return $inscriptions->mapWithKeys(fn (ESBTPInscription $inscription): array => [
            (int) $inscription->etudiant_id => [
                'id' => (int) $inscription->etudiant_id,
                'name' => trim($inscription->etudiant->nom.' '.$inscription->etudiant->prenoms),
                'matricule' => $inscription->etudiant->matricule,
            ],
        ]);
    }

    private function hasRequiredTables(): bool
    {
        return Schema::hasTable('esbtp_classes')
            && Schema::hasTable('esbtp_matieres')
            && Schema::hasTable('esbtp_matiere_filiere_niveau')
            && Schema::hasTable('esbtp_inscriptions')
            && Schema::hasTable('esbtp_evaluations')
            && Schema::hasTable('esbtp_notes');
    }

    private function empty(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'classe' => null,
            'maquette' => [
                'renseignee' => false,
                'etat' => \App\Domain\BtsTroncCommun\BtsMaquette::ETAT_AUCUN,
                'semestre' => null,
                'systeme' => null,
            ],
            'summary' => [
                'state' => 'indisponible',
                'subjects_total' => 0,
                'subjects_evaluated' => 0,
                'orphan_subjects' => 0,
                'evaluations_total' => 0,
                'students_expected' => 0,
                'expected_results' => 0,
                'treated_results' => 0,
                'numeric_notes' => 0,
                'missing_results' => 0,
                'orphan_expected_results' => 0,
                'orphan_treated_results' => 0,
                'incomplete_students' => 0,
                'actors_count' => 0,
            ],
            'subjects' => [],
            'incomplete_students' => [],
        ];
    }
}

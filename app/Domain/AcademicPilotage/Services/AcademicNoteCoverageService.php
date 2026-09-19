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

        $classe = ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->find($classId);

        if (! $classe) {
            return $this->empty('Classe introuvable.');
        }

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

        $attendu = $this->expectedSubjects->forClasse($classe, $period);
        $subjects = $attendu['subjects'];
        $students = $this->activeStudents($yearId, $classId, $period, $classe);
        $evaluations = $this->evaluations($yearId, $period, $classId);
        $entries = $this->resolvedEntries($evaluations->pluck('id'));

        $enseignants = $this->contacts->pourLaClasse($classe, $yearId, $attendu['semestre'], $subjects);

        return $this->buildPayload($classe, $subjects, $students, $evaluations, $entries, $attendu, $enseignants);
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
            $etudiantIds = $this->cohorte->etudiantIdsPourPeriode($classId, $yearId, $period);

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
                'entries.entered_by',
                'entries.updated_at',
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->evaluation_id.'-'.(int) $row->etudiant_id);
    }

    private function buildPayload(
        ESBTPClasse $classe,
        Collection $subjects,
        Collection $students,
        Collection $evaluations,
        Collection $entries,
        array $attendu,
        array $enseignants = [],
    ): array {
        $studentIndex = $this->studentIndex($students);
        $evaluationsBySubject = $evaluations->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id);
        $subjectRows = $subjects->map(fn (ESBTPMatiere $subject): array => $this->subjectRow(
            $subject,
            $evaluationsBySubject->get((int) $subject->id, collect()),
            $studentIndex,
            $entries,
            false,
            $enseignants,
        ));

        $orphanRows = $evaluations
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id)
            ->map(fn (Collection $items): array => $this->subjectRow($items->first()->matiere ?? null, $items, $studentIndex, $entries, true, $enseignants))
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
            'summary' => [
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
            ],
            'subjects' => $subjectRows->all(),
            'incomplete_students' => $incompleteStudents->values()->all(),
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
    private function subjectRow(?ESBTPMatiere $subject, Collection $evaluations, Collection $students, Collection $entries, bool $orphan = false, array $enseignants = []): array
    {
        $evaluationRows = $evaluations->map(fn (ESBTPEvaluation $evaluation): array => $this->evaluationRow($evaluation, $students, $entries))->values();
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

        if ($entry && in_array($entry->status, [
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
                // Pourquoi l'ecole jurera que la note a ete saisie : elle l'a
                // ete, sur l'homonyme. Voir `homonymesDansLaClasse()`.
                'homonymes' => $this->homonymesDansLaClasse($student, $students),
            ];
        })->filter(fn (array $student) => $student['missing_subjects_count'] > 0)->values();
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
        $reference = $this->nomComparable((string) $student['name']);

        if ($reference === '') {
            return [];
        }

        return $students
            ->reject(fn (array $autre): bool => (int) $autre['id'] === (int) $student['id'])
            ->filter(function (array $autre) use ($reference): bool {
                $candidat = $this->nomComparable((string) $autre['name']);

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

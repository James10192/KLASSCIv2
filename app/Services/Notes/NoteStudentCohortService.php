<?php

namespace App\Services\Notes;

use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class NoteStudentCohortService
{
    /**
     * Libellé de phase par défaut quand l'étudiant est simplement inscrit dans la
     * classe demandée (pas de parcours tronc commun → spécialité). Les vues
     * masquent le badge de phase quand le label vaut cette constante.
     */
    public const CURRENT_CLASS_LABEL = 'Classe actuelle';

    public function __construct(private readonly BtsPhaseResolver $phaseResolver)
    {
    }

    /**
     * Compte la cohorte éligible d'une classe pour les semestres donnés, sans
     * matérialiser les modèles étudiants (ni eager-load d'accessibilité, ni tri).
     * Utilisé par les boucles de statistiques par classe (assiduité) où seul le
     * dénominateur importe.
     */
    public function countStudentsForClass(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        array $semesters = []
    ): int {
        $requestedSemesters = $this->normalizeSemesters($semesters);
        $eligibleIds = [];

        foreach ($this->candidateInscriptions($classe, $annee, forCounting: true) as $inscription) {
            if (! $inscription->etudiant_id) {
                continue;
            }

            $eligibility = $this->resolveEligibilityForClass(
                $inscription,
                (int) $classe->id,
                $requestedSemesters
            );

            if (! empty($eligibility['semesters'])) {
                $eligibleIds[(int) $inscription->etudiant_id] = true;
            }
        }

        return count($eligibleIds);
    }

    public function studentsForClass(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        array $semesters = []
    ): Collection {
        $requestedSemesters = $this->normalizeSemesters($semesters);
        $inscriptions = $this->candidateInscriptions($classe, $annee);
        $students = collect();

        foreach ($inscriptions as $inscription) {
            if (! $inscription->etudiant) {
                continue;
            }

            $eligibility = $this->resolveEligibilityForClass(
                $inscription,
                (int) $classe->id,
                $requestedSemesters
            );

            if (empty($eligibility['semesters'])) {
                continue;
            }

            $student = $inscription->etudiant;
            $existing = $students->get($student->id);
            if ($existing) {
                $mergedSemesters = array_values(array_unique(array_merge(
                    $existing->getAttribute('notes_eligible_semesters') ?? [],
                    $eligibility['semesters']
                )));
                sort($mergedSemesters);
                $existing->setAttribute('notes_eligible_semesters', $mergedSemesters);
                continue;
            }

            $student->setAttribute('notes_inscription_id', $inscription->id);
            $student->setAttribute('notes_eligible_semesters', $eligibility['semesters']);
            $student->setAttribute('notes_cohort_source', $eligibility['source']);
            $student->setAttribute('notes_phase_label', $eligibility['label']);
            $students->put($student->id, $student);
        }

        $orderedStudents = $students
            ->sortBy(fn (ESBTPEtudiant $student) => mb_strtolower(($student->nom ?? '').' '.($student->prenoms ?? '')))
            ->values();

        return new EloquentCollection($orderedStudents->all());
    }

    public function studentsForEvaluation(ESBTPEvaluation $evaluation): Collection
    {
        $evaluation->loadMissing(['classe', 'anneeUniversitaire']);
        $annee = $evaluation->anneeUniversitaire
            ?: ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $evaluation->classe || ! $annee) {
            return new EloquentCollection();
        }

        return $this->studentsForClass(
            $evaluation->classe,
            $annee,
            [$this->semesterFromEvaluation($evaluation)]
        );
    }

    public function allowedStudentIdsForEvaluation(ESBTPEvaluation $evaluation): Collection
    {
        return $this->studentsForEvaluation($evaluation)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function semesterFromEvaluation(ESBTPEvaluation $evaluation): int
    {
        return $this->normalizeSemester($evaluation->periode ?? null) ?? 1;
    }

    private function candidateInscriptions(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, bool $forCounting = false): Collection
    {
        // Le comptage n'a besoin que des phases (résolution d'éligibilité) : on
        // évite l'eager-load étudiant.accessibilityProfile, inutile pour un count.
        $relations = $forCounting
            ? [
                'classe.filiere',
                'filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionOrigine.filiere',
                'inscriptionOrigine.phases.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ]
            : [
                'etudiant.accessibilityProfile',
                'classe.filiere',
                'filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionOrigine.filiere',
                'inscriptionOrigine.phases.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ];

        return ESBTPInscription::query()
            ->with($relations)
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->where(function ($query) use ($classe) {
                $query->where('classe_id', $classe->id)
                    ->orWhereHas('phases', function ($phaseQuery) use ($classe) {
                        $phaseQuery->where('classe_id', $classe->id);
                    })
                    ->orWhereHas('inscriptionOrigine', function ($originQuery) use ($classe) {
                        $originQuery->where('classe_id', $classe->id);
                    })
                    ->orWhereHas('inscriptionSpecialisation', function ($specialisationQuery) use ($classe) {
                        $specialisationQuery->where('classe_id', $classe->id);
                    });
            })
            ->get();
    }

    private function resolveEligibilityForClass(
        ESBTPInscription $inscription,
        int $classeId,
        array $requestedSemesters
    ): array {
        $journey = $this->phaseResolver->buildJourney($inscription);
        $semesters = [];
        $source = 'direct';
        $label = self::CURRENT_CLASS_LABEL;

        foreach ($journey['timeline'] ?? [] as $phase) {
            if ((int) ($phase['classe_id'] ?? 0) !== $classeId) {
                continue;
            }

            $phaseSemesters = $this->semesterRange(
                (int) ($phase['semestre_debut'] ?? 1),
                $phase['semestre_fin'] ?? null
            );
            $matched = $this->filterSemesters($phaseSemesters, $requestedSemesters);
            if ($matched === []) {
                continue;
            }

            $semesters = array_merge($semesters, $matched);
            $source = $phase['type_phase'] ?? 'phase';
            $label = $phase['label'] ?? 'Parcours';
        }

        if ($semesters === [] && (int) $inscription->classe_id === $classeId) {
            $semesters = $requestedSemesters ?: [1, 2];
        }

        $semesters = array_values(array_unique($semesters));
        sort($semesters);

        return [
            'semesters' => $semesters,
            'source' => $source,
            'label' => $label,
        ];
    }

    private function filterSemesters(array $available, array $requested): array
    {
        if ($requested === []) {
            return $available;
        }

        return array_values(array_intersect($available, $requested));
    }

    private function semesterRange(int $start, mixed $end): array
    {
        $start = max(1, min(2, $start));
        $end = $end === null ? 2 : max(1, min(2, (int) $end));

        return range($start, max($start, $end));
    }

    private function normalizeSemesters(array $semesters): array
    {
        $normalized = [];
        foreach ($semesters as $semester) {
            $value = $this->normalizeSemester($semester);
            if ($value !== null) {
                $normalized[] = $value;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private function normalizeSemester(mixed $semester): ?int
    {
        if ($semester === null || $semester === '') {
            return null;
        }

        $value = is_numeric($semester)
            ? (int) $semester
            : (int) str_replace('semestre', '', strtolower((string) $semester));

        return in_array($value, [1, 2], true) ? $value : null;
    }
}

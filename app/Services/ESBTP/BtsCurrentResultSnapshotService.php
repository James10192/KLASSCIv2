<?php

namespace App\Services\ESBTP;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;

class BtsCurrentResultSnapshotService
{
    public function __construct(
        private BulletinService $bulletinService,
        private BtsAnnualClassMapResolver $classMapResolver
    )
    {
    }

    public function getPeriodeSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $normalizedPeriode = $this->bulletinService->normalizePeriode($periode);

        if ($normalizedPeriode === 'annuel') {
            return $this->buildAnnualSnapshot($etudiantId, $classeId, $anneeUniversitaireId);
        }

        return $this->buildSemesterSnapshot($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);
    }

    public function getSemesterSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        return $this->buildSemesterSnapshot(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $this->bulletinService->normalizePeriode($periode)
        );
    }

    public function getAnnualSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId): array
    {
        return $this->buildAnnualSnapshot($etudiantId, $classeId, $anneeUniversitaireId);
    }

    private function buildSemesterSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $notes = ESBTPNote::query()
            ->where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere'])
            ->whereHas('evaluation', function ($query) use ($anneeUniversitaireId, $classeId, $periode) {
                $query->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('classe_id', $classeId)
                    ->whereIn('periode', $periode === 'semestre1' ? ['semestre1', '1'] : ['semestre2', '2']);
            })
            ->get();

        $manualResultats = ESBTPResultat::query()
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('periode', $periode)
            ->with('matiere')
            ->get();

        $subjects = [];

        foreach ($notes as $note) {
            $matiere = $note->evaluation?->matiere;
            $matiereId = $note->matiere_id ?: $matiere?->id;

            if (! $matiere || ! $matiereId) {
                continue;
            }

            if (! isset($subjects[$matiereId])) {
                $subjects[$matiereId] = [
                    'matiere_id' => $matiereId,
                    'matiere' => $matiere->name,
                    'source' => 'calculee',
                    'coefficient' => null,
                    'moyenne' => null,
                    'notes_count' => 0,
                    'evaluations' => [],
                    'manual_resultat' => null,
                ];
            }

            $value = is_numeric($note->note) ? (float) $note->note : (is_numeric($note->valeur) ? (float) $note->valeur : null);
            $bareme = (float) ($note->evaluation?->bareme ?: 20);
            $evaluationCoefficient = (float) ($note->evaluation?->coefficient ?: 1);

            $subjects[$matiereId]['notes_count']++;
            $subjects[$matiereId]['evaluations'][] = [
                'evaluation_id' => $note->evaluation_id,
                'note_id' => $note->id,
                'note' => $value,
                'bareme' => $bareme,
                'coefficient' => $evaluationCoefficient,
                'normalized_on_20' => $value !== null && $bareme > 0 ? round(($value / $bareme) * 20, 2) : null,
            ];
        }

        foreach ($subjects as $matiereId => $subject) {
            $subjects[$matiereId]['moyenne'] = round($this->bulletinService->computeMoyenneFromNotesData(array_map(
                fn (array $evaluation) => [
                    'note' => $evaluation['note'],
                    'coefficient' => $evaluation['coefficient'],
                    'bareme' => $evaluation['bareme'],
                ],
                $subject['evaluations']
            )), 2);
        }

        foreach ($manualResultats as $resultat) {
            $matiereId = $resultat->matiere_id;
            if (! isset($subjects[$matiereId])) {
                $subjects[$matiereId] = [
                    'matiere_id' => $matiereId,
                    'matiere' => $resultat->matiere?->name ?? 'Matière inconnue',
                    'source' => 'manuelle',
                    'coefficient' => null,
                    'moyenne' => null,
                    'notes_count' => 0,
                    'evaluations' => [],
                    'manual_resultat' => null,
                ];
            }

            $subjects[$matiereId]['source'] = 'manuelle';
            $subjects[$matiereId]['moyenne'] = $resultat->moyenne !== null ? round((float) $resultat->moyenne, 2) : null;
            $subjects[$matiereId]['manual_resultat'] = [
                'resultat_id' => $resultat->id,
                'moyenne' => $resultat->moyenne !== null ? round((float) $resultat->moyenne, 2) : null,
                'coefficient' => $resultat->coefficient !== null ? round((float) $resultat->coefficient, 2) : null,
                'appreciation' => $resultat->appreciation,
            ];
        }

        $weightedPoints = 0.0;
        $weightedCoefficients = 0.0;
        $missingConfiguration = [];

        foreach ($subjects as $matiereId => $subject) {
            $coefficient = $subject['manual_resultat']['coefficient'] ?? null;

            if ($coefficient === null) {
                try {
                    $coefficient = $this->bulletinService->getCoefficientForCombination($matiereId, $classeId, $anneeUniversitaireId);
                } catch (\RuntimeException) {
                    $missingConfiguration[] = "coefficient:matiere:{$matiereId}";
                }
            }

            $subjects[$matiereId]['coefficient'] = $coefficient !== null ? round((float) $coefficient, 2) : null;

            if ($subject['moyenne'] !== null && $coefficient !== null) {
                $weightedPoints += (float) $subject['moyenne'] * (float) $coefficient;
                $weightedCoefficients += (float) $coefficient;
            }
        }

        $coefficientsMissing = false;
        if ($weightedCoefficients > 0) {
            $rawTotal = round($weightedPoints / $weightedCoefficients, 2);
            $state = 'semester_complete';
        } else {
            // Lot 3 fix: fallback moyenne arithmétique simple quand AUCUN coefficient n'est
            // configuré mais des notes existent. Permet à l'index resultats d'afficher quelque
            // chose (moyenne brute) au lieu de "aucune note" + 0. Flag coefficients_missing
            // permet à l'UI de signaler "Coefficients à configurer".
            $matieresWithMoyenne = array_filter(
                $subjects,
                fn ($s) => $s['moyenne'] !== null
            );
            if (! empty($matieresWithMoyenne)) {
                $sumMoyennes = array_sum(array_map(fn ($s) => (float) $s['moyenne'], $matieresWithMoyenne));
                $rawTotal = round($sumMoyennes / count($matieresWithMoyenne), 2);
                $state = 'semester_complete_no_coefficients';
                $coefficientsMissing = true;
            } else {
                $rawTotal = null;
                $state = 'no_data';
            }
        }

        $attendanceNote = round(
            $this->bulletinService->calculateEffectiveAttendanceNoteForStudent(
                $etudiantId,
                $classeId,
                $anneeUniversitaireId,
                $periode
            ),
            2
        );

        return [
            'state' => $state,
            'periode' => $periode,
            'raw_total' => $rawTotal,
            'attendance_note' => $rawTotal !== null ? $attendanceNote : null,
            'effective_total' => $rawTotal !== null ? round($rawTotal + $attendanceNote, 2) : null,
            'subjects' => array_values($subjects),
            'notes_count' => $notes->count(),
            'manual_resultats_count' => $manualResultats->count(),
            'coefficients_missing' => $coefficientsMissing,
            'configuration' => [
                'ready' => empty($missingConfiguration),
                'missing_items' => $missingConfiguration,
            ],
        ];
    }

    private function buildAnnualSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId): array
    {
        $classMap = $this->classMapResolver->resolve($etudiantId, $classeId, $anneeUniversitaireId);
        $semestre1 = $this->buildSemesterSnapshot(
            $etudiantId,
            $classMap['semestre1_classe_id'] ?? $classeId,
            $anneeUniversitaireId,
            'semestre1'
        );
        $semestre2 = $this->buildSemesterSnapshot(
            $etudiantId,
            $classMap['semestre2_classe_id'] ?? $classeId,
            $anneeUniversitaireId,
            'semestre2'
        );
        $weights = $this->bulletinService->getSemesterWeights();

        $annualEffective = $this->bulletinService->calculateAnnualAverage(
            $semestre1['effective_total'],
            $semestre2['effective_total'],
            $weights
        );

        $annualRaw = $this->bulletinService->calculateAnnualAverage(
            $semestre1['raw_total'],
            $semestre2['raw_total'],
            $weights
        );

        $hasSemestre1 = $semestre1['effective_total'] !== null;
        $hasSemestre2 = $semestre2['effective_total'] !== null;
        $primarySemester = $hasSemestre1 ? 'semestre1' : ($hasSemestre2 ? 'semestre2' : null);
        $primarySnapshot = $primarySemester === 'semestre2' ? $semestre2 : $semestre1;

        // Lot 3 : propage le flag coefficients_missing depuis les snapshots semestriels
        $coefficientsMissing = ($semestre1['coefficients_missing'] ?? false)
            || ($semestre2['coefficients_missing'] ?? false);

        if ($annualEffective !== null && $annualRaw !== null) {
            $state = $coefficientsMissing ? 'annual_complete_no_coefficients' : 'annual_complete';
            $rawTotal = round($annualRaw, 2);
            $effectiveTotal = round($annualEffective, 2);
            $attendanceNote = round($effectiveTotal - $rawTotal, 2);
            $subjects = [];
        } elseif ($primarySemester !== null) {
            $state = 'annual_incomplete';
            $rawTotal = $primarySnapshot['raw_total'];
            $effectiveTotal = $primarySnapshot['effective_total'];
            $attendanceNote = $primarySnapshot['attendance_note'];
            $subjects = $primarySnapshot['subjects'];
        } else {
            $state = 'no_data';
            $rawTotal = null;
            $effectiveTotal = null;
            $attendanceNote = null;
            $subjects = [];
        }

        return [
            'state' => $state,
            'periode' => 'annuel',
            'raw_total' => $rawTotal,
            'attendance_note' => $attendanceNote,
            'effective_total' => $effectiveTotal,
            'subjects' => $subjects,
            'notes_count' => ($semestre1['notes_count'] ?? 0) + ($semestre2['notes_count'] ?? 0),
            'manual_resultats_count' => ($semestre1['manual_resultats_count'] ?? 0) + ($semestre2['manual_resultats_count'] ?? 0),
            'coefficients_missing' => $coefficientsMissing,
            'configuration' => [
                'ready' => ($semestre1['configuration']['ready'] ?? false) && ($semestre2['configuration']['ready'] ?? false),
                'missing_items' => array_values(array_unique(array_merge(
                    $semestre1['configuration']['missing_items'] ?? [],
                    $semestre2['configuration']['missing_items'] ?? []
                ))),
            ],
            'primary_semester' => $primarySemester,
            'class_map' => $classMap,
            'semester_snapshots' => [
                'semestre1' => $semestre1,
                'semestre2' => $semestre2,
            ],
        ];
    }
}

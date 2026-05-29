<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Support\Collection;

class BulletinConsistencyService
{
    private const EPSILON = 0.01;

    public function __construct(private BulletinService $bulletinService)
    {
    }

    public function getSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $normalizedPeriode = $this->bulletinService->normalizePeriode($periode);
        $officialBulletin = $this->findOfficialBulletin($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);
        $current = $this->buildCurrentSnapshot($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);

        $officialRaw = $officialBulletin?->moyenne_generale !== null
            ? round((float) $officialBulletin->moyenne_generale, 2)
            : null;
        $officialAttendance = $officialBulletin
            ? round($this->bulletinService->getEffectiveBulletinAttendanceNote($officialBulletin), 2)
            : null;
        $officialEffective = $officialBulletin
            ? round((float) $this->bulletinService->getEffectiveBulletinAverage($officialBulletin), 2)
            : null;

        $differenceReasons = $this->computeDifferenceReasonCodes(
            $officialRaw,
            $current['raw_total'],
            $officialAttendance,
            $current['attendance_note'],
            $officialEffective,
            $current['effective_total']
        );

        $differenceValue = null;
        if ($officialEffective !== null && $current['effective_total'] !== null) {
            $differenceValue = round($current['effective_total'] - $officialEffective, 2);
        }

        $hasDivergence = $officialBulletin !== null && ! empty($differenceReasons);
        $canRegenerate = $officialBulletin !== null && ! empty($current['configuration']['ready']);

        return [
            'official_bulletin_exists' => $officialBulletin !== null,
            'official_bulletin_id' => $officialBulletin?->id,
            'official_moyenne_generale' => $officialRaw,
            'official_note_assiduite' => $officialAttendance,
            'official_effective_total' => $officialEffective,
            'current_recomputed_raw_total' => $current['raw_total'],
            'current_recomputed_effective_total' => $current['effective_total'],
            'current_recomputed_note_assiduite' => $current['attendance_note'],
            'has_divergence' => $hasDivergence,
            'difference_value' => $differenceValue,
            'difference_reason_codes' => $differenceReasons,
            'preview_pdf_uses_official_bulletin' => $officialBulletin !== null,
            'regeneration_required' => $hasDivergence,
            'regeneration_available' => $canRegenerate,
            'status' => $officialBulletin === null
                ? 'no_official_bulletin'
                : ($hasDivergence ? 'official_exists_but_stale' : 'aligned'),
            'user_message' => $this->buildUserMessage($officialBulletin !== null, $hasDivergence),
            'configuration' => $current['configuration'],
            'current_subjects' => $current['subjects'],
            'diagnostic' => [
                'official' => $this->buildOfficialDiagnostic($officialBulletin),
                'current' => $current,
            ],
        ];
    }

    public function getSnapshotsForStudents(iterable $etudiantIds, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $snapshots = [];

        foreach ($etudiantIds as $etudiantId) {
            $snapshots[(int) $etudiantId] = $this->getSnapshot((int) $etudiantId, $classeId, $anneeUniversitaireId, $periode);
        }

        return $snapshots;
    }

    public function regenerateOfficialBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $normalizedPeriode = $this->bulletinService->normalizePeriode($periode);
        $officialBulletin = $this->findOfficialBulletin($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);

        if (! $officialBulletin) {
            throw new \RuntimeException('Aucun bulletin officiel existant à régénérer.');
        }

        $this->bulletinService->genererDonneesBulletin(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $normalizedPeriode
        );

        return $this->getSnapshot($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);
    }

    private function findOfficialBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): ?ESBTPBulletin
    {
        $periodeOptions = [$periode];
        if ($periode === 'semestre1') {
            $periodeOptions[] = '1';
        } elseif ($periode === 'semestre2') {
            $periodeOptions[] = '2';
        }

        return ESBTPBulletin::with(['resultats.matiere'])
            ->where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', array_unique($periodeOptions))
            ->latest('updated_at')
            ->first();
    }

    private function buildCurrentSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
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

        $rawTotal = $weightedCoefficients > 0
            ? round($weightedPoints / $weightedCoefficients, 2)
            : null;
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
            'raw_total' => $rawTotal,
            'attendance_note' => $rawTotal !== null ? $attendanceNote : null,
            'effective_total' => $rawTotal !== null ? round($rawTotal + $attendanceNote, 2) : null,
            'subjects' => array_values($subjects),
            'notes_count' => $notes->count(),
            'manual_resultats_count' => $manualResultats->count(),
            'configuration' => [
                'ready' => empty($missingConfiguration),
                'missing_items' => $missingConfiguration,
            ],
        ];
    }

    private function computeDifferenceReasonCodes(
        ?float $officialRaw,
        ?float $currentRaw,
        ?float $officialAttendance,
        ?float $currentAttendance,
        ?float $officialEffective,
        ?float $currentEffective
    ): array {
        $reasons = [];

        if ($officialRaw !== null && $currentRaw !== null && abs($currentRaw - $officialRaw) >= self::EPSILON) {
            $reasons[] = 'raw_average_changed';
        }

        if ($officialAttendance !== null && $currentAttendance !== null && abs($currentAttendance - $officialAttendance) >= self::EPSILON) {
            $reasons[] = 'attendance_note_changed';
        }

        if ($officialEffective !== null && $currentEffective !== null && abs($currentEffective - $officialEffective) >= self::EPSILON) {
            $reasons[] = 'effective_total_changed';
        }

        return $reasons;
    }

    private function buildOfficialDiagnostic(?ESBTPBulletin $bulletin): ?array
    {
        if (! $bulletin) {
            return null;
        }

        return [
            'bulletin_id' => $bulletin->id,
            'created_at' => optional($bulletin->created_at)->toDateTimeString(),
            'updated_at' => optional($bulletin->updated_at)->toDateTimeString(),
            'moyenne_generale' => $bulletin->moyenne_generale !== null ? round((float) $bulletin->moyenne_generale, 2) : null,
            'note_assiduite' => $bulletin->note_assiduite !== null ? round((float) $bulletin->note_assiduite, 2) : null,
            'effective_total' => $this->bulletinService->getEffectiveBulletinAverage($bulletin) !== null
                ? round((float) $this->bulletinService->getEffectiveBulletinAverage($bulletin), 2)
                : null,
            'effectif_classe' => $bulletin->effectif_classe,
            'subjects' => $bulletin->resultats
                ->map(function ($resultat) {
                    return [
                        'matiere_id' => $resultat->matiere_id,
                        'matiere' => $resultat->matiere?->name ?? 'Matière inconnue',
                        'moyenne' => $resultat->moyenne !== null ? round((float) $resultat->moyenne, 2) : null,
                        'coefficient' => $resultat->coefficient !== null ? round((float) $resultat->coefficient, 2) : null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function buildUserMessage(bool $officialExists, bool $hasDivergence): string
    {
        if (! $officialExists) {
            return 'Aucun bulletin officiel n’existe encore pour cette période. Le PDF sera généré à partir des données courantes.';
        }

        if ($hasDivergence) {
            return 'Les résultats affichés ont changé depuis la génération du bulletin officiel. Le PDF officiel actuel est obsolète et doit être régénéré.';
        }

        return 'Le bulletin officiel existe déjà. L’aperçu PDF et le PDF utiliseront cette version officielle.';
    }
}

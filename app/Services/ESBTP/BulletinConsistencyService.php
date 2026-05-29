<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPBulletin;
use App\Services\BulletinService;

class BulletinConsistencyService
{
    private const EPSILON = 0.01;

    public function __construct(
        private BulletinService $bulletinService,
        private BtsCurrentResultSnapshotService $currentResultSnapshotService
    )
    {
    }

    public function getSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $normalizedPeriode = $this->bulletinService->normalizePeriode($periode);
        $officialBulletin = $normalizedPeriode === 'annuel'
            ? null
            : $this->findOfficialBulletin($etudiantId, $classeId, $anneeUniversitaireId, $normalizedPeriode);
        $current = $this->currentResultSnapshotService->getPeriodeSnapshot(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $normalizedPeriode
        );

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
        $canRegenerate = $officialBulletin !== null && ($current['configuration']['ready'] ?? false);

        return [
            'official_bulletin_exists' => $officialBulletin !== null,
            'official_bulletin_id' => $officialBulletin?->id,
            'official_moyenne_generale' => $officialRaw,
            'official_note_assiduite' => $officialAttendance,
            'official_effective_total' => $officialEffective,
            'current_recomputed_raw_total' => $current['raw_total'],
            'current_recomputed_effective_total' => $current['effective_total'],
            'current_recomputed_note_assiduite' => $current['attendance_note'],
            'current_state' => $current['state'] ?? null,
            'has_divergence' => $hasDivergence,
            'difference_value' => $differenceValue,
            'difference_reason_codes' => $differenceReasons,
            'preview_pdf_uses_official_bulletin' => $officialBulletin !== null,
            'regeneration_required' => $hasDivergence,
            'regeneration_available' => $canRegenerate,
            'status' => $officialBulletin === null
                ? 'no_official_bulletin'
                : ($hasDivergence ? 'official_exists_but_stale' : 'aligned'),
            'user_message' => $this->buildUserMessage($officialBulletin !== null, $hasDivergence, $current['state'] ?? null),
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

    private function buildUserMessage(bool $officialExists, bool $hasDivergence, ?string $currentState): string
    {
        if ($currentState === 'annual_incomplete') {
            return 'Calcul annuel partiel, une seule période BTS est disponible. La note reste affichée avec le statut Partiel.';
        }

        if ($currentState === 'no_data') {
            return 'Aucune note exploitable n’est disponible pour cette période.';
        }

        if (! $officialExists) {
            return 'Aucun bulletin officiel n’existe encore pour cette période. Le PDF sera généré à partir des données courantes.';
        }

        if ($hasDivergence) {
            return 'Les résultats affichés ont changé depuis la génération du bulletin officiel. Le PDF officiel actuel est obsolète et doit être régénéré.';
        }

        return 'Le bulletin officiel existe déjà. L’aperçu PDF et le PDF utiliseront cette version officielle.';
    }
}

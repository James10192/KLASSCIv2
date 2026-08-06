<?php

namespace App\Services\ESBTP;

use App\Domain\AcademicPilotage\Services\AcademicMetricSnapshotInvalidationService;
use App\Models\ESBTPAttendanceManualHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManualAttendanceHoursService
{
    public const PERIODES = ['semestre1', 'semestre2', 'annuel'];

    /** Valeur du paramètre `mode` attendue côté `loadManualTab` pour la saisie globale (sans matière). */
    public const MODE_GLOBAL = 'global';

    public function __construct(
        private readonly AcademicMetricSnapshotInvalidationService $invalidation,
    ) {}

    public function getForEtudiant(int $etudiantId, int $anneeId, string $periode): Collection
    {
        return ESBTPAttendanceManualHours::forEtudiant($etudiantId)
            ->forPeriod($anneeId, $periode)
            ->get()
            ->keyBy('matiere_id');
    }

    public function getForMatiere(int $etudiantId, int $matiereId, int $anneeId, string $periode): ?ESBTPAttendanceManualHours
    {
        return ESBTPAttendanceManualHours::forEtudiant($etudiantId)
            ->where('matiere_id', $matiereId)
            ->forPeriod($anneeId, $periode)
            ->first();
    }

    public function getForClasseMatiere(int $classeId, int $matiereId, int $anneeId, string $periode): Collection
    {
        return ESBTPAttendanceManualHours::forClasse($classeId)
            ->where('matiere_id', $matiereId)
            ->forPeriod($anneeId, $periode)
            ->get()
            ->keyBy('etudiant_id');
    }

    public function preloadForClasse(int $classeId, int $anneeId, string $periode): Collection
    {
        return ESBTPAttendanceManualHours::forClasse($classeId)
            ->forPeriod($anneeId, $periode)
            ->get()
            ->groupBy(fn ($row) => $row->etudiant_id.'_'.$row->matiere_id);
    }

    /**
     * Crée, met à jour ou supprime les lignes manual_hours en batch.
     *
     * `$context['matiere_id']` peut être `null` → saisie globale
     * (une seule ligne par (étudiant, année, période)). La logique de
     * matching du `existing` s'adapte automatiquement via `matchQuery`.
     */
    public function upsertBatch(array $entries, array $context, int $userId): int
    {
        $contexts = [];
        $count = DB::transaction(function () use ($entries, $context, $userId, &$contexts): int {
            $count = 0;
            foreach ($entries as $entry) {
                $count += (int) $this->upsertEntry($entry, $context, $userId, $contexts);
            }

            return $count;
        });

        if ($count > 0) {
            $contexts[] = $context;
            $this->invalidation->invalidateManyAfterCommit(
                $this->uniqueInvalidationContexts($contexts),
                'manual_attendance_hours_batch',
            );
        }

        return $count;
    }

    private function upsertEntry(array $entry, array $context, int $userId, array &$contexts): bool
    {
        // withTrashed() : l'index unique `manual_hours_unique_v2`
        // (etudiant_id, matiere_key, annee, periode) ne tient PAS compte de `deleted_at`.
        // Une ligne soft-deletée occupe donc toujours la clé unique. Sans withTrashed,
        // une re-saisie après un effacement ne verrait pas la ligne soft-deletée et
        // tenterait un create() qui collisionne → « 1062 Duplicate entry » (500).
        // On récupère aussi les lignes soft-deletées pour les restaurer/mettre à jour.
        $existing = $this->matchQuery(
            (int) $entry['etudiant_id'],
            $context['matiere_id'] ?? null,
            (int) $context['annee_universitaire_id'],
            (string) $context['periode']
        )->withTrashed()->first();

        if (! $this->hasValue($entry)) {
            // Rien de vivant à effacer : aucune ligne, ou déjà soft-deletée.
            if (! $existing || $existing->trashed()) {
                return false;
            }

            $original = $existing->getAttributes();
            $existing->update(['updated_by' => $userId]);
            $existing->delete();
            $contexts[] = $original;

            return true;
        }

        $original = $existing?->getAttributes();
        $payload = $this->payload($entry, $context, $userId);

        if ($existing) {
            if ($existing->trashed()) {
                // Ré-activer la ligne soft-deletée au lieu d'un INSERT qui collisionne.
                $existing->restore();
            }
            $existing->update($payload);
        } else {
            ESBTPAttendanceManualHours::create($payload + ['created_by' => $userId]);
        }

        if ($original !== null) {
            $contexts[] = $original;
        }
        $contexts[] = $payload;

        return true;
    }

    private function hasValue(array $entry): bool
    {
        return ($entry['heures_presence'] ?? 0) > 0
            || ($entry['heures_absence_justifiees'] ?? 0) > 0
            || ($entry['heures_absence_non_justifiees'] ?? 0) > 0
            || ! empty($entry['notes']);
    }

    private function payload(array $entry, array $context, int $userId): array
    {
        return [
            'etudiant_id' => $entry['etudiant_id'],
            'matiere_id' => $context['matiere_id'] ?? null,
            'classe_id' => $context['classe_id'],
            'annee_universitaire_id' => $context['annee_universitaire_id'],
            'periode' => $context['periode'],
            'heures_presence' => $entry['heures_presence'] ?? 0,
            'heures_absence_justifiees' => $entry['heures_absence_justifiees'] ?? 0,
            'heures_absence_non_justifiees' => $entry['heures_absence_non_justifiees'] ?? 0,
            'notes' => $entry['notes'] ?? null,
            'updated_by' => $userId,
        ];
    }

    private function matchQuery(int $etudiantId, ?int $matiereId, int $anneeId, string $periode)
    {
        $q = ESBTPAttendanceManualHours::query()
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('periode', $periode);

        return $matiereId === null
            ? $q->whereNull('matiere_id')
            : $q->where('matiere_id', $matiereId);
    }

    public function delete(int $id, int $userId): bool
    {
        $row = ESBTPAttendanceManualHours::find($id);
        if (! $row) {
            return false;
        }
        $row->update(['updated_by' => $userId]);
        $row->delete();
        $this->invalidateContext($row->getAttributes(), 'manual_attendance_hours_delete');

        return true;
    }

    private function invalidateContext(array $context, string $source): void
    {
        $this->invalidation->invalidateManyAfterCommit(
            $this->uniqueInvalidationContexts([$context]),
            $source,
        );
    }

    private function uniqueInvalidationContexts(array $contexts): array
    {
        $unique = [];
        foreach ($contexts as $context) {
            $normalized = [
                'classId' => (int) $context['classe_id'],
                'academicYearId' => (int) $context['annee_universitaire_id'],
                'period' => (string) $context['periode'],
            ];
            $unique[implode(':', $normalized).':class'] = $normalized + ['studentId' => null];

            $studentId = (int) ($context['etudiant_id'] ?? 0);
            if ($studentId > 0) {
                $unique[implode(':', $normalized).':student:'.$studentId] = $normalized + [
                    'studentId' => $studentId,
                ];
            }
        }

        return array_values($unique);
    }

    public static function isValidPeriode(string $periode): bool
    {
        return in_array($periode, self::PERIODES, true);
    }
}

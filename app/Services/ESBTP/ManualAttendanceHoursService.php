<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAttendanceManualHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManualAttendanceHoursService
{
    public const PERIODES = ['semestre1', 'semestre2', 'annuel'];

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
        $count = 0;

        DB::transaction(function () use ($entries, $context, $userId, &$count) {
            foreach ($entries as $entry) {
                $hasValue = ($entry['heures_presence'] ?? 0) > 0
                    || ($entry['heures_absence_justifiees'] ?? 0) > 0
                    || ($entry['heures_absence_non_justifiees'] ?? 0) > 0
                    || !empty($entry['notes']);

                $existing = $this->matchQuery(
                    (int) $entry['etudiant_id'],
                    $context['matiere_id'] ?? null,
                    (int) $context['annee_universitaire_id'],
                    (string) $context['periode']
                )->first();

                if (!$hasValue) {
                    if ($existing) {
                        $existing->update(['updated_by' => $userId]);
                        $existing->delete();
                        $count++;
                    }
                    continue;
                }

                $payload = [
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

                if ($existing) {
                    $existing->update($payload);
                } else {
                    $payload['created_by'] = $userId;
                    ESBTPAttendanceManualHours::create($payload);
                }

                $count++;
            }
        });

        return $count;
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
        if (!$row) {
            return false;
        }
        $row->update(['updated_by' => $userId]);
        $row->delete();
        return true;
    }

    public static function isValidPeriode(string $periode): bool
    {
        return in_array($periode, self::PERIODES, true);
    }
}

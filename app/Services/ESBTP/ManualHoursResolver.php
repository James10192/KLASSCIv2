<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPAttendanceManualHours;
use App\Support\Attendance\ManualHoursSnapshot;

/**
 * Produit un `ManualHoursSnapshot` pour un étudiant × année × période.
 *
 * Extraction SOLID : avant, `ESBTPAbsenceService` et `BulletinService`
 * lisaient le modèle Eloquent directement et ignoraient la distinction
 * per-matière / global. Ajouter un simple `if ($global)` dans chaque
 * service violait OCP (toute nouvelle règle de priorité impacte N sites)
 * et ISP (les deux services n'ont pas les mêmes besoins).
 *
 * Maintenant ils injectent ce resolver et consomment le DTO. La règle
 * de priorité vit côté consommateur mais s'appuie sur une vue unifiée.
 */
class ManualHoursResolver
{
    /**
     * Cache in-process pour éviter de re-requêter les mêmes
     * (étudiant, année, période) lors d'une même requête HTTP.
     * BulletinService appelle à la fois `calculerDetailAbsences` et
     * `calculerAbsencesParMatiere` pour chaque étudiant lors de la
     * génération d'un bulletin de classe — sans ce cache on doublerait
     * les queries.
     */
    private array $cache = [];

    public function snapshot(int $etudiantId, int $anneeId, string $periode): ManualHoursSnapshot
    {
        $key = "{$etudiantId}:{$anneeId}:{$periode}";

        return $this->cache[$key] ??= $this->build($etudiantId, $anneeId, $periode);
    }

    /**
     * Snapshot annuel : agrège les saisies manuelles des deux semestres
     * (et d'une éventuelle ligne 'annuel' directe) en sommant les heures par
     * matière (et pour la ligne globale). Sans cela, un bulletin 'annuel'
     * n'interroge que `periode = 'annuel'` et rate les heures saisies par
     * semestre → 0 h affiché.
     */
    public function annualSnapshot(int $etudiantId, int $anneeId): ManualHoursSnapshot
    {
        $key = "{$etudiantId}:{$anneeId}:annuel:agg";

        return $this->cache[$key] ??= $this->buildAnnual($etudiantId, $anneeId);
    }

    private function build(int $etudiantId, int $anneeId, string $periode): ManualHoursSnapshot
    {
        $rows = ESBTPAttendanceManualHours::query()
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('periode', $periode)
            ->get();

        $global = $rows->firstWhere('matiere_id', null);
        $perMatiere = $rows
            ->filter(fn ($row) => $row->matiere_id !== null)
            ->keyBy('matiere_id');

        return new ManualHoursSnapshot($perMatiere, $global);
    }

    private function buildAnnual(int $etudiantId, int $anneeId): ManualHoursSnapshot
    {
        $rows = ESBTPAttendanceManualHours::query()
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', ['semestre1', 'semestre2', 'annuel'])
            ->with('matiere')
            ->get();

        $perMatiere = $rows
            ->filter(fn ($row) => $row->matiere_id !== null)
            ->groupBy('matiere_id')
            ->map(fn ($group, $matiereId) => $this->mergeGroup($group, (int) $matiereId, $etudiantId, $anneeId));

        $globalGroup = $rows->filter(fn ($row) => $row->matiere_id === null);
        $global = $globalGroup->isEmpty()
            ? null
            : $this->mergeGroup($globalGroup, null, $etudiantId, $anneeId);

        return new ManualHoursSnapshot($perMatiere, $global);
    }

    /**
     * Fusionne un groupe de lignes (même matière, périodes différentes) en une
     * ligne synthétique non persistée dont les heures sont sommées. Une ligne
     * unique est renvoyée telle quelle (aucune synthèse inutile).
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPAttendanceManualHours>  $group
     */
    private function mergeGroup(\Illuminate\Support\Collection $group, ?int $matiereId, int $etudiantId, int $anneeId): ESBTPAttendanceManualHours
    {
        if ($group->count() === 1) {
            return $group->first();
        }

        $first = $group->first();

        $aggregate = new ESBTPAttendanceManualHours([
            'etudiant_id' => $etudiantId,
            'matiere_id' => $matiereId,
            'annee_universitaire_id' => $anneeId,
            'periode' => 'annuel',
            'heures_presence' => (float) $group->sum(fn ($row) => (float) $row->heures_presence),
            'heures_absence_justifiees' => (float) $group->sum(fn ($row) => (float) $row->heures_absence_justifiees),
            'heures_absence_non_justifiees' => (float) $group->sum(fn ($row) => (float) $row->heures_absence_non_justifiees),
            'notes' => $group->pluck('notes')->filter()->unique()->implode(' · '),
        ]);

        // Conserver la relation matière déjà chargée pour éviter une requête N+1
        // lors de la lecture de `optional($row->matiere)->name`.
        if ($matiereId !== null && $first->relationLoaded('matiere')) {
            $aggregate->setRelation('matiere', $first->getRelation('matiere'));
        }

        return $aggregate;
    }
}

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
    public function snapshot(int $etudiantId, int $anneeId, string $periode): ManualHoursSnapshot
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
}

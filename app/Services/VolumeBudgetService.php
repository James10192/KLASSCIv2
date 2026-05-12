<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les volumes horaires réalisés (depuis esbtp_seance_cours)
 * vs planifiés (depuis esbtp_planifications_academiques) par ECUE.
 *
 * Agnostique filière/parcours :
 *   - LMD  → jointure via $classe->parcours_id (→ filiere_id dérivé)
 *   - BTS  → jointure via $classe->filiere_id directement
 */
class VolumeBudgetService
{
    /**
     * Bulk: retourne les budgets pour toutes les ECUEs d'une classe
     * filtrées par niveau et semestre.
     *
     * @return array<int, array{cm: array, td: array, tp: array}>
     *              Indexed by matiere_id (= ecue_id).
     */
    public function forClasse(
        ESBTPClasse $classe,
        int $niveauId,
        int $semestre,
        int $anneeId
    ): array {
        $filiereId  = $this->resolveFiliereId($classe);
        $matiereIds = $this->matiereIdsForNiveauSemestre($filiereId, $niveauId, $semestre);

        if ($matiereIds->isEmpty()) {
            return [];
        }

        $planifies  = $this->loadPlanifies($filiereId, $niveauId, $semestre, $matiereIds->all());
        $realises   = $this->loadRealises($classe->id, $anneeId, $matiereIds->all());

        $result = [];
        foreach ($matiereIds as $matiereId) {
            $p = $planifies[$matiereId] ?? [];
            $r = $realises[$matiereId] ?? [];
            $result[$matiereId] = [
                'cm'  => $this->budget($p['cm']  ?? 0, $r['CM']  ?? 0),
                'td'  => $this->budget($p['td']  ?? 0, $r['TD']  ?? 0),
                'tp'  => $this->budget($p['tp']  ?? 0, $r['TP']  ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Bulk: réalisé vs planifié pour toutes les ECUEs d'une filière/niveau/semestre,
     * agrégé sur TOUTES les classes LMD liées à cette filière.
     * Utilisé par la page planning (maquette pédagogique) qui n'a pas de filtre classe.
     *
     * @return array<int, array{cm: array, td: array, tp: array}>
     */
    public function forFiliere(int $filiereId, int $niveauId, int $semestre, int $anneeId): array
    {
        $matiereIds = $this->matiereIdsForNiveauSemestre($filiereId, $niveauId, $semestre);

        if ($matiereIds->isEmpty()) {
            return [];
        }

        $planifies = $this->loadPlanifies($filiereId, $niveauId, $semestre, $matiereIds->all());
        $realises  = $this->loadRealisesForFiliere($filiereId, $anneeId, $matiereIds->all());

        $result = [];
        foreach ($matiereIds as $matiereId) {
            $p = $planifies[$matiereId] ?? [];
            $r = $realises[$matiereId] ?? [];
            $result[$matiereId] = [
                'cm' => $this->budget($p['cm'] ?? 0, $r['CM'] ?? 0),
                'td' => $this->budget($p['td'] ?? 0, $r['TD'] ?? 0),
                'tp' => $this->budget($p['tp'] ?? 0, $r['TP'] ?? 0),
            ];
        }

        return $result;
    }

    // ------------------------------------------------------------------ //

    private function resolveFiliereId(ESBTPClasse $classe): int
    {
        if ($classe->parcours_id) {
            // LMD: derive filiere from parcours
            return $classe->parcours?->filiere_id ?? $classe->filiere_id;
        }

        return $classe->filiere_id;
    }

    private function matiereIdsForNiveauSemestre(int $filiereId, int $niveauId, int $semestre)
    {
        return ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('semestre', $semestre)
            ->whereNotNull('matiere_id')
            ->pluck('matiere_id')
            ->unique();
    }

    /** Load planned volumes indexed by matiere_id. */
    private function loadPlanifies(int $filiereId, int $niveauId, int $semestre, array $matiereIds): array
    {
        return ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('semestre', $semestre)
            ->whereIn('matiere_id', $matiereIds)
            ->get(['matiere_id', 'volume_horaire_cm', 'volume_horaire_td', 'volume_horaire_tp'])
            ->keyBy('matiere_id')
            ->map(fn ($p) => [
                'cm' => (float) $p->volume_horaire_cm,
                'td' => (float) $p->volume_horaire_td,
                'tp' => (float) $p->volume_horaire_tp,
            ])
            ->all();
    }

    /**
     * Load realized hours from seance_cours using a single aggregate query.
     * Groups by matiere_id and type_seance — no N+1.
     *
     * @return array<int, array<string, float>>  e.g. [42 => ['CM' => 12.5, 'TD' => 8.0]]
     */
    private function loadRealises(int $classeId, int $anneeId, array $matiereIds): array
    {
        $rows = DB::table('esbtp_seance_cours')
            ->select([
                'matiere_id',
                'type_seance',
                DB::raw("SUM((TIME_TO_SEC(heure_fin) - TIME_TO_SEC(heure_debut)) / 3600.0) AS heures"),
            ])
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('matiere_id', $matiereIds)
            ->whereIn('type_seance', ['CM', 'TD', 'TP'])
            ->whereNull('deleted_at')
            ->groupBy('matiere_id', 'type_seance')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->matiere_id][$row->type_seance] = round((float) $row->heures, 2);
        }

        return $result;
    }

    /**
     * Realized hours aggregated across ALL LMD classes whose parcours
     * belongs to $filiereId. One SQL query, no N+1.
     *
     * @return array<int, array<string, float>>
     */
    private function loadRealisesForFiliere(int $filiereId, int $anneeId, array $matiereIds): array
    {
        $classeIds = ESBTPClasse::query()
            ->whereHas('parcours', fn ($q) => $q->where('filiere_id', $filiereId))
            ->pluck('id');

        if ($classeIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('esbtp_seance_cours')
            ->select([
                'matiere_id',
                'type_seance',
                DB::raw("SUM((TIME_TO_SEC(heure_fin) - TIME_TO_SEC(heure_debut)) / 3600.0) AS heures"),
            ])
            ->whereIn('classe_id', $classeIds)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('matiere_id', $matiereIds)
            ->whereIn('type_seance', ['CM', 'TD', 'TP'])
            ->whereNull('deleted_at')
            ->groupBy('matiere_id', 'type_seance')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->matiere_id][$row->type_seance] = round((float) $row->heures, 2);
        }

        return $result;
    }

    /** Build a single budget entry with percentage. */
    private function budget(float $planifie, float $realise): array
    {
        $pct = $planifie > 0 ? min(100, round($realise / $planifie * 100)) : ($realise > 0 ? 100 : 0);

        return [
            'planifie' => $planifie,
            'realise'  => $realise,
            'pct'      => $pct,
        ];
    }
}

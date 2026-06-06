<?php

namespace App\Services\LMD;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;

/**
 * Soft-delete les UE / ECUE / planifications d'un (ou plusieurs) parcours LMD
 * afin qu'un ré-import de maquette puisse repartir propre, sans codes en
 * doublon. Idempotent. Garde-fou : toute UE dont une ECUE porte déjà des
 * évaluations est PROTÉGÉE (jamais supprimée) et rapportée comme "blocked".
 *
 * Ne touche jamais au Domaine / Mention / Parcours / Filière (le ré-import les
 * réutilise via upsert par code).
 */
class LMDCleanupService
{
    /**
     * @param  string[]  $parcoursCodes  codes de parcours (ex: ['TIR','BU'])
     * @return array{dry_run:bool, parcours:array<int,array>, totals:array}
     */
    public function cleanupParcours(array $parcoursCodes, bool $dryRun = true): array
    {
        $perParcours = [];
        $totals = ['ues' => 0, 'ecues' => 0, 'planifs' => 0, 'ues_blocked' => 0];

        foreach ($parcoursCodes as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }

            $result = $dryRun
                ? $this->process($code, commit: false)
                : DB::transaction(fn () => $this->process($code, commit: true));

            $perParcours[] = $result;
            foreach (['ues', 'ecues', 'planifs', 'ues_blocked'] as $k) {
                $totals[$k] += $result[$k] ?? 0;
            }
        }

        return ['dry_run' => $dryRun, 'parcours' => $perParcours, 'totals' => $totals];
    }

    /**
     * Traite un parcours. commit=false => compte seulement (dry-run).
     */
    private function process(string $code, bool $commit): array
    {
        $parcours = ESBTPLMDParcours::where('code', $code)->first();
        if (!$parcours) {
            return ['code' => $code, 'found' => false, 'parcours' => null,
                'ues' => 0, 'ecues' => 0, 'planifs' => 0, 'ues_blocked' => 0, 'blocked' => []];
        }

        // UE rattachées soit par colonne parcours_id, soit via le pivot.
        $directIds = ESBTPUniteEnseignement::where('parcours_id', $parcours->id)->pluck('id');
        $pivotIds = DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $parcours->id)->pluck('unite_enseignement_id');
        $ueIds = $directIds->merge($pivotIds)->unique()->values();
        $ues = ESBTPUniteEnseignement::whereIn('id', $ueIds)->get();

        $uesDeleted = $ecuesDeleted = $planifsDeleted = 0;
        $blocked = [];

        foreach ($ues as $ue) {
            $ecueIds = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->pluck('id')->all();

            $evalCount = $ecueIds ? ESBTPEvaluation::whereIn('matiere_id', $ecueIds)->count() : 0;
            if ($evalCount > 0) {
                $blocked[] = ['ue' => $ue->code, 'name' => $ue->name, 'evaluations' => $evalCount];
                continue;
            }

            $planifsDeleted += $ecueIds
                ? (int) ESBTPPlanificationAcademique::whereIn('matiere_id', $ecueIds)->count()
                : 0;

            if ($commit) {
                if ($ecueIds) {
                    ESBTPPlanificationAcademique::whereIn('matiere_id', $ecueIds)->delete();
                    ESBTPMatiere::whereIn('id', $ecueIds)->delete();
                }
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('parcours_id', $parcours->id)
                    ->where('unite_enseignement_id', $ue->id)
                    ->delete();
                $ue->delete();
            }

            $uesDeleted++;
            $ecuesDeleted += count($ecueIds);
        }

        return [
            'code' => $code,
            'found' => true,
            'parcours' => $parcours->name,
            'ues' => $uesDeleted,
            'ecues' => $ecuesDeleted,
            'planifs' => $planifsDeleted,
            'ues_blocked' => count($blocked),
            'blocked' => $blocked,
        ];
    }
}

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
 *
 * Une unité partagée avec un AUTRE parcours n'est jamais supprimée : le
 * nettoyage se limite alors à retirer le lien du parcours demandé. Le service
 * détachait déjà `esbtp_lmd_parcours_ue` de façon scopée, mais supprimait
 * l'unité et ses éléments constitutifs sans ce scope — il se contredisait
 * lui-même, et le second parcours perdait sa maquette sans être nommé nulle
 * part.
 */
class LMDCleanupService
{
    public function __construct(private SuppressionUeService $suppressionUe) {}

    /**
     * @param  string[]  $parcoursCodes  codes de parcours (ex: ['TIR','BU'])
     * @return array{dry_run:bool, parcours:array<int,array>, totals:array}
     */
    public function cleanupParcours(array $parcoursCodes, bool $dryRun = true): array
    {
        $perParcours = [];
        $totals = ['ues' => 0, 'ecues' => 0, 'planifs' => 0, 'ues_blocked' => 0,
            'ues_partagees' => 0, 'ecues_preserves' => 0];

        foreach ($parcoursCodes as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }

            $result = $dryRun
                ? $this->process($code, commit: false)
                : DB::transaction(fn () => $this->process($code, commit: true));

            $perParcours[] = $result;
            foreach (array_keys($totals) as $k) {
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
                'ues' => 0, 'ecues' => 0, 'planifs' => 0, 'ues_blocked' => 0, 'blocked' => [],
                'ues_partagees' => 0, 'ecues_preserves' => 0, 'partagees' => []];
        }

        // UE rattachées soit par colonne parcours_id, soit via le pivot.
        $directIds = ESBTPUniteEnseignement::where('parcours_id', $parcours->id)->pluck('id');
        $pivotIds = DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $parcours->id)->pluck('unite_enseignement_id');
        $ueIds = $directIds->merge($pivotIds)->unique()->values();
        $ues = ESBTPUniteEnseignement::whereIn('id', $ueIds)->get();

        $uesDeleted = $ecuesDeleted = $planifsDeleted = $ecuesPreserves = 0;
        $blocked = [];
        $partagees = [];

        foreach ($ues as $ue) {
            // Une unité que d'AUTRES parcours utilisent encore ne se supprime
            // pas au nom de celui qu'on nettoie : on ne retire que son lien.
            $autresParcours = array_values(array_filter(
                $this->suppressionUe->parcoursRattaches($ue),
                fn (array $p) => (int) $p['id'] !== (int) $parcours->id
            ));

            if ($autresParcours !== []) {
                if ($commit) {
                    DB::table('esbtp_lmd_parcours_ue')
                        ->where('parcours_id', $parcours->id)
                        ->where('unite_enseignement_id', $ue->id)
                        ->delete();
                }

                $partagees[] = ['ue' => $ue->code, 'name' => $ue->name,
                    'parcours' => array_column($autresParcours, 'libelle')];
                continue;
            }

            $ecueIds = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->pluck('id')->all();

            // Un élément constitutif que le pivot rattache AUSSI à une autre
            // unité est partagé : le supprimer déshabillerait cette unité-là,
            // hors du parcours nettoyé. On le laisse vivre — seul son lien vers
            // l'unité supprimée disparaît, avec les lignes de pivot ci-dessous.
            $ecuesPartages = $ecueIds
                ? DB::table('esbtp_ue_matiere')
                    ->whereIn('matiere_id', $ecueIds)
                    ->where('unite_enseignement_id', '!=', $ue->id)
                    ->pluck('matiere_id')->map('intval')->unique()->all()
                : [];

            if ($ecuesPartages !== []) {
                $ecueIds = array_values(array_diff(array_map('intval', $ecueIds), $ecuesPartages));
                $ecuesPreserves += count($ecuesPartages);
            }

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
                // La suppression de l'unité est douce : la contrainte
                // `cascadeOnDelete` de `esbtp_ue_matiere` ne se déclenche pas.
                // Sans cette ligne, ses liens survivent à l'unité qu'ils
                // désignent.
                DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)->delete();
                // Tous les liens de parcours, pas seulement celui qu'on
                // nettoie : on ne passe ici que si l'unité n'appartient à aucun
                // autre parcours vivant. Ce qui pourrait rester désigne un
                // parcours lui-même supprimé, et n'a plus de sens.
                DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->delete();
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
            'ues_partagees' => count($partagees),
            'ecues_preserves' => $ecuesPreserves,
            'partagees' => $partagees,
        ];
    }
}

<?php

namespace App\Services\LMD;

use Illuminate\Support\Collection;

/**
 * Groupe une collection de matieres LMD (ECUEs) par Unite d'Enseignement
 * avec agregats CM/TD/TP par UE + bucket "Hors UE" pour orphans.
 *
 * Source canonique des matieres : ESBTPPlanificationAcademique (cf rule
 * klassci-classe-matieres.md).
 *
 * Consumers :
 * - ESBTPClasseController::show() tab Suivi heures (classes.show LMD)
 * - ESBTPEmploiTempsController::show() tab Suivi heures (emploi-temps/show)
 *
 * Extrait de la methode privee ESBTPClasseController::buildLmdUesAvecEcues()
 * (commit 359929a8) suite a l'arrivee d'un 2e consumer = rule of three respectee.
 */
class MatiereTreeBuilder
{
    /**
     * @param Collection $lmdMatieres Collection issue de PlanificationAcademique groupee par matiere_id
     *                                avec keys [matiere, cm, td, tp, coefficient, credits_ects, volume_horaire_total, semestres]
     * @param array      $lmdVolumeBudget Keyed by matiere_id avec ['cm' => ['planifie', 'realise'], 'td' => ..., 'tp' => ...]
     * @return Collection<array{ue, is_orphan, code, name, type_ue, type_label, ecues, totaux, total_credits, total_coef, pct_realise, nb_ecues}>
     */
    public function forClasse(Collection $lmdMatieres, array $lmdVolumeBudget): Collection
    {
        return $lmdMatieres
            ->groupBy(fn ($row) => optional($row['matiere']->uniteEnseignement)->id ?? 0)
            ->map(function ($ecues, $ueId) use ($lmdVolumeBudget) {
                $firstMatiere = $ecues->first()['matiere'];
                $ue = $firstMatiere->uniteEnseignement;
                $isOrphan = ! $ue;

                $totaux = ['cm_p' => 0.0, 'cm_r' => 0.0, 'td_p' => 0.0, 'td_r' => 0.0, 'tp_p' => 0.0, 'tp_r' => 0.0];
                $totalCredits = 0;
                $totalCoef = 0;

                $ecuesData = $ecues->map(function ($row) use ($lmdVolumeBudget, &$totaux, &$totalCredits, &$totalCoef) {
                    $matiereId = $row['matiere']->id;
                    $budget = $lmdVolumeBudget[$matiereId] ?? [];
                    $cmR = (float) ($budget['cm']['realise'] ?? 0);
                    $tdR = (float) ($budget['td']['realise'] ?? 0);
                    $tpR = (float) ($budget['tp']['realise'] ?? 0);
                    $cmP = (float) ($row['cm'] ?? 0);
                    $tdP = (float) ($row['td'] ?? 0);
                    $tpP = (float) ($row['tp'] ?? 0);

                    $totaux['cm_p'] += $cmP;
                    $totaux['cm_r'] += $cmR;
                    $totaux['td_p'] += $tdP;
                    $totaux['td_r'] += $tdR;
                    $totaux['tp_p'] += $tpP;
                    $totaux['tp_r'] += $tpR;
                    $totalCredits += (int) ($row['credits_ects'] ?? 0);
                    $totalCoef += (float) ($row['coefficient'] ?? 0);

                    return [
                        'matiere' => $row['matiere'],
                        'cm_p' => $cmP, 'cm_r' => $cmR,
                        'td_p' => $tdP, 'td_r' => $tdR,
                        'tp_p' => $tpP, 'tp_r' => $tpR,
                        'total_p' => $cmP + $tdP + $tpP,
                        'total_r' => $cmR + $tdR + $tpR,
                        'coefficient' => (float) ($row['coefficient'] ?? 0),
                        'credits_ects' => (int) ($row['credits_ects'] ?? 0),
                    ];
                })->values();

                $totalPlanifie = $totaux['cm_p'] + $totaux['td_p'] + $totaux['tp_p'];
                $totalRealise = $totaux['cm_r'] + $totaux['td_r'] + $totaux['tp_r'];
                $pct = $totalPlanifie > 0 ? min(100, round($totalRealise / $totalPlanifie * 100)) : 0;

                return [
                    'ue' => $ue,
                    'is_orphan' => $isOrphan,
                    'code' => $isOrphan ? null : ($ue->code ?? null),
                    'name' => $isOrphan ? 'Hors UE' : ($ue->name ?? 'UE sans nom'),
                    'type_ue' => $isOrphan ? null : ($ue->type_ue ?? null),
                    'type_label' => $isOrphan ? null : (optional($ue->type_ue)->label() ?? null),
                    'ecues' => $ecuesData,
                    'totaux' => $totaux,
                    'total_credits' => $totalCredits,
                    'total_coef' => $totalCoef,
                    'pct_realise' => $pct,
                    'nb_ecues' => $ecuesData->count(),
                ];
            })
            ->sortBy([
                ['is_orphan', 'asc'],
                ['code', 'asc'],
            ])
            ->values();
    }
}

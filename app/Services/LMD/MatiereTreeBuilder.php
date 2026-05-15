<?php

namespace App\Services\LMD;

use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Collection;

/**
 * Groupe une collection de matieres LMD (ECUEs) par Unite d'Enseignement
 * avec agregats CM/TD/TP par UE + bucket "Hors UE" pour orphans.
 *
 * Source canonique :
 * - Pour classe LMD avec parcours : pattern Planning LMD (parcours->unitesEnseignement)
 * - Pour classe LMD sans parcours (tronc commun) : fallback ESBTPPlanificationAcademique
 *   par filiere+niveau (cf rule klassci-classe-matieres.md).
 *
 * Consumers :
 * - ESBTPClasseController::show() tab Suivi heures (classes.show LMD)
 * - ESBTPEmploiTempsController::show() tab Suivi heures (emploi-temps/show)
 */
class MatiereTreeBuilder
{
    /**
     * Charge les matieres LMD pour une classe en privilegiant le pattern Planning LMD
     * (parcours.unitesEnseignement) si parcours existe, sinon fallback filiere+niveau.
     *
     * Retourne la structure $lmdMatieres compatible avec forClasse() :
     * [matiere_id => ['matiere' => ..., 'cm' => X, 'td' => X, 'tp' => X, 'coefficient' => X, 'credits_ects' => X, 'volume_horaire_total' => X, 'semestres' => []]]
     */
    public function loadLmdMatieresForClasse(ESBTPClasse $classe): Collection
    {
        if ($classe->parcours_id && $classe->parcours) {
            return $this->loadFromParcours($classe);
        }

        // Fallback LMD tronc commun (sans parcours) ou cas legacy : utiliser filiere+niveau
        return $this->loadFromFiliereNiveau($classe);
    }

    /**
     * Pattern Planning LMD : charge UEs via parcours -> ECUEs effectifs -> planifications.
     * Strict scope : seulement les ECUEs effectivement attachees au parcours de la classe.
     */
    private function loadFromParcours(ESBTPClasse $classe): Collection
    {
        $parcours = $classe->parcours;

        // 1. Charger les UEs du parcours (via pivot esbtp_lmd_parcours_unites_enseignement)
        $ues = $parcours->unitesEnseignement()
            ->with(['ecues', 'matieres'])
            ->where('esbtp_unites_enseignement.is_active', true)
            ->get();

        if ($ues->isEmpty()) {
            return collect();
        }

        // 2. Recolter les ECUEs effectifs (priorite pivot esbtp_ue_matiere, fallback FK direct)
        $ecuesByMatiereId = collect();
        foreach ($ues as $ue) {
            foreach ($ue->getEcuesEffectifs() as $ecue) {
                if ($ecue && ! $ecuesByMatiereId->has($ecue->id)) {
                    // Force l'association UE -> ECUE pour le grouping par UE
                    $ecue->setRelation('uniteEnseignement', $ue);
                    $ecuesByMatiereId->put($ecue->id, $ecue);
                }
            }
        }

        if ($ecuesByMatiereId->isEmpty()) {
            return collect();
        }

        // 3. Charger les planifications pour ces ECUEs uniquement
        $matiereIds = $ecuesByMatiereId->keys();
        $filiereResolvedId = $parcours->filiere_id ?: $classe->filiere_id;

        $planifs = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $filiereResolvedId)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereIn('matiere_id', $matiereIds)
            ->orderBy('semestre')
            ->get()
            ->groupBy('matiere_id');

        // 4. Construire la structure $lmdMatieres
        return $ecuesByMatiereId->map(function ($ecue) use ($planifs) {
            $planifsEcue = $planifs->get($ecue->id, collect());
            $first = $planifsEcue->first();

            return [
                'matiere' => $ecue,
                'volume_horaire_total' => $planifsEcue->sum('volume_horaire_total'),
                'coefficient' => (float) ($first->coefficient ?? 0),
                'credits_ects' => (int) ($first->credits_ects ?? 0),
                'semestres' => $planifsEcue->pluck('semestre')->unique()->sort()->values()->all(),
                'cm' => $planifsEcue->sum('volume_horaire_cm'),
                'td' => $planifsEcue->sum('volume_horaire_td'),
                'tp' => $planifsEcue->sum('volume_horaire_tp'),
            ];
        })->values();
    }

    /**
     * Fallback LMD tronc commun (sans parcours_id) : charge toutes les planifs filiere+niveau.
     * Comportement legacy avant fix scope parcours (15/05/2026).
     */
    private function loadFromFiliereNiveau(ESBTPClasse $classe): Collection
    {
        return ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereNotNull('matiere_id')
            ->with(['matiere.uniteEnseignement'])
            ->orderBy('semestre')
            ->get()
            ->groupBy('matiere_id')
            ->map(function ($planifs) {
                $first = $planifs->first();
                return [
                    'matiere' => $first->matiere,
                    'volume_horaire_total' => $planifs->sum('volume_horaire_total'),
                    'coefficient' => (float) ($first->coefficient ?? 0),
                    'credits_ects' => (int) ($first->credits_ects ?? 0),
                    'semestres' => $planifs->pluck('semestre')->unique()->sort()->values()->all(),
                    'cm' => $planifs->sum('volume_horaire_cm'),
                    'td' => $planifs->sum('volume_horaire_td'),
                    'tp' => $planifs->sum('volume_horaire_tp'),
                ];
            })
            ->filter(fn ($row) => $row['matiere'] !== null)
            ->values();
    }

    /**
     * Pour une classe LMD : produit la structure $planificationData['matieres_planifiees']
     * attendue par les UIs legacy (composant <x-emploi-temps.planification-section>,
     * seances-cours/create form, etc.) MAIS avec le scope strict parcours.unitesEnseignement
     * (au lieu de la pivot esbtp_matiere_filiere qui est vide pour LMD).
     */
    public function overridePlanificationForLmd(array $planificationData, ESBTPClasse $classe): array
    {
        $lmdMatieres = $this->loadLmdMatieresForClasse($classe);
        if ($lmdMatieres->isEmpty()) {
            return $planificationData;
        }

        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',').'h';
        $matieresPlanifiees = $lmdMatieres->map(function ($row) use ($fmt) {
            $totalPlanifie = (float) ($row['volume_horaire_total'] ?? 0);
            return [
                'matiere' => $row['matiere'],
                'planification_id' => null,
                'volume_horaire_total' => $totalPlanifie,
                'volume_horaire_total_formatted' => $fmt($totalPlanifie),
                'heures_restantes' => $totalPlanifie,
                'heures_restantes_formatted' => $fmt($totalPlanifie),
                'pourcentage_utilise' => 0,
                'enseignant_affiche' => null,
                'enseignants_selectables' => collect(),
            ];
        });

        $totalHeures = (float) $matieresPlanifiees->sum('volume_horaire_total');

        return array_merge($planificationData, [
            'planifications_configurees' => true,
            'matieres_planifiees' => $matieresPlanifiees,
            'heures_totales' => $totalHeures,
            'heures_totales_formatted' => $fmt($totalHeures),
            'heures_restantes' => $totalHeures,
            'heures_restantes_formatted' => $fmt($totalHeures),
            'message_configuration' => null,
        ]);
    }

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

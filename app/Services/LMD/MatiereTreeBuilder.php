<?php

namespace App\Services\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\VolumeBudgetService;
use Illuminate\Support\Collection;

/**
 * MatiereTreeBuilder — Single Source of Truth pour les matières d'une classe.
 *
 * Source canonique :
 * - Pour classe LMD avec parcours : pattern Planning LMD (parcours->unitesEnseignement)
 * - Pour classe LMD sans parcours (tronc commun) : fallback ESBTPPlanificationAcademique
 *   par filiere+niveau (cf rule klassci-classe-matieres.md).
 *
 * ## API publique (rule `lmd-bts-matieres-single-source.md`)
 *
 * - `buildForPlanning($planificationData, $classe)` — sans volumeBudget (heures réalisées)
 *   Pour : bulk-edit, addSession, seances-cours/edit, formulaires planification
 *
 * - `buildWithVolumeBudget($planificationData, $classe, $annee)` — avec volumeBudget
 *   Pour : emploi-temps/show, dashboards KPI réalisation
 *
 * - `loadLmdMatieresForClasse($classe)` — helper bas-niveau pour classes.show tab Suivi heures
 *
 * - `forClasse($lmdMatieres, $lmdVolumeBudget)` — groupage UE → ECUE avec agrégats CM/TD/TP
 *
 * ## Pourquoi 2 méthodes publiques (pas un flag boolean)
 *
 * Decision Critic round 2 (chantier 2026-05) : flag `bool $includeVolumeBudget` = smell
 * d'avenir, un caller oubliera le param tôt ou tard. 2 méthodes distinctes = impossible
 * d'oublier l'intent.
 *
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 * @see memory/feedback_matiere_tree_builder_canonical.md
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
    public function loadLmdMatieresForClasse(ESBTPClasse $classe, ?int $semestre = null): Collection
    {
        if ($classe->parcours_id && $classe->parcours) {
            return $this->loadFromParcours($classe, $semestre);
        }

        // Fallback LMD tronc commun (sans parcours) ou cas legacy : utiliser filiere+niveau
        return $this->loadFromFiliereNiveau($classe, $semestre);
    }

    /**
     * Pattern Planning LMD : charge UEs via parcours -> ECUEs effectifs -> planifications.
     * Strict scope : seulement les ECUEs effectivement attachees au parcours de la classe.
     */
    private function loadFromParcours(ESBTPClasse $classe, ?int $semestre = null): Collection
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
        // PR17.5 : filtrage optionnel par semestre (bulk-edit + show passent $emploi_temp->semestre)
        $matiereIds = $ecuesByMatiereId->keys();
        $filiereResolvedId = $parcours->filiere_id ?: $classe->filiere_id;

        $planifs = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $filiereResolvedId)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereIn('matiere_id', $matiereIds)
            ->when($semestre !== null, fn ($q) => $q->where('semestre', $semestre))
            ->orderBy('semestre')
            ->get()
            ->groupBy('matiere_id');

        // 4. Construire la structure $lmdMatieres
        //    Si semestre fourni : ne garder que les ECUE qui ont au moins une planif a ce semestre.
        return $ecuesByMatiereId
            ->filter(fn ($ecue) => $semestre === null || $planifs->has($ecue->id))
            ->map(function ($ecue) use ($planifs) {
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
                'tpe' => $planifsEcue->sum('volume_horaire_tpe'),
            ];
        })->values();
    }

    /**
     * Fallback LMD tronc commun (sans parcours_id) : charge toutes les planifs filiere+niveau.
     * Comportement legacy avant fix scope parcours (15/05/2026).
     */
    private function loadFromFiliereNiveau(ESBTPClasse $classe, ?int $semestre = null): Collection
    {
        return ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->whereNotNull('matiere_id')
            ->when($semestre !== null, fn ($q) => $q->where('semestre', $semestre))
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
                    'tpe' => $planifs->sum('volume_horaire_tpe'),
                ];
            })
            ->filter(fn ($row) => $row['matiere'] !== null)
            ->values();
    }

    /**
     * Pour une classe LMD : produit la structure $planificationData['matieres_planifiees']
     * SANS volumeBudget (heures réalisées CM/TD/TP).
     *
     * Pour : bulk-edit, addSession, seances-cours/edit, formulaires planification —
     * tous les contextes où on n'a pas besoin de tracker les heures réalisées en live.
     *
     * Si tu veux le volumeBudget (heures réalisées) → utilise `buildWithVolumeBudget()` à la place.
     *
     * @param array $planificationData Structure initiale retournée par getPlanificationDataForClasse()
     * @param ESBTPClasse $classe Classe LMD (filtrée par le caller via systeme_academique === 'LMD')
     * @return array Structure $planificationData mise à jour avec matieres_planifiees LMD
     */
    public function buildForPlanning(array $planificationData, ESBTPClasse $classe, ?int $semestre = null): array
    {
        $lmdMatieres = $this->loadLmdMatieresForClasse($classe, $semestre);
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
     * Pour une classe LMD : produit la structure $planificationData['matieres_planifiees']
     * AVEC volumeBudget (heures réalisées CM/TD/TP via VolumeBudgetService).
     *
     * Pour : emploi-temps/show, dashboards KPI réalisation — tous les contextes où
     * on veut afficher "heures_restantes" calculées à partir des séances réellement
     * tenues (date_seance < now() ET teacher_attendance.status != 'absent').
     *
     * Calcule les semestres applicables au niveau LMD (L1=[1,2], L2=[3,4], L3=[5,6], etc.)
     * et merge les volumes des 2 semestres dans le budget.
     *
     * @param array $planificationData Structure initiale
     * @param ESBTPClasse $classe Classe LMD
     * @param ESBTPAnneeUniversitaire|null $annee Année universitaire (défaut: current)
     * @return array Structure $planificationData avec heures_restantes calculées
     */
    public function buildWithVolumeBudget(
        array $planificationData,
        ESBTPClasse $classe,
        ?ESBTPAnneeUniversitaire $annee = null,
        ?int $semestre = null
    ): array {
        $lmdMatieres = $this->loadLmdMatieresForClasse($classe, $semestre);
        if ($lmdMatieres->isEmpty()) {
            return $planificationData;
        }

        // VolumeBudget par matière (heures réalisées) — via VolumeBudgetService.
        // Boucle sur les 2 semestres LMD du niveau pour merger les volumes.
        $volumeBudget = $this->loadVolumeBudget($classe, $annee);

        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',').'h';
        $matieresPlanifiees = $lmdMatieres->map(function ($row) use ($volumeBudget, $fmt) {
            $mid = $row['matiere']->id;
            $totalPlanifie = (float) ($row['volume_horaire_total'] ?? 0);
            $b = $volumeBudget[$mid] ?? [];
            $totalRealise = (float) (
                ($b['cm']['realise'] ?? 0)
                + ($b['td']['realise'] ?? 0)
                + ($b['tp']['realise'] ?? 0)
            );
            $heuresRestantes = max(0, $totalPlanifie - $totalRealise);
            $pct = $totalPlanifie > 0
                ? min(100, (int) round($totalRealise / $totalPlanifie * 100))
                : 0;

            return [
                'matiere' => $row['matiere'],
                'planification_id' => null,
                'volume_horaire_total' => $totalPlanifie,
                'volume_horaire_total_formatted' => $fmt($totalPlanifie),
                'heures_restantes' => $heuresRestantes,
                'heures_restantes_formatted' => $fmt($heuresRestantes),
                'pourcentage_utilise' => $pct,
                'enseignant_affiche' => null,
                'enseignants_selectables' => collect(),
                // PR17.4 : expose budget CM/TD/TP detail pour planification-section component
                'volume_budget' => [
                    'cm' => [
                        'planifie' => $row['cm'] ?? 0,
                        'realise' => $b['cm']['realise'] ?? 0,
                    ],
                    'td' => [
                        'planifie' => $row['td'] ?? 0,
                        'realise' => $b['td']['realise'] ?? 0,
                    ],
                    'tp' => [
                        'planifie' => $row['tp'] ?? 0,
                        'realise' => $b['tp']['realise'] ?? 0,
                    ],
                ],
            ];
        });

        $totalHeures = (float) $matieresPlanifiees->sum('volume_horaire_total');
        $totalRestant = (float) $matieresPlanifiees->sum('heures_restantes');

        return array_merge($planificationData, [
            'planifications_configurees' => true,
            'matieres_planifiees' => $matieresPlanifiees,
            'heures_totales' => $totalHeures,
            'heures_totales_formatted' => $fmt($totalHeures),
            'heures_restantes' => $totalRestant,
            'heures_restantes_formatted' => $fmt($totalRestant),
            'message_configuration' => null,
        ]);
    }

    /**
     * Charge le volumeBudget (heures réalisées) pour les 2 semestres LMD applicables.
     * Helper privé utilisé par buildWithVolumeBudget().
     *
     * Mapping niveau LMD → semestres :
     * - Licence : L1=[1,2], L2=[3,4], L3=[5,6]
     * - Master  : M1=[7,8], M2=[9,10]
     * - Doctorat: D1=[11,12], D2=[13,14]
     *
     * @return array Keyed by matiere_id, structure {cm,td,tp} => {planifie, realise}
     */
    private function loadVolumeBudget(ESBTPClasse $classe, ?ESBTPAnneeUniversitaire $annee): array
    {
        $volumeBudget = [];
        try {
            $anneeId = $annee?->id ?? optional(ESBTPAnneeUniversitaire::current())->id;
            if (!$anneeId) {
                return $volumeBudget;
            }

            $volumeBudgetService = app(VolumeBudgetService::class);
            $niveauType = optional($classe->niveau)->type ?? '';
            $niveauYear = (int) (optional($classe->niveau)->year ?? 1);

            // Calcul base semestre selon le type de niveau LMD UEMOA
            $baseSem = match ($niveauType) {
                'Licence' => ($niveauYear - 1) * 2,
                'Master' => 6 + ($niveauYear - 1) * 2,
                'Doctorat' => 10 + ($niveauYear - 1) * 2,
                default => 0,
            };

            foreach ([$baseSem + 1, $baseSem + 2] as $sem) {
                $sb = $volumeBudgetService->forClasse(
                    $classe,
                    $classe->niveau_etude_id,
                    $sem,
                    $anneeId
                );
                foreach ($sb as $mid => $b) {
                    if (!isset($volumeBudget[$mid])) {
                        $volumeBudget[$mid] = $b;
                    } else {
                        foreach (['cm', 'td', 'tp'] as $k) {
                            $volumeBudget[$mid][$k]['planifie'] = ($volumeBudget[$mid][$k]['planifie'] ?? 0) + ($b[$k]['planifie'] ?? 0);
                            $volumeBudget[$mid][$k]['realise'] = ($volumeBudget[$mid][$k]['realise'] ?? 0) + ($b[$k]['realise'] ?? 0);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('MatiereTreeBuilder::loadVolumeBudget failed: '.$e->getMessage(), [
                'classe_id' => $classe->id,
            ]);
        }

        return $volumeBudget;
    }

    /**
     * @deprecated since PR1 (2026-05-22) of chantier emploi-temps-lmd-unification.
     *             Use `buildForPlanning()` (sans volumeBudget) ou `buildWithVolumeBudget()` (avec).
     *             Will be removed in a future PR once all callers are migrated.
     *
     * Alias rétrocompat — préserve les usages existants (anti-régression strangler fig).
     */
    public function overridePlanificationForLmd(array $planificationData, ESBTPClasse $classe): array
    {
        return $this->buildForPlanning($planificationData, $classe);
    }

    /**
     * @param Collection $lmdMatieres Collection issue de PlanificationAcademique groupee par matiere_id
     *                                avec keys [matiere, cm, td, tp, coefficient, credits_ects, volume_horaire_total, semestres]
     * @param array      $lmdVolumeBudget Keyed by matiere_id avec ['cm' => ['planifie', 'realise'], 'td' => ..., 'tp' => ...]
     *                                    Source de vérité pour les volumes planifiés (semestre-filtrés au niveau LMD du
     *                                    cours, ex L2 → S3+S4). Garantit la cohérence avec la "Répartition par catégorie".
     * @return Collection<array{ue, is_orphan, code, name, type_ue, type_label, ecues, totaux, total_credits, total_coef, pct_realise, nb_ecues}>
     */
    public function forClasse(Collection $lmdMatieres, array $lmdVolumeBudget): Collection
    {
        // Union des matiere_id : ECUE du parcours (via pivot UE-matiere) + ECUE présents
        // dans les planifs filiere+niveau+semestre (source du $lmdVolumeBudget). Les 2 sources
        // peuvent être disjointes si le pivot esbtp_ue_matiere n'est pas peuplé pour des
        // ECUE qui ont des planifications. Dans ce cas, on charge la matiere depuis la DB
        // pour récupérer son uniteEnseignement et la grouper avec les autres ECUE de la même UE.
        $byMatiereId = $lmdMatieres->keyBy(fn ($row) => $row['matiere']->id);
        $missingIds = collect(array_keys($lmdVolumeBudget))->diff($byMatiereId->keys());
        if ($missingIds->isNotEmpty()) {
            $extras = ESBTPMatiere::with('uniteEnseignement')
                ->whereIn('id', $missingIds->all())
                ->get();
            foreach ($extras as $matiere) {
                $byMatiereId->put($matiere->id, [
                    'matiere' => $matiere,
                    'cm' => 0, 'td' => 0, 'tp' => 0, 'tpe' => 0,
                    'coefficient' => 0, 'credits_ects' => 0,
                    'volume_horaire_total' => 0,
                    'semestres' => [],
                ]);
            }
        }

        return $byMatiereId->values()
            ->groupBy(fn ($row) => optional($row['matiere']->uniteEnseignement)->id ?? 0)
            ->map(function ($ecues, $ueId) use ($lmdVolumeBudget) {
                $firstMatiere = $ecues->first()['matiere'];
                $ue = $firstMatiere->uniteEnseignement;
                $isOrphan = ! $ue;

                // tpe_p : volume theorique TPE alloue par ECUE (lecture seule, jamais realise en seance).
                $totaux = ['cm_p' => 0.0, 'cm_r' => 0.0, 'td_p' => 0.0, 'td_r' => 0.0, 'tp_p' => 0.0, 'tp_r' => 0.0, 'tpe_p' => 0.0];
                $totalCredits = 0;
                $totalCoef = 0;

                $ecuesData = $ecues->map(function ($row) use ($lmdVolumeBudget, &$totaux, &$totalCredits, &$totalCoef) {
                    $matiereId = $row['matiere']->id;
                    $budget = $lmdVolumeBudget[$matiereId] ?? [];
                    $cmR = (float) ($budget['cm']['realise'] ?? 0);
                    $tdR = (float) ($budget['td']['realise'] ?? 0);
                    $tpR = (float) ($budget['tp']['realise'] ?? 0);
                    // Planifié : prioritaire depuis $lmdVolumeBudget (semestre-filtré au niveau LMD)
                    // pour cohérence avec "Répartition par catégorie pédagogique LMD" qui agrège
                    // sur cette même source. Fallback sur $row[k] (sans filtre semestre) si le matiere_id
                    // n'est pas dans $lmdVolumeBudget — ex: ECUE rattachée au parcours via le pivot
                    // mais sans planification configurée pour le semestre courant.
                    $cmP = isset($budget['cm']) ? (float) ($budget['cm']['planifie'] ?? 0) : (float) ($row['cm'] ?? 0);
                    $tdP = isset($budget['td']) ? (float) ($budget['td']['planifie'] ?? 0) : (float) ($row['td'] ?? 0);
                    $tpP = isset($budget['tp']) ? (float) ($budget['tp']['planifie'] ?? 0) : (float) ($row['tp'] ?? 0);
                    $tpeP = (float) ($row['tpe'] ?? 0);

                    $totaux['cm_p'] += $cmP;
                    $totaux['cm_r'] += $cmR;
                    $totaux['td_p'] += $tdP;
                    $totaux['td_r'] += $tdR;
                    $totaux['tp_p'] += $tpP;
                    $totaux['tp_r'] += $tpR;
                    $totaux['tpe_p'] += $tpeP;
                    $totalCredits += (int) ($row['credits_ects'] ?? 0);
                    $totalCoef += (float) ($row['coefficient'] ?? 0);

                    return [
                        'matiere' => $row['matiere'],
                        'cm_p' => $cmP, 'cm_r' => $cmR,
                        'td_p' => $tdP, 'td_r' => $tdR,
                        'tp_p' => $tpP, 'tp_r' => $tpR,
                        'tpe_p' => $tpeP,
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

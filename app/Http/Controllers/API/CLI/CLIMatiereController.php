<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIMatiereController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /api/cli/matieres/planifications
     *
     * Inspecte les planifications académiques (esbtp_planifications_academiques),
     * source canonique des matières d'une classe (filière + niveau + semestre).
     *
     * Sert à diagnostiquer les matières qui apparaissent à tort sur un bulletin :
     * ex. une matière de spécialité planifiée par erreur sur la filière tronc commun.
     *
     * Filtres (tous optionnels, combinables) :
     *  - classe_id        : résout filière + niveau de la classe et liste ses planifs
     *  - filiere_id       : filtre par filière
     *  - niveau_id        : filtre par niveau d'étude
     *  - semestre         : 1 ou 2
     *  - matiere_search   : recherche par nom/code de matière (révèle OÙ elle est planifiée)
     *  - annee_universitaire_id : sinon année courante
     */
    public function planifications(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');
        $search = $request->input('matiere_search');
        $classeContext = null;

        $legacyPivotMatieres = null;
        $legacyMismatchCount = 0;

        if ($classeId = $request->input('classe_id')) {
            $classe = ESBTPClasse::with([
                'filiere:id,name,code,is_tronc_commun',
                'niveauEtude:id,name,code',
                'matieres:id,name,code,unite_enseignement_id',
                'matieres.filieres:id,name,code,is_tronc_commun',
            ])->find((int) $classeId);
            if (! $classe) {
                return $this->errorResponse('Classe introuvable', ['classe_id' => $classeId], 404);
            }
            $filiereId = $classe->filiere_id;
            $niveauId = $classe->niveau_etude_id;
            $classeContext = [
                'id' => $classe->id,
                'name' => $classe->name,
                'filiere' => $classe->filiere?->name,
                'filiere_is_tronc_commun' => (bool) ($classe->filiere?->is_tronc_commun),
                'niveau' => $classe->niveauEtude?->name,
            ];

            // Source legacy : pivot esbtp_classe_matiere (tenants BTS sans
            // planifications académiques). Une matière dont le pivot filieres
            // n'inclut PAS la filière de la classe = matière de spécialité
            // rattachée par erreur (ex: Sécurité sur une classe tronc commun).
            $legacyPivotMatieres = $classe->matieres
                ->filter(fn ($m) => $search === null || $search === '' ? true
                    : (stripos((string) $m->name, (string) $search) !== false
                        || stripos((string) $m->code, (string) $search) !== false))
                ->map(function ($m) use ($classe, &$legacyMismatchCount) {
                    $matiereFiliereIds = $m->filieres->pluck('id')->all();
                    $mismatch = ! empty($matiereFiliereIds)
                        && ! in_array((int) $classe->filiere_id, $matiereFiliereIds, true);
                    if ($mismatch) {
                        $legacyMismatchCount++;
                    }

                    return [
                        'matiere_id' => $m->id,
                        'matiere' => $m->name,
                        'matiere_code' => $m->code,
                        'pivot_coefficient' => $m->pivot->coefficient ?? null,
                        'pivot_is_active' => (bool) ($m->pivot->is_active ?? true),
                        'matiere_is_lmd_ecue' => $m->unite_enseignement_id !== null,
                        'matiere_filieres' => $m->filieres->map(fn ($f) => [
                            'name' => $f->name,
                            'is_tronc_commun' => (bool) $f->is_tronc_commun,
                        ])->all(),
                        'filiere_mismatch' => $mismatch,
                    ];
                })->values()->all();
        }

        $query = ESBTPPlanificationAcademique::query()
            ->with([
                'matiere:id,name,code,unite_enseignement_id',
                'matiere.filieres:id,name,code,is_tronc_commun',
                'filiere:id,name,code,is_tronc_commun',
                'niveauEtude:id,name,code',
            ]);

        if ($request->filled('annee_universitaire_id')) {
            $query->where('annee_universitaire_id', (int) $request->input('annee_universitaire_id'));
        }
        if ($filiereId) {
            $query->where('filiere_id', (int) $filiereId);
        }
        if ($niveauId) {
            $query->where('niveau_etude_id', (int) $niveauId);
        }
        if ($request->filled('semestre')) {
            $query->where('semestre', (int) $request->input('semestre'));
        }
        if ($search !== null && $search !== '') {
            $query->whereHas('matiere', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $planifications = $query->orderBy('semestre')->get();

        $mismatchCount = 0;
        $rows = $planifications->map(function (ESBTPPlanificationAcademique $planif) use (&$mismatchCount) {
            // La planif scope la matière sur (filiere_id, niveau, semestre). Si la
            // matière n'est PAS déclarée pour cette filière dans son pivot filieres,
            // c'est une planification potentiellement mal scopée (matière de spé sur TC).
            $matiereFiliereIds = $planif->matiere?->filieres->pluck('id')->all() ?? [];
            $filiereMismatch = ! empty($matiereFiliereIds)
                && ! in_array((int) $planif->filiere_id, $matiereFiliereIds, true);
            if ($filiereMismatch) {
                $mismatchCount++;
            }

            return [
                'planif_id' => $planif->id,
                'semestre' => $planif->semestre,
                'filiere_id' => $planif->filiere_id,
                'filiere' => $planif->filiere?->name,
                'filiere_is_tronc_commun' => (bool) ($planif->filiere?->is_tronc_commun),
                'niveau' => $planif->niveauEtude?->name,
                'matiere_id' => $planif->matiere_id,
                'matiere' => $planif->matiere?->name,
                'matiere_code' => $planif->matiere?->code,
                'matiere_is_lmd_ecue' => $planif->matiere?->unite_enseignement_id !== null,
                'matiere_filieres' => $planif->matiere?->filieres->map(fn ($f) => [
                    'name' => $f->name,
                    'is_tronc_commun' => (bool) $f->is_tronc_commun,
                ])->all(),
                'filiere_mismatch' => $filiereMismatch,
            ];
        })->values()->all();

        return $this->successResponse([
            'classe_context' => $classeContext,
            'total' => count($rows),
            'filiere_mismatch_count' => $mismatchCount,
            'planifications' => $rows,
            'legacy_pivot' => [
                'source' => 'esbtp_classe_matiere',
                'note' => 'Rempli uniquement pour un classe_id. Source des matières BTS legacy quand la classe n\'a pas de planification académique.',
                'total' => $legacyPivotMatieres === null ? null : count($legacyPivotMatieres),
                'filiere_mismatch_count' => $legacyMismatchCount,
                'matieres' => $legacyPivotMatieres,
            ],
            'explanation' => [
                'filiere_mismatch' => 'La matière est rattachée (planif OU pivot classe) à une filière absente de son pivot filieres. Sur une classe tronc commun, cela signale une matière de spécialité rattachée par erreur au tronc commun (ex: Sécurité sur un bulletin TC).',
                'fix' => 'Detacher la matière de la classe tronc commun (pivot esbtp_classe_matiere) ou de la planification, ou corriger le rattachement filière de la matière.',
            ],
        ], 'Planifications académiques et matières de classe listées');
    }

    /**
     * GET /api/cli/matieres/diagnose-liaisons
     *
     * Diagnostique les liaisons matières pour détecter les incohérences entre :
     *  - La relation `filieres` (pivot esbtp_matiere_filiere)
     *  - La relation `niveaux` (pivot esbtp_matiere_niveau)
     *  - La relation `liaisonsFilieresNiveaux` (pivot esbtp_matiere_filiere_niveau,
     *    source canonique avec combinaison stricte par ligne)
     *
     * Détecte :
     *  - Matières liées à un niveau via niveaux mais pas via liaisonsFilieresNiveaux
     *    (orphelines niveau)
     *  - Matières liées à une filière via filieres mais pas via liaisonsFilieresNiveaux
     *    (orphelines filière)
     *  - Combinaisons "fantômes" qui apparaissent dans whereHas(filieres).whereHas(niveaux)
     *    AND-logic mais pas dans la combinaison canonique du pivot
     */
    public function diagnoseLiaisons(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $matiereCode = $request->input('code');
        $matiereId = $request->input('id');
        $filiereId = $request->input('filiere_id');
        $niveauId = $request->input('niveau_id');

        $query = ESBTPMatiere::with([
            'filieres:id,name,code',
            'niveaux:id,name,code',
            'liaisonsFilieresNiveaux:id,matiere_id,filiere_id,niveau_etude_id',
            'liaisonsFilieresNiveaux.filiere:id,name,code',
            'liaisonsFilieresNiveaux.niveauEtude:id,name,code',
        ])->where('is_active', true);

        if ($matiereCode) {
            $query->where('code', $matiereCode);
        }
        if ($matiereId) {
            $query->where('id', (int) $matiereId);
        }

        $matieres = $query->orderBy('name')->get();

        $details = [];
        $incoherencesCount = 0;

        foreach ($matieres as $matiere) {
            $filiereIds = $matiere->filieres->pluck('id')->all();
            $niveauIds = $matiere->niveaux->pluck('id')->all();
            $combinaisons = $matiere->liaisonsFilieresNiveaux->map(fn ($l) => [
                'filiere_id' => $l->filiere_id,
                'filiere' => $l->filiere?->code ?? '?',
                'niveau_id' => $l->niveau_etude_id,
                'niveau' => $l->niveauEtude?->code ?? '?',
            ])->all();

            // Combinaisons "fantômes" : filière_id ∈ filieres ET niveau_id ∈ niveaux
            // mais (filière_id, niveau_id) PAS dans liaisonsFilieresNiveaux
            $combinaisonsPivot = $matiere->liaisonsFilieresNiveaux
                ->map(fn ($l) => $l->filiere_id . '|' . $l->niveau_etude_id)
                ->all();

            $ghostCombinations = [];
            foreach ($filiereIds as $fId) {
                foreach ($niveauIds as $nId) {
                    $key = $fId . '|' . $nId;
                    if (! in_array($key, $combinaisonsPivot, true)) {
                        $ghostCombinations[] = [
                            'filiere_id' => $fId,
                            'niveau_id' => $nId,
                            'filiere_code' => $matiere->filieres->firstWhere('id', $fId)?->code,
                            'niveau_code' => $matiere->niveaux->firstWhere('id', $nId)?->code,
                        ];
                    }
                }
            }

            $hasIncoherence = count($ghostCombinations) > 0;
            if ($hasIncoherence) {
                $incoherencesCount++;
            }

            $entry = [
                'id' => $matiere->id,
                'name' => $matiere->name,
                'code' => $matiere->code,
                'filieres_count' => count($filiereIds),
                'niveaux_count' => count($niveauIds),
                'combinaisons_canoniques' => $combinaisons,
                'ghost_combinations' => $ghostCombinations,
                'has_incoherence' => $hasIncoherence,
            ];

            // Filtre optionnel par filière/niveau cible
            if ($filiereId && ! in_array((int) $filiereId, $filiereIds, true)) {
                continue;
            }
            if ($niveauId && ! in_array((int) $niveauId, $niveauIds, true)) {
                continue;
            }

            $details[] = $entry;
        }

        return $this->successResponse([
            'total_matieres' => count($details),
            'incoherences_count' => $incoherencesCount,
            'summary' => [
                'matieres_avec_ghosts' => $incoherencesCount,
                'matieres_propres' => count($details) - $incoherencesCount,
            ],
            'details' => $details,
            'explanation' => [
                'ghost_combinations' => 'Combinaisons (filière_id, niveau_id) qui apparaîtraient dans une requête whereHas(filieres).whereHas(niveaux) AND-logic mais qui NE sont PAS dans le pivot canonique esbtp_matiere_filiere_niveau.',
                'fix' => 'Pour éliminer une ghost combination, soit ajoute la ligne au pivot canonique, soit retire la matière de la filière OU du niveau qui crée le ghost.',
            ],
        ], 'Diagnostic des liaisons matières généré');
    }
}

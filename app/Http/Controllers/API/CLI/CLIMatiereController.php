<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPMatiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CLIMatiereController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
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

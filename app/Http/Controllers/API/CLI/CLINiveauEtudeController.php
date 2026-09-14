<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Niveaux d'etudes d'un tenant, en lecture et en creation par lot.
 *
 * Pendant des filieres : ouvrir une ecole demande les deux referentiels, et
 * les saisir a la main imposait un compte superAdmin sur le tenant.
 */
class CLINiveauEtudeController extends BaseApiController
{
    /**
     * GET /api/cli/niveaux/coherence
     *
     * Niveaux LMD dont l'annee contredit le cycle (un Master en annee 1), avec
     * ce qu'une correction toucherait. Lecture seule.
     */
    public function coherence(Request $request, \App\Services\LMD\CoherenceNiveauxLmd $coherence): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $rapport = $coherence->rapport();

        return $this->successResponse($rapport, sprintf(
            '%d niveau(x) LMD incoherent(s) sur %d.',
            count($rapport['incoherents']),
            $rapport['niveaux_lmd']
        ));
    }

    /**
     * POST /api/cli/niveaux/{niveau}/annee — Replace un niveau LMD sur une
     * annee de son cycle. Previsualisation sans `apply: true`.
     *
     * Body: { year: int, apply?: bool }
     */
    public function corrigerAnnee(Request $request, ESBTPNiveauEtude $niveau, \App\Services\LMD\CoherenceNiveauxLmd $coherence): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:1|max:10',
            'apply' => 'nullable|boolean',
        ]);

        $avant = (int) $niveau->year;
        $resultat = $coherence->corrigerAnnee($niveau, (int) $validated['year'], (bool) ($validated['apply'] ?? false));

        if ($resultat['applique']) {
            Log::warning('CLI: annee de niveau LMD corrigee', [
                'niveau_id' => $niveau->id,
                'avant' => $avant,
                'apres' => $resultat['annee_cible'],
                'caller_user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
        }

        $message = match (true) {
            $resultat['refus'] !== [] => 'Correction refusee.',
            $resultat['applique'] => "{$niveau->name} passe en annee {$resultat['annee_cible']}.",
            default => 'Aucune ecriture : previsualisation seulement.',
        };

        return $this->successResponse($resultat, $message);
    }

    /**
     * GET /api/cli/niveaux
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $niveaux = ESBTPNiveauEtude::query()
            ->orderBy('type')
            ->orderBy('year')
            ->get(['id', 'name', 'libelle', 'code', 'type', 'year', 'is_active'])
            ->all();

        return $this->successResponse([
            'total' => count($niveaux),
            'niveaux' => $niveaux,
        ]);
    }

    /**
     * POST /api/cli/niveaux — Creation par lot, idempotente.
     *
     * L'identite d'un niveau est le couple (type, year), pas son libelle :
     * « Premiere Annee BTS » et « 1ere annee BTS » designent le meme niveau et
     * ne doivent pas cohabiter. Le code sert d'appoint quand il est fourni.
     *
     * Body: { niveaux: [{ name, type, year, code?, libelle?, is_active? }], dry_run?: bool }
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'niveaux' => 'required|array|min:1|max:50',
            'niveaux.*.name' => 'required|string|max:255',
            'niveaux.*.type' => 'required|string|max:50',
            'niveaux.*.year' => ['required', 'integer', 'min:1', 'max:10', new \App\Rules\AnneeDuCycleLmd()],
            'niveaux.*.code' => 'nullable|string|max:50',
            'niveaux.*.libelle' => 'nullable|string|max:255',
            'niveaux.*.is_active' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
        ]);

        $couples = array_map(
            static fn (array $n): string => $n['type'].'#'.$n['year'],
            $validated['niveaux']
        );

        if (count($couples) !== count(array_unique($couples))) {
            return $this->errorResponse(
                'Le lot contient deux fois le meme couple type + annee.',
                [],
                422
            );
        }

        $plan = [];
        foreach ($validated['niveaux'] as $n) {
            $existant = ESBTPNiveauEtude::where('type', $n['type'])
                ->where('year', $n['year'])
                ->first();

            $plan[] = [
                'type' => $n['type'],
                'year' => $n['year'],
                'name' => trim($n['name']),
                'action' => $existant ? 'mise a jour' : 'creation',
                'nom_actuel' => $existant?->name,
                'id_existant' => $existant?->id,
            ];
        }

        if ((bool) ($validated['dry_run'] ?? false)) {
            return $this->successResponse([
                'dry_run' => true,
                'plan' => $plan,
            ], 'Aucune ecriture : previsualisation seulement.');
        }

        $crees = 0;
        $misAJour = 0;

        DB::transaction(function () use ($validated, &$crees, &$misAJour) {
            foreach ($validated['niveaux'] as $n) {
                $niveau = ESBTPNiveauEtude::where('type', $n['type'])
                    ->where('year', $n['year'])
                    ->first();

                $donnees = [
                    'name' => trim($n['name']),
                    'libelle' => $n['libelle'] ?? trim($n['name']),
                    'type' => $n['type'],
                    'year' => (int) $n['year'],
                    'is_active' => $n['is_active'] ?? true,
                ];

                // Le code n'ecrase pas l'existant quand il n'est pas fourni :
                // un import partiel ne doit pas vider une colonne deja remplie.
                if (! empty($n['code'])) {
                    $donnees['code'] = $n['code'];
                }

                if ($niveau) {
                    $niveau->update($donnees);
                    $misAJour++;
                } else {
                    ESBTPNiveauEtude::create($donnees);
                    $crees++;
                }
            }
        });

        Log::info('CLI: niveaux importes', [
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'crees' => $crees,
            'mis_a_jour' => $misAJour,
            'total' => $crees + $misAJour,
        ], "{$crees} niveau(x) cree(s), {$misAJour} mis a jour.");
    }
}

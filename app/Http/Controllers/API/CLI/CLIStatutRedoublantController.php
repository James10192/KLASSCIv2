<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Inscriptions\StatutRedoublant;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Le statut redoublant, à distance : ce qu'il vaut, ce qu'il reste à
 * confirmer, le recensement de toutes les années, et la confirmation d'une
 * inscription.
 */
class CLIStatutRedoublantController extends BaseApiController
{
    public function __construct(private readonly StatutRedoublant $statut)
    {
    }

    /** Le recensement à blanc : ce qui serait posé, sans rien écrire. */
    public function etat(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $bilan = $this->statut->recenser(false);
        $bilan['a_confirmer_par_annee'] = StatutRedoublant::contraindreAConfirmer(ESBTPInscription::query())
            ->join('esbtp_annee_universitaires as a', 'a.id', '=', 'esbtp_inscriptions.annee_universitaire_id')
            ->groupBy('a.name')
            ->selectRaw('a.name as annee, count(*) as nombre')
            ->pluck('nombre', 'annee');

        return $this->successResponse($bilan, sprintf(
            '%d inscription(s) examinée(s), %d statut(s) à poser, %d établi(s) par une personne.',
            $bilan['examinees'], $bilan['a_poser'], $bilan['etablies']
        ));
    }

    /** Pose la valeur déduite partout où personne n'a tranché. */
    public function recenser(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate(['apply' => ['nullable', 'boolean']]);
        $ecrire = (bool) ($valide['apply'] ?? false);
        $bilan = $this->statut->recenser($ecrire);

        return $this->successResponse($bilan, $ecrire
            ? sprintf('%d statut(s) posé(s), %d décision(s) reprise(s).', $bilan['a_poser'], $bilan['decisions'])
            : sprintf('À blanc : %d statut(s) seraient posés. Relancez avec apply=1.', $bilan['a_poser']));
    }

    /** Confirme ou corrige le statut d'une inscription (motif si la valeur change). */
    public function etablir(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:write')) {
            return $this->errorResponse('Token missing cli:write ability', [], 403);
        }

        $donnees = $request->validate([
            'valeur' => ['required', 'boolean'],
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $inscription = ESBTPInscription::with('anneeUniversitaire')->find($id);
        if (! $inscription) {
            return $this->errorResponse('Inscription introuvable.', [], 404);
        }

        try {
            $this->statut->etablir($inscription, $request->user(), (bool) $donnees['valeur'], $donnees['motif'] ?? null);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        }

        return $this->successResponse(
            $this->statut->pourAffichage($inscription->fresh(['redoublantConfirmePar'])),
            'Statut redoublant enregistré.'
        );
    }
}

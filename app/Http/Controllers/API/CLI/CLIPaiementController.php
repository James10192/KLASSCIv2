<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Comptabilite\Paiements\Actions\RestaurerPaiement;
use App\Exceptions\AvoirForbiddenException;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPPaiement;
use App\Services\AvoirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Annuler ou restaurer un versement à distance.
 *
 * Annuler n'est PAS supprimer. Le versement reste dans la liste, et un avoir
 * validé, numéroté et motivé le compense : c'est la contre-écriture comptable,
 * la même que le bouton « Avoir » de la fiche du paiement. Ce que devient
 * l'argent — crédit gardé par l'école ou remboursement sorti de la caisse —
 * ne se devine pas : l'appelant le dit.
 *
 * Montre par défaut, n'écrit que sur `apply`.
 */
class CLIPaiementController extends BaseApiController
{
    public function annuler(Request $request, int $id, AvoirService $avoirs): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'avoir_kind' => ['required', Rule::in([AvoirService::KIND_CREDIT, AvoirService::KIND_REFUND])],
            'motif' => ['required', 'string', 'min:10', 'max:500'],
            'apply' => ['nullable', 'boolean'],
        ]);

        $paiement = ESBTPPaiement::with('etudiant:id,matricule,nom,prenoms')->find($id);
        if (! $paiement) {
            return $this->errorResponse("Versement #{$id} introuvable (ou supprimé : restaurez-le d'abord).", [], 404);
        }

        $montant = $avoirs->availableAmount($paiement);
        $apercu = [
            'paiement' => $this->resume($paiement),
            'montant_a_annuler' => $montant,
            'avoir_kind' => $valide['avoir_kind'],
            'applique' => false,
        ];

        if ($montant <= 0) {
            return $this->errorResponse('Ce versement est déjà entièrement annulé par des avoirs.', $apercu, 422);
        }

        if (! ($valide['apply'] ?? false)) {
            return $this->successResponse($apercu, sprintf(
                "Le versement %s (%s FCFA) serait annulé par un avoir de %s FCFA. Rien n'a été écrit.",
                $paiement->numero_recu,
                number_format((float) $paiement->montant, 0, ',', ' '),
                number_format($montant, 0, ',', ' ')
            ));
        }

        try {
            $avoir = $avoirs->issue($paiement, $montant, $valide['avoir_kind'], $valide['motif'], (int) $request->user()->id);
        } catch (AvoirForbiddenException $e) {
            return $this->errorResponse($e->getMessage(), $apercu, 422);
        }

        return $this->successResponse(array_merge($apercu, [
            'applique' => true,
            'avoir' => ['id' => $avoir->id, 'numero' => $avoir->numero_avoir, 'montant' => (float) $avoir->montant],
        ]), "Versement {$paiement->numero_recu} annulé par l'avoir {$avoir->numero_avoir}.");
    }

    public function restaurer(Request $request, int $id, RestaurerPaiement $restauration): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $paiement = ESBTPPaiement::onlyTrashed()->with('etudiant:id,matricule,nom,prenoms')->find($id);
        if (! $paiement) {
            return $this->errorResponse("Aucun versement supprimé #{$id}.", [], 404);
        }

        $apercu = [
            'paiement' => $this->resume($paiement) + [
                'supprime_le' => $paiement->deleted_at?->toIso8601String(),
                'motif_suppression' => $paiement->motif_suppression,
            ],
            'applique' => false,
        ];

        if (! $request->boolean('apply')) {
            return $this->successResponse($apercu, "Le versement {$paiement->numero_recu} serait restauré. Rien n'a été écrit.");
        }

        try {
            $cascade = $restauration->execute($paiement, (int) $request->user()->id);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), $apercu, 422);
        }

        return $this->successResponse(
            array_merge($apercu, ['applique' => true, 'cascade' => $cascade]),
            "Versement {$paiement->numero_recu} restauré."
        );
    }

    private function resume(ESBTPPaiement $paiement): array
    {
        return [
            'id' => $paiement->id,
            'numero_recu' => $paiement->numero_recu,
            'montant' => (float) $paiement->montant,
            'status' => $paiement->status,
            'date_paiement' => $paiement->date_paiement?->format('Y-m-d'),
            'etudiant' => $paiement->etudiant
                ? trim($paiement->etudiant->nom.' '.$paiement->etudiant->prenoms)
                : null,
            'matricule' => $paiement->etudiant?->matricule,
        ];
    }
}

<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\InKindDepositForbiddenException;
use App\Http\Controllers\Controller;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\InKindDepositService;

/**
 * Defait un depot en nature.
 *
 * Le pendant de MarkInKindDepositedController, qui n'existait pas : une case
 * cochee par erreur ne se decochait plus, et le frais restait a zero.
 *
 * Meme autorisation qu'a l'aller — qui peut marquer peut defaire. Separer les
 * deux droits obligerait a passer par un superieur pour corriger sa propre
 * erreur de saisie, ce qui produit surtout des erreurs qu'on ne corrige pas.
 */
class UnmarkInKindDepositedController extends Controller
{
    public function __invoke(
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        InKindDepositService $deposits,
    ) {
        $this->authorize('markInKind', $inscription);

        $subscription = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('frais_category_id', $category->id)
            ->first();

        if (! $subscription) {
            return redirect()
                ->route('esbtp.inscriptions.show', $inscription)
                ->with('error', 'Aucune souscription à ce frais pour cet étudiant.');
        }

        try {
            $deposits->unmarkDeposited($subscription, (int) auth()->id());
        } catch (InKindDepositForbiddenException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', sprintf(
                'Dépôt en nature annulé : « %s » redevient dû.',
                $category->name
            ));
    }
}

<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\InKindDepositForbiddenException;
use App\Http\Controllers\Controller;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Services\InKindDepositService;

class MarkInKindDepositedController extends Controller
{
    public function __invoke(
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        InKindDepositService $deposits,
    ) {
        $this->authorize('markInKind', $inscription);

        $subscription = ESBTPFraisSubscription::where('inscription_id', $inscription->id)
            ->where('frais_category_id', $category->id)
            ->firstOrFail();

        try {
            $deposits->markDeposited($subscription, (int) auth()->id());
        } catch (InKindDepositForbiddenException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', 'Dépôt en nature enregistré.');
    }
}

<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\InKindDepositForbiddenException;
use App\Http\Controllers\Controller;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use App\Services\InKindDepositService;

class MarkInKindDepositedController extends Controller
{
    public function __invoke(
        ESBTPInscription $inscription,
        ESBTPFraisCategory $category,
        InKindDepositService $deposits,
    ) {
        $this->authorize('markInKind', $inscription);

        try {
            $deposits->markDepositedFor($inscription, $category, (int) auth()->id());
        } catch (InKindDepositForbiddenException $e) {
            abort(403, $e->getMessage());
        }

        $manquants = app(SouscriptionsObligatoiresManquantes::class)
            ->executer(true, null, [$inscription->id]);

        $message = 'Dépôt en nature enregistré.';
        if ($manquants['total'] > 0) {
            $message .= sprintf(' %d frais obligatoire(s) manquant(s) ont été ajoutés.', $manquants['total']);
        }

        return redirect()
            ->route('esbtp.inscriptions.show', $inscription)
            ->with('success', $message);
    }
}

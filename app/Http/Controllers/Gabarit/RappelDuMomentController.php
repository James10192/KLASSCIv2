<?php

namespace App\Http\Controllers\Gabarit;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Support\RappelsDuGabarit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le rappel du gabarit à ouvrir maintenant, demandé par le navigateur quand
 * l'heure est passée pour au moins un rappel. Les droits sont revérifiés ici :
 * un rappel demandé mais non permis est ignoré.
 */
class RappelDuMomentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $demandes = array_values(array_filter(
            explode(',', (string) $request->query('rappels', '')),
            fn (string $rappel) => array_key_exists($rappel, RappelsDuGabarit::ORDRE)
        ));

        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $annee || $demandes === []) {
            return response()->json(['rappel' => null, 'cle' => null, 'html' => null, 'verifies' => []]);
        }

        return response()->json(RappelsDuGabarit::premierDu($request->user(), $annee, $demandes));
    }
}

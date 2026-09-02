<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompleterFraisManquantsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:inscriptions.edit');
    }

    public function preview(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse
    {
        $ids = $this->ids($request);
        $resultat = $rattrapage->executer(false, null, $ids);

        return response()->json([
            'success' => true,
            'total' => $resultat['total'],
            'inscriptions' => $resultat['inscriptions'],
            'lignes' => $resultat['lignes'],
        ]);
    }

    public function apply(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse|RedirectResponse
    {
        $ids = $this->ids($request);
        $resultat = $rattrapage->executer(true, null, $ids);

        $message = $resultat['total'] > 0
            ? sprintf('%d frais obligatoire(s) ajouté(s) sur %d inscription(s).', $resultat['total'], $resultat['inscriptions'])
            : 'Aucun frais manquant à ajouter.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'total' => $resultat['total'],
                'inscriptions' => $resultat['inscriptions'],
                'lignes' => $resultat['lignes'],
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * @return array<int, int>
     */
    private function ids(Request $request): array
    {
        $validated = $request->validate([
            'inscription_ids' => 'required|array|min:1',
            'inscription_ids.*' => 'integer|exists:esbtp_inscriptions,id',
        ]);

        return array_map('intval', $validated['inscription_ids']);
    }
}

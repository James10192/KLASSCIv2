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
            'total' => $resultat['total_ajouter'] + $resultat['total_retirer'],
            'total_ajouter' => $resultat['total_ajouter'],
            'total_retirer' => $resultat['total_retirer'],
            'inscriptions' => $resultat['inscriptions'],
            'lignes' => $resultat['lignes'],
            'lignes_retrait' => $resultat['lignes_retrait'],
        ]);
    }

    public function apply(Request $request, SouscriptionsObligatoiresManquantes $rattrapage): JsonResponse|RedirectResponse
    {
        $ids = $this->ids($request);
        $resultat = $rattrapage->executer(true, null, $ids);

        $message = $this->message($resultat);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'total' => $resultat['total_ajouter'] + $resultat['total_retirer'],
                'total_ajouter' => $resultat['total_ajouter'],
                'total_retirer' => $resultat['total_retirer'],
                'inscriptions' => $resultat['inscriptions'],
                'lignes' => $resultat['lignes'],
                'lignes_retrait' => $resultat['lignes_retrait'],
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

    private function message(array $resultat): string
    {
        $ajoutes = (int) $resultat['total_ajouter'];
        $retires = (int) $resultat['total_retirer'];
        if ($ajoutes === 0 && $retires === 0) {
            return 'Aucun écart : les frais sont à jour.';
        }
        $parts = [];
        if ($ajoutes > 0) {
            $parts[] = sprintf('%d ajouté(s)', $ajoutes);
        }
        if ($retires > 0) {
            $parts[] = sprintf('%d retiré(s)', $retires);
        }

        return sprintf('%s sur %d inscription(s).', implode(', ', $parts), $resultat['inscriptions']);
    }
}

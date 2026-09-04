<?php

namespace App\Http\Controllers;

use App\Domain\Inscriptions\Pieces\PieceHorsCatalogueException;
use App\Domain\Inscriptions\Pieces\PiecesDossierService;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Le panneau « Pieces du dossier » de la fiche d'inscription.
 *
 * Tout repond en JSON : cocher une piece ne doit jamais recharger la page, le
 * secretariat en coche plusieurs d'affilee au guichet.
 */
class ESBTPInscriptionPieceController extends Controller
{
    public function __construct(private readonly PiecesDossierService $pieces)
    {
    }

    /**
     * L'etat courant, pour reafficher le panneau sans recharger la fiche.
     */
    public function index(ESBTPInscription $inscription): JsonResponse
    {
        $this->authorize('view', $inscription);

        return response()->json([
            'success' => true,
            'etat' => $this->pieces->etat($inscription),
        ]);
    }

    /**
     * Coche ou decoche une piece.
     */
    public function basculer(Request $request, ESBTPInscription $inscription): JsonResponse
    {
        $this->authorize('view', $inscription);

        $donnees = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'fournie' => ['required', 'boolean'],
            'observation' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $etat = $this->pieces->basculer(
                $inscription,
                $donnees['code'],
                (bool) $donnees['fournie'],
                $request->user()?->id,
                $donnees['observation'] ?? null,
            );
        } catch (PieceHorsCatalogueException $e) {
            // 422 et non 500 : le catalogue a bouge sous l'onglet ouvert, ce
            // n'est pas une panne, l'ecran doit juste se remettre a jour.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'etat' => $this->pieces->etat($inscription),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $donnees['fournie']
                ? 'Piece enregistree comme fournie.'
                : 'Piece repassee en « non fournie ».',
            'etat' => $etat,
        ]);
    }
}

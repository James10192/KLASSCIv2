<?php

namespace App\Http\Controllers\Assistant;

use App\Domain\Assistant\Pieces\LectureDePiece;
use App\Domain\Assistant\Pieces\PieceIllisible;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Un fichier joint dans le panneau de l'assistant. Il est lu tout de suite,
 * puis oublié : seul le tableau extrait est gardé, pour cette personne.
 */
class PieceJointeController extends Controller
{
    public function deposer(Request $request, LectureDePiece $lecture, PiecesJointes $pieces): JsonResponse
    {
        $request->validate([
            // 2 Mo : de quoi tenir des centaines de lignes, pas un classeur entier d'archives.
            'fichier' => ['required', 'file', 'max:2048', 'mimes:xlsx,xls,csv,txt,docx'],
        ], [
            'fichier.mimes' => 'Joignez un fichier Excel (.xlsx, .xls), CSV ou Word (.docx).',
            'fichier.max' => 'Le fichier dépasse 2 Mo.',
        ]);

        $fichier = $request->file('fichier');
        try {
            $tableau = $lecture->lire($fichier);
        } catch (PieceIllisible $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $id = $pieces->garder((int) $request->user()->id, $fichier->getClientOriginalName(), $tableau);

        return response()->json([
            'id' => $id,
            'nom' => $fichier->getClientOriginalName(),
            'colonnes' => $tableau['colonnes'],
            'nombre_lignes' => count($tableau['lignes']),
            'apercu' => array_slice($tableau['lignes'], 0, 5),
        ], 201);
    }
}

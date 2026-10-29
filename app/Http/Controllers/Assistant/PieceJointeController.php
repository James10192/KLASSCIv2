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
            'fichier' => ['required', 'file', 'max:4096', 'mimes:xlsx,xls,csv,txt,docx,jpg,jpeg,png,webp'],
        ], [
            'fichier.mimes' => 'Joignez un fichier Excel (.xlsx, .xls), CSV, Word (.docx) ou une image JPEG, PNG ou WebP.',
            'fichier.max' => 'Le fichier dépasse 4 Mo.',
        ]);

        $fichier = $request->file('fichier');
        if (str_starts_with((string) $fichier->getMimeType(), 'image/')) {
            $taille = @getimagesize($fichier->getRealPath());
            $mimes = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];
            $mime = $taille ? ($mimes[$taille[2] ?? 0] ?? null) : null;
            if (! $mime) {
                return response()->json(['message' => 'L’image est illisible ou son format ne correspond pas au fichier joint.'], 422);
            }

            $id = $pieces->garderImage((int) $request->user()->id, $fichier->getClientOriginalName(), $mime, (string) file_get_contents($fichier->getRealPath()));

            return response()->json([
                'id' => $id, 'nom' => $fichier->getClientOriginalName(), 'type' => 'image',
                'largeur' => (int) $taille[0], 'hauteur' => (int) $taille[1],
                'message' => 'Image prête : Nanan la lit avec votre demande et prépare une action à valider.',
            ], 201);
        }

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
            // Au-delà de 500 lignes ou 30 colonnes, le reste n'est pas lu : l'écran le dit.
            'tronque' => $tableau['tronque'],
            'apercu' => array_slice($tableau['lignes'], 0, 5),
        ], 201);
    }
}

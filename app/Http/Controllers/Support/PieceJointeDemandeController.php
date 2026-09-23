<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Exceptions\PorteeAbsente;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\Concerns\PorteeDeLecture;
use App\Http\Requests\Support\JoindrePieceRequest;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les pieces jointes d'une demande, relayees au Master. Le navigateur ne parle
 * jamais au Master : l'envoi et la lecture passent par ce controleur.
 *
 * Joindre se fait toujours en portee `mine`, comme repondre : seul l'auteur de
 * la demande y ajoute des fichiers. Lire suit la portee de lecture.
 */
class PieceJointeDemandeController extends Controller
{
    use PorteeDeLecture;

    public function __construct(
        private readonly DisponibiliteSupport $disponibilite,
        private readonly ClientMasterSupport $master,
    ) {
    }

    public function store(JoindrePieceRequest $request, string $reference): JsonResponse
    {
        $this->referenceValide($reference);
        $fichier = $request->file('fichier');

        try {
            $demande = $this->master->joindre(
                $reference,
                $request->user()->getKey(),
                (string) $fichier->get(),
                $fichier->getClientOriginalName(),
                $request->user()->name,
                $request->validated('cle'),
            );
        } catch (PorteeAbsente) {
            return response()->json(['message' => "Votre établissement ne peut pas encore joindre de fichier depuis KLASSCI. Écrivez-nous à ".config('app.support_email').'.'], 403);
        } catch (MasterSupportIndisponible) {
            return response()->json(['message' => 'Le support est momentanément injoignable. Réessayez dans un instant.'], 503);
        } catch (MasterSupportRefus $e) {
            abort_if($e->statut === 404, 404);

            return match ($e->codeErreur) {
                'attachment_rejected' => response()->json(['message' => $e->getMessage()], 422),
                'ticket_closed' => response()->json(['message' => 'Cette demande est fermée : ouvrez-en une nouvelle si le problème revient.'], 409),
                default => $this->refusInattendu($e),
            };
        }

        return response()->json([
            'pieces' => view('support.demandes._pieces', ['demande' => $demande])->render(),
            'statut' => view('support.demandes._statut', ['statut' => $demande['statut'] ?? []])->render(),
        ]);
    }

    public function show(Request $request, string $reference, int $piece): Response
    {
        $this->referenceValide($reference);

        try {
            $contenu = $this->master->piece($reference, $request->user()->getKey(), $this->portee($request, defaut: 'school'), $piece);
        } catch (MasterSupportIndisponible|MasterSupportRefus) {
            abort(503, 'Le support est momentanément injoignable.');
        }
        abort_if($contenu === null, 404);

        // Seuls ces types sortent du Master ; tout autre est traite en telechargement brut.
        $type = strtok($contenu['type'], ';') ?: 'application/octet-stream';
        $image = in_array($type, ['image/png', 'image/jpeg', 'image/webp'], true);

        return response($contenu['contenu'], 200, [
            'Content-Type' => $image || $type === 'application/pdf' ? $type : 'application/octet-stream',
            'Content-Disposition' => ($image ? 'inline' : 'attachment').'; filename="piece-'.$piece.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function referenceValide(string $reference): void
    {
        abort_unless($this->disponibilite->suivi(), 404);
        abort_unless(preg_match('/^KC-\d{4}-\d{6,}$/', $reference) === 1, 404);
    }

    private function refusInattendu(MasterSupportRefus $e): JsonResponse
    {
        Log::error('KLASSCI Care : pièce jointe refusée par le Master', ['statut' => $e->statut, 'code' => $e->codeErreur, 'erreurs' => $e->erreurs]);

        return response()->json(['message' => "Le fichier n'a pas pu être transmis. Écrivez-nous à ".config('app.support_email').'.'], 422);
    }
}

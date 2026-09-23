<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Exceptions\DebitLimiteAtteint;
use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Domain\Support\Exceptions\PorteeAbsente;
use App\Domain\Support\Services\DisponibiliteSupport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\Concerns\EcritSurUneDemande;
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
    use EcritSurUneDemande;
    use PorteeDeLecture;

    private const EXTENSIONS = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

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
            return response()->json(['message' => "Votre établissement ne peut pas encore joindre de fichier depuis KLASSCI. Écrivez-nous à ".config('app.support_email').'.', 'peut_joindre' => false], 403);
        } catch (DebitLimiteAtteint) {
            return response()->json(['message' => "Trop d'envois en même temps. Réessayez dans une minute : choisissez le même fichier, il ne sera pas envoyé deux fois."], 429);
        } catch (MasterSupportIndisponible) {
            return response()->json(['message' => 'Le support est momentanément injoignable. Réessayez dans un instant.'], 503);
        } catch (MasterSupportRefus $e) {
            abort_if($e->statut === 404, 404);

            return match ($e->codeErreur) {
                'attachment_rejected' => response()->json(['message' => $e->getMessage()], 422),
                'ticket_closed' => $this->demandeFermee($reference, $request->user()->getKey()),
                default => $this->refusInattendu($e),
            };
        }

        return response()->json([
            'pieces' => view('support.demandes._pieces', ['demande' => $demande])->render(),
            'statut' => view('support.demandes._statut', ['statut' => $demande['statut'] ?? []])->render(),
            'peut_joindre' => $this->peutJoindre($demande, $request->user()->getKey()),
        ]);
    }

    public function show(Request $request, string $reference, int $piece): Response
    {
        $this->referenceValide($reference);

        try {
            $contenu = $this->master->piece($reference, $request->user()->getKey(), $this->portee($request, defaut: 'school'), $piece);
        } catch (MasterSupportIndisponible) {
            return $this->retourALaDemande($reference, 'Le support est momentanément injoignable. Réessayez d\'ouvrir la pièce dans un instant.');
        } catch (MasterSupportRefus $e) {
            Log::error('KLASSCI Care : lecture de pièce refusée par le Master', ['statut' => $e->statut, 'code' => $e->codeErreur]);

            return $this->retourALaDemande($reference, "Cette pièce n'a pas pu être lue. Écrivez-nous à ".config('app.support_email').'.');
        }
        abort_if($contenu === null, 404);

        // Seuls ces types sortent du Master ; tout autre est traite en telechargement brut.
        $type = strtolower(trim((string) strtok($contenu['type'], ';'))) ?: 'application/octet-stream';
        $extension = self::EXTENSIONS[$type] ?? null;
        $image = in_array($type, ['image/png', 'image/jpeg', 'image/webp'], true);

        return response($contenu['contenu'], 200, [
            'Content-Type' => $extension ? $type : 'application/octet-stream',
            // L'extension vient du type admis, jamais du nom envoye : un PDF telecharge
            // s'ouvre d'un double clic sans qu'un nom choisi par l'ecole atteigne l'en-tete.
            'Content-Disposition' => ($image ? 'inline' : 'attachment').'; filename="piece-'.$piece.($extension ? '.'.$extension : '').'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; sandbox",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Un lien de telechargement suivi dans la page : une page d'erreur nue
     * remplacerait la demande. On y revient, avec le message.
     */
    private function retourALaDemande(string $reference, string $message): Response
    {
        return redirect()->route('support.demandes.show', $reference)->with('erreur_piece', $message);
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

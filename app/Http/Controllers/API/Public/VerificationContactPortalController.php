<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\VerifierContactRequest;
use App\Services\Verification\ControleVerification;
use App\Services\Verification\RenvoiVerification;
use App\Http\Requests\Verification\RenvoyerContactRequest;
use Illuminate\Http\JsonResponse;

/**
 * Verification du contact (e-mail ou WhatsApp) d'une demande deposee sur
 * klassci.com. Memes routes pour les deux canaux ; le champ `canal` n'est
 * qu'un controle de coherence, la ligne de verification connait le sien.
 *
 * Signees par le site vitrine comme les autres routes publiques.
 */
class VerificationContactPortalController extends Controller
{
    public function verifier(VerifierContactRequest $request, ControleVerification $controle): JsonResponse
    {
        $donnees = $request->validated();
        // Canal absent : e-mail. Toute autre valeur que email/telephone est deja refusee par la requete.
        $canal = $donnees['canal'] ?? 'email';

        $resultat = ! empty($donnees['jeton'])
            ? $controle->parJeton((string) $donnees['jeton'], $canal)
            : $controle->parCode((string) $donnees['demande_id'], (string) $donnees['code'], $canal);

        return response()->json($resultat->corps(), $resultat->statutHttp());
    }

    public function renvoyer(RenvoyerContactRequest $request, RenvoiVerification $renvoi): JsonResponse
    {
        // Une saisie mal formee rend la meme reponse qu'une demande inconnue.
        if ($request->demandeId() === null) {
            return response()->json(['envoye' => true], 202);
        }

        $attente = $renvoi->renvoyer($request->demandeId(), $request->canal());

        return $attente === null
            ? response()->json(['envoye' => true], 202)
            : response()->json(['envoye' => false, 'retry_after' => $attente], 429);
    }
}

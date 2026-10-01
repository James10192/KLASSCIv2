<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\VerifierContactRequest;
use App\Services\Verification\ControleVerification;
use App\Services\Verification\LienWhatsappVerification;
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
        if ($attente !== null) {
            return response()->json(['envoye' => false, 'retry_after' => $attente], 429);
        }

        // Verification inversee : le nouveau lien remplace l'ancien a l'ecran.
        $lien = $request->canal() === 'telephone' ? LienWhatsappVerification::lire($request->demandeId()) : null;

        return response()->json($lien === null ? ['envoye' => true] : ['envoye' => true, 'lien_whatsapp' => $lien], 202);
    }

    /**
     * Ou en est une verification WhatsApp inversee. Le site l'appelle toutes
     * les quelques secondes pendant que la famille envoie le code : 202 tant
     * que le message n'est pas arrive (avec le lien, pour le reafficher apres
     * un rechargement), 200 des que la demande est verifiee.
     */
    public function statut(RenvoyerContactRequest $request, ControleVerification $controle): JsonResponse
    {
        if ($request->demandeId() === null) {
            return response()->json(['verifie' => false, 'motif' => ControleVerification::CODE_INVALIDE], 422);
        }

        $resultat = $controle->parStatut($request->demandeId(), $request->canal());
        $corps = $resultat->corps();
        if ($resultat->motif === ControleVerification::EN_ATTENTE && ($lien = LienWhatsappVerification::lire($request->demandeId())) !== null) {
            $corps['lien_whatsapp'] = $lien;
        }

        return response()->json($corps, $resultat->statutHttp());
    }
}

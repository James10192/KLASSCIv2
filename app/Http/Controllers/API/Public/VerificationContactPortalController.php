<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Contracts\PorteurDeRendezVous;
use App\Enums\CanalVerification;
use App\Http\Requests\Verification\ChangerCanalRequest;
use App\Http\Requests\Verification\RenvoyerContactRequest;
use App\Http\Requests\Verification\VerifierContactRequest;
use App\Models\ESBTPVerificationContact;
use App\Services\Portail\ReferencePublique;
use App\Services\RendezVous\AccesRendezVous;
use App\Services\RendezVous\JetonAccesRdv;
use App\Services\Verification\ChangementCanalVerification;
use App\Services\Verification\ControleVerification;
use App\Services\Verification\LienWhatsappVerification;
use App\Services\Verification\RenvoiVerification;
use Illuminate\Http\JsonResponse;

/**
 * Verification du contact (e-mail ou WhatsApp) d'une demande deposee sur
 * klassci.com. Memes routes pour les deux canaux ; le champ `canal` n'est
 * qu'un controle de coherence, la ligne de verification connait le sien.
 *
 * Signees par le site vitrine comme les autres routes publiques.
 *
 * Les memes routes servent le code qui ouvre une convocation
 * (AccesRendezVous) : son identifiant vit dans le cache et non en base, et
 * c'est a lui seul qu'on le reconnait. Le site garde ainsi un seul ecran de code.
 */
class VerificationContactPortalController extends Controller
{
    public function verifier(VerifierContactRequest $request, ControleVerification $controle, AccesRendezVous $acces): JsonResponse
    {
        $donnees = $request->validated();

        if (empty($donnees['jeton']) && $acces->estAcces($donnees['demande_id'] ?? null)) {
            [$statut, $corps] = $acces->verifier((string) $donnees['demande_id'], (string) $donnees['code']);

            return response()->json($corps, $statut);
        }

        // Canal absent : e-mail. Toute autre valeur que email/telephone est deja refusee par la requete.
        $canal = $donnees['canal'] ?? 'email';

        $resultat = ! empty($donnees['jeton'])
            ? $controle->parJeton((string) $donnees['jeton'], $canal)
            : $controle->parCode((string) $donnees['demande_id'], (string) $donnees['code'], $canal);

        $corps = $resultat->corps();
        if ($resultat->verifie && empty($donnees['jeton'])) {
            $corps += $this->accesAuRendezVous((string) $donnees['demande_id']);
        }

        return response()->json($corps, $resultat->statutHttp());
    }

    /**
     * Le contact vient d'etre prouve : la famille enchaine sur le choix du
     * creneau sans second code. Le jeton ne vaut que pour CE dossier.
     *
     * @return array{jeton_acces?: string}
     */
    private function accesAuRendezVous(string $demandeId): array
    {
        $porteur = ESBTPVerificationContact::query()->where('demande_id', $demandeId)->first()?->verifiable;
        if (! $porteur instanceof PorteurDeRendezVous) {
            return [];
        }

        $reference = app(ReferencePublique::class)->normaliser($porteur->assurerReferencePublique());

        return $reference === '' ? [] : ['jeton_acces' => JetonAccesRdv::emettre($reference)];
    }

    /** « Je ne reçois rien sur WhatsApp » : le code par e-mail, ou a une autre adresse. */
    public function changerCanal(ChangerCanalRequest $request, ChangementCanalVerification $changement): JsonResponse
    {
        $donnees = $request->validated();
        [$statut, $corps] = $changement->changer(
            (string) $donnees['demande_id'],
            CanalVerification::from((string) $donnees['canal']),
            $donnees['email'] ?? null,
        );

        return response()->json($corps, $statut);
    }

    public function renvoyer(RenvoyerContactRequest $request, RenvoiVerification $renvoi, AccesRendezVous $acces): JsonResponse
    {
        // Une saisie mal formee rend la meme reponse qu'une demande inconnue.
        if ($request->demandeId() === null) {
            return response()->json(['envoye' => true], 202);
        }

        if ($acces->estAcces($request->demandeId())) {
            $attente = $acces->renvoyer($request->demandeId());

            return $attente === null
                ? response()->json(['envoye' => true], 202)
                : response()->json(['envoye' => false, 'retry_after' => $attente], 429);
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
        if ($resultat->verifie) {
            $corps += $this->accesAuRendezVous($request->demandeId());
        }
        if ($resultat->motif === ControleVerification::EN_ATTENTE && ($lien = LienWhatsappVerification::lire($request->demandeId())) !== null) {
            $corps['lien_whatsapp'] = $lien;
        }

        return response()->json($corps, $resultat->statutHttp());
    }
}

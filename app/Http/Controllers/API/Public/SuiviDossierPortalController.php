<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portail\ReferenceOublieeRequest;
use App\Http\Requests\Portail\SuiviDossierEmailRequest;
use App\Http\Requests\Portail\SuiviDossierRequest;
use App\Services\Portail\ReferenceOubliee;
use App\Services\Portail\SuiviDossierPortail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Suivi d'un dossier deja depose, depuis le portail public : ou il en est,
 * l'adresse e-mail (masquee) a verifier ou corriger, et la convocation a
 * recevoir. Tout echec d'identification rend le meme corps.
 */
class SuiviDossierPortalController extends Controller
{
    public function __construct(
        private readonly SuiviDossierPortail $suivi,
        private readonly ReferenceOubliee $references,
    ) {
    }

    public function consulter(SuiviDossierRequest $request): JsonResponse
    {
        return $this->avecDossier($request, fn (Model $porteur) => response()->json($this->suivi->situation($porteur)));
    }

    public function email(SuiviDossierEmailRequest $request): JsonResponse
    {
        return $this->avecDossier($request, function (Model $porteur) use ($request) {
            return $this->repondre($this->suivi->changerEmail($porteur, $request->validated()['email']), $porteur);
        });
    }

    public function verifier(SuiviDossierRequest $request): JsonResponse
    {
        return $this->avecDossier($request, fn (Model $porteur) => $this->repondre($this->suivi->verifier($porteur), $porteur));
    }

    public function convocation(SuiviDossierRequest $request): JsonResponse
    {
        return $this->avecDossier($request, function (Model $porteur) {
            $code = $this->suivi->envoyerConvocation($porteur);

            return response()->json(['code' => $code, 'situation' => $this->suivi->situation($porteur)], $code === 'envoyee' ? 200 : 409);
        });
    }

    public function referenceOubliee(ReferenceOublieeRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $seau = ReferenceOubliee::seau($donnees['email']);
        if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
            return response()->json($seau->refus(), 429);
        }
        RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

        $this->references->envoyer($donnees['email'], $donnees['date_naissance']);

        return response()->json(['envoye' => true], 202);
    }

    /** @param  array{code: string, verification?: \App\Services\Verification\VerificationDemarree}  $resultat */
    private function repondre(array $resultat, Model $porteur): JsonResponse
    {
        $corps = ['code' => $resultat['code']];
        if (isset($resultat['verification'])) {
            $corps += $resultat['verification']->reponse();
        }
        $corps['situation'] = $this->suivi->situation($porteur->fresh() ?? $porteur);

        return response()->json($corps, in_array($resultat['code'], ['dossier_clos', 'envoi_impossible'], true) ? 409 : 200);
    }

    /** @param  callable(Model): JsonResponse  $suite */
    private function avecDossier(SuiviDossierRequest $request, callable $suite): JsonResponse
    {
        $donnees = $request->validated();
        $seau = SuiviDossierPortail::seau($donnees['identifiant']);
        if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
            return response()->json($seau->refus(), 429);
        }

        $porteur = $this->suivi->retrouver($donnees['identifiant'], $donnees['date_naissance']);
        if ($porteur === null) {
            RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

            return response()->json(['trouve' => false]);
        }

        return $suite($porteur);
    }
}

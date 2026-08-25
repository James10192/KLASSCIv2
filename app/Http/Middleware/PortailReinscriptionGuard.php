<?php

namespace App\Http\Middleware;

use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Porte d'entree du portail public de reinscription.
 *
 * Trois refus, dans cet ordre, et l'ordre compte :
 *
 * 1. Signature du site vitrine. Sans elle, personne ne parle a ce canal.
 * 2. Limitation de debit, comptee sur des valeurs SIGNEES.
 * 3. Fenetre saisonniere. Hors periode, l'ecole n'expose rien.
 *
 * La limitation vit ici, et non dans un `throttle:` pose sur la route, pour
 * une raison qui n'a rien d'esthetique : Laravel trie la pile d'intergiciels
 * par `middlewarePriority`, ou `ThrottleRequests` figure. L'ordre declare dans
 * le fichier de routes n'est donc PAS l'ordre d'execution — verifie : le
 * limiteur y passait AVANT ce garde. Il comptait alors sur un `matricule` et
 * une `ip_client` non authentifies, et n'importe qui pouvait, avec cinq
 * signatures bidon, epuiser le quota d'un etudiant nomme pendant un quart
 * d'heure, ou fermer le canal pour l'ecole entiere avec cent vingt requetes
 * par minute. Compter apres `hash_equals` est le seul endroit du code ou ces
 * valeurs sont demontrablement dignes de confiance.
 *
 * La signature passe avant la fenetre saisonniere parce que l'etat du canal
 * est une information : repondre 503 avant authentification permettrait de
 * cartographier quelles ecoles ont ouvert leur portail et sur quelle periode.
 *
 * Ces refus sont rendus IMMEDIATEMENT, sans plancher de temps de reponse (voir
 * PortailReinscriptionPlancher) : ils ne dependent d'aucune donnee etudiante,
 * donc leur rapidite ne revele rien. Les faire attendre offrirait a un
 * attaquant non authentifie un quart de seconde de processus PHP par requete
 * bidon, soit un amplificateur de deni de service sur un hebergement mutualise.
 */
class PortailReinscriptionGuard
{
    /** Bornes de volume, comptees ici sur des valeurs signees. La borne par
     * matricule vit dans PortailReinscriptionService : le garde la consulte,
     * le controleur la remplit. */
    private const MAX_PAR_ADRESSE_PAR_MINUTE = 10;

    private const MAX_GLOBAL_PAR_MINUTE = 120;

    public function __construct(
        private readonly PortailReinscriptionService $portail,
        private readonly PortailSignatureVerifier $signature,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->signatureValide($request)) {
            return response()->json([
                'trouve' => false,
                'message' => 'Requête non authentifiée.',
            ], 401);
        }

        if ($this->tropDeTentatives($request)) {
            return response()->json([
                'trouve' => false,
                'message' => 'Trop de tentatives. Réessayez dans quelques minutes.',
            ], 429);
        }

        if (! $this->portail->canalOuvert()) {
            return response()->json([
                'ouvert' => false,
                'message' => "Les réinscriptions en ligne ne sont pas ouvertes actuellement.",
            ], 503);
        }

        return $next($request);
    }

    /**
     * La signature couvre le corps BRUT, la methode et le chemin. C'est ce qui
     * rend l'adresse du visiteur (`ip_client`) digne de confiance : elle est
     * signee par le site vitrine, donc infalsifiable par l'appelant, et c'est
     * elle qui sert de cle a la limitation ci-dessous.
     */
    private function signatureValide(Request $request): bool
    {
        if (! $this->signature->estConfigure()) {
            Log::error('Export de reinscription appele sans secret configure');

            return false;
        }

        $entete = (string) $request->header('X-Klassci-Signature', '');
        $horodatage = (int) $request->header('X-Klassci-Timestamp', '0');

        if ($entete === '' || $horodatage === 0) {
            return false;
        }

        return $this->signature->verifie(
            $request->getContent(),
            $request->getMethod(),
            $request->path(),
            $entete,
            $horodatage,
        );
    }

    /**
     * Le matricule borne le forcage d'une date de naissance, l'adresse borne
     * le balayage depuis un poste, et le plafond global protege le tenant d'un
     * balayage distribue.
     *
     * `$request->ip()` n'entre volontairement dans aucune de ces cles : ce
     * serait l'adresse de sortie du site vitrine, partagee par toute l'ecole
     * et renouvelee a chaque demarrage a froid chez l'hebergeur.
     *
     * Les TROIS seaux sont consultes avant qu'aucun ne soit incremente. Sinon
     * une requete refusee par le plafond global aurait deja brule un jeton du
     * matricule : pendant une inondation, chaque tentative d'un etudiant
     * legitime lui couterait un jeton sans rien lui servir, et le verrouillerait
     * durablement.
     *
     * Le seau du matricule n'est PAS incremente ici : ce serait compter les
     * succes. Le parcours normal — consulter, puis deposer — en consommerait
     * deux sur cinq, et cinq envois du formulaire public nommant le matricule
     * d'un camarade suffiraient a le verrouiller un quart d'heure. Seul
     * l'echec d'identification le consomme, et le controleur est le seul a
     * savoir qu'il y a eu echec.
     */
    private function tropDeTentatives(Request $request): bool
    {
        $adresse = $request->input('ip_client');

        $seauxDeVolume = [
            ['rp-ip:'.hash('sha256', is_string($adresse) ? $adresse : ''), self::MAX_PAR_ADRESSE_PAR_MINUTE, 60],
            ['rp-global', self::MAX_GLOBAL_PAR_MINUTE, 60],
        ];

        $aConsulter = array_merge([[
            PortailReinscriptionService::cleDebitMatricule($request->input('matricule')),
            PortailReinscriptionService::DEBIT_MATRICULE_MAX,
        ]], $seauxDeVolume);

        foreach ($aConsulter as [$cle, $maximum]) {
            if (RateLimiter::tooManyAttempts($cle, $maximum)) {
                return true;
            }
        }

        foreach ($seauxDeVolume as [$cle, , $fenetre]) {
            RateLimiter::hit($cle, $fenetre);
        }

        return false;
    }
}

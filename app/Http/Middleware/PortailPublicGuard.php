<?php

namespace App\Http\Middleware;

use App\Enums\CanalPortailPublic;
use App\Enums\NaturePortailPublic;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Porte d'entree du portail public : reinscriptions ET candidatures.
 *
 * Trois refus, dans cet ordre, et l'ordre compte :
 *
 * 1. Signature du site vitrine. Sans elle, personne ne parle a ce canal.
 * 2. Limitation de debit, comptee sur des valeurs SIGNEES.
 * 3. Fenetre saisonniere. Hors periode, l'ecole n'expose rien.
 *
 * Ce qui distingue les canaux — interrupteur, phrase de fermeture, seaux — vit
 * dans CanalPortailPublic. Ce fichier ne garde que l'enchainement, qui leur est
 * commun.
 *
 * La limitation vit ici, et non dans un `throttle:` pose sur la route, pour
 * une raison qui n'a rien d'esthetique : Laravel trie la pile d'intergiciels
 * par `middlewarePriority`, ou `ThrottleRequests` figure. L'ordre declare dans
 * le fichier de routes n'est donc PAS l'ordre d'execution — verifie : le
 * limiteur y passait AVANT ce garde. Il comptait alors sur un `matricule` et
 * une `ip_client` non authentifies, et n'importe qui pouvait, avec cinq
 * signatures bidon, epuiser le quota d'un etudiant nomme pendant un quart
 * d'heure, ou fermer le canal pour l'ecole entiere. Compter apres `hash_equals`
 * est le seul endroit du code ou ces valeurs sont demontrablement dignes de
 * confiance.
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
class PortailPublicGuard
{
    public function __construct(private readonly PortailSignatureVerifier $signature) {}

    /**
     * @param  string  $canal   `reinscriptions` (defaut) ou `candidatures`.
     * @param  string  $nature  `identite` (defaut) pour les points d'entree qui
     *                          portent l'identite de quelqu'un, `catalogue`
     *                          pour ceux qui ne servent qu'une liste publique.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $canal = 'reinscriptions',
        string $nature = 'identite'
    ): Response {
        if (! $this->signatureValide($request)) {
            // Pas de cle `trouve` : c'est le vocabulaire de la reinscription,
            // qui cherche un dossier. Une candidature ne cherche rien, et ce
            // garde sert les deux canaux.
            return response()->json(['message' => 'Requête non authentifiée.'], 401);
        }

        $canalPublic = CanalPortailPublic::depuis($canal);

        // Le CODE voyage avec le message, et c'est lui qui compte : le site
        // vitrine affiche ses propres textes, traduits, et ne lit jamais celui
        // du serveur. Sans code, les deux saturations arrivaient la-bas
        // indistinguables, et le visiteur d'une ecole engorgee lisait qu'il
        // avait trop essaye — alors qu'il n'avait rien tente, et que patienter
        // ne libere rien tant que l'affluence dure.
        if (($saturation = $this->debitDepasse($request, $canalPublic, NaturePortailPublic::depuis($nature))) !== null) {
            return response()->json($saturation, 429);
        }

        if (! $canalPublic->ouvert()) {
            return response()->json([
                'ouvert' => false,
                'message' => $canalPublic->messageFerme(),
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
            Log::error('Portail public appele sans secret configure');

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
     * Les seaux de debit, et le refus que leur saturation merite.
     *
     * Le matricule borne le forcage d'une date de naissance, l'adresse borne
     * le balayage depuis un poste, et le plafond global protege le tenant d'un
     * balayage distribue.
     *
     * `$request->ip()` n'entre volontairement dans aucune de ces cles : ce
     * serait l'adresse de sortie du site vitrine, partagee par toute l'ecole
     * et renouvelee a chaque demarrage a froid chez l'hebergeur.
     *
     * Les seaux sont TOUS consultes avant qu'aucun ne soit incremente. Sinon
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
     *
     * @return array{code: string, message: string}|null null si la requete
     *         passe, sinon le refus a rendre
     */
    private function debitDepasse(Request $request, CanalPortailPublic $canal, NaturePortailPublic $nature): ?array
    {
        if (($refus = $this->matriculeEpuise($request)) !== null) {
            return $refus;
        }

        // `is_string`, et pas un transtypage : le debit est compte AVANT toute
        // validation — la regle `ip` vit dans le FormRequest, en aval. Un
        // `ip_client[]=x` produirait sinon un avertissement de conversion et
        // hacherait la chaine « Array », rangeant tous ces appels dans un seau
        // commun. Ce garde est la frontiere qui ne fait pas confiance.
        $brut = $request->input('ip_client');
        $seaux = $canal->seaux($nature, hash('sha256', is_string($brut) ? $brut : ''));

        foreach ($seaux as $seau) {
            if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
                return $seau->refus();
            }
        }

        foreach ($seaux as $seau) {
            RateLimiter::hit($seau->cle, $seau->fenetreSecondes);
        }

        return null;
    }

    /**
     * Le seau du matricule, propre a la reinscription.
     *
     * Une candidature n'en porte pas : garder ce seau lui donnerait une cle
     * vide, donc UN seul compteur partage par tous les candidats du pays, et
     * cinq envois fermeraient le canal pour tout le monde. C'est le champ qui
     * decide, pas le canal — un canal futur qui porterait un matricule en
     * heriterait naturellement.
     *
     * Son refus a son propre code et sa propre phrase : c'est le seul seau
     * qu'un TIERS peut remplir, et sa fenetre dure un quart d'heure. Lui
     * appliquer « trop de tentatives, patientez quelques minutes » serait faux
     * sur la cause comme sur le delai.
     *
     * @return array{code: string, message: string}|null
     */
    private function matriculeEpuise(Request $request): ?array
    {
        $matricule = $request->input('matricule');

        if (! is_string($matricule) || trim($matricule) === '') {
            return null;
        }

        $seau = PortailReinscriptionService::seauDuMatricule($matricule);

        return RateLimiter::tooManyAttempts($seau->cle, $seau->maximum) ? $seau->refus() : null;
    }
}

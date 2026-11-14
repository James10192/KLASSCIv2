<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reinscription\PortailRequest;
use App\Http\Requests\Reinscription\PortailSubmitRequest;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Emails\AnalyseurEmail;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\SituationReinscription;
use App\Services\Verification\DemarrageVerification;
use App\Services\Verification\MasqueContact;
use App\Support\ColonnesDeployees;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Export securise consomme par le site klassci.com.
 *
 * Premiere des deux surfaces non authentifiees de l'application. Les gardes transverses
 * — signature du site vitrine, limitation de debit, fenetre saisonniere —
 * vivent dans `PortailPublicGuard`, et le plancher de temps de reponse
 * dans `PortailReinscriptionPlancher`. Il ne reste ici que deux disciplines,
 * celles qui portent sur le CONTENU des reponses :
 *
 * 1. Reponses UNIFORMES. « Ce matricule n'existe pas », « il existe mais la
 *    date ne correspond pas » et « il existe mais n'a rien a reinscrire »
 *    rendent exactement la meme reponse. Distinguer ces cas donnerait un
 *    oracle pour enumerer les matricules.
 * 2. Aucune donnee financiere, aucun motif de refus detaille. Le facteur
 *    d'identification est faible, donc il ne doit rien proteger de sensible.
 *    Le nom complet sort depuis novembre 2026 : sans lui, deux etudiants au
 *    meme prenom ne savaient pas s'ils tenaient leur dossier. Contacts
 *    seulement masques.
 */
class ReinscriptionPortalController extends Controller
{
    public function __construct(
        private readonly PortailReinscriptionService $portail,
    ) {}

    /** Retrouver sa situation. Ne cree rien. */
    public function lookup(PortailRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $situation = $this->situation($donnees);

        if ($situation === null) {
            return $this->introuvable($donnees['matricule']);
        }

        RateLimiter::clear(PortailReinscriptionService::cleDebitMatricule($donnees['matricule']));

        return response()->json([
            'trouve' => true,
            // `prenom` reste pour les sites pas encore a jour. Le nom complet
            // sert a se reconnaitre entre homonymes de prenom ; il ne sort
            // qu'apres un matricule ET une date de naissance exacts, comme
            // la convocation elle-meme.
            'prenom' => $this->prenomAbrege($situation->etudiant),
            'nom' => trim((string) $situation->etudiant->nom),
            'prenoms' => trim((string) $situation->etudiant->prenoms),
            // Ou partira le code de confirmation, masque : la famille sait
            // d'avance si c'est la bonne adresse, et peut en donner une autre.
            'contacts' => $this->contactsMasques($situation->etudiant),
            'verification' => app(DemarrageVerification::class)->active(),
            'classe_actuelle' => $situation->classeActuelle(),
            'annee_cible' => $situation->anneeCibleLibelle(),
            'eligible' => $situation->eligible(),
            'demande_existante' => $situation->demandeExistante,
        ]);
    }

    /** Deposer une demande. Cree une demande INERTE, jamais une inscription. */
    public function submit(PortailSubmitRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $situation = $this->situation($donnees);

        if ($situation === null) {
            return $this->introuvable($donnees['matricule']);
        }

        $demande = $this->portail->deposer($situation, $donnees['ip_client']);

        // Un etudiant deja reinscrit, ou sans rien a reinscrire, recoit la
        // meme reponse qu'un matricule inconnu : le portail ne confirme jamais
        // l'existence d'un dossier qu'il refuse de servir.
        if ($demande === null) {
            return $this->introuvable($donnees['matricule']);
        }

        RateLimiter::clear(PortailReinscriptionService::cleDebitMatricule($donnees['matricule']));

        if (($refus = $this->retenirContact($demande, $donnees)) !== null) {
            return $refus;
        }

        // Une reinscription se finalise SUR PLACE, elle aussi.
        //
        // C'est une inscription comme une autre : l'etudiant existe deja en
        // base, mais les pieces se remettent et les frais se reglent au
        // guichet — il n'y a pas de paiement a distance. La confirmation
        // emporte donc la meme date d'ouverture que celle d'une candidature,
        // et pour la meme raison : « je viens quand ? » est la question que la
        // scolarite entend le plus.
        //
        // Si l'ecole l'a active, un code part vers le contact du dossier (e-mail
        // joignable, sinon WhatsApp) ; la demande est transmise dans tous les cas.
        $demarrage = app(DemarrageVerification::class);
        $verification = $demarrage->apresDepot($demande);

        if ($verification === null && $demarrage->active() && $demande->fresh()->contactMarque()) {
            return response()->json([
                'enregistre' => true,
                'statut' => 'verification_indisponible',
                'message' => 'Votre demande est enregistrée, mais le code de vérification n’a pas pu être envoyé. Vérifiez votre contact, puis réessayez ou utilisez une adresse e-mail.',
                'reference_publique' => $demande->referencePubliqueAffichee(),
            ], 503);
        }

        return response()->json(array_merge([
            'enregistre' => true,
            'message' => 'Votre demande a bien été transmise à votre établissement.'
                .($verification === null ? '' : ' Confirmez votre contact avec le code reçu pour être convoqué(e).'),
            'inscriptions_physiques' => app(PortailCandidaturePublication::class)->inscriptionsPhysiques(),
            'reference_publique' => $demande->referencePubliqueAffichee(),
        ], $verification?->reponse() ?? []), 201);
    }

    /**
     * @param  array{matricule: string, date_naissance: string, ip_client: string}  $donnees
     */
    private function situation(array $donnees): ?SituationReinscription
    {
        $etudiant = $this->portail->identifier($donnees['matricule'], $donnees['date_naissance']);

        return $etudiant === null ? null : $this->portail->evaluer($etudiant);
    }

    /**
     * Reponse unique pour tous les echecs, quelle qu'en soit la cause. C'est
     * le coeur de la protection contre l'enumeration.
     *
     * C'est ICI, et nulle part ailleurs, que le seau du matricule se remplit :
     * seul un echec d'identification doit couter un jeton. Le compter en amont
     * ferait payer les succes, et cinq envois du formulaire public nommant le
     * matricule d'un camarade suffiraient a le verrouiller un quart d'heure.
     */
    private function introuvable(mixed $matricule): JsonResponse
    {
        $seau = PortailReinscriptionService::seauDuMatricule($matricule);

        RateLimiter::hit($seau->cle, $seau->fenetreSecondes);

        return response()->json([
            'trouve' => false,
            'message' => 'Aucun dossier ne correspond. Vérifiez votre matricule et votre date de naissance, ou rapprochez-vous de votre établissement.',
        ]);
    }

    /**
     * L'adresse et le canal choisis par la famille, ranges sur la DEMANDE
     * avant que le code ne parte. Jamais sur le dossier : voir
     * ChangementCanalVerification.
     *
     * @param  array<string, mixed>  $donnees
     */
    private function retenirContact(ESBTPReinscriptionDemande $demande, array $donnees): ?JsonResponse
    {
        $email = trim((string) ($donnees['email'] ?? ''));
        if ($email !== '' && ! app(AnalyseurEmail::class)->analyser($email)->joignable()) {
            return response()->json([
                'message' => 'Cette adresse e-mail ne peut pas recevoir de message.',
                'errors' => ['email' => ['Cette adresse e-mail ne peut pas recevoir de message.']],
            ], 422);
        }

        $champs = [];
        if ($email !== '' && ColonnesDeployees::existe('esbtp_reinscription_demandes', 'email_contact')) {
            $champs['email_contact'] = mb_strtolower($email);
        }
        if (! empty($donnees['canal']) && ColonnesDeployees::existe('esbtp_reinscription_demandes', 'canal_prefere')) {
            $champs['canal_prefere'] = (string) $donnees['canal'];
        }
        if ($champs !== []) {
            $demande->forceFill($champs)->save();
        }

        return null;
    }

    /** @return array{email_masque: ?string, telephone_masque: ?string} */
    private function contactsMasques(ESBTPEtudiant $etudiant): array
    {
        $email = trim((string) ($etudiant->email_personnel ?: $etudiant->email));
        $joignable = $email !== '' && app(AnalyseurEmail::class)->analyser($email)->joignable();
        $mobile = \App\Domain\Notifications\PhoneNormalizer::estMobileNational($etudiant->telephone);

        return [
            'email_masque' => $joignable ? MasqueContact::email($email) : null,
            'telephone_masque' => $mobile ? MasqueContact::telephone(\App\Domain\Notifications\PhoneNormalizer::toE164($etudiant->telephone)) : null,
        ];
    }

    private function prenomAbrege(ESBTPEtudiant $etudiant): string
    {
        $prenoms = trim((string) ($etudiant->prenoms ?? ''));

        return $prenoms === '' ? '' : explode(' ', $prenoms)[0];
    }
}

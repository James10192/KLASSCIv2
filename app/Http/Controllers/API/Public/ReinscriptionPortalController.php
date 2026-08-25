<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reinscription\PortailRequest;
use App\Http\Requests\Reinscription\PortailSubmitRequest;
use App\Models\ESBTPEtudiant;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\SituationReinscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Export securise consomme par le site klassci.com.
 *
 * Seule surface non authentifiee de l'application. Les gardes transverses
 * — signature du site vitrine, limitation de debit, fenetre saisonniere —
 * vivent dans `PortailReinscriptionGuard`, et le plancher de temps de reponse
 * dans `PortailReinscriptionPlancher`. Il ne reste ici que deux disciplines,
 * celles qui portent sur le CONTENU des reponses :
 *
 * 1. Reponses UNIFORMES. « Ce matricule n'existe pas », « il existe mais la
 *    date ne correspond pas » et « il existe mais n'a rien a reinscrire »
 *    rendent exactement la meme reponse. Distinguer ces cas donnerait un
 *    oracle pour enumerer les matricules.
 * 2. Aucune donnee financiere, aucune identite complete, aucun motif de refus
 *    detaille. Le facteur d'identification est faible, donc il ne doit rien
 *    proteger de sensible.
 */
class ReinscriptionPortalController extends Controller
{
    public function __construct(private readonly PortailReinscriptionService $portail) {}

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
            // Prenom seul : de quoi confirmer « c'est bien moi » sans exposer
            // une identite complete a qui aurait devine le couple.
            'prenom' => $this->prenomAbrege($situation->etudiant),
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

        return response()->json([
            'enregistre' => true,
            'message' => 'Votre demande a bien été transmise à votre établissement.',
        ], 201);
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
        RateLimiter::hit(
            PortailReinscriptionService::cleDebitMatricule($matricule),
            PortailReinscriptionService::DEBIT_MATRICULE_FENETRE_SECONDES
        );

        return response()->json([
            'trouve' => false,
            'message' => "Aucun dossier ne correspond. Vérifiez votre matricule et votre date de naissance, ou rapprochez-vous de votre établissement.",
        ]);
    }

    private function prenomAbrege(ESBTPEtudiant $etudiant): string
    {
        $prenoms = trim((string) ($etudiant->prenoms ?? ''));

        return $prenoms === '' ? '' : explode(' ', $prenoms)[0];
    }
}

<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inscription\PortailCandidatureRequest;
use App\Services\Inscription\PortailCandidatureService;
use Illuminate\Http\JsonResponse;

/**
 * Candidatures des NOUVEAUX eleves, depuis klassci.com.
 *
 * Deuxieme surface non authentifiee de l'application, et elle ne se protege
 * pas comme la premiere. La reinscription identifie quelqu'un, donc son ennemi
 * est l'enumeration : d'ou ses reponses uniformes et son plancher de temps.
 * Une candidature n'identifie personne — il n'y a rien a retrouver, donc rien
 * a enumerer — et peut donc repondre franchement.
 *
 * Son ennemi a elle est le remplissage abusif. Il est tenu par trois choses :
 * la signature du site vitrine (personne d'autre ne parle a ce canal), les
 * seaux de debit du garde partage, et l'unicite (telephone, annee) qui
 * transforme un renvoi de formulaire en mise a jour plutot qu'en doublon.
 */
class CandidaturePortalController extends Controller
{
    public function __construct(private readonly PortailCandidatureService $candidatures) {}

    /**
     * Les filieres et niveaux que l'ecole publie.
     *
     * Des noms, rien d'autre : ni effectif, ni place restante. Un candidat n'a
     * pas a savoir quelle classe est pleine, et le publier renseignerait un
     * concurrent sur l'etat de l'ecole.
     */
    public function choix(): JsonResponse
    {
        return response()->json($this->candidatures->choixPublies());
    }

    public function submit(PortailCandidatureRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $candidature = $this->candidatures->deposer(
            $donnees,
            $this->empreinte($donnees['ip_client'])
        );

        if ($candidature === null) {
            // L'ecole n'a pas d'annee visee : c'est un defaut de configuration
            // de son cote, pas une faute du candidat. On ne lui demande donc
            // pas de verifier sa saisie.
            return response()->json([
                'enregistre' => false,
                'message' => "Les inscriptions ne sont pas encore configurées pour cette rentrée. Rapprochez-vous de l'établissement.",
            ], 503);
        }

        return response()->json([
            'enregistre' => true,
            'message' => 'Votre candidature a bien été transmise à l\'établissement.',
        ], 201);
    }

    /**
     * Empreinte de l'adresse du visiteur : de quoi voir un abus, pas de quoi
     * identifier une personne. La cle applicative sert de sel.
     */
    private function empreinte(string $adresse): string
    {
        return hash_hmac('sha256', $adresse, (string) config('app.key'));
    }
}

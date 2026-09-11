<?php

namespace App\Services\RendezVous;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Services\Reinscription\PortailReinscriptionService;

/**
 * Combien de familles l'ecole doit recevoir cette rentree.
 *
 * Le chiffre qui manque a l'ecran de reglage. Sans lui, une ecole lit
 * « vingt-huit places par jour », trouve cela raisonnable, et decouvre en
 * novembre qu'il lui fallait quatre-vingt-huit jours d'ouverture pour deux mille
 * quatre cent cinquante eleves.
 *
 * L'annee est un PARAMETRE, et c'est tout l'objet de cette classe.
 * BulkReinscriptionService, qui repond a la meme question pour son ecran, lit
 * `is_current`. Le portail, lui, vise `inscriptions.annee_cible`. Les deux
 * divergent par conception : une ecole ouvre sa rentree avant d'avoir clos
 * l'annee precedente, parce que la saisie des notes continue sur l'annee
 * courante — c'est la situation d'ESBTP Abidjan, et le depot la nomme. Compter
 * sur `is_current` y rendrait la cohorte de l'annee d'avant : un chiffre
 * plausible, du bon ordre de grandeur, et faux sans que rien ne le dise.
 *
 * Des `count()`, jamais une collection. La question posee est un nombre, et
 * hydrater deux mille cinq cents eleves avec leurs relations pour les compter
 * ferait ramer l'ecran de reglages a chaque frappe.
 */
final class VolumeARecevoir
{
    public function __construct(private readonly PortailReinscriptionService $saison) {}

    /**
     * @return array{
     *     annee: ?string,
     *     reinscriptions: int,
     *     candidatures: int,
     *     total: int,
     *     candidatures_previsibles: bool,
     * }
     */
    public function pourLaRentree(): array
    {
        $annee = $this->saison->anneeCible();

        if ($annee === null) {
            return [
                'annee' => null,
                'reinscriptions' => 0,
                'candidatures' => 0,
                'total' => 0,
                'candidatures_previsibles' => false,
            ];
        }

        $reinscriptions = $this->aReinscrire($annee);
        $candidatures = $this->candidaturesEnAttente($annee);

        return [
            'annee' => $annee->name,
            'reinscriptions' => $reinscriptions,
            'candidatures' => $candidatures,
            'total' => $reinscriptions + $candidatures,

            // Personne ne sait combien de bacheliers deposeront. Le chiffre des
            // candidatures est un CONSTAT, pas une prevision, et l'ecran doit le
            // dire pour ce qu'il est : il grossira toute la campagne.
            'candidatures_previsibles' => false,
        ];
    }

    /**
     * Les eleves qui ont encore leur reinscription a faire.
     *
     * Memes criteres que l'ecran de reinscription en masse — une inscription
     * active et menee a son terme sur l'annee precedente, aucune sur l'annee
     * visee — mais comptes, et sur l'annee du portail.
     */
    public function aReinscrire(ESBTPAnneeUniversitaire $annee): int
    {
        $precedente = ESBTPAnneeUniversitaire::where('end_date', '<', $annee->start_date)
            ->orderByDesc('end_date')
            ->first();

        if ($precedente === null) {
            return 0;
        }

        return ESBTPEtudiant::query()
            ->whereHas('inscriptions', function ($requete) use ($precedente) {
                $requete->where('annee_universitaire_id', $precedente->id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree');
            })
            ->whereDoesntHave('inscriptions', function ($requete) use ($annee) {
                $requete->where('annee_universitaire_id', $annee->id);
            })
            ->count();
    }

    /** Les candidatures deja deposees et pas encore traitees. */
    public function candidaturesEnAttente(ESBTPAnneeUniversitaire $annee): int
    {
        return ESBTPCandidature::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('statut', ESBTPCandidature::STATUT_EN_ATTENTE)
            ->count();
    }
}

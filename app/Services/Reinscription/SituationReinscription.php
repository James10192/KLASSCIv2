<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;

/**
 * La situation d'un etudiant vis-a-vis de la reinscription, evaluee une fois.
 *
 * Cet objet existe pour une raison precise : tant que la consultation et le
 * depot recalculaient chacun de leur cote l'annee cible, l'inscription
 * precedente et l'existence d'une reinscription, le depot pouvait ignorer un
 * invariant que la consultation avait pourtant calcule. C'est exactement ce
 * qui s'est produit : le portail annoncait « non eligible » et acceptait quand
 * meme la demande, ouvrant la voie a une seconde inscription, donc a une
 * seconde facturation de la meme famille.
 *
 * En passant l'objet entier au depot plutot que l'etudiant seul, oublier
 * l'invariant devient impossible : il est dans la main de l'appelant.
 *
 * Toutes les proprietes sont non nulles. Une situation qui n'existe pas n'est
 * pas un objet aux champs vides, c'est `null` : `evaluer()` le rend, et le
 * seul test `=== null` remplace le predicat « cet objet est-il exploitable ? »
 * qu'il faudrait sinon penser a appeler avant chaque dereferencement.
 */
class SituationReinscription
{
    public function __construct(
        public readonly ESBTPEtudiant $etudiant,
        public readonly ESBTPAnneeUniversitaire $anneeCible,
        public readonly ESBTPInscription $inscriptionPrecedente,
        public readonly bool $dejaReinscrit,
        public readonly bool $demandeExistante,
    ) {}

    /**
     * Volontairement grossier : l'ecole tranche a la conversion, en voyant le
     * solde et l'historique. Le portail ne prejuge pas — il verifie seulement
     * qu'il n'y a pas deja une inscription vivante pour l'annee visee.
     */
    public function eligible(): bool
    {
        return ! $this->dejaReinscrit;
    }

    public function classeActuelle(): ?string
    {
        return $this->inscriptionPrecedente->classe?->name;
    }

    public function anneeCibleLibelle(): ?string
    {
        return $this->anneeCible->display_name;
    }
}

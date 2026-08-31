<?php

namespace Tests\Unit\Paiements;

use App\Models\ESBTPPaiement;
use App\Models\User;
use Tests\TestCase;

/**
 * Qui signe le recu.
 *
 * La regle etait recopiee dans trois gabarits — recu, apercu, avoir — et rien
 * n'aurait dit qu'une copie diverge : la difference n'apparait que sur un
 * papier imprime, et seulement pour les paiements qui empruntent le repli.
 *
 * Elle vit maintenant sur le modele, et ces tests la fixent.
 */
class SignataireRecuTest extends TestCase
{
    private function utilisateur(string $nom): User
    {
        $u = new User;
        $u->name = $nom;

        return $u;
    }

    private function paiement(?User $emetteur, ?User $validateur): ESBTPPaiement
    {
        $p = new ESBTPPaiement;
        $p->setRelation('creator', $emetteur);
        $p->setRelation('validatedBy', $validateur);

        return $p;
    }

    /**
     * Le cas courant, et le seul qui compte vraiment : le cachet engage celui
     * qui a recu l'argent et remis le papier.
     */
    public function test_l_emetteur_signe_meme_quand_un_autre_a_valide(): void
    {
        $paiement = $this->paiement(
            $this->utilisateur('SALL AMINATA'),
            $this->utilisateur('KOUAME PAUL')
        );

        $this->assertSame('SALL AMINATA', $paiement->signataire);
    }

    /**
     * Les recus anciens n'ont pas toujours de createur enregistre. Le
     * validateur vaut alors mieux que rien : c'est la seule personne connue.
     */
    public function test_sans_emetteur_le_validateur_signe(): void
    {
        $paiement = $this->paiement(null, $this->utilisateur('KOUAME PAUL'));

        $this->assertSame('KOUAME PAUL', $paiement->signataire);
    }

    /**
     * Ni l'un ni l'autre : on nomme la fonction plutot que de laisser la ligne
     * vide, qu'un lecteur prendrait pour un oubli d'impression.
     */
    public function test_sans_personne_connue_on_nomme_la_fonction(): void
    {
        $this->assertSame('Le Comptable', $this->paiement(null, null)->signataire);
    }
}

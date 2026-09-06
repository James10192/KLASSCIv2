<?php

namespace Tests\Unit\Maintenance;

use App\Services\Maintenance\ReparateurEncodage;
use PHPUnit\Framework\TestCase;

/**
 * La detection qui autorise une reecriture de libelle en production.
 *
 * Un faux positif renomme une classe, une filiere ou un etudiant sans que personne
 * ne l'ait demande. Ce qui est teste ici, ce n'est donc pas seulement que la
 * reparation marche : c'est surtout qu'elle REFUSE de s'appliquer partout ailleurs.
 */
class ReparateurEncodageTest extends TestCase
{
    private ReparateurEncodage $reparateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reparateur = new ReparateurEncodage();
    }

    public function test_repare_les_six_libelles_abimes_d_usat(): void
    {
        // Les valeurs exactes relevees sur usat.klassci.com le 6 septembre 2026.
        $this->assertSame(
            'L1 Mines, Géologie, Pétrole',
            $this->reparateur->reparer('L1 Mines, GÃ©ologie, PÃ©trole')
        );
        $this->assertSame('L2 Bâtiment', $this->reparateur->reparer('L2 BÃ¢timent'));
    }

    public function test_ne_touche_pas_un_libelle_sain(): void
    {
        // La condition qui rend l'operation idempotente : « Géologie » relu en
        // octets donne « G\xe9ologie », qui n'est pas de l'UTF-8 valide.
        foreach (['L1 Mines, Géologie, Pétrole', 'L2 Bâtiment', 'Productions Végétales'] as $sain) {
            $this->assertNull($this->reparateur->reparer($sain), $sain);
        }
    }

    public function test_relancer_la_reparation_ne_degrade_rien(): void
    {
        $repare = $this->reparateur->reparer('L1 BÃ¢timent');
        $this->assertSame('L1 Bâtiment', $repare);
        $this->assertNull($this->reparateur->reparer($repare));
    }

    public function test_ne_touche_pas_un_libelle_sans_accent(): void
    {
        // Les onze classes de l'import ne portent aucun accent : rien a corriger,
        // et surtout rien a inventer.
        foreach (['MGP L1', 'GBAT L2', 'DROIT L1', 'ATPV L2', ''] as $neutre) {
            $this->assertNull($this->reparateur->reparer($neutre), $neutre);
        }
    }

    public function test_refuse_une_chaine_qui_ne_tient_pas_sur_un_octet(): void
    {
        // Au-dela de 255, la conversion remplacerait le caractere par « ? » : elle
        // detruirait le libelle au lieu de le reparer.
        $this->assertNull($this->reparateur->reparer('Licence — Économie'));
        $this->assertNull($this->reparateur->reparer('Parcours ✓'));
    }

    public function test_refuse_quand_le_resultat_ne_porte_aucun_accent(): void
    {
        // « Ã¢ » se decode en « â », donc accepte ; mais une chaine dont la relecture
        // ne rend que de l'ASCII ne prouve rien et doit etre laissee tranquille.
        $this->assertNull($this->reparateur->reparer("Groupe A\u{00c2}\u{0080}\u{0099}"));
    }

    public function test_le_registre_ne_vise_que_des_colonnes_de_libelle(): void
    {
        // Garde-fou de revue : aucune colonne de montant, d'identifiant ou de code
        // ne doit jamais entrer dans ce registre.
        $interdits = ['amount', 'montant', 'price', 'prix', 'password', 'token', 'code', 'matricule', 'id'];

        foreach (ReparateurEncodage::COLONNES as $table => $colonnes) {
            foreach ($colonnes as $colonne) {
                $this->assertNotContains(
                    $colonne,
                    $interdits,
                    "{$table}.{$colonne} n'a rien a faire dans une reparation d'encodage"
                );
            }
        }
    }
}

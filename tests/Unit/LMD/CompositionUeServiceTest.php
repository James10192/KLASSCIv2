<?php

namespace Tests\Unit\LMD;

use App\Services\LMD\CompositionUeService;
use PHPUnit\Framework\TestCase;

/**
 * Composition d'une unité d'enseignement, maquette par maquette.
 *
 * Ce sont des tests d'ABSENCE : ils vérifient que ce qui appartient à une
 * maquette ne fuit pas dans l'autre, et qu'une écriture visant une maquette
 * n'en touche pas une autre. Un test de présence ne les attraperait pas — le
 * défaut d'origine rendait justement TROP d'éléments, jamais trop peu.
 *
 * Les fonctions testées sont pures : aucune base, aucun Eloquent.
 */
class CompositionUeServiceTest extends TestCase
{
    private const COMMUN = CompositionUeService::TOUTES_MAQUETTES;
    private const BU = 11;   // Bâtiment et Urbanisme
    private const TIR = 22;  // Travaux Publics

    /** @test */
    public function un_element_reserve_a_batiment_n_apparait_pas_dans_travaux_publics(): void
    {
        $lignes = [
            $this->ligne(matiere: 100, maquette: self::COMMUN),
            $this->ligne(matiere: 200, maquette: self::BU),
        ];

        $vuDeTir = CompositionUeService::resoudre($lignes, self::TIR);

        $this->assertNotContains(200, array_column($vuDeTir, 'matiere_id'));
        $this->assertSame([100], array_column($vuDeTir, 'matiere_id'));
    }

    /** @test */
    public function une_maquette_voit_le_commun_et_ce_qui_lui_est_reserve(): void
    {
        $lignes = [
            $this->ligne(matiere: 100, maquette: self::COMMUN),
            $this->ligne(matiere: 200, maquette: self::BU),
            $this->ligne(matiere: 300, maquette: self::TIR),
        ];

        $vuDeBu = CompositionUeService::resoudre($lignes, self::BU);

        $this->assertSame([100, 200], array_column($vuDeBu, 'matiere_id'));
    }

    /** @test */
    public function creer_un_element_reserve_ne_modifie_pas_la_ligne_commune(): void
    {
        // La matière est déjà commune à toute l'unité.
        $lignesDuCouple = [['parcours_id' => self::COMMUN]];

        $plan = CompositionUeService::planifierPose($lignesDuCouple, self::BU, strict: true);

        // Le geste est refusé : aucune insertion, et surtout aucune mise à jour
        // de la ligne commune, qui appartient aussi aux autres maquettes.
        $this->assertSame('deja_commun', $plan['refus']);
        $this->assertNull($plan['inserer']);
        $this->assertSame([], $plan['mettre_a_jour']);
    }

    /** @test */
    public function creer_un_element_reserve_quand_l_unite_ne_l_a_pas_insere_sur_la_maquette(): void
    {
        $plan = CompositionUeService::planifierPose([], self::BU, strict: true);

        $this->assertNull($plan['refus']);
        $this->assertSame(self::BU, $plan['inserer']);
        $this->assertSame([], $plan['mettre_a_jour']);
    }

    /** @test */
    public function reserver_a_batiment_ne_touche_pas_la_reservation_de_travaux_publics(): void
    {
        $lignesDuCouple = [['parcours_id' => self::TIR]];

        $plan = CompositionUeService::planifierPose($lignesDuCouple, self::BU, strict: true);

        $this->assertSame(self::BU, $plan['inserer']);
        $this->assertNotContains(self::TIR, $plan['mettre_a_jour']);
    }

    /** @test */
    public function enregistrer_depuis_une_maquette_met_a_jour_sa_propre_ligne_seulement(): void
    {
        $lignesDuCouple = [
            ['parcours_id' => self::BU],
            ['parcours_id' => self::TIR],
        ];

        $plan = CompositionUeService::planifierPose($lignesDuCouple, self::BU);

        $this->assertNull($plan['inserer']);
        $this->assertSame([self::BU], $plan['mettre_a_jour']);
    }

    /** @test */
    public function poser_le_commun_sur_un_element_deja_reserve_n_ajoute_aucune_ligne(): void
    {
        // Sinon l'élément existerait deux fois — une ligne commune et une ligne
        // réservée — et compterait deux fois dans la moyenne et les crédits.
        $lignesDuCouple = [
            ['parcours_id' => self::BU],
            ['parcours_id' => self::TIR],
        ];

        $plan = CompositionUeService::planifierPose($lignesDuCouple, self::COMMUN);

        $this->assertNull($plan['inserer']);
        $this->assertSame([self::BU, self::TIR], $plan['mettre_a_jour']);
    }

    /** @test */
    public function retirer_un_element_de_batiment_ne_le_retire_pas_de_travaux_publics(): void
    {
        $lignesDuCouple = [
            ['parcours_id' => self::BU],
            ['parcours_id' => self::TIR],
        ];

        $plan = CompositionUeService::planifierRetrait($lignesDuCouple, self::BU, [self::BU, self::TIR]);

        $this->assertSame([self::BU], $plan['supprimer']);
        $this->assertNotContains(self::TIR, $plan['supprimer']);
        $this->assertTrue($plan['reste'], 'La clé étrangère ne doit pas être libérée : Travaux Publics garde l\'élément.');
    }

    /** @test */
    public function retirer_un_element_commun_d_une_seule_maquette_le_reserve_aux_autres(): void
    {
        $lignesDuCouple = [['parcours_id' => self::COMMUN]];

        $plan = CompositionUeService::planifierRetrait($lignesDuCouple, self::BU, [self::BU, self::TIR]);

        $this->assertSame([self::COMMUN], $plan['supprimer']);
        $this->assertSame([self::TIR], $plan['reserver']);
        $this->assertTrue($plan['reste']);
    }

    /** @test */
    public function sans_maquette_de_travail_le_retrait_porte_sur_toute_l_unite(): void
    {
        $lignesDuCouple = [
            ['parcours_id' => self::COMMUN],
            ['parcours_id' => self::BU],
        ];

        $plan = CompositionUeService::planifierRetrait($lignesDuCouple, self::COMMUN, [self::BU, self::TIR]);

        $this->assertSame([self::COMMUN, self::BU], $plan['supprimer']);
        $this->assertFalse($plan['reste'], 'Plus aucune ligne : la clé étrangère peut être libérée.');
    }

    /** @test */
    public function une_matiere_n_est_jamais_comptee_deux_fois(): void
    {
        // Cas dégradé que la base ne sait pas interdire : la même matière commune
        // ET réservée. Sans déduplication, sa note pèserait double dans la
        // moyenne de l'unité et son crédit compterait deux fois.
        $lignes = [
            $this->ligne(matiere: 100, maquette: self::COMMUN, credit: 3),
            $this->ligne(matiere: 100, maquette: self::BU, credit: 5),
        ];

        $vuDeBu = CompositionUeService::resoudre($lignes, self::BU);

        $this->assertCount(1, $vuDeBu);
        // La ligne réservée prime : c'est elle qui porte le crédit voulu par BU.
        $this->assertSame(5, $vuDeBu[0]['credit_ecue']);
    }

    /** @test */
    public function sans_maquette_de_lecture_la_composition_entiere_est_rendue_sans_doublon(): void
    {
        $lignes = [
            $this->ligne(matiere: 100, maquette: self::COMMUN),
            $this->ligne(matiere: 200, maquette: self::BU),
            $this->ligne(matiere: 200, maquette: self::TIR),
            $this->ligne(matiere: 300, maquette: self::TIR),
        ];

        $tout = CompositionUeService::resoudre($lignes, null);

        $this->assertSame([100, 200, 300], array_column($tout, 'matiere_id'));
    }

    /** @param int $credit crédit propre à la ligne */
    private function ligne(int $matiere, int $maquette, int $credit = 3): array
    {
        return [
            'matiere_id' => $matiere,
            'parcours_id' => $maquette,
            'coefficient_ecue' => 1.0,
            'credit_ecue' => $credit,
            'ordre_bulletin' => 0,
        ];
    }
}

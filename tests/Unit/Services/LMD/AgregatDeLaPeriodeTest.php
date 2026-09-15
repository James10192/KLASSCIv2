<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\AgregatDeLaPeriode;
use PHPUnit\Framework\TestCase;

/**
 * L'agrégat d'une période délibérée.
 *
 * `PHPUnit\Framework\TestCase` et non celui de Laravel : cette classe ne touche
 * ni la base ni le conteneur, et ces cas doivent rester exécutables là où
 * `RefreshDatabase` ne l'est pas — c'est la seule façon de les rejouer vite.
 */
class AgregatDeLaPeriodeTest extends TestCase
{
    /** Un bulletin minimal, sans Eloquent : l'agrégat ne lit que ces champs. */
    private function bulletin(?int $id, ?int $semestre, ?float $moyenne, ?int $capitalises, ?int $totaux): object
    {
        return (object) [
            'id' => $id,
            'semestre' => $semestre,
            'moyenne_generale' => $moyenne,
            'credits_capitalises' => $capitalises,
            'credits_totaux' => $totaux,
        ];
    }

    // --- Le cas semestriel doit se comporter EXACTEMENT comme avant ---

    public function test_un_seul_bulletin_rend_sa_moyenne_telle_quelle(): void
    {
        $periode = [$this->bulletin(1, 1, 11.37, 28, 30)];

        $this->assertSame(11.37, AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_un_seul_bulletin_sans_credits_rend_quand_meme_sa_moyenne(): void
    {
        // Le cas semestriel ne doit PAS exiger de crédits pour rendre une
        // moyenne : l'ancien code lisait `moyenne_generale` directement.
        $periode = [$this->bulletin(1, 1, 9.5, null, null)];

        $this->assertSame(9.5, AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_aucun_bulletin_ne_rend_aucune_moyenne(): void
    {
        $this->assertNull(AgregatDeLaPeriode::moyenne([]));
        $this->assertFalse(AgregatDeLaPeriode::creditsDisponibles([]));
    }

    // --- Le cas annuel : c'est lui qui était faux ---

    public function test_deux_semestres_sont_ponderes_par_leurs_credits(): void
    {
        // 12 sur 30 crédits, 10 sur 30 crédits → (12*30 + 10*30) / 60 = 11.00
        $periode = [
            $this->bulletin(1, 1, 12.0, 30, 30),
            $this->bulletin(2, 2, 10.0, 24, 30),
        ];

        $this->assertSame(11.0, AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_la_ponderation_suit_les_credits_et_non_le_nombre_de_semestres(): void
    {
        // 16 sur 10 crédits, 10 sur 50 crédits. La moyenne arithmétique
        // donnerait 13.00 ; la pondérée donne 11.00. C'est la seconde qui est
        // juste, et c'est l'écart que le défaut produisait.
        $periode = [
            $this->bulletin(1, 1, 16.0, 10, 10),
            $this->bulletin(2, 2, 10.0, 40, 50),
        ];

        $this->assertSame(11.0, AgregatDeLaPeriode::moyenne($periode));
        $this->assertNotEquals(13.0, AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_un_semestre_a_zero_pese_au_lieu_de_disparaitre(): void
    {
        // Zéro est un résultat — celui de l'étudiant absent tout le semestre —
        // et non une absence de résultat. C'est la distinction sur laquelle la
        // fiche étudiant se trompait : elle filtrait `moyenne_generale > 0`,
        // donc ce semestre sortait du calcul, il n'en restait qu'un, et la page
        // annonçait 8,50 comme moyenne de l'ANNÉE au lieu de 4,25.
        //
        // À lire pour ce qu'il est : un cas de CARACTÉRISATION, pas de
        // non-régression. `AgregatDeLaPeriode` n'a jamais filtré `> 0`, donc ce
        // cas passait déjà avant que les appelants ne lui soient confiés. Il ne
        // couvre pas la correction ; il verrouille la distinction du côté où le
        // filtre ne doit plus revenir. Ce qui couvre la correction est
        // `tests/Unit/Services/MoyenneAnnuelleDuParcoursTest`.
        //
        // Le comparer à `test_un_semestre_non_calculable…`, juste en dessous,
        // qui montre le cas où l'absence est réelle.
        $periode = [
            $this->bulletin(1, 1, 8.5, 0, 30),
            $this->bulletin(2, 2, 0.0, 0, 30),
        ];

        $this->assertSame(4.25, AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_les_credits_de_la_periode_sont_sommes(): void
    {
        $periode = [
            $this->bulletin(1, 1, 12.0, 28, 30),
            $this->bulletin(2, 2, 10.0, 24, 30),
        ];

        $this->assertTrue(AgregatDeLaPeriode::creditsDisponibles($periode));
        $this->assertSame(52, AgregatDeLaPeriode::creditsObtenus($periode));
        $this->assertSame(60, AgregatDeLaPeriode::creditsAttendus($periode));
    }

    public function test_un_semestre_non_calculable_rend_la_periode_non_calculable(): void
    {
        // Plutôt qu'une moyenne plausible sur la moitié de l'année. La décision
        // part alors en « defere », ce qui est la vérité.
        $periode = [
            $this->bulletin(1, 1, 12.0, 30, 30),
            $this->bulletin(2, 2, null, 24, 30),
        ];

        $this->assertNull(AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_un_semestre_a_zero_credit_rend_la_periode_non_calculable(): void
    {
        // Diviser par la somme des crédits exige qu'ils existent. Zéro crédit
        // sur un semestre annulerait son poids en silence.
        $periode = [
            $this->bulletin(1, 1, 12.0, 0, 0),
            $this->bulletin(2, 2, 10.0, 24, 30),
        ];

        $this->assertNull(AgregatDeLaPeriode::moyenne($periode));
    }

    public function test_un_credit_manquant_rend_les_credits_indisponibles(): void
    {
        $periode = [
            $this->bulletin(1, 1, 12.0, 30, 30),
            $this->bulletin(2, 2, 10.0, null, 30),
        ];

        $this->assertFalse(AgregatDeLaPeriode::creditsDisponibles($periode));
    }

    // --- La déduplication : un bulletin régénéré ne compte pas deux fois ---

    public function test_deux_bulletins_du_meme_semestre_ne_comptent_qu_une_fois(): void
    {
        $periode = AgregatDeLaPeriode::parSemestre([
            $this->bulletin(1, 1, 8.0, 12, 30),
            $this->bulletin(7, 1, 14.0, 30, 30),
        ]);

        $this->assertCount(1, $periode);
        // Le dernier écrit gagne — ce que faisait déjà `orderByDesc('id')->first()`.
        $this->assertSame(7, $periode[0]->id);
        $this->assertSame(14.0, AgregatDeLaPeriode::moyenne($periode));
        $this->assertSame(30, AgregatDeLaPeriode::creditsAttendus($periode));
    }

    public function test_les_semestres_sont_ordonnes_du_plus_ancien_au_plus_recent(): void
    {
        $periode = AgregatDeLaPeriode::parSemestre([
            $this->bulletin(9, 2, 10.0, 24, 30),
            $this->bulletin(4, 1, 12.0, 30, 30),
        ]);

        $this->assertSame([1, 2], array_map(fn ($b) => $b->semestre, $periode));
    }

    public function test_un_semestre_nul_ne_fait_pas_disparaitre_le_bulletin(): void
    {
        // Un jury annuel dont les bulletins ne portent pas de semestre reste une
        // période d'un seul élément, pas une période vide.
        $periode = AgregatDeLaPeriode::parSemestre([
            $this->bulletin(3, null, 11.0, 55, 60),
        ]);

        $this->assertCount(1, $periode);
        $this->assertSame(11.0, AgregatDeLaPeriode::moyenne($periode));
    }

    // --- La concordance, telle que le garde d'émission la lit ---

    public function test_deux_moyennes_egales_concordent(): void
    {
        $this->assertTrue(AgregatDeLaPeriode::moyennesConcordent(11.20, 11.20));
    }

    public function test_un_ecart_d_arrondi_concorde(): void
    {
        // Les moyennes sont arrondies au centième avant d'être écrites :
        // comparer à l'égalité stricte ferait échouer sur un flottant
        // reconstitué, et bloquerait des procès-verbaux justes.
        $this->assertTrue(AgregatDeLaPeriode::moyennesConcordent(11.20, 11.2000001));
    }

    public function test_un_ecart_reel_ne_concorde_pas(): void
    {
        // Le cas de la réclamation aboutie : la décision gelée dit 9.40, les
        // bulletins disent 11.20. Le PV ne doit pas sceller cet écart.
        $this->assertFalse(AgregatDeLaPeriode::moyennesConcordent(9.40, 11.20));
    }

    public function test_deux_absences_de_moyenne_concordent(): void
    {
        $this->assertTrue(AgregatDeLaPeriode::moyennesConcordent(null, null));
    }

    public function test_une_moyenne_apparue_ne_concorde_pas_avec_une_absence(): void
    {
        $this->assertFalse(AgregatDeLaPeriode::moyennesConcordent(null, 11.20));
        $this->assertFalse(AgregatDeLaPeriode::moyennesConcordent(11.20, null));
    }
}

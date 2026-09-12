<?php

namespace Tests\Unit\RendezVous;

use App\Services\RendezVous\ConfigurationRendezVous as Config;
use App\Services\RendezVous\GenerateurCreneaux;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * La grille des rendez-vous de guichet, eprouvee sans base de donnees.
 *
 * Sans base, deliberement. Ce que ces tests protegent, ce sont des regles
 * d'arithmetique et de refus — et une regle qu'on ne peut eprouver qu'avec
 * MySQL finit par n'etre eprouvee que par les ecoles.
 *
 * Deux familles de cas, et la seconde compte autant que la premiere : ce que la
 * grille produit quand la configuration est bonne, et ce qu'elle REFUSE quand
 * elle ne l'est pas.
 */
class GrilleDesCreneauxTest extends TestCase
{
    /**
     * Une journee de guichet ordinaire, telle qu'une ecole la decrirait.
     *
     * 8h-16h, une heure de pause a midi, un quart d'heure par famille, un seul
     * guichet. Les tests qui suivent ne changent qu'un parametre a la fois.
     *
     * @param  array<string, mixed>  $modifications
     */
    private function config(array $modifications = []): Config
    {
        return Config::depuisLesValeurs(array_merge([
            Config::REGLAGE_ACTIF => '1',
            Config::REGLAGE_PREMIER_JOUR => '2026-09-14',   // un lundi
            Config::REGLAGE_DERNIER_JOUR => '2026-10-30',
            Config::REGLAGE_JOURS_OUVERTS => '1,2,3,4,5',
            Config::REGLAGE_OUVERTURE => '08:00',
            Config::REGLAGE_FERMETURE => '16:00',
            Config::REGLAGE_PAUSE_DEBUT => '12:00',
            Config::REGLAGE_PAUSE_FIN => '13:00',
            Config::REGLAGE_DUREE => '15',
            Config::REGLAGE_CAPACITE => '1',
        ], $modifications));
    }

    private function grille(array $modifications = []): GenerateurCreneaux
    {
        return new GenerateurCreneaux($this->config($modifications));
    }

    // -----------------------------------------------------------------
    // Ce que la grille produit
    // -----------------------------------------------------------------

    /** @test */
    public function une_journee_de_huit_heures_moins_la_pause_donne_vingt_huit_creneaux(): void
    {
        $grille = $this->grille();

        $this->assertSame([], $grille->configuration()->problemes);
        $this->assertSame(28, $grille->creneauxParJour());
        $this->assertSame(28, $grille->placesParJour());
    }

    /**
     * @test
     *
     * Le chiffre que le fondateur voulait saisir. Il est rendu, pas demande :
     * deux guichets tenus en parallele, ce sont les memes creneaux, deux fois
     * plus de familles.
     */
    public function deux_familles_a_la_fois_doublent_les_places_sans_toucher_a_la_grille(): void
    {
        $grille = $this->grille([Config::REGLAGE_CAPACITE => '2']);

        $this->assertSame(28, $grille->creneauxParJour());
        $this->assertSame(56, $grille->placesParJour());
    }

    /** @test */
    public function aucun_creneau_ne_mord_sur_la_pause(): void
    {
        $creneaux = $this->grille()->pourLeJour(CarbonImmutable::parse('2026-09-14'));

        foreach ($creneaux as $creneau) {
            $debut = $creneau->debut->format('H:i');
            $fin = $creneau->fin->format('H:i');

            $this->assertFalse(
                $debut < '13:00' && $fin > '12:00',
                "Le creneau {$debut}-{$fin} empiete sur la pause de midi.",
            );
        }

        // Le dernier creneau s'arrete PILE a l'heure de fermeture : la boucle
        // n'emet un creneau que s'il tient entier avant la fermeture.
        $dernier = end($creneaux);

        $this->assertSame('08:00', $creneaux[0]->debut->format('H:i'));
        $this->assertSame('15:45', $dernier->debut->format('H:i'));
        $this->assertSame('16:00', $dernier->fin->format('H:i'));
    }

    /** @test */
    public function le_guichet_ne_recoit_pas_les_jours_fermes(): void
    {
        $grille = $this->grille();

        // 2026-09-20 est un dimanche, et la configuration s'arrete au vendredi.
        $this->assertSame([], $grille->pourLeJour(CarbonImmutable::parse('2026-09-20')));

        // Mais la journee type, elle, comporte toujours ses creneaux : la
        // question « combien par jour » ne depend pas du calendrier.
        $this->assertSame(28, $grille->creneauxParJour());
    }

    /** @test */
    public function hors_campagne_il_n_y_a_pas_de_creneau(): void
    {
        $grille = $this->grille();

        $this->assertSame([], $grille->pourLeJour(CarbonImmutable::parse('2026-09-11')));
        $this->assertSame([], $grille->pourLeJour(CarbonImmutable::parse('2026-11-02')));
    }

    /**
     * @test
     *
     * Le chiffre que l'ecran doit montrer en septembre plutot qu'en novembre.
     */
    public function la_campagne_dit_combien_de_jours_il_faut_pour_recevoir_tout_le_monde(): void
    {
        $grille = $this->grille();

        // Du lundi 14 septembre au vendredi 30 octobre, jours ouvres seulement.
        $this->assertSame(35, $grille->joursDeReception());
        $this->assertSame(980, $grille->placesSurLaCampagne());

        // L'effectif d'ESBTP Abidjan contre une journee a 28 places.
        $this->assertSame(88, $grille->joursNecessairesPour(2450));
    }

    /** @test */
    public function sans_grille_la_question_du_nombre_de_jours_n_a_pas_de_reponse(): void
    {
        $grille = $this->grille([Config::REGLAGE_DUREE => '0']);

        $this->assertNull($grille->joursNecessairesPour(2450));
    }

    // -----------------------------------------------------------------
    // Ce que la grille refuse
    // -----------------------------------------------------------------

    /**
     * @test
     *
     * Zero n'est pas une valeur, et le depot a deja paye de le croire :
     * ESBTPFraisOption transforme une capacite de zero en capacite illimitee
     * dans deux chemins sur trois. Ici, zero est refuse a la lecture.
     *
     * @dataProvider valeursRefusees
     */
    public function une_configuration_incoherente_est_refusee_et_dit_pourquoi(array $modifications): void
    {
        $config = $this->config($modifications);

        $this->assertNotSame([], $config->problemes);
        $this->assertFalse($config->estUtilisable());
        $this->assertFalse($config->estComplete());
        $this->assertSame(0, (new GenerateurCreneaux($config))->placesParJour());
    }

    public static function valeursRefusees(): array
    {
        return [
            'duree nulle' => [[Config::REGLAGE_DUREE => '0']],
            'duree absurde' => [[Config::REGLAGE_DUREE => '10000']],
            'capacite nulle' => [[Config::REGLAGE_CAPACITE => '0']],
            'fermeture avant ouverture' => [[Config::REGLAGE_OUVERTURE => '16:00', Config::REGLAGE_FERMETURE => '08:00']],
            'fermeture egale a l ouverture' => [[Config::REGLAGE_FERMETURE => '08:00']],
            'heure illisible' => [[Config::REGLAGE_OUVERTURE => '8h']],
            'heure inexistante' => [[Config::REGLAGE_OUVERTURE => '25:00']],
            'pause a moitie saisie' => [[Config::REGLAGE_PAUSE_FIN => '']],
            'pause inversee' => [[Config::REGLAGE_PAUSE_DEBUT => '13:00', Config::REGLAGE_PAUSE_FIN => '12:00']],
            'pause hors de la plage' => [[Config::REGLAGE_PAUSE_DEBUT => '07:00', Config::REGLAGE_PAUSE_FIN => '09:00']],
            'plage trop courte pour un creneau' => [[Config::REGLAGE_FERMETURE => '08:10', Config::REGLAGE_PAUSE_DEBUT => '', Config::REGLAGE_PAUSE_FIN => '']],
            'aucun jour ouvert' => [[Config::REGLAGE_JOURS_OUVERTS => '']],
            'jour illisible' => [[Config::REGLAGE_JOURS_OUVERTS => 'lundi,mardi']],
            'jour hors bornes' => [[Config::REGLAGE_JOURS_OUVERTS => '1,8']],
            'premier jour absent' => [[Config::REGLAGE_PREMIER_JOUR => '']],
            'dernier jour absent' => [[Config::REGLAGE_DERNIER_JOUR => '']],
            'campagne a l envers' => [[Config::REGLAGE_PREMIER_JOUR => '2026-10-30', Config::REGLAGE_DERNIER_JOUR => '2026-09-14']],
            // Une faute de frappe dans la case annee. Sans plafond, le parcours
            // du calendrier tourne des centaines de milliers de fois, trois fois
            // par requete : la page ne revient pas.
            'campagne interminable' => [[Config::REGLAGE_DERNIER_JOUR => '9999-12-31']],
            'date qui deborde le mois' => [[Config::REGLAGE_PREMIER_JOUR => '2026-02-31']],
            'date illisible' => [[Config::REGLAGE_DERNIER_JOUR => '30/10/2026']],
        ];
    }

    /**
     * @test
     *
     * Une plage qui ne laisse la place a aucun creneau doit le DIRE. Rendre un
     * tableau vide en silence se lirait « c'est complet » sur la vitrine.
     */
    public function une_plage_sans_creneau_possible_nomme_la_raison(): void
    {
        $problemes = $this->config([
            Config::REGLAGE_PAUSE_DEBUT => '08:00',
            Config::REGLAGE_PAUSE_FIN => '16:00',
        ])->problemes;

        $this->assertNotSame([], $problemes);
        $this->assertStringContainsString('aucun creneau', implode(' ', $problemes));
    }

    /**
     * @test
     *
     * Une configuration complete mais eteinte se regle et se previsualise ;
     * elle ne se propose pas. C'est la distinction qui permet a l'ecole de
     * mettre sa grille au point avant d'ouvrir le canal.
     */
    public function une_configuration_complete_mais_eteinte_ne_se_propose_pas(): void
    {
        $config = $this->config([Config::REGLAGE_ACTIF => '0']);

        $this->assertTrue($config->estComplete());
        $this->assertFalse($config->estUtilisable());
        $this->assertSame(28, (new GenerateurCreneaux($config))->creneauxParJour());
    }
}

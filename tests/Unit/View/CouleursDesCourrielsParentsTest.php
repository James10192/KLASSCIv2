<?php

namespace Tests\Unit\View;

use App\View\Composers\CouleursDesCourrielsParents;
use Illuminate\View\View;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Les couleurs des courriels aux parents.
 *
 * Sans base, et c'est possible parce que le composeur lit les réglages
 * d'instance en second : `$donnees['…'] ?? SettingsHelper::get(…)`. PHP
 * n'évalue la droite du `??` que si la gauche manque, donc fournir les quatre
 * valeurs suffit à n'émettre aucune requête. Les cas couverts sont ceux qui
 * portent une DÉCISION — priorité de l'appelant, repli du fond d'en-tête sur la
 * couleur primaire, rejet d'une valeur mal formée.
 *
 * Ce qui n'est PAS couvert ici : la lecture des réglages elle-même, qui
 * demanderait MySQL.
 */
class CouleursDesCourrielsParentsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<string, string>
     */
    private function couleursPour(array $donnees): array
    {
        $posees = [];

        $view = Mockery::mock(View::class);
        $view->shouldReceive('getData')->andReturn($donnees);
        $view->shouldReceive('with')->once()->andReturnUsing(function (array $valeurs) use (&$posees) {
            $posees = $valeurs;
        });

        (new CouleursDesCourrielsParents)->compose($view);

        return $posees;
    }

    /** Les quatre couleurs d'une instance qui a tout configuré correctement. */
    private function quatreCouleurs(array $remplacements = []): array
    {
        return array_merge([
            'emailPrimaryColor' => '#123456',
            'emailHeaderBgColor' => '#abcdef',
            'emailHeaderTextColor' => '#ffffff',
            'emailSecondaryColor' => '#654321',
        ], $remplacements);
    }

    public function test_les_couleurs_de_l_appelant_priment(): void
    {
        $couleurs = $this->couleursPour($this->quatreCouleurs());

        $this->assertSame('#123456', $couleurs['emailPrimaryColor']);
        $this->assertSame('#abcdef', $couleurs['emailHeaderBgColor']);
        $this->assertSame('#ffffff', $couleurs['emailHeaderTextColor']);
        $this->assertSame('#654321', $couleurs['emailSecondaryColor']);
    }

    public function test_les_quatre_couleurs_sont_toujours_posees(): void
    {
        // C'est le contrat qui corrige le défaut : un modèle enfant lit ces
        // variables dans son `@section`, évalué AVANT le gabarit. Si l'une
        // manquait, on retomberait sur « Undefined variable ».
        $couleurs = $this->couleursPour($this->quatreCouleurs());

        $this->assertSame([
            'emailPrimaryColor',
            'emailHeaderBgColor',
            'emailHeaderTextColor',
            'emailSecondaryColor',
        ], array_keys($couleurs));
    }

    public function test_une_couleur_mal_formee_retombe_sur_le_defaut(): void
    {
        // Ces valeurs partent dans un attribut `style`, et un réglage
        // d'instance se saisit à la main.
        $couleurs = $this->couleursPour($this->quatreCouleurs([
            'emailPrimaryColor' => 'bleu KLASSCI',
            'emailSecondaryColor' => '#ABC',
        ]));

        $this->assertSame('#0453cb', $couleurs['emailPrimaryColor']);
        $this->assertSame('#64748b', $couleurs['emailSecondaryColor']);
    }

    public function test_un_fond_d_en_tete_mal_forme_retombe_sur_la_couleur_primaire(): void
    {
        // Et non sur la valeur d'usine : une école qui a choisi sa couleur sans
        // choisir son en-tête doit voir la sienne.
        $couleurs = $this->couleursPour($this->quatreCouleurs([
            'emailPrimaryColor' => '#7a0000',
            'emailHeaderBgColor' => '',
        ]));

        $this->assertSame('#7a0000', $couleurs['emailHeaderBgColor']);
    }

    public function test_la_casse_hexadecimale_est_acceptee_telle_quelle(): void
    {
        $couleurs = $this->couleursPour($this->quatreCouleurs([
            'emailPrimaryColor' => '#AbCdEf',
        ]));

        $this->assertSame('#AbCdEf', $couleurs['emailPrimaryColor']);
    }
}

<?php

namespace Tests\Unit\Securite;

use App\Domain\Securite\CodesDeSecours;
use PHPUnit\Framework\TestCase;

/**
 * Les codes qui rendent la double authentification supportable.
 *
 * Un second facteur sans porte de secours n'est pas une securite, c'est un
 * risque deplace : le telephone se perd, et l'ecole se retrouve sans son
 * secretariat un matin de rentree.
 */
class CodesDeSecoursTest extends TestCase
{
    public function test_on_en_remet_assez_pour_ne_pas_etre_a_court(): void
    {
        $codes = CodesDeSecours::generer();

        $this->assertCount(8, $codes);
        $this->assertSame(8, count(array_unique($codes)), 'deux codes identiques dans un meme jeu');
    }

    public function test_le_format_se_recopie_a_la_main(): void
    {
        // On les recopie depuis un papier, sous le stress de ne plus pouvoir
        // se connecter. Le tiret coupe la lecture en deux.
        foreach (CodesDeSecours::generer() as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $code);
        }
    }

    public function test_l_alphabet_ecarte_les_caracteres_qu_on_confond(): void
    {
        // 0 et O, 1 et I et L : on les confond en ecrivant, et le code est
        // refuse pour une raison que la personne ne peut pas voir.
        $tous = implode('', CodesDeSecours::generer());

        foreach (['0', 'O', '1', 'I', 'L'] as $ambigu) {
            $this->assertStringNotContainsString($ambigu, $tous, "caractere ambigu : {$ambigu}");
        }
    }

    public function test_les_codes_ne_sont_pas_previsibles(): void
    {
        // Ces codes contournent le second facteur : ils valent le mot de
        // passe. Deux jeux successifs ne doivent rien partager.
        $premier = CodesDeSecours::generer();
        $second = CodesDeSecours::generer();

        $this->assertEmpty(array_intersect($premier, $second));
    }

    public function test_un_code_ne_sert_qu_une_fois(): void
    {
        // Sans cela, un code lu par-dessus une epaule vaut un acces
        // permanent, et l'on a seulement remplace un mot de passe par un
        // autre, en moins bien.
        $codes = CodesDeSecours::generer();
        $premier = $codes[0];

        $restants = CodesDeSecours::consommer($codes, $premier);

        $this->assertCount(7, $restants);
        $this->assertNotContains($premier, $restants);
        $this->assertNull(CodesDeSecours::consommer($restants, $premier), 'le code a resservi');
    }

    public function test_un_code_faux_ne_consomme_rien(): void
    {
        $codes = CodesDeSecours::generer();

        $this->assertNull(CodesDeSecours::consommer($codes, 'ZZZZ-ZZZZ'));
        $this->assertNull(CodesDeSecours::consommer($codes, ''));
    }

    public function test_un_code_juste_mal_recopie_est_accepte(): void
    {
        // Avec ou sans le tiret, en minuscules, avec une espace collee par le
        // presse-papiers. Refuser un bon code parce qu'il a ete tape en
        // minuscules fabrique un appel au support un jour ou la personne est
        // deja bloquee.
        $codes = CodesDeSecours::generer();
        $code = $codes[0];

        foreach ([
            strtolower($code),
            str_replace('-', '', $code),
            ' ' . $code . ' ',
            strtolower(str_replace('-', ' ', $code)),
        ] as $variante) {
            $this->assertNotNull(
                CodesDeSecours::consommer($codes, $variante),
                "variante refusee : " . var_export($variante, true),
            );
        }
    }

    public function test_un_seul_code_est_consomme_meme_en_cas_de_doublon(): void
    {
        // Cas theorique, mais si deux codes identiques se retrouvaient dans un
        // jeu, en consommer un ne doit pas retirer les deux — ni en laisser
        // un qui resservirait.
        $codes = ['AAAA-BBBB', 'AAAA-BBBB', 'CCCC-DDDD'];

        $restants = CodesDeSecours::consommer($codes, 'AAAA-BBBB');

        $this->assertSame(['AAAA-BBBB', 'CCCC-DDDD'], $restants);
    }
}

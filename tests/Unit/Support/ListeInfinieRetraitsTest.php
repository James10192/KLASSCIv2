<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Des lignes retirees de l'ecran sans rechargement (restauration dans la
 * corbeille, suppression groupee de bulletins) ne doivent faire sauter aucune
 * ligne au defilement suivant, quel qu'en soit le nombre.
 *
 * La pagination est par decalage : retirer k lignes fait reculer toute la
 * suite de k. Le premier correctif rejouait une seule tranche, ce qui ne
 * tenait que si k ne depassait pas la taille d'une tranche — « tout cocher »
 * sur 100 bulletins en perdait 80. Le client demande desormais la tranche a
 * partir de ce qu'il affiche (ListeInfinie.pageAPrendre).
 *
 * Le test execute public/js/liste-infinie.js REELLEMENT livre, sous Node,
 * contre un serveur simule (tests/Unit/Support/scripts/liste-infinie-retraits.js).
 */
class ListeInfinieRetraitsTest extends TestCase
{
    /** @var array<string, array{ok: bool, detail: string}>|null */
    private static ?array $resultats = null;

    protected function setUp(): void
    {
        parent::setUp();

        $sonde = shell_exec('node --version 2>&1');
        if (! is_string($sonde) || preg_match('/^v\d+\./', trim($sonde)) !== 1) {
            $this->markTestSkipped('Node introuvable : impossible d\'executer le script livre.');
        }

        if (self::$resultats === null) {
            $racine = dirname(__DIR__, 3);
            $sortie = shell_exec(sprintf(
                'node %s %s 2>&1',
                escapeshellarg($racine.'/tests/Unit/Support/scripts/liste-infinie-retraits.js'),
                escapeshellarg($racine.'/public/js/liste-infinie.js'),
            ));
            self::$resultats = json_decode((string) $sortie, true) ?? ['execution' => ['ok' => false, 'detail' => (string) $sortie]];
        }
    }

    /**
     * @dataProvider scenarios
     */
    public function test_aucune_ligne_n_est_sautee_ni_repetee(string $scenario, string $pourquoi): void
    {
        $resultat = self::$resultats[$scenario] ?? self::$resultats['execution'] ?? ['ok' => false, 'detail' => 'scenario absent'];

        $this->assertTrue($resultat['ok'], $pourquoi.' — '.$resultat['detail']);
    }

    public static function scenarios(): array
    {
        return [
            'sans retrait' => ['sans_retrait', 'Le defilement ordinaire va jusqu\'au bout.'],
            'moins qu\'une tranche' => ['moins_qu_une_tranche', 'Trois restaurations dans la corbeille.'],
            'plus qu\'une tranche' => ['plus_qu_une_tranche', '« Tout cocher » sur 100 lignes puis suppression groupee.'],
            'retraits cumules' => ['retraits_cumules', 'Plusieurs retraits d\'affilee avant le chargement suivant.'],
            'retrait pendant un chargement' => ['retrait_pendant_un_chargement', 'La tranche, choisie avant le retrait, est servie apres.'],
        ];
    }
}

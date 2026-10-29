<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ESBTPLMDJury;
use App\Models\User;
use Mockery;
use Tests\TestCase;

/**
 * Qui voit l'ensemble des deliberations, et qui se borne aux siennes.
 *
 * La regle a une consequence lourde : celui qui ne la franchit pas obtient une
 * liste vide, des compteurs a zero et un refus sur chaque proces-verbal. Elle est
 * donc figee ici, faute de quoi un simple ajout de droit dans un ecran de
 * configuration fermerait sans bruit l'archive des deliberations a la direction
 * des etudes — des pieces legales que l'etablissement doit pouvoir produire.
 */
class ESBTPLMDJuryVueDEnsembleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @param array<int, string> $droits */
    private function utilisateur(array $droits): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')
            ->andReturnUsing(fn (string $droit): bool => in_array($droit, $droits, true));

        return $user;
    }

    /** @return array<string, array{0: array<int, string>, 1: bool}> */
    public static function titres(): array
    {
        return [
            'president' => [['lmd.jury.preside'], true],
            'deliberant' => [['lmd.jury.deliberate'], true],
            'publiant' => [['lmd.jury.publish'], true],
            'direction des etudes' => [['identity.direct_studies'], true],
            'simple consultant' => [['lmd.jury.view'], false],
            'signataire sans supervision' => [['lmd.jury.view', 'lmd.jury.sign'], false],
            'exportateur sans supervision' => [['lmd.jury.view', 'lmd.pv.export'], false],
            'sans aucun droit' => [[], false],
        ];
    }

    /**
     * @dataProvider titres
     *
     * @param array<int, string> $droits
     */
    public function test_le_titre_de_supervision_ouvre_la_vue_d_ensemble(array $droits, bool $attendu): void
    {
        $this->assertSame(
            $attendu,
            ESBTPLMDJury::utilisateurVoitTousLesJurys($this->utilisateur($droits))
        );
    }

    public function test_un_visiteur_anonyme_ne_voit_rien_en_entier(): void
    {
        $this->assertFalse(ESBTPLMDJury::utilisateurVoitTousLesJurys(null));
    }
}

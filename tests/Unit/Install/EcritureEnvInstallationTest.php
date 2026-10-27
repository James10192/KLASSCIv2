<?php

namespace Tests\Unit\Install;

use App\Http\Controllers\InstallController;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Ce que l'assistant écrit dans .env doit être relu à l'identique au
 * démarrage suivant : une valeur mal formée rend tout le fichier illisible,
 * et l'école entière tombe.
 */
class EcritureEnvInstallationTest extends TestCase
{
    private const VALEURS = [
        'APP_NAME' => 'PRESENTATION-TEST KLASSCI',
        'DB_PASSWORD' => 'p$1a\\b"c #d ${X}',
        'APP_URL' => 'https://presentation-test.klassci.com',
        'DB_PORT' => '3306',
        'VIDE' => '',
    ];

    private function ecrire(string $contenu, array $valeurs): string
    {
        $classe = new ReflectionClass(InstallController::class);
        $methode = $classe->getMethod('ecrireValeursEnv');
        $methode->setAccessible(true);

        return $methode->invoke($classe->newInstanceWithoutConstructor(), $contenu, $valeurs);
    }

    public function test_chaque_valeur_est_relue_telle_quelle_par_dotenv(): void
    {
        $lu = Dotenv::parse($this->ecrire('', self::VALEURS));

        foreach (self::VALEURS as $cle => $valeur) {
            $this->assertSame($valeur, $lu[$cle] ?? null, "{$cle} n'est pas relu à l'identique");
        }
    }

    public function test_une_cle_existante_est_remplacee_sans_doublon_ni_reference_de_remplacement(): void
    {
        $contenu = $this->ecrire("# entete\nDB_PORT=1\nDB_PASSWORD=ancien\n", self::VALEURS);
        $contenu = $this->ecrire($contenu, self::VALEURS);

        $this->assertSame(1, substr_count($contenu, "\nDB_PORT="));
        $this->assertSame(1, substr_count($contenu, "\nDB_PASSWORD="));
        $this->assertSame(1, substr_count($contenu, "\nAPP_NAME="));
        $this->assertStringStartsWith('# entete', $contenu);
        $this->assertSame('p$1a\\b"c #d ${X}', Dotenv::parse($contenu)['DB_PASSWORD']);
    }
}

<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;

/**
 * bin/verifier-fraicheur-nouveautes.php avertit dès que la fenêtre
 * « Nouveautés » a un mois de retard sur le CHANGELOG. Septembre 2026 → octobre
 * n'avait rien déclenché : le seuil tolérait un mois, et un écart calculé en
 * secondes puis arrondi donnait 0 sur un mois de trente jours.
 */
class FraicheurNouveautesTest extends TestCase
{
    /** @dataProvider ecarts */
    public function test_un_mois_de_retard_avertit(string $modal, string $moisChangelog, bool $avertit): void
    {
        $sortie = $this->lancer($modal, $moisChangelog);

        $this->assertSame($avertit, str_contains($sortie, '::warning'), $sortie);
    }

    public static function ecarts(): array
    {
        return [
            'septembre → octobre' => ['2026_09_25', 'Octobre 2026', true],
            'février → mars' => ['2026_02_10', 'Mars 2026', true],
            'décembre → janvier' => ['2026_12_01', 'Janvier 2027', true],
            'même mois' => ['2026_10_02', 'Octobre 2026', false],
        ];
    }

    private function lancer(string $version, string $mois): string
    {
        $dossier = sys_get_temp_dir().'/nvx-'.uniqid();
        mkdir($dossier);
        file_put_contents($dossier.'/layout.blade.php', "@include('x', ['cleVersion' => 'whatsNew.v{$version}'])");
        file_put_contents($dossier.'/CHANGELOG.md', "# Changelog\n\n## {$mois}\n\n- entrée\n");

        $script = escapeshellarg(dirname(__DIR__, 3).'/bin/verifier-fraicheur-nouveautes.php');
        $sortie = (string) shell_exec(sprintf(
            'NVX_LAYOUT=%s NVX_CHANGELOG=%s %s %s 2>&1',
            escapeshellarg($dossier.'/layout.blade.php'),
            escapeshellarg($dossier.'/CHANGELOG.md'),
            escapeshellarg(PHP_BINARY),
            $script
        ));

        array_map('unlink', glob($dossier.'/*'));
        rmdir($dossier);

        return $sortie;
    }
}

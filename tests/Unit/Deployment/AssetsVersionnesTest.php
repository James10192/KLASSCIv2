<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Une feuille de style ou un script du dossier public/ se charge avec un
 * numéro de version : `asset('css/x.css') }}?v={{ @filemtime(...) }}`.
 *
 * Le `.htaccess` sert le fichier versionné avec un cache d'un an : le
 * navigateur ne le redemande plus. Sans version, il est servi en `no-cache`
 * et le navigateur le revérifie à CHAQUE page. Mesuré sur presentation
 * (octobre 2026) : `dashboard-moderne.css`, appelé sans version par 173
 * vues, coûtait 1,5 à 2 s par page, sur une feuille qui bloque l'affichage.
 *
 * Ce test échoue dès qu'un `asset('css|js/…')` réapparaît sans `?v=`.
 */
class AssetsVersionnesTest extends TestCase
{
    public function test_chaque_feuille_et_script_de_public_porte_une_version(): void
    {
        $racine = dirname(__DIR__, 3);
        $fautes = [];

        $iterateur = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($racine . '/resources/views', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterateur as $fichier) {
            if (! $fichier->isFile() || ! str_ends_with($fichier->getFilename(), '.blade.php')) {
                continue;
            }

            foreach (self::referencesSansVersion(file_get_contents($fichier->getPathname())) as $ligne) {
                $fautes[] = substr($fichier->getPathname(), strlen($racine) + 1) . ':' . $ligne;
            }
        }

        $this->assertSame([], $fautes, "Feuille ou script sans ?v= (revérifié à chaque page) :\n" . implode("\n", $fautes));
    }

    public function test_le_controle_attrape_une_reference_sans_version(): void
    {
        $vue = "<link href=\"{{ asset('css/a.css') }}\">\n"
            . "<script src=\"{{ asset('js/b.js') }}?v={{ @filemtime(public_path('js/b.js')) ?: '1' }}\"></script>\n"
            . "<img src=\"{{ asset('images/logo.png') }}\">\n";

        $this->assertSame([1], self::referencesSansVersion($vue));
    }

    /** @return int[] numéros de ligne */
    private static function referencesSansVersion(string $source): array
    {
        preg_match_all(
            '/\{\{\s*asset\([\'"]\/?(?:css|js)\/[^\'"]+\.(?:css|js)[\'"]\)\s*\}\}(?!\?v=)/',
            $source,
            $trouvailles,
            PREG_OFFSET_CAPTURE
        );

        return array_map(
            static fn (array $t): int => substr_count($source, "\n", 0, $t[1]) + 1,
            $trouvailles[0]
        );
    }
}

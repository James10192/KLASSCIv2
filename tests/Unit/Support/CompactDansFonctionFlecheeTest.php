<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;

/**
 * Une fonction flechee ne capture que les variables qu'elle NOMME dans son
 * corps. compact('x') ne nomme rien : il lit une chaine a l'execution. Une
 * variable exterieure passee ainsi est donc indefinie, et la vue part en 500.
 *
 * Incident (septembre 2026) : la suite de l'audit comptable au defilement
 * tombait en erreur sur presentation, la premiere tranche s'affichant bien.
 */
class CompactDansFonctionFlecheeTest extends TestCase
{
    public function test_aucune_fonction_flechee_ne_compacte_une_variable_exterieure(): void
    {
        $fautes = [];
        $racine = dirname(__DIR__, 3).'/app';
        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }
            foreach (file($fichier->getPathname()) as $numero => $ligne) {
                if (! preg_match('/\bfn\s*\(([^)]*)\)\s*=>.*?\bcompact\(([^)]*)\)/', $ligne, $m)) {
                    continue;
                }
                preg_match_all('/\$(\w+)/', $m[1], $parametres);
                preg_match_all("/['\"](\w+)['\"]/", $m[2], $compactes);
                $exterieures = array_diff($compactes[1], $parametres[1]);
                if ($exterieures !== []) {
                    $fautes[] = str_replace($racine, 'app', $fichier->getPathname()).':'.($numero + 1)
                        .' — '.implode(', ', $exterieures);
                }
            }
        }

        $this->assertSame([], $fautes, "compact() d'une variable exterieure dans une fonction flechee :\n".implode("\n", $fautes));
    }
}

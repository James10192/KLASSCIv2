<?php

namespace App\Services\Emails;

/**
 * Distance d'alignement optimal (Damerau-Levenshtein restreinte) : insertion,
 * suppression, substitution, et transposition de deux lettres voisines, chacune
 * pour 1. `gmial` → `gmail` vaut 1, pas 2 comme avec `levenshtein()`.
 *
 * Meme calcul que `distanceEdition` de klassci-landing.
 */
final class DistanceEdition
{
    public static function alignementOptimal(string $a, string $b): int
    {
        $la = strlen($a);
        $lb = strlen($b);
        $d = [];

        for ($i = 0; $i <= $la; $i++) {
            $d[$i] = [$i];
        }
        for ($j = 0; $j <= $lb; $j++) {
            $d[0][$j] = $j;
        }

        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                $cout = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $cout);

                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + 1);
                }
            }
        }

        return $d[$la][$lb];
    }
}

<?php

namespace App\Domain\Securite;

/**
 * Les codes qui rendent la double authentification supportable.
 *
 * Un second facteur sans porte de secours n'est pas une sécurité, c'est un
 * risque déplacé : le téléphone se perd, se casse, se vole, et l'école se
 * retrouve sans son secrétariat un matin de rentrée. Les codes de secours
 * sont ce qui permet à quelqu'un de récupérer son compte sans appeler
 * l'éditeur.
 *
 * **Chaque code ne sert qu'une fois.** Sans cela, un code lu par-dessus une
 * épaule, ou resté sur un papier dans un tiroir, vaut un accès permanent —
 * et l'on n'a fait que remplacer un mot de passe par un autre, en moins bien.
 */
class CodesDeSecours
{
    /** Combien on en remet. Assez pour ne pas se retrouver à court, peu assez pour tenir sur un papier. */
    public const NOMBRE = 8;

    /**
     * Génère un jeu de codes.
     *
     * `random_bytes` et non `rand` : ces codes contournent le second facteur,
     * ils valent le mot de passe. Un générateur prévisible les rendrait
     * devinables.
     *
     * Le format `XXXX-XXXX` n'est pas décoratif : on les recopie à la main
     * depuis un papier, sous le stress de ne plus pouvoir se connecter. Le
     * tiret coupe la lecture en deux, et l'alphabet écarte 0/O et 1/I/L, qu'on
     * confond en écrivant.
     */
    public static function generer(): array
    {
        $codes = [];

        for ($i = 0; $i < self::NOMBRE; $i++) {
            $codes[] = self::unCode();
        }

        return $codes;
    }

    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private static function unCode(): string
    {
        $tirage = static function (int $longueur): string {
            $sortie = '';
            $max = strlen(self::ALPHABET) - 1;

            for ($i = 0; $i < $longueur; $i++) {
                $sortie .= self::ALPHABET[random_int(0, $max)];
            }

            return $sortie;
        };

        return $tirage(4) . '-' . $tirage(4);
    }

    /**
     * Le code fourni figure-t-il dans le jeu ? Si oui, rend le jeu amputé.
     *
     * Rend `null` quand le code ne correspond à rien — ce qui distingue « code
     * faux » de « code juste, jeu mis à jour », sans que l'appelant ait à
     * comparer deux tableaux.
     *
     * La comparaison est en temps constant : comparer avec `===` laisse fuir,
     * par la durée, combien de caractères de tête sont bons. C'est peu, et sur
     * huit codes essayés en boucle c'est mesurable.
     */
    public static function consommer(array $codes, string $fourni): ?array
    {
        $normalise = self::normaliser($fourni);
        $restants = [];
        $trouve = false;

        foreach ($codes as $code) {
            if (! $trouve && hash_equals(self::normaliser($code), $normalise)) {
                $trouve = true;

                continue;
            }

            $restants[] = $code;
        }

        return $trouve ? $restants : null;
    }

    /**
     * La forme sous laquelle deux codes se comparent.
     *
     * On recopie ces codes à la main : avec ou sans le tiret, en minuscules,
     * avec une espace collée par le presse-papiers. Refuser un bon code parce
     * qu'il a été tapé en minuscules, c'est fabriquer un appel au support un
     * jour où la personne est déjà bloquée.
     */
    private static function normaliser(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }
}

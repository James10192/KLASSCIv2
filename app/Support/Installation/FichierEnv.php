<?php

namespace App\Support\Installation;

/**
 * Écrit des clés dans un fichier .env, sous une forme que dotenv relit telle
 * quelle. Sans dépendance à Laravel : le démarrage du conteneur s'en sert
 * avant que l'application existe (bin/deploy/ecrire-env-instance.php).
 */
final class FichierEnv
{
    /**
     * Remplace ou ajoute chaque clé.
     *
     * Une clé existante est remplacée par une fonction de rappel : un mot de
     * passe contenant `$1` ou `\\` serait sinon lu comme une référence de
     * remplacement par preg_replace.
     */
    public static function ecrire(string $contenu, array $valeurs): string
    {
        foreach ($valeurs as $cle => $valeur) {
            $ligne = $cle . '=' . self::formater($valeur);
            $motif = '/^' . preg_quote($cle, '/') . '=.*$/m';

            if (preg_match($motif, $contenu)) {
                $contenu = preg_replace_callback($motif, fn () => $ligne, $contenu);
            } else {
                $contenu = rtrim($contenu, "\n") . "\n" . $ligne . "\n";
            }
        }

        return $contenu;
    }

    /**
     * Un nom d'école avec espaces (« PRESENTATION-TEST KLASSCI ») écrit nu
     * rend tout le fichier illisible au démarrage suivant : on entoure de
     * guillemets dès qu'un caractère l'exige, en échappant `\`, `"` et `$`.
     */
    public static function formater($valeur): string
    {
        $valeur = (string) ($valeur ?? '');

        if ($valeur === '' || preg_match('/^[A-Za-z0-9_.:\/@+,-]+$/', $valeur)) {
            return $valeur;
        }

        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $valeur) . '"';
    }
}

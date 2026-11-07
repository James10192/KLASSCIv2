<?php

namespace App\Support;

/**
 * Nom de fichier proposé au téléchargement.
 *
 * Symfony refuse « / » et « \ » dans l'en-tête Content-Disposition et lève une
 * exception : un matricule « 25/ESBTP/001 », un code de classe « 1BTS/A » ou une
 * année « 2025/2026 » faisaient échouer l'impression d'un bulletin. Les
 * caractères interdits par Windows et les caractères de contrôle sont remplacés
 * par un tiret ; l'extension est conservée.
 */
final class NomDeFichier
{
    public static function sur(?string $nom, string $repli = 'document'): string
    {
        $nom = (string) $nom;
        $nom = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F\x7F]+/u', '-', $nom) ?? '';
        $nom = preg_replace('/\s+/u', ' ', $nom) ?? '';
        $nom = preg_replace('/-{2,}/', '-', $nom) ?? '';
        $nom = trim($nom, " .-");

        return $nom === '' ? $repli : $nom;
    }
}

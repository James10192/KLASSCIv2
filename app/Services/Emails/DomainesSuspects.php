<?php

namespace App\Services\Emails;

use RuntimeException;

/**
 * Les listes partagees avec le site vitrine, lues une fois par processus.
 *
 * Le fichier est la copie de `lib/email/domaines-suspects.json` de
 * klassci-landing. Absent ou illisible, on leve : une regle de validation qui
 * tournerait sans ses listes laisserait tout passer sans le dire.
 */
class DomainesSuspects
{
    /** @var array<string, mixed>|null */
    private ?array $donnees = null;

    /** @return array<string, string> */
    public function correctionsConnues(): array
    {
        return $this->liste('corrections_connues');
    }

    /** @return array<string, string> */
    public function correctionsTld(): array
    {
        return $this->liste('corrections_tld');
    }

    /** @return list<string> */
    public function domainesReference(): array
    {
        return $this->liste('domaines_reference');
    }

    /** @return list<string> */
    public function nomsReelsVoisins(): array
    {
        return $this->liste('noms_reels_voisins');
    }

    /** @return list<string> */
    public function domainesFactices(): array
    {
        return $this->liste('domaines_factices');
    }

    /** @return list<string> Extensions reservees (RFC 2606 / 6761) : jamais de courrier derriere. */
    public function extensionsReservees(): array
    {
        return $this->liste('extensions_reservees');
    }

    public function distanceMaximale(): int
    {
        return (int) ($this->donnees()['distance_maximale'] ?? 2);
    }

    /** @return array<mixed> */
    private function liste(string $cle): array
    {
        $valeur = $this->donnees()[$cle] ?? [];

        return is_array($valeur) ? $valeur : [];
    }

    /** @return array<string, mixed> */
    private function donnees(): array
    {
        if ($this->donnees !== null) {
            return $this->donnees;
        }

        $chemin = (string) config('emails_joignables.fichier_domaines', resource_path('data/domaines-suspects.json'));
        $contenu = is_file($chemin) ? file_get_contents($chemin) : false;
        $donnees = $contenu === false ? null : json_decode($contenu, true);

        if (! is_array($donnees)) {
            throw new RuntimeException('Liste des domaines suspects illisible : '.$chemin);
        }

        return $this->donnees = $donnees;
    }
}

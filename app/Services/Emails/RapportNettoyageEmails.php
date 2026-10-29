<?php

namespace App\Services\Emails;

use App\Services\Verification\MasqueContact;

/**
 * Le rapport de `emails:nettoyer-factices`, sous forme de donnees : ce que
 * rend l'API CLI. Aucune adresse complete : des domaines, des comptes, et au
 * plus vingt exemples masques.
 */
class RapportNettoyageEmails
{
    private const EXEMPLES_MAX = 20;

    public function __construct(
        private readonly NettoyageAdressesFactices $nettoyage,
        private readonly InventaireAdresses $inventaire,
    ) {}

    /** @return array{lignes: list<array<string, mixed>>, comptes_par_role: array<string, int>, exemples: list<array{email_masque: string, table: string, colonne: string, type: string}>} */
    public function produire(bool $avecMx = true): array
    {
        $lignes = $this->nettoyage->rapport($avecMx);

        return [
            'lignes' => $lignes,
            'comptes_par_role' => $this->nettoyage->comptesParRole(),
            'exemples' => $this->exemples($lignes),
        ];
    }

    /** @param  list<array<string, mixed>>  $lignes */
    private function exemples(array $lignes): array
    {
        $exemples = [];
        foreach ($lignes as $ligne) {
            $reste = self::EXEMPLES_MAX - count($exemples);
            if ($reste <= 0) {
                break;
            }
            foreach ($this->inventaire->lignes($ligne['table'], $ligne['colonne'], [$ligne['domaine']], min(3, $reste)) as $r) {
                $exemples[] = [
                    'email_masque' => MasqueContact::email((string) $r->email),
                    'table' => $ligne['table'],
                    'colonne' => $ligne['colonne'],
                    'type' => $ligne['type'],
                ];
            }
        }

        return $exemples;
    }
}

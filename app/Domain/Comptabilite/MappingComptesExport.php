<?php

namespace App\Domain\Comptabilite;

final class MappingComptesExport
{
    public const EVENEMENTS = [
        'student.payment.validated',
        'supplier.invoice.approved',
        'supplier.payment.executed',
        'stock.receipt',
        'stock.issue',
    ];

    public static function estPret(array $mapping, string $compteDefaut): bool
    {
        $renseignes = array_filter($mapping, static fn ($compte) => $compte !== null && $compte !== '');

        return $renseignes !== [] || trim($compteDefaut) !== '';
    }

    public static function nImportePasLesComptesCiParDefaut(string $compteDefaut): bool
    {
        return trim($compteDefaut) === '';
    }

    public static function totauxConcordent(float $envoye, float $livre): bool
    {
        return abs($envoye - $livre) < 0.005;
    }
}

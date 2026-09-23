<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;

/**
 * Classe un DOMAINE (et non une adresse) : factice, faute de frappe, sans MX
 * ou valide. Memorise par processus : un inventaire rencontre le meme domaine
 * dans plusieurs tables.
 */
class ClassementDomaines
{
    /** @var array<string, AnalyseEmail> */
    private array $memo = [];

    public function __construct(private readonly DiagnosticEmail $diagnostic) {}

    public function classer(string $domaine, bool $avecMx = true): AnalyseEmail
    {
        $cle = ($avecMx ? 'mx:' : '').$domaine;

        return $this->memo[$cle] ??= $domaine === ''
            ? new AnalyseEmail(EtatEmail::Invalide)
            : $this->diagnostic->diagnostiquer('x@'.$domaine, $avecMx);
    }
}

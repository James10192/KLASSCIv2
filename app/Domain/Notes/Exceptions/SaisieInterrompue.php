<?php

namespace App\Domain\Notes\Exceptions;

/**
 * Saisie tout-ou-rien annulée avant le commit : une ligne est refusée, ou une
 * note a changé depuis ce qui avait été montré. Rien n'est écrit, aucun avis ne part.
 */
class SaisieInterrompue extends \RuntimeException
{
    /** @param array<int, array{etudiant_id:int, evaluation_id:int, raison:string}> $refus */
    public function __construct(string $message, public readonly array $refus = [])
    {
        parent::__construct($message);
    }
}

<?php

namespace App\Domain\Inscriptions\Pieces;

use RuntimeException;

/**
 * On a demande a cocher une piece qui n'est pas attendue pour cette inscription.
 *
 * Arrive quand l'ecole retire une ligne du catalogue pendant qu'un onglet reste
 * ouvert : mieux vaut le dire que d'enregistrer une piece que plus personne
 * n'attend.
 */
class PieceHorsCatalogueException extends RuntimeException
{
    // Le nom `codePiece` evite la collision avec Exception::$code, qui existe
    // deja et n'est pas readonly.
    public function __construct(public readonly string $codePiece)
    {
        parent::__construct("La piece « {$codePiece} » n'est pas attendue pour cette inscription.");
    }
}

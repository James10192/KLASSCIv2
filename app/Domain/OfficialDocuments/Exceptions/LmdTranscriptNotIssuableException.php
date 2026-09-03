<?php

namespace App\Domain\OfficialDocuments\Exceptions;

use RuntimeException;

/**
 * Le releve de notes ne peut pas etre emis en l'etat.
 *
 * Meme forme que JuryPvNotIssuableException : la liste des motifs est portee par
 * l'exception pour que l'appelant puisse la rendre telle quelle a l'utilisateur.
 */
class LmdTranscriptNotIssuableException extends RuntimeException
{
    public function __construct(public readonly array $reasons)
    {
        parent::__construct('Le releve de notes ne remplit pas les conditions d emission.');
    }
}

<?php

namespace App\Domain\OfficialDocuments\Services;

use RuntimeException;

class OfficialDocumentIntegrityException extends RuntimeException
{
    public function __construct(public readonly int $documentId, public readonly string $reference)
    {
        parent::__construct('Échec de la vérification de l intégrité du document officiel.');
    }
}

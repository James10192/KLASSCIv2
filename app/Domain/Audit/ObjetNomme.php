<?php

namespace App\Domain\Audit;

/**
 * Un objet du journal tel qu'une personne le reconnait : son nom, ou la
 * personne qu'il concerne, et deux ou trois reperes qui levent le doute.
 * L'identifiant technique n'en fait pas partie : il reste dans les details.
 */
final class ObjetNomme
{
    /**
     * @param  string  $type  le genre d'objet, au singulier (« Paiement »)
     * @param  string  $designation  ce qui precede le nom dans une phrase (« la note de »)
     * @param  string  $nom  ce qu'on reconnait (« KONÉ Awa », « REC-2026-0412 »)
     * @param  list<string>  $reperes
     */
    public function __construct(
        public readonly string $type,
        public readonly string $designation,
        public readonly string $nom,
        public readonly array $reperes = [],
        public readonly ?string $url = null,
        public readonly bool $supprime = false,
    ) {
    }

    /** « la note de KONÉ Awa », « le paiement REC-2026-0412 ». */
    public function enClair(): string
    {
        return trim($this->designation.' '.$this->nom);
    }
}

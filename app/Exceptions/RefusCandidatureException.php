<?php

namespace App\Exceptions;

use App\Enums\RefusCandidature;
use RuntimeException;

/**
 * Un depot de candidature refuse, avec sa raison.
 *
 * Une exception qui PORTE une valeur, plutot que trois classes vides dont le
 * seul contenu etait leur nom. L'appelant fait un `match` sur `$e->raison` au
 * lieu d'enchainer trois `catch` — et un `match` sur enum est exhaustif par
 * construction, la ou un `catch` de trop ou de moins ne se voit pas.
 *
 * Le message technique reste au constructeur, pour le journal. Ce que lit le
 * candidat est ecrit par le controleur, seul a connaitre la formulation
 * publique : recopier la phrase ici en ferait une seconde version a maintenir,
 * dans un endroit qui ne la montrera jamais.
 */
class RefusCandidatureException extends RuntimeException
{
    public function __construct(
        public readonly RefusCandidature $raison,
        string $message,
    ) {
        parent::__construct($message);
    }
}

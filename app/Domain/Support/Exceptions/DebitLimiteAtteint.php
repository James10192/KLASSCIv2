<?php

namespace App\Domain\Support\Exceptions;

/**
 * Le Master a repondu 429 : une limite de debit, propre a UN point d'entree
 * (20 pieces par minute et par instance, par exemple), est atteinte.
 *
 * Indisponibilite passagere pour cet appel, pas pour l'instance : aucun
 * coupe-circuit n'est pose, sinon deux personnes qui joignent des captures en
 * meme temps fermeraient le support a toute l'ecole pendant une minute.
 */
class DebitLimiteAtteint extends MasterSupportIndisponible
{
}

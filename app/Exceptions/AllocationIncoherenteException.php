<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * La repartition d'un versement ne boucle pas sur son montant.
 *
 * Tout le calcul par categorie repose sur un invariant : un versement qui porte
 * des allocations est lu PAR ses allocations, et plus du tout par sa propre
 * `frais_category_id`. Si leur somme ne fait pas le montant du versement, la
 * difference disparait purement et simplement des totaux — sans erreur, sans
 * trace, sans que personne ne s'en apercoive.
 *
 * On refuse donc d'ecrire une repartition qui ne boucle pas. Un lot qui echoue
 * bruyamment se repare ; de l'argent qui s'evapore en silence, non.
 */
class AllocationIncoherenteException extends RuntimeException
{
}

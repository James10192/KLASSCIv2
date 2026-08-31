<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Refus METIER d'imputer un versement sur des frais.
 *
 * Le caissier encaisse plus que l'etudiant ne doit, ou repartit une somme qui
 * ne fait pas le compte. Ce n'est pas une panne : c'est une saisie a corriger,
 * et la caisse doit lire pourquoi en toutes lettres.
 *
 * Distincte de {@see AllocationIncoherenteException}, qui signale une erreur de
 * CALCUL — la repartition ne totalise pas le versement — donc un defaut du code
 * et non de la saisie. La premiere se corrige a l'ecran, la seconde se corrige
 * dans le programme.
 */
class RepartitionRefuseeException extends RuntimeException
{
}

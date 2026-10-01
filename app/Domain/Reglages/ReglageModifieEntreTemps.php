<?php

namespace App\Domain\Reglages;

/**
 * Un réglage examiné n'a plus la valeur montrée au moment d'écrire : rien
 * n'a été écrit (la transaction est annulée).
 */
class ReglageModifieEntreTemps extends \RuntimeException
{
}

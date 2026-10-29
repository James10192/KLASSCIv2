<?php

namespace App\Domain\Assistant\Actions;

/**
 * Levée par une action au moment d'écrire, quand ce qu'elle trouve n'est plus
 * ce qui a été montré. L'action garantit que rien n'a été écrit.
 */
class PropositionPerimee extends \RuntimeException
{
}

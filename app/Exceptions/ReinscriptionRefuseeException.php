<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Refus METIER d'une reinscription : solde non solde, aucune inscription
 * active, aucune annee universitaire courante.
 *
 * Cette classe existe pour que l'appelant puisse distinguer « l'ecole refuse,
 * et voici pourquoi » de « quelque chose a casse ». Sans elle, la corbeille
 * attrapait \Exception et affichait le message tel quel : une contrainte
 * d'unicite violee ou un verrou MySQL expire — incidents banals en periode de
 * rush — arrivaient a l'agent de scolarite sous la forme d'un
 * « SQLSTATE[23000] ... insert into esbtp_inscriptions », presente comme un
 * refus metier et donc invisible au gestionnaire d'erreurs.
 */
class ReinscriptionRefuseeException extends RuntimeException
{
}

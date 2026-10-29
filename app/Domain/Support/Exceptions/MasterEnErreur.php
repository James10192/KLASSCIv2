<?php

namespace App\Domain\Support\Exceptions;

/**
 * Le Master a repondu par une erreur serveur (5xx). Le code de l'exception
 * porte le statut HTTP.
 *
 * C'est l'appelant qui decide si elle ferme le support : un appel ordinaire en
 * erreur dit que le Master va mal, un transfert de fichier en erreur peut ne
 * dire que quelque chose de CE fichier (un assainissement qui echoue sur une
 * image piegee). Voir `ClientMasterSupport::requete()`.
 */
class MasterEnErreur extends MasterSupportIndisponible
{
}

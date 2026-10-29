<?php

namespace App\Domain\Support\Exceptions;

/**
 * Le Master refuse l'identifiant de l'instance (401, 403).
 *
 * C'est une indisponibilite, pas un refus de la demande : la faute est celle de
 * l'instance (jeton revoque, expire), et la demande partira telle quelle une fois
 * l'identifiant remplace. La boite d'envoi ne compte donc pas cet echec contre elle.
 */
class IdentifiantInstanceRefuse extends MasterSupportIndisponible
{
}

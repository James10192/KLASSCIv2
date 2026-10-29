<?php

namespace App\Domain\Support\Exceptions;

/**
 * L'identifiant de l'instance est valide mais ne couvre pas cette action
 * (403 `insufficient_scope`).
 *
 * Comme un identifiant refuse, c'est la faute de l'instance et non de la
 * demande : un signalement part dans la boite d'envoi et y attend qu'on elargisse
 * l'identifiant, sans y user ses tentatives. A la difference d'un identifiant
 * revoque, le reste de l'API reste joignable : aucun coupe-circuit n'est pose.
 */
class PorteeAbsente extends IdentifiantInstanceRefuse
{
}

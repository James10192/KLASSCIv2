<?php

namespace App\Domain\Bulletins\Taches;

/**
 * Une tâche ne peut pas aboutir, pour une raison que son demandeur doit lire
 * telle quelle (classe supprimée, configuration incomplète, fichiers effacés).
 *
 * Le message part dans la notification : il s'adresse à une personne, pas à
 * un journal.
 */
class TacheBulletinsImpossible extends \RuntimeException {}

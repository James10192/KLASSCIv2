<?php

declare(strict_types=1);

namespace App\Services\LMD\Exceptions;

use DomainException;

/**
 * La maquette du semestre ne rend aucune unité, et le bulletin en porte.
 *
 * On refuse plutôt que de recalculer, parce qu'un recalcul écrirait une moyenne
 * vide et des crédits à zéro sous une liste d'unités intacte — et sortirait
 * l'étudiant du classement, ce qui décale les rangs de toute sa classe.
 *
 * Et on refuse bruyamment plutôt que de rendre le bulletin tel quel : la
 * condition vaut pour toute la classe, donc une génération en masse aurait
 * annoncé « 25 bulletins générés » sans en avoir recalculé un seul. Remplacer
 * une donnée fausse par un message faux n'est pas un progrès.
 *
 * `DomainException` et non une exception maison sans parenté : l'écran des
 * sessions de rattrapage attrape déjà `\DomainException`, donc le refus y
 * devient un message propre au lieu d'une erreur serveur.
 */
final class MaquetteSansCompositionException extends DomainException
{
    public function __construct(
        public readonly int $classeId,
        public readonly int $semestre,
        public readonly int $lignesConservees,
    ) {
        parent::__construct(sprintf(
            'La maquette du semestre S%d ne rattache aucune unité d’enseignement à cette classe. '
            .'Les bulletins déjà calculés sont laissés intacts — les recalculer les viderait. '
            .'Vérifiez la maquette du parcours avant de relancer la génération.',
            $semestre,
        ));
    }
}

<?php

namespace App\Services\Frais;

/**
 * La regle de service : dans quel ordre une somme eteint des dettes.
 *
 * Le frais que le caissier a designe passe en premier — c'est l'intention
 * explicite du versement — puis les autres dans l'ordre ou ils arrivent, qui est
 * celui que L'ECOLE a range (`sort_order`, cf. {@see OrdreDesCategoriesFrais}).
 * On ne sert jamais un frais au-dela de ce qu'il reclame encore.
 *
 * Cette regle vit ICI, en un seul exemplaire, parce qu'elle a deux appelants :
 * l'encaissement, qui l'applique au moment ou l'argent entre, et
 * {@see RepartitionTropPercu}, qui la rejoue sur l'historique. Un second
 * exemplaire — en PHP ailleurs, ou en JavaScript dans l'ecran de caisse —
 * finirait par diverger sur le frais servi en premier ou sur le porteur de
 * l'avance, et la meme saisie produirait deux ecritures comptables selon la
 * porte d'entree.
 *
 * Le navigateur PROPOSE en interrogeant cette regle ; il ne la reimplemente pas.
 */
class ServirLesFrais
{
    /**
     * En deca, la somme n'est plus qu'un residu d'arrondi : ni un reste a
     * servir, ni une part a ecrire.
     */
    public const EPSILON = 0.009;

    /**
     * @param  array<int, float>  $reste  Ce que chaque frais reclame encore,
     *                                    dans l'ordre de service de l'ecole.
     * @param  int|null  $fraisDesigne  Le frais que le caissier a nomme. Sert en
     *                                  premier s'il figure dans $reste.
     * @return array{allocations: array<int, float>, reste: array<int, float>, reliquat: float}
     *         `reliquat` est ce qui n'a trouve aucun frais a eteindre. C'est a
     *         l'appelant de decider ce qu'il en fait : l'encaissement refuse,
     *         la reprise d'historique le laisse en avance.
     */
    public function servir(float $montant, array $reste, ?int $fraisDesigne = null): array
    {
        $aRepartir = round($montant, 2);
        $allocations = [];

        $ordre = array_keys($reste);

        if ($fraisDesigne !== null && isset($reste[$fraisDesigne])) {
            $ordre = array_merge(
                [$fraisDesigne],
                array_values(array_diff($ordre, [$fraisDesigne]))
            );
        }

        foreach ($ordre as $categoryId) {
            if ($aRepartir <= self::EPSILON) {
                break;
            }

            $part = min($aRepartir, $reste[$categoryId]);

            if ($part <= self::EPSILON) {
                continue;
            }

            $allocations[$categoryId] = round(($allocations[$categoryId] ?? 0) + $part, 2);
            $reste[$categoryId] = round($reste[$categoryId] - $part, 2);
            $aRepartir = round($aRepartir - $part, 2);
        }

        return [
            'allocations' => $allocations,
            'reste' => $reste,
            'reliquat' => $aRepartir,
        ];
    }
}

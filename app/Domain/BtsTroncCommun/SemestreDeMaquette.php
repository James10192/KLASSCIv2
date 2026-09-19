<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

/**
 * Le semestre auquel une matiere est prevue, dans la maquette BTS.
 *
 * TROIS VALEURS, ET `null` N'EST PAS « VIDE » : 1, 2, ou null qui veut dire
 * « les deux semestres ». C'est la colonne `esbtp_matiere_filiere_niveau.
 * semestre`, et c'est `semestre_renseigne` qui distingue « declare aux deux »
 * de « jamais renseigne ».
 *
 * POURQUOI CETTE CLASSE EXISTE. L'endpoint de chargement ecrivait le semestre
 * demande en dur sur chaque ligne. Charger la maquette du second semestre
 * apres celle du premier faisait donc basculer de 1 a 2 toute matiere commune
 * aux deux : elle disparaissait du premier semestre, sans un mot. Chez ESBTP
 * Abidjan, la maquette de Batiment 2e annee porte aujourd'hui dix matieres
 * figees au semestre 2 par un chargement de ce genre.
 *
 * LA REGLE RETENUE EST DE REFUSER, PAS DE DEVINER. Quand un chargement
 * contredit ce qui est deja declare, on ne choisit pas a la place de l'ecole :
 * « deja au semestre 2, chargee au semestre 1 » peut vouloir dire « elle est
 * aux deux » comme « elle a change de semestre ». Les deux sont frequents et
 * ils n'ont pas le meme bulletin. L'appelant tranche en posant le semestre
 * ligne par ligne ; sans cela, le lot entier est refuse avec la liste des
 * conflits. C'est la meme discipline que la resolution des libelles ambigus,
 * juste au-dessus dans le meme controleur.
 */
final class SemestreDeMaquette
{
    /** Prevue aux deux semestres. */
    public const LES_DEUX = null;

    /** Valeur acceptee dans les charges utiles pour « les deux ». */
    public const MOT_LES_DEUX = 'les_deux';

    /**
     * Traduit ce qu'a ecrit l'appelant en valeur de colonne.
     *
     * Accepte 1, 2, "1", "2", "les_deux", null.
     */
    public static function depuisLaSaisie(mixed $valeur): ?int
    {
        if ($valeur === null || $valeur === '' || $valeur === self::MOT_LES_DEUX) {
            return self::LES_DEUX;
        }

        return (int) $valeur;
    }

    /**
     * Ce chargement contredit-il le semestre deja declare ?
     *
     * Non si la ligne n'a jamais ete renseignee : le chargement la renseigne,
     * il ne la contredit pas. Non non plus si le semestre demande est celui
     * qui est deja pose. Oui dans tous les autres cas, y compris quand la
     * ligne dit « les deux » et que le chargement la restreindrait a un seul.
     */
    public static function estUnConflit(
        ?int $semestreActuel,
        bool $dejaRenseigne,
        ?int $semestreDemande,
    ): bool {
        if (! $dejaRenseigne) {
            return false;
        }

        return $semestreActuel !== $semestreDemande;
    }

    /** Libelle lisible, pour les rapports rendus a l'appelant. */
    public static function libelle(?int $semestre): string
    {
        return match ($semestre) {
            1 => 'semestre 1',
            2 => 'semestre 2',
            default => 'les deux semestres',
        };
    }

    /**
     * Les semestres concrets que recouvre une declaration.
     *
     * `null` en recouvre deux : c'est ce qui permet d'ecrire la place au
     * bulletin dans les deux lignes de `esbtp_maquette_places_semestre`.
     *
     * @return list<int>
     */
    public static function semestresCouverts(?int $semestre): array
    {
        return $semestre === null ? [1, 2] : [$semestre];
    }
}

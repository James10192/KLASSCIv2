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
     * Trois cas, et le troisieme est celui qui a couté cher :
     *
     * - le semestre demande est celui qui est deja pose : rien ne change ;
     * - la ligne ne dit encore RIEN (pas de semestre, jamais validee) : le
     *   chargement la renseigne, il ne la contredit pas ;
     * - la ligne porte deja un semestre : conflit, MEME SI le couple n'a pas
     *   encore ete valide.
     *
     * Cette derniere clause a d'abord ete ecrite a l'envers — on ne signalait
     * que les lignes deja validees — et elle laissait passer exactement le
     * defaut qu'elle devait arreter. Un chargement sans `valider` ecrit le
     * semestre et laisse `semestre_renseigne` a faux : charger le semestre 1
     * puis le semestre 2 de cette facon basculait en silence toute matiere
     * commune aux deux, et c'est la maquette qu'on retrouve aujourd'hui chez
     * ESBTP Abidjan, dix matieres figees au semestre 2.
     *
     * Une valeur inerte n'est pas une valeur absente : elle devient vraie au
     * moment ou quelqu'un valide le couple. La reecrire sans le dire, c'est
     * choisir a la place de l'ecole ce qu'elle validera.
     *
     * Le couple valide qui dit « les deux » (semestre null) reste un conflit
     * quand le chargement le restreindrait a un seul : la validation a fait de
     * ce nul une declaration.
     */
    public static function estUnConflit(
        ?int $semestreActuel,
        bool $dejaRenseigne,
        ?int $semestreDemande,
    ): bool {
        if ($semestreActuel === $semestreDemande) {
            return false;
        }

        if ($semestreActuel === null && ! $dejaRenseigne) {
            return false;
        }

        return true;
    }

    /**
     * Ce qu'une ligne de maquette DECLARE vraiment comme semestre.
     *
     * Une ligne non validee vaut « les deux », quel que soit le semestre
     * qu'elle porte : `ChargementDeMaquette` pose un semestre sans valider, et
     * la maquette d'ESBTP Abidjan en compte (voir plus haut). Lire `semestre`
     * sans consulter `semestre_renseigne` fait donc sortir du bulletin des
     * matieres que le bulletin garde.
     *
     * CETTE NORMALISATION EST LA REGLE, ET ELLE VIT ICI. Elle a ete ecrite
     * trois fois — ici, dans `BtsMaquette::semestresParMatiere()`, et dans
     * `ESBTPMatiereClassificationController::prevueAu()`, ou la troisieme
     * version la remplacait par une garde PAR COUPLE. Les deux premieres
     * s'accordaient, la troisieme divergeait dans son unique cas d'effet : une
     * ligne non validee, dans un combo dont une AUTRE ligne l'etait. L'ecran
     * annoncait alors moins de matieres au semestre 1 que le bulletin n'en
     * portait.
     *
     * Le controle qui rejoue cette phrase, plutot que de la croire :
     *   grep -rn "semestre_renseigne" app/ | grep -v SemestreDeMaquette
     * Toute lecture de cette colonne hors d'ici doit passer par cette methode.
     */
    public static function declarationEffective(?int $semestre, bool $renseigne): ?int
    {
        return $renseigne ? $semestre : null;
    }

    /**
     * Une matiere declaree a `$semestreDeclare` est-elle prevue a `$semestreVise` ?
     *
     * `null` veut dire « les deux », donc oui partout. On lui passe ce que rend
     * `declarationEffective()`, jamais la colonne `semestre` brute.
     */
    public static function estPrevueAu(?int $semestreDeclare, int $semestreVise): bool
    {
        return $semestreDeclare === null || $semestreDeclare === $semestreVise;
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

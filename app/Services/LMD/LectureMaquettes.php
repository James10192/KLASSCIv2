<?php

namespace App\Services\LMD;

use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lit, pour une unite, a quelles maquettes appartient chacun de ses elements.
 *
 * « Maquette » = un parcours a un semestre donne, tel que l'ecran l'affiche
 * deja sous forme de pastille (« BU (S3) »). On evite a dessein les mots
 * « portee » et « commun » cote interface : « commun » designe un ensemble qui
 * bouge — le jour ou un troisieme parcours est rattache a l'unite, tout element
 * commun y bascule sans que personne ne l'ait decide. Une liste de maquettes,
 * elle, dit exactement ce qui est vrai aujourd'hui.
 *
 * Le partage par parcours vit EXCLUSIVEMENT dans le pivot `esbtp_ue_matiere`.
 * `esbtp_matieres.unite_enseignement_id` reste le discriminateur BTS/LMD d'une
 * vingtaine d'ecrans en service : cette classe ne l'ecrit ni ne le lit comme un
 * scope de parcours.
 *
 * La colonne de parcours du pivot n'existe pas encore sur toutes les instances.
 * Tant qu'elle manque, chaque element est lu comme appartenant a TOUTES les
 * maquettes de son unite — c'est exactement ce que l'ecran montre aujourd'hui,
 * donc rien ne change au deploiement. La colonne lue explicitement, jamais via
 * le pivot Eloquent : un `withPivot` oublie rendrait null en silence, et tout
 * element reserve se lirait alors comme partage.
 */
class LectureMaquettes
{
    /** Colonne de scope, ajoutee au pivot par le lot qui rend le partage possible. */
    public const COLONNE_PARCOURS = 'parcours_id';

    /**
     * Valeur qui signifie « visible par toutes les maquettes de l'unite ».
     *
     * Zero plutot que NULL : un index unique ordinaire sur le triplet
     * (unite, matiere, parcours) fonctionne alors sur tous les moteurs, alors
     * qu'avec NULL deux lignes « partagees » pourraient coexister — MySQL ne
     * considere jamais deux NULL comme egaux.
     */
    public const MAQUETTE_PARTAGEE = 0;

    private ?bool $colonneDisponible = null;

    /**
     * Le pivot sait-il a quelle maquette appartient une ligne ?
     */
    public function pivotPorteLeParcours(): bool
    {
        if ($this->colonneDisponible === null) {
            $this->colonneDisponible = Schema::hasColumn('esbtp_ue_matiere', self::COLONNE_PARCOURS);
        }

        return $this->colonneDisponible;
    }

    /**
     * Pour chaque element de l'unite, la liste des parcours auxquels il est
     * reserve. Une liste vide signifie « toutes les maquettes de l'unite ».
     *
     * @return array<int, int[]> matiere_id => parcours_ids
     */
    public function parcoursParEcue(ESBTPUniteEnseignement $ue): array
    {
        if (! $this->pivotPorteLeParcours()) {
            return [];
        }

        $lignes = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->get(['matiere_id', self::COLONNE_PARCOURS]);

        $parEcue = [];
        foreach ($lignes as $ligne) {
            $matiereId = (int) $ligne->matiere_id;
            $parcoursId = (int) ($ligne->{self::COLONNE_PARCOURS} ?? self::MAQUETTE_PARTAGEE);

            $parEcue[$matiereId] ??= [];

            if ($parcoursId !== self::MAQUETTE_PARTAGEE) {
                $parEcue[$matiereId][] = $parcoursId;
            }
        }

        return $parEcue;
    }

    /**
     * Un element est-il visible depuis la maquette de travail ?
     *
     * @param int[] $parcoursDeLElement liste vide = partage par toute l'unite
     */
    public static function visibleDepuis(array $parcoursDeLElement, ?int $parcoursTravailId): bool
    {
        if ($parcoursDeLElement === []) {
            return true;
        }

        if ($parcoursTravailId === null) {
            return true;
        }

        return in_array($parcoursTravailId, $parcoursDeLElement, true);
    }

    /**
     * Les deux nombres que la ligne d'unite affiche sans qu'on la deplie :
     * combien d'elements, et combien d'entre eux sont propres a un parcours.
     *
     * Quand une maquette de travail est choisie, on ne compte QUE ce que cette
     * maquette voit — un compteur qui additionne les elements des autres
     * parcours ment a la personne qui saisit.
     *
     * @param array<int, int[]> $parcoursParEcue matiere_id => parcours_ids
     * @param int[] $idsElements identifiants des elements de l'unite
     * @return array{visibles:int, reserves:int}
     */
    public static function compter(array $parcoursParEcue, array $idsElements, ?int $parcoursTravailId): array
    {
        $visibles = 0;
        $reserves = 0;

        foreach ($idsElements as $id) {
            $parcours = $parcoursParEcue[(int) $id] ?? [];

            if (! self::visibleDepuis($parcours, $parcoursTravailId)) {
                continue;
            }

            $visibles++;

            if ($parcours !== []) {
                $reserves++;
            }
        }

        return ['visibles' => $visibles, 'reserves' => $reserves];
    }
}

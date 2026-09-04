<?php

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;

/**
 * Décide si une unité d'enseignement peut être supprimée, et la supprime
 * proprement.
 *
 * Le code d'une UE est unique dans toute l'école : la même unité est donc
 * RÉELLEMENT partagée par plusieurs parcours, jamais dupliquée. Supprimer une
 * unité partagée n'était pourtant gardé que par l'existence de résultats LMD :
 * une maquette saisie mais pas encore notée passait sans un mot, et les
 * éléments constitutifs des DEUX parcours partaient d'un coup.
 *
 * Deux effets de bord tenaient au geste lui-même :
 *
 * - `esbtp_matieres.unite_enseignement_id` remis à nul verse les éléments dans
 *   le catalogue BTS : une vingtaine d'écrans en service — évaluations, notes,
 *   examens, sélecteurs de matières — lisent cette colonne comme le
 *   discriminateur BTS/LMD (`scopeBtsOnly` / `scopeLmdOnly`). Ce que le
 *   réglage ci-dessous permet désormais de refuser, par instance ;
 * - l'unité étant en suppression douce, aucune cascade ne se déclenche : les
 *   lignes de `esbtp_ue_matiere` et de `esbtp_lmd_parcours_ue` survivaient en
 *   désignant une unité supprimée.
 */
class SuppressionUeService
{
    /**
     * Faut-il rendre au cursus BTS les éléments constitutifs d'une unité
     * supprimée ?
     *
     * La question se tranche différemment d'une école à l'autre, donc elle se
     * règle. Une école tout-LMD (USAT) n'a pas de catalogue BTS où les verser
     * et coupera le réglage ; une école mixte qui s'appuie sur ce report depuis
     * l'origine le garde.
     *
     * Le défaut REPRODUIT le comportement d'aujourd'hui : un déploiement ne
     * change rien tant que personne n'a décidé le contraire.
     */
    public const REGLAGE_LIBERER_ECUES = 'lmd_suppression_ue_libere_ecues_vers_bts';

    /**
     * Les parcours auxquels l'unité appartient encore, colonne et pivot réunis.
     *
     * Les deux voies coexistent : l'import de maquettes renseigne la colonne
     * `parcours_id`, le modal « Lier à des parcours » écrit dans le pivot. Ne
     * regarder qu'une des deux laisserait passer un partage.
     *
     * Un parcours en suppression douce ne compte pas : il ne retient plus rien.
     *
     * @return list<array{id:int, code:?string, name:?string, libelle:string}>
     */
    public function parcoursRattaches(ESBTPUniteEnseignement $ue): array
    {
        $ids = DB::table('esbtp_lmd_parcours_ue')
            ->where('unite_enseignement_id', $ue->id)
            ->pluck('parcours_id')
            ->all();

        if ($ue->parcours_id !== null) {
            $ids[] = $ue->parcours_id;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        return ESBTPLMDParcours::whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (ESBTPLMDParcours $parcours) => [
                'id' => (int) $parcours->id,
                'code' => $parcours->code,
                'name' => $parcours->name,
                'libelle' => self::libelleParcours($parcours->name, $parcours->code),
            ])
            ->all();
    }

    /**
     * Le message de refus, ou null si la suppression peut avoir lieu.
     */
    public function refusSiPartagee(ESBTPUniteEnseignement $ue): ?string
    {
        $parcours = $this->parcoursRattaches($ue);

        if (count($parcours) < 2) {
            return null;
        }

        return self::formaterRefusPartage(array_column($parcours, 'libelle'));
    }

    /**
     * Le refus NOMME les maquettes concernées : sans elles, la directrice des
     * études ne sait pas où aller retirer l'unité, et le refus devient un mur.
     *
     * @param  list<string>  $libelles
     */
    public static function formaterRefusPartage(array $libelles): string
    {
        return sprintf(
            'Impossible de supprimer cette unité : elle est encore inscrite dans %d maquettes (%s). '
            . 'Retirez-la d\'abord de chacune d\'elles depuis « Lier à des parcours », '
            . 'puis supprimez-la depuis la dernière. La supprimer ici la retirerait des %d d\'un coup.',
            count($libelles),
            implode(', ', $libelles),
            count($libelles)
        );
    }

    /**
     * « Bâtiment et Urbanisme (BU) » — le nom pour se repérer, le code parce
     * que c'est lui qui figure sur les maquettes papier.
     */
    public static function libelleParcours(?string $name, ?string $code): string
    {
        $name = trim((string) $name);
        $code = trim((string) $code);

        if ($name === '') {
            return $code !== '' ? $code : '(parcours sans nom)';
        }

        return $code !== '' ? sprintf('%s (%s)', $name, $code) : $name;
    }

    /**
     * Supprime l'unité et les liens qu'aucune cascade ne nettoie.
     *
     * L'appelant a déjà vérifié qu'elle n'est pas partagée et qu'aucun résultat
     * n'y est rattaché.
     */
    public function supprimer(ESBTPUniteEnseignement $ue): void
    {
        DB::transaction(function () use ($ue) {
            // La suppression est douce : la contrainte `cascadeOnDelete` de ces
            // deux pivots ne se déclenchera jamais. Sans ce nettoyage, leurs
            // lignes désignent indéfiniment une unité supprimée.
            DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)->delete();
            DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->delete();

            if ($this->libereEcuesVersBts()) {
                $ue->matieres()->update([
                    'unite_enseignement_id' => null,
                    'updated_by' => auth()->id(),
                ]);
            }

            $ue->delete();
        });
    }

    /**
     * Lecture du réglage. Le défaut vaut « oui » : c'est ce que le code faisait
     * avant que la question ne se pose.
     */
    public function libereEcuesVersBts(): bool
    {
        return self::interpreterBooleen(SettingsHelper::get(self::REGLAGE_LIBERER_ECUES, true), true);
    }

    /**
     * Les réglages sont stockés en texte : « 0 », « false » et « non » valent
     * non. Toute autre valeur renseignée vaut oui, l'absence vaut le défaut.
     */
    public static function interpreterBooleen(mixed $valeur, bool $defaut): bool
    {
        if ($valeur === null || $valeur === '') {
            return $defaut;
        }

        if (is_bool($valeur)) {
            return $valeur;
        }

        if (is_int($valeur)) {
            return $valeur !== 0;
        }

        return ! in_array(mb_strtolower(trim((string) $valeur)), ['0', 'false', 'non', 'no', 'off'], true);
    }
}

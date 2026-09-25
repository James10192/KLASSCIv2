<?php

namespace App\Domain\Lms\Synchronisation;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\DB;

/**
 * Une page de « ce qui a change depuis » : parcourt les flux demandes dans un
 * ordre fixe, jusqu'a remplir la page, puis les suppressions definitives.
 *
 * Chaque flux garde sa propre position dans le curseur. Une page pleine
 * s'arrete au milieu d'un flux ; l'appel suivant reprend exactement la.
 */
final class SynchronisationLms
{
    /**
     * Les lignes modifiees depuis moins de ce delai attendent l'appel suivant.
     * Eloquent date une ligne au `save()`, DANS la transaction, pas a sa
     * validation : une transaction encore ouverte (un import de maquette LMD
     * tient tout dans une seule) validera plus tard des lignes datees d'avant
     * le curseur, qui ne repartiraient jamais. Le LMS n'appelle que toutes les
     * 5 minutes : un delai large ne coute rien.
     *
     * Limite assumee : une transaction plus longue que ce delai peut encore
     * faire perdre une ligne.
     */
    public const DECALAGE_PAR_DEFAUT = 120;

    public static function decalageSecondes(): int
    {
        return max(5, (int) SettingsHelper::get('lms.sync.decalage_secondes', self::DECALAGE_PAR_DEFAUT));
    }

    /**
     * @param  array<int, string>  $types
     * @return array{changements: array<int, array>, curseur: CurseurDeSynchronisation, a_suivre: bool}
     */
    public function page(array $types, CurseurDeSynchronisation $curseur, int $limite): array
    {
        $borne = now()->subSeconds(self::decalageSecondes())->format('Y-m-d H:i:s');
        $reste = $limite;
        $aSuivre = false;
        $changements = [];

        foreach (FluxDeSynchronisation::TYPES as $type) {
            if (! in_array($type, $types, true)) {
                continue;
            }
            if ($reste === 0) {
                $aSuivre = true;
                break;
            }

            [$lignes, $plein] = $this->lire($type, $curseur, $borne, $reste);
            foreach ($lignes as $r) {
                $changements[] = $this->changement($type, $r);
            }
            if ($dernier = $lignes->last()) {
                $curseur->positions[$type] = [(string) $dernier->modifie_le, (int) $dernier->id];
            }
            $reste -= $lignes->count();
            $aSuivre = $aSuivre || $plein;
        }

        if ($reste === 0) {
            $aSuivre = true;
        } else {
            [$suppressions, $plein] = $this->suppressionsDefinitives($curseur, $borne, $reste);
            array_push($changements, ...$suppressions);
            $aSuivre = $aSuivre || $plein;
        }

        return ['changements' => $changements, 'curseur' => $curseur, 'a_suivre' => $aSuivre];
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: bool} les lignes, et s'il en reste */
    private function lire(string $type, CurseurDeSynchronisation $curseur, string $borne, int $reste): array
    {
        [$requete, $date] = FluxDeSynchronisation::requete($type, $curseur->anneeId);

        if ($position = $curseur->positions[$type] ?? null) {
            [$t, $id] = $position;
            $requete->whereRaw("({$date} > ? OR ({$date} = ? AND t.id > ?))", [$t, $t, $id]);
        }

        $lignes = $requete->selectRaw("{$date} as modifie_le")
            ->whereRaw("{$date} <= ?", [$borne])
            ->orderByRaw($date)
            ->orderBy('t.id')
            ->limit($reste + 1)
            ->get();

        $plein = $lignes->count() > $reste;

        return [$plein ? $lignes->take($reste) : $lignes, $plein];
    }

    private function changement(string $type, object $r): array
    {
        $supprime = $r->deleted_at !== null;

        return array_filter([
            'type' => FluxDeSynchronisation::OBJET[$type],
            'id' => (int) $r->id,
            'supprime' => $supprime,
            'modifie_le' => (string) $r->modifie_le,
            'donnees' => $supprime ? null : FluxDeSynchronisation::donnees($type, $r),
        ], fn ($v) => $v !== null);
    }

    /**
     * Tous types confondus, meme ceux que `types=` n'a pas demandes : ainsi
     * le curseur reste valable si le LMS change sa liste de types.
     *
     * @return array{0: array<int, array>, 1: bool}
     */
    private function suppressionsDefinitives(CurseurDeSynchronisation $curseur, string $borne, int $reste): array
    {
        $lignes = DB::table('lms_suppressions')
            ->where('id', '>', $curseur->derniereSuppression)
            ->where('supprime_le', '<=', $borne)
            ->orderBy('id')
            ->limit($reste + 1)
            ->get();

        $plein = $lignes->count() > $reste;
        $lignes = $plein ? $lignes->take($reste) : $lignes;

        if ($dernier = $lignes->last()) {
            $curseur->derniereSuppression = (int) $dernier->id;
        }

        return [$lignes->map(fn ($s) => [
            'type' => $s->type,
            'id' => (int) $s->objet_id,
            'supprime' => true,
            'definitif' => true,
            'modifie_le' => (string) $s->supprime_le,
        ])->all(), $plein];
    }

    /** Point de depart d'une premiere synchronisation : les purges passees n'interessent pas le LMS. */
    public static function curseurInitial(?int $anneeId): CurseurDeSynchronisation
    {
        return new CurseurDeSynchronisation($anneeId, [], (int) DB::table('lms_suppressions')->max('id'));
    }
}

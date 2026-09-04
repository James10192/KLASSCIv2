<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Les ecritures de la composition d'une unite d'enseignement.
 *
 * Le code d'une unite est unique dans l'ecole : la meme unite sert reellement
 * plusieurs parcours. Sa composition, elle, peut differer de l'un a l'autre —
 * « Resistance des materiaux » n'a pas forcement les memes elements en Batiment
 * qu'en Travaux Publics. Le pivot `esbtp_ue_matiere` porte donc un `parcours_id`,
 * et son unicite tient sur le TRIPLET (unite, matiere, parcours). Zero y designe
 * la composition commune, valable pour toutes les maquettes.
 *
 * Cette classe existe parce que les primitives d'Eloquent ne savent PAS lire ce
 * triplet, et se trompent en silence :
 *
 * - `syncWithoutDetaching([$id => [...]])` retrouve la ligne existante par le
 *   seul `matiere_id` et fait un UPDATE dessus. Poser un element commun sur une
 *   unite qui en a deja une version reservee REECRIRAIT donc cette reservation,
 *   et l'element changerait de maquette sans que personne l'ait demande.
 * - `detach($id)` supprime TOUTES les lignes de cette matiere, toutes maquettes
 *   confondues. Retirer un element de la maquette Batiment le retirerait aussi
 *   de Travaux Publics.
 *
 * Aucune de ces deux erreurs ne leve d'exception. C'est pour cela qu'aucun
 * appelant ne doit plus toucher `ecues()->attach/detach/sync` directement : tout
 * passe ici, ou le parcours est un argument.
 */
class CompositionUe
{
    /**
     * La composition commune a toutes les maquettes.
     *
     * Zero, et non `null` : deux `null` sont DISTINCTS dans un index unique sur
     * MySQL et MariaDB. Avec `null`, la contrainte ne mordrait pas sur la
     * composition commune — celle qui porte le plus de lignes — et le meme
     * element pourrait y figurer autant de fois qu'on l'enregistre.
     */
    public const COMMUN = 0;

    /**
     * Pose ou met a jour un element dans UNE maquette.
     *
     * @param  array{coefficient_ecue?: float|null, credit_ecue?: int|null, ordre_bulletin?: int}  $valeurs
     */
    public function poser(
        ESBTPUniteEnseignement $ue,
        int $matiereId,
        array $valeurs,
        int $parcoursId = self::COMMUN
    ): void {
        $cle = [
            'unite_enseignement_id' => $ue->id,
            'matiere_id' => $matiereId,
            'parcours_id' => $parcoursId,
        ];

        $donnees = [
            'coefficient_ecue' => $valeurs['coefficient_ecue'] ?? null,
            'credit_ecue' => $valeurs['credit_ecue'] ?? null,
            'ordre_bulletin' => (int) ($valeurs['ordre_bulletin'] ?? 0),
            'updated_at' => now(),
        ];

        // `created_at` seulement a la creation. Le laisser dans la charge d'un
        // `updateOrInsert` reecrivait la date de creation a chaque changement de
        // coefficient : on ne saurait plus depuis quand l'element figure dans la
        // maquette.
        if (! DB::table('esbtp_ue_matiere')->where($cle)->exists()) {
            $donnees['created_at'] = now();
        }

        DB::table('esbtp_ue_matiere')->updateOrInsert($cle, $donnees);
    }

    /**
     * Retire des elements d'UNE maquette, et d'elle seule.
     *
     * La cle etrangere `esbtp_matieres.unite_enseignement_id` n'est PAS touchee
     * ici : elle est globale, elle ne connait pas les maquettes, et c'est aussi
     * le discriminateur BTS/LMD que vingt ecrans en service interrogent. La vider
     * pour retirer un element d'une seule maquette le verserait au catalogue BTS
     * des deux ecoles qui melangent les cursus. Ce geste-la se demande a part,
     * par `libererCleEtrangere()`.
     *
     * @param  array<int, int>  $matiereIds
     * @return int nombre de lignes retirees
     */
    public function retirer(
        ESBTPUniteEnseignement $ue,
        array $matiereIds,
        int $parcoursId = self::COMMUN
    ): int {
        if ($matiereIds === []) {
            return 0;
        }

        return DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->whereIn('matiere_id', $matiereIds)
            ->where('parcours_id', $parcoursId)
            ->delete();
    }

    /**
     * Les identifiants des elements que porte UNE maquette.
     *
     * La composition commune est incluse quand on interroge un parcours : une
     * maquette, c'est ce qui lui est propre PLUS ce que l'ecole a pose pour tout
     * le monde. Sans parcours, on rend tout, toutes maquettes confondues.
     *
     * @return Collection<int, int>
     */
    public function idsDe(ESBTPUniteEnseignement $ue, ?int $parcoursId = null): Collection
    {
        $requete = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id);

        if ($parcoursId !== null) {
            $requete->whereIn('parcours_id', [self::COMMUN, $parcoursId]);
        }

        return $requete->pluck('matiere_id')->map(fn ($id) => (int) $id)->unique()->values();
    }

    /**
     * La somme des credits que porte UNE maquette.
     *
     * Ni la somme de toutes les lignes, ni celle des seules reservees :
     * l'UNION DEDUPLIQUEE, un element une fois, sa ligne reservee primant sur la
     * commune. C'est exactement ce que le bulletin comptera.
     *
     * Sommer toutes les lignes faisait depasser le plafond des qu'une seconde
     * maquette existait, et plus rien ne pouvait etre ajoute nulle part. Ne
     * sommer que les reservees laissait au contraire reserver a l'infini sur une
     * unite deja pourvue en commun : le plafond ne mordait jamais, et
     * l'invariant UEMOA des trente credits par semestre devenait franchissable
     * en silence.
     *
     * @param  array<int, int>  $matieresExclues  ignorees du total (l'element en cours d'edition)
     */
    public function creditsDe(
        ESBTPUniteEnseignement $ue,
        int $parcoursId = self::COMMUN,
        array $matieresExclues = []
    ): int {
        $lignes = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->whereIn('parcours_id', array_unique([self::COMMUN, $parcoursId]))
            ->when($matieresExclues !== [], fn ($q) => $q->whereNotIn('matiere_id', $matieresExclues))
            ->get(['matiere_id', 'parcours_id', 'credit_ecue']);

        $retenus = [];
        foreach ($lignes as $ligne) {
            $matiere = (int) $ligne->matiere_id;
            $portee = (int) $ligne->parcours_id;

            // La reservee prime : elle ecrase la commune si les deux existent.
            if (! isset($retenus[$matiere]) || $portee !== self::COMMUN) {
                $retenus[$matiere] = (int) $ligne->credit_ecue;
            }
        }

        return array_sum($retenus);
    }

    /**
     * Reprend dans le pivot ce que la cle etrangere disait deja.
     *
     * Une unite dont le pivot est vide lit ses elements par `unite_enseignement_id`
     * (l'import de maquettes n'ecrit que cette colonne). Des qu'on s'apprete a
     * partager un de ses elements avec une autre unite, ce repli cesse de la
     * proteger : il faut d'abord graver sa composition dans le pivot, sinon les
     * valeurs qu'on posera sur la matiere deviendraient les siennes.
     *
     * Ce qui est repris l'est en COMMUN : c'est ce que la lecture affichait, et
     * l'ecran ne doit pas changer.
     */
    public function materialiserDepuisCleEtrangere(int $uniteEnseignementId): void
    {
        $unite = ESBTPUniteEnseignement::find($uniteEnseignementId);

        if (! $unite) {
            return;
        }

        // La garde porte sur la composition COMMUNE. Tester l'existence de
        // n'importe quelle ligne rendrait cette materialisation impossible des
        // qu'un seul element aurait ete reserve a une maquette — et l'unite
        // resterait alors exposee au depouillement que cette methode previent.
        $dejaGrave = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $unite->id)
            ->where('parcours_id', self::COMMUN)
            ->exists();

        if ($dejaGrave) {
            return;
        }

        // Meme perimetre que le repli de getEcuesEffectifs() : les actives.
        foreach ($unite->matieres()->where('is_active', true)->get() as $ecue) {
            $this->poser($unite, (int) $ecue->id, [
                'coefficient_ecue' => $ecue->coefficient_ecue,
                'credit_ecue' => $ecue->credit_ecue,
                'ordre_bulletin' => (int) ($ecue->ordre_bulletin ?? 0),
            ]);
        }
    }

    /**
     * Normalise ce qu'un ecran envoie comme portee.
     *
     * Une chaine vide, un zero ou l'absence valent « commun » : c'est le defaut
     * legitime, et la portee la plus courante.
     *
     * Un parcours DESIGNE mais non rattache a l'unite, en revanche, est une
     * erreur et se dit. Retomber silencieusement sur « commun » ferait ecrire
     * dans la composition partagee par TOUTES les maquettes, par le chemin le
     * plus discret : le plus large rayon d'action atteint par la plus petite
     * faute de frappe.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function porteeValide(ESBTPUniteEnseignement $ue, mixed $parcoursId): int
    {
        $demande = (int) ($parcoursId ?: 0);

        if ($demande === self::COMMUN) {
            return self::COMMUN;
        }

        $rattache = (int) ($ue->parcours_id ?? 0) === $demande
            || DB::table('esbtp_lmd_parcours_ue')
                ->where('unite_enseignement_id', $ue->id)
                ->where('parcours_id', $demande)
                ->exists();

        if (! $rattache) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parcours_id' => sprintf(
                    "Le parcours demandé n'utilise pas l'unité « %s » : rattachez-l'y d'abord, "
                    . 'depuis « Lier à des parcours ».',
                    $ue->name ?? $ue->code
                ),
            ]);
        }

        return $demande;
    }

    /**
     * Libere la cle etrangere des elements qui ne figurent plus dans AUCUNE
     * maquette de cette unite.
     *
     * Retirer un element de la seule maquette Batiment ne doit pas le detacher de
     * l'unite : Travaux Publics s'en sert encore. On ne coupe que ce qui n'est
     * plus rattache nulle part.
     *
     * @param  array<int, int>  $matiereIds
     */
    public function libererCleEtrangere(ESBTPUniteEnseignement $ue, array $matiereIds): void
    {
        if ($matiereIds === []) {
            return;
        }

        // Une unite dont le pivot n'a JAMAIS ete ecrit rendrait ce test vrai par
        // vacuite : aucune ligne ne designe l'element, donc il passerait pour
        // orphelin, et sa cle serait coupee alors qu'elle etait le SEUL lien.
        // L'element quitterait toutes les maquettes d'un coup et tomberait dans
        // le catalogue BTS, ou une vingtaine d'ecrans en service l'afficheraient.
        // On grave donc la composition commune avant de juger.
        $this->materialiserDepuisCleEtrangere($ue->id);

        $encoreLiees = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->whereIn('matiere_id', $matiereIds)
            ->pluck('matiere_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $orphelines = array_values(array_diff($matiereIds, $encoreLiees));

        if ($orphelines === []) {
            return;
        }

        ESBTPMatiere::whereIn('id', $orphelines)
            ->where('unite_enseignement_id', $ue->id)
            ->update(['unite_enseignement_id' => null, 'updated_by' => auth()->id()]);
    }
}

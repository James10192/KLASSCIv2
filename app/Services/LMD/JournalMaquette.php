<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDJournalMaquette;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enveloppe un geste qui touche les maquettes d'une unite : il laisse une trace
 * consultable, et il redevient annulable.
 *
 * L'instantane est pris avec SELECT * sur les deux pivots plutot que colonne
 * par colonne : le schema du pivot des elements va gagner une colonne de
 * parcours, et un instantane generique l'emporte sans qu'on ait a revenir ici.
 *
 * Ce que l'annulation ne fait JAMAIS : remettre a null
 * `esbtp_matieres.unite_enseignement_id` sur une matiere qu'elle n'a pas vue
 * avant le geste. Cette colonne est le discriminateur BTS/LMD d'une vingtaine
 * d'ecrans en service ; la vider sur une matiere creee entre-temps la ferait
 * apparaitre dans les selecteurs BTS de deux ecoles de 2000 inscrits.
 */
class JournalMaquette
{
    /** Gestes reversibles : ils ne creent aucune matiere, ils ne font que relier. */
    public const ACTIONS_ANNULABLES = ['liaison_parcours', 'retrait_element'];

    /**
     * Entree ecrite par le dernier appel a enregistrer(), ou null si le geste
     * n'a rien change. L'ecran s'en sert pour proposer « Annuler » la ou le
     * geste vient d'avoir lieu, sans avoir a rouvrir le journal.
     */
    public ?ESBTPLMDJournalMaquette $derniereEntree = null;

    /**
     * Joue le geste, puis enregistre l'ecart s'il y en a un.
     *
     * @template T
     * @param  callable():T  $geste
     * @return T
     */
    public function enregistrer(
        ESBTPUniteEnseignement $ue,
        string $action,
        string $libelle,
        callable $geste
    ) {
        $this->derniereEntree = null;

        return DB::transaction(function () use ($ue, $action, $libelle, $geste) {
            // Le peripherie est fige AVANT : un retrait vide la cle etrangere,
            // la matiere sortirait donc de l'instantane d'apres et ne pourrait
            // plus etre restauree.
            $idsMatieres = $this->matieresConcernees($ue->id);

            $avant = $this->instantane($ue->id, $idsMatieres);
            $resultat = $geste();
            $idsMatieres = array_values(array_unique(array_merge(
                $idsMatieres,
                $this->matieresConcernees($ue->id)
            )));
            $apres = $this->instantane($ue->id, $idsMatieres);

            // Un geste sans effet ne merite pas une ligne de journal : la trace
            // doit rester lisible pour qui la consulte trois mois plus tard.
            if ($this->memeEtat($avant, $apres)) {
                return $resultat;
            }

            $this->derniereEntree = ESBTPLMDJournalMaquette::create([
                'unite_enseignement_id' => $ue->id,
                'action' => $action,
                'libelle' => $libelle,
                'etat_avant' => $avant,
                'etat_apres' => $apres,
                'created_by' => auth()->id(),
            ]);

            return $resultat;
        });
    }

    /**
     * Repose l'etat d'avant le geste.
     *
     * Refuse si l'etat courant n'est plus celui laisse par le geste : quelqu'un
     * d'autre a modifie l'unite entre-temps, et ecraser son travail au nom d'un
     * « annuler » serait pire que le geste qu'on annule.
     */
    public function annuler(ESBTPLMDJournalMaquette $entree): void
    {
        if ($entree->estAnnulee()) {
            throw ValidationException::withMessages([
                'journal' => 'Ce geste a déjà été annulé.',
            ]);
        }

        if (! in_array($entree->action, self::ACTIONS_ANNULABLES, true)) {
            throw ValidationException::withMessages([
                'journal' => "Ce geste ne s'annule pas : il a créé un élément. Retirez-le de l'unité si vous ne le vouliez pas.",
            ]);
        }

        DB::transaction(function () use ($entree) {
            $avant = $entree->etat_avant ?? [];
            $apres = $entree->etat_apres ?? [];
            $ids = array_column($avant['matieres'] ?? [], 'id');
            $idsApres = array_column($apres['matieres'] ?? [], 'id');
            $courant = $this->instantane(
                $entree->unite_enseignement_id,
                array_values(array_unique(array_merge($ids, $idsApres)))
            );

            if (! $this->memeEtat($courant, $apres)) {
                throw ValidationException::withMessages([
                    'journal' => "L'unité a changé depuis ce geste : l'annulation écraserait une modification plus récente. Reprenez la main à la main.",
                ]);
            }

            DB::table('esbtp_lmd_parcours_ue')
                ->where('unite_enseignement_id', $entree->unite_enseignement_id)->delete();
            DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $entree->unite_enseignement_id)->delete();

            foreach ($avant['parcours_ue'] ?? [] as $ligne) {
                DB::table('esbtp_lmd_parcours_ue')->insert((array) $ligne);
            }
            foreach ($avant['ue_matiere'] ?? [] as $ligne) {
                DB::table('esbtp_ue_matiere')->insert((array) $ligne);
            }

            // Uniquement les matieres vues avant le geste, jamais une autre.
            foreach ($avant['matieres'] ?? [] as $matiere) {
                DB::table('esbtp_matieres')
                    ->where('id', $matiere['id'])
                    ->update(['unite_enseignement_id' => $matiere['unite_enseignement_id']]);
            }

            $entree->forceFill([
                'annulee_at' => now(),
                'annulee_par' => auth()->id(),
            ])->save();
        });
    }

    /**
     * Les matieres que l'unite touche : celles qu'elle tient par la cle
     * etrangere, et celles que son pivot referencie.
     *
     * @return int[]
     */
    private function matieresConcernees(int $ueId): array
    {
        $parCle = DB::table('esbtp_matieres')->where('unite_enseignement_id', $ueId)->pluck('id');
        $parPivot = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ueId)->pluck('matiere_id');

        return $parCle->merge($parPivot)->unique()->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param  int[]  $idsMatieres
     * @return array{parcours_ue: array, ue_matiere: array, matieres: array}
     */
    private function instantane(int $ueId, array $idsMatieres): array
    {
        $lignes = fn ($collection) => $collection->map(fn ($ligne) => (array) $ligne)->values()->all();

        return [
            'parcours_ue' => $lignes(
                DB::table('esbtp_lmd_parcours_ue')
                    ->where('unite_enseignement_id', $ueId)->orderBy('id')->get()
            ),
            'ue_matiere' => $lignes(
                DB::table('esbtp_ue_matiere')
                    ->where('unite_enseignement_id', $ueId)->orderBy('id')->get()
            ),
            'matieres' => $lignes(
                DB::table('esbtp_matieres')
                    ->whereIn('id', $idsMatieres ?: [0])
                    ->orderBy('id')->get(['id', 'unite_enseignement_id'])
            ),
        ];
    }

    /**
     * Compare deux instantanes en ignorant les horodatages : un simple
     * `updated_at` rafraichi ne doit ni creer une entree de journal vide, ni
     * faire echouer une annulation legitime.
     */
    private function memeEtat(array $a, array $b): bool
    {
        return $this->normaliser($a) == $this->normaliser($b);
    }

    private function normaliser(array $etat): array
    {
        foreach ($etat as $table => $lignes) {
            $etat[$table] = array_map(function ($ligne) {
                $ligne = (array) $ligne;
                unset($ligne['created_at'], $ligne['updated_at']);
                ksort($ligne);

                return array_map(fn ($v) => is_null($v) ? null : (string) $v, $ligne);
            }, $lignes);
        }

        return $etat;
    }
}

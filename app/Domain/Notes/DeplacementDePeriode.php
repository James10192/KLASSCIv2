<?php

namespace App\Domain\Notes;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Support\Facades\DB;

/**
 * Deplacer une liste d'evaluations d'un semestre vers un autre, sur decision
 * humaine : rien dans la donnee ne permet de deviner la bonne periode (les
 * dates des deux semestres se chevauchent).
 *
 * Partage par la CLI (`POST /api/cli/evaluations/deplacer-periode`) et par
 * Nanan (`proposer_deplacement_periode`) : l'apercu sert la simulation comme
 * l'execution, pour que les deux disent la meme chose, et l'ecriture recalcule
 * les agregats des deux semestres (piege #7 de klassci-debugging-discipline).
 */
final class DeplacementDePeriode
{
    /**
     * Au-dela, on demande de decouper l'appel : la transaction reste courte
     * et la reponse reste lisible pour celui qui la relit.
     */
    public const LOT_MAX = 200;

    /**
     * Ce que le lot ferait : les evaluations a deplacer, celles deja sur la
     * cible, celles qu'on n'a pas trouvees.
     *
     * @param  array<int,int>  $ids
     * @return array{0:array<int,array<string,mixed>>, 1:array<int,array<string,mixed>>, 2:array<int,int>}
     */
    public function apercu(array $ids, string $cible): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $evaluations = ESBTPEvaluation::query()
            ->whereIn('id', $ids)
            ->with(['classe:id,name', 'matiere:id,name'])
            ->get()
            ->keyBy('id');

        $introuvables = array_values(array_diff($ids, $evaluations->keys()->all()));

        $aDeplacer = [];
        $deja = [];

        foreach ($ids as $id) {
            $evaluation = $evaluations->get($id);
            if (! $evaluation) {
                continue;
            }

            $ligne = [
                'evaluation_id' => (int) $evaluation->id,
                'titre' => $evaluation->titre,
                'classe' => $evaluation->classe->name ?? null,
                // Les identifiants, et pas seulement les noms : le message de
                // repli renvoie vers `POST /api/cli/notes/recompute`, qui EXIGE
                // classe_id et annee_universitaire_id.
                'classe_id' => (int) $evaluation->classe_id,
                'annee_universitaire_id' => (int) $evaluation->annee_universitaire_id,
                'matiere_id' => $evaluation->matiere_id !== null ? (int) $evaluation->matiere_id : null,
                'matiere' => $evaluation->matiere->name ?? null,
                'date' => optional($evaluation->date_evaluation)->toDateString(),
                'periode_actuelle' => $evaluation->periode,
                'periode_cible' => $cible,
                'nb_notes' => ESBTPNote::where('evaluation_id', $evaluation->id)->count(),
            ];

            // Deja sur la bonne periode : on le dit, on ne le compte pas comme
            // un deplacement. Relancer le meme appel deux fois reste sans effet.
            if (ESBTPEvaluation::numeroDeSemestre((string) $evaluation->periode)
                === ESBTPEvaluation::numeroDeSemestre($cible)) {
                $deja[] = $ligne;

                continue;
            }

            $aDeplacer[] = $ligne;
        }

        return [$aDeplacer, $deja, $introuvables];
    }

    /**
     * Deplace, realigne les notes, puis recalcule les agregats. Le recalcul
     * reste HORS de la transaction : il tourne sur place et ne doit pas la
     * tenir ouverte, et le deplacement reste acquis s'il echoue.
     *
     * @param  array<int,array<string,mixed>>  $aDeplacer  lignes rendues par apercu()
     * @return array{traitees: array<int,array<string,mixed>>, recalcul: array<string,mixed>}
     */
    public function appliquer(array $aDeplacer, string $cible, ?int $declencheur): array
    {
        $traitees = [];
        $deplacees = [];

        DB::transaction(function () use ($aDeplacer, $cible, &$traitees, &$deplacees) {
            foreach ($aDeplacer as $ligne) {
                $evaluation = ESBTPEvaluation::find($ligne['evaluation_id']);
                if (! $evaluation) {
                    continue;
                }

                $avant = $evaluation->periode;
                $evaluation->periode = $cible;
                $evaluation->save();

                // L'encodage de cette colonne vit sur le modele, avec le hook
                // qui le decide. Voir ESBTPNote::realignerLeSemestre().
                $notes = ESBTPNote::realignerLeSemestre($evaluation->id, $evaluation->periode);

                $deplacees[] = ['evaluation' => $evaluation, 'periode_avant' => $avant];

                $traitees[] = $ligne + [
                    'periode_avant' => $avant,
                    'notes_realignees' => $notes,
                ];
            }
        });

        // Un `update()` de query builder n'emet aucun evenement Eloquent :
        // `periode` etant une coordonnee de la cle d'`esbtp_resultats`, les deux
        // semestres garderaient la moyenne d'avant sans ce recalcul.
        $recalcul = RecalculApresDeplacement::pourUnLotDePeriodes($deplacees, $declencheur);

        return ['traitees' => $traitees, 'recalcul' => $recalcul];
    }
}

<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deplacement explicite d'evaluations d'un semestre vers un autre.
 *
 * La periode d'une evaluation est saisie a la main. Rien dans la donnee ne
 * permet de deviner la bonne : les dates des deux semestres se chevauchent
 * (a l'ESBTP Abidjan, semestre1 et semestre2 couvrent tous deux octobre a
 * aout). Un meme lot cree le meme jour se retrouve range en S1 pour une
 * classe et en S2 pour la voisine, et le bulletin du semestre s'en trouve
 * incomplet ou pollue selon le sens de l'erreur.
 *
 * Cet endpoint ne devine donc rien : il deplace la liste d'evaluations qu'on
 * lui donne, apres qu'un humain l'a arretee. La simulation est le defaut.
 *
 * A distinguer de CLIEvaluationPeriodeController, qui detecte tout seul un
 * cas precis : les evaluations posees avant l'ouverture de leur classe de
 * specialite. Ici il n'y a aucune detection possible, seulement une decision.
 */
class CLIEvaluationDeplacementController extends BaseApiController
{
    /**
     * Au-dela, on demande de decouper l'appel : la transaction reste courte
     * et la reponse reste lisible pour celui qui la relit.
     */
    private const LOT_MAX = 200;

    /**
     * POST /api/cli/evaluations/deplacer-periode
     */
    public function deplacer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'evaluation_ids' => 'required|array|min:1|max:'.self::LOT_MAX,
            'evaluation_ids.*' => 'required|integer|min:1',
            'periode' => 'required|string|in:semestre1,semestre2',
            'dry_run' => 'nullable|boolean',
        ]);

        $simulation = $request->boolean('dry_run', true);
        $cible = $valide['periode'];
        $ids = array_values(array_unique(array_map('intval', $valide['evaluation_ids'])));

        [$aDeplacer, $deja, $introuvables] = $this->apercuDuLot($ids, $cible);

        if ($simulation) {
            return $this->successResponse([
                'dry_run' => true,
                'a_deplacer' => $aDeplacer,
                'deja_sur_la_cible' => $deja,
                'introuvables' => $introuvables,
                'total_a_deplacer' => count($aDeplacer),
                'notes_concernees' => array_sum(array_column($aDeplacer, 'nb_notes')),
            ], 'Simulation : rien n a ete ecrit. Relancer avec dry_run=false pour appliquer.');
        }

        [$traitees, $deplacees] = $this->appliquerLeLot($aDeplacer, $cible);

        // Cet `update()` est un update de QUERY BUILDER : aucun evenement
        // Eloquent, donc aucun recalcul. `periode` etant une coordonnee de la
        // cle d'`esbtp_resultats`, les deux semestres gardaient la moyenne
        // d'avant — et l'agregat perime l'emporte sur les notes. Hors
        // transaction a dessein : le deplacement reste acquis.
        $recalcul = RecalculApresDeplacement::pourUnLotDePeriodes($deplacees, $request->user()->id);

        Log::warning('CLI: evaluations deplacees de semestre sur decision humaine', [
            'periode_cible' => $cible,
            'nombre' => count($traitees),
            'evaluations' => array_column($traitees, 'evaluation_id'),
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'traitees' => $traitees,
            'deja_sur_la_cible' => $deja,
            'introuvables' => $introuvables,
            'total' => count($traitees),
            'notes_realignees' => array_sum(array_column($traitees, 'notes_realignees')),
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'agregats_orphelins' => $recalcul['orphelins'],
            'recalculs_en_echec' => $recalcul['echecs'],
            'recalcul_reporte' => $recalcul['reporte'],
            'perimetres_reportes' => $recalcul['perimetres_reportes'],
        ], count($traitees).' evaluation(s) deplacee(s) vers '.$cible.'.'
            .RecalculApresDeplacement::motDeLaFin($recalcul));
    }

    /**
     * Ce que le lot ferait : les evaluations a deplacer, celles deja sur la
     * cible, celles qu'on n'a pas trouvees. Sert a la simulation comme a
     * l'execution, pour que les deux disent la meme chose.
     *
     * @param  array<int,int>  $ids
     * @return array{0:array<int,array<string,mixed>>, 1:array<int,array<string,mixed>>, 2:array<int,int>}
     */
    private function apercuDuLot(array $ids, string $cible): array
    {
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
                // classe_id et annee_universitaire_id, et dont le refus de
                // perimetre trop large conseille « Ajoutez matiere_id ». Les
                // omettre donnait des consignes qu'on ne pouvait pas suivre avec
                // la reponse sous les yeux.
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
            // un deplacement. Relancer le meme appel deux fois doit rester sans
            // effet, sinon on ne sait plus ce qui a ete fait.
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
     * L'ecriture, et rien d'autre : la transaction courte qui deplace, realigne
     * les notes, et rend de quoi recalculer ensuite. Le recalcul reste DEHORS —
     * il tourne sur place et ne doit pas tenir la transaction ouverte.
     *
     * @param  array<int,array<string,mixed>>  $aDeplacer
     * @return array{0:array<int,array<string,mixed>>, 1:array<int,array{evaluation:ESBTPEvaluation, periode_avant:string}>}
     */
    private function appliquerLeLot(array $aDeplacer, string $cible): array
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
                // qui le decide : y ecrire la chaine plutot que l'entier ouvrait
                // un chemin de SUPPRESSION d'agregat. Voir
                // ESBTPNote::realignerLeSemestre().
                $notes = ESBTPNote::realignerLeSemestre($evaluation->id, $evaluation->periode);

                $deplacees[] = ['evaluation' => $evaluation, 'periode_avant' => $avant];

                $traitees[] = $ligne + [
                    'periode_avant' => $avant,
                    'notes_realignees' => $notes,
                ];
            }
        });

        return [$traitees, $deplacees];
    }
}

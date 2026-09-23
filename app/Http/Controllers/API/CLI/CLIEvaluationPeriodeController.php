<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Evaluations placees sur un semestre ou leur classe n'accueille encore
 * personne.
 *
 * Une classe de specialite issue du tronc commun ne s'ouvre qu'au semestre
 * porte par esbtp_classe_orientation_targets.semestre_activation. Une
 * evaluation creee avant ce semestre produit une note qui remonte ensuite sur
 * le bulletin de tronc commun de l'etudiant.
 *
 * Le diagnostic est en lecture seule. La reparation est en simulation par
 * defaut : elle n'ecrit que si dry_run vaut explicitement false.
 */
class CLIEvaluationPeriodeController extends BaseApiController
{
    /**
     * GET /api/cli/diagnostics/evaluations-periode
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $anomalies = $this->detecter($request->query('annee_id'));

        return $this->successResponse([
            'anomalies' => $anomalies,
            'total' => count($anomalies),
            'notes_concernees' => array_sum(array_column($anomalies, 'nb_notes')),
            'lecture' => 'Ces evaluations sont placees sur un semestre anterieur '
                .'a l ouverture de leur classe. Les notes correspondantes remontent '
                .'sur les bulletins de tronc commun des etudiants.',
        ]);
    }

    /**
     * POST /api/cli/diagnostics/evaluations-periode/repair
     *
     * Deplace chaque evaluation vers le semestre d'ouverture de sa classe, et
     * aligne la colonne denormalisee des notes. Simulation par defaut.
     */
    public function repair(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $simulation = $request->boolean('dry_run', true);
        $anomalies = $this->detecter($request->query('annee_id'));

        if ($anomalies === []) {
            return $this->successResponse([
                'dry_run' => $simulation,
                'traitees' => 0,
            ], 'Aucune evaluation a deplacer.');
        }

        if ($simulation) {
            return $this->successResponse([
                'dry_run' => true,
                'a_traiter' => $anomalies,
                'total' => count($anomalies),
            ], 'Simulation : rien n a ete ecrit. Relancer avec dry_run=false pour appliquer.');
        }

        [$traitees, $deplacees] = $this->appliquerLeLot($anomalies);

        // Cet `update()` est un update de QUERY BUILDER : aucun evenement
        // Eloquent, donc aucun recalcul. `periode` etant une coordonnee de la
        // cle d'`esbtp_resultats`, les deux semestres gardaient la moyenne
        // d'avant — et l'agregat perime l'emporte sur les notes. Hors
        // transaction a dessein : le deplacement reste acquis.
        $recalcul = RecalculApresDeplacement::pourUnLotDePeriodes($deplacees, $request->user()->id);

        Log::warning('CLI: evaluations deplacees vers le semestre d ouverture de leur classe', [
            'nombre' => count($traitees),
            'evaluations' => array_column($traitees, 'evaluation_id'),
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'traitees' => $traitees,
            'total' => count($traitees),
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'agregats_orphelins' => $recalcul['orphelins'],
            'recalculs_en_echec' => $recalcul['echecs'],
            'recalcul_reporte' => $recalcul['reporte'],
            'perimetres_reportes' => $recalcul['perimetres_reportes'],
        ], count($traitees).' evaluation(s) deplacee(s).'.RecalculApresDeplacement::motDeLaFin($recalcul));
    }


    /**
     * @return array<int, array<string, mixed>>
     */
    private function detecter(?string $anneeId): array
    {
        $ouvertures = app(ClasseOuvertureResolver::class)->ouverturesParClasse();

        if ($ouvertures === []) {
            return [];
        }

        $evaluations = ESBTPEvaluation::query()
            ->with(['classe:id,name', 'matiere:id,name'])
            ->whereIn('classe_id', array_keys($ouvertures))
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', (int) $anneeId))
            ->where('status', '!=', 'cancelled')
            ->get();

        $anomalies = [];

        foreach ($evaluations as $evaluation) {
            $semestre = ESBTPEvaluation::numeroDeSemestre((string) $evaluation->periode);
            $attendu = (int) $ouvertures[$evaluation->classe_id];

            if ($semestre === null || $semestre >= $attendu) {
                continue;
            }

            $anomalies[] = [
                'evaluation_id' => $evaluation->id,
                'titre' => $evaluation->titre,
                'classe' => $evaluation->classe->name ?? null,
                // Les deux identifiants, et pas seulement le nom de la classe :
                // le message de repli renvoie vers
                // `POST /api/cli/notes/recompute`, qui les EXIGE tous les deux.
                'classe_id' => (int) $evaluation->classe_id,
                'annee_universitaire_id' => (int) $evaluation->annee_universitaire_id,
                // `matiere_id` et pas seulement le nom : sans lui, le conseil
                // « Ajoutez matiere_id » du refus de rattrapage ne se suit pas.
                'matiere_id' => $evaluation->matiere_id !== null ? (int) $evaluation->matiere_id : null,
                'matiere' => $evaluation->matiere->name ?? null,
                'periode_actuelle' => $evaluation->periode,
                'semestre_attendu' => $attendu,
                'nb_notes' => ESBTPNote::where('evaluation_id', $evaluation->id)->count(),
            ];
        }

        return $anomalies;
    }

    /**
     * L'ecriture, et rien d'autre : la transaction courte qui deplace, realigne
     * les notes, et rend de quoi recalculer ensuite. Le recalcul reste DEHORS —
     * il tourne sur place et ne doit pas tenir la transaction ouverte.
     *
     * @param  array<int,array<string,mixed>>  $anomalies
     * @return array{0:array<int,array<string,mixed>>, 1:array<int,array{evaluation:ESBTPEvaluation, periode_avant:string}>}
     */
    private function appliquerLeLot(array $anomalies): array
    {
        $traitees = [];
        $deplacees = [];

        DB::transaction(function () use ($anomalies, &$traitees, &$deplacees) {
            foreach ($anomalies as $a) {
                $evaluation = ESBTPEvaluation::find($a['evaluation_id']);
                if (! $evaluation) {
                    continue;
                }

                $avant = $evaluation->periode;
                $evaluation->periode = 'semestre'.$a['semestre_attendu'];
                $evaluation->save();

                // L'encodage de cette colonne vit sur le modele, avec le hook
                // qui le decide : y ecrire la chaine plutot que l'entier ouvrait
                // un chemin de SUPPRESSION d'agregat. Voir
                // ESBTPNote::realignerLeSemestre().
                $notes = ESBTPNote::realignerLeSemestre($evaluation->id, $evaluation->periode);

                $deplacees[] = ['evaluation' => $evaluation, 'periode_avant' => $avant];

                $traitees[] = [
                    'evaluation_id' => $evaluation->id,
                    'titre' => $a['titre'],
                    'classe' => $a['classe'],
                    'classe_id' => $a['classe_id'],
                    'annee_universitaire_id' => $a['annee_universitaire_id'],
                    'matiere_id' => $a['matiere_id'],
                    'matiere' => $a['matiere'],
                    'de' => $avant,
                    'vers' => $evaluation->periode,
                    'notes_realignees' => $notes,
                ];
            }
        });

        return [$traitees, $deplacees];
    }
}

<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\RebasculeDeMatiere;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Rebasculer une evaluation vers la matiere du bon systeme academique.
 *
 * Contrôleur à lui seul, comme {@see CLIEvaluationDeplacementController} et
 * {@see CLINotesRecomputeController}. Le garde et l'écriture vivent dans
 * {@see RebasculeDeMatiere}, partagé avec Nanan.
 */
class CLIEvaluationMatiereController extends BaseApiController
{
    public function __construct(private RebasculeDeMatiere $rebascule)
    {
        parent::__construct();
    }

    /**
     * POST /api/cli/evaluations/{id}/matiere — rebascule une evaluation.
     *
     * Sert a reparer une fuite de selecteur : une evaluation posee sur une
     * matiere du mauvais systeme academique. Le mouvement n'est autorise que
     * s'il RETABLIT la coherence, jamais s'il la rompt.
     *
     * Body: { matiere_id: int, dry_run?: bool }
     */
    public function evaluationChangeMatiere(Request $request, $id): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
            'dry_run' => 'nullable|boolean',
        ]);

        $evaluation = ESBTPEvaluation::with(['classe', 'matiere'])->find($id);
        if (! $evaluation) {
            return $this->errorResponse("Evaluation #{$id} not found", [], 404);
        }

        $cible = ESBTPMatiere::find($validated['matiere_id']);

        if ($refus = $this->rebascule->refus($evaluation, $cible)) {
            return $this->errorResponse($refus, [], 422);
        }

        if ((bool) ($validated['dry_run'] ?? false)) {
            return $this->apercuDeRebascule($evaluation, $cible, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
        }

        ['avant' => $avant, 'notes' => $notes, 'recalcul' => $recalcul] = $this->rebascule->appliquer($evaluation, $cible, $request->user()->id);

        Log::warning('CLI: evaluation rebasculee', [
            'evaluation_id' => $evaluation->id,
            'classe' => $evaluation->classe?->name,
            'avant' => $avant,
            'apres' => ['matiere_id' => $cible->id, 'matiere' => $cible->name],
            'notes_deplacees' => $notes,
            'caller_user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'evaluation_id' => $evaluation->id,
            'classe' => $evaluation->classe?->name,
            'matiere_avant' => $avant['matiere'],
            'matiere_apres' => $cible->name,
            'notes_deplacees' => $notes,
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'agregats_orphelins' => $recalcul['orphelins'],
            'recalculs_en_echec' => $recalcul['echecs'],
        ], $this->messageDeRebascule($evaluation->id, $cible->name, $notes, $recalcul));
    }

    /** Previsualisation : aucune ecriture, ni sur l'evaluation ni sur les agregats. */
    private function apercuDeRebascule(ESBTPEvaluation $evaluation, ESBTPMatiere $cible, int $notes): JsonResponse
    {
        return $this->successResponse([
            'dry_run' => true,
            'evaluation_id' => $evaluation->id,
            'titre' => $evaluation->titre,
            'classe' => $evaluation->classe?->name,
            'matiere_actuelle' => $evaluation->matiere?->name,
            'matiere_cible' => $cible->name,
            'notes_a_deplacer' => $notes,
        ], 'Aucune ecriture : previsualisation seulement.');
    }

    /**
     * @param  array{recalculs_tentes:int, orphelins:array<int,array<string,mixed>>, echecs:int}  $recalcul
     */
    private function messageDeRebascule(int $evaluationId, string $cible, int $notes, array $recalcul): string
    {
        $message = "Evaluation #{$evaluationId} rebasculee sur '{$cible}' ({$notes} note(s) suivie(s), "
            ."{$recalcul['recalculs_tentes']} recalcul(s) lance(s)).";

        if ($recalcul['orphelins'] !== []) {
            $message .= ' '.count($recalcul['orphelins']).' agregat(s) n ont plus rien a moyenner (aucune note, '
                .'ou seulement des absences) : ils sont laisses en place, jamais remis a zero, '
                .'et leur sort est une decision d ecole.';
        }

        return $message;
    }
}

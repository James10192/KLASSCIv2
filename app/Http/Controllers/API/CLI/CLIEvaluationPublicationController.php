<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\ChangementDeStatut;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Lister les évaluations d'une classe pour une année, et les rendre effectives :
 * terminées (donc lues par le bulletin), publiées, notes publiées.
 *
 * Le changement de statut passe par ChangementDeStatut, seule entrée qui
 * recalcule les moyennes touchées. Les évaluations annulées ne sont jamais
 * réactivées ici : une annulation est une décision, pas un oubli.
 */
class CLIEvaluationPublicationController extends BaseApiController
{
    /** GET /api/cli/evaluations/classe?classe_id=&annee_universitaire_id= */
    public function lister(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }
        $v = $this->perimetre($request);

        $evaluations = $this->requete($v)->withCount('notes')->with('matiere:id,code,name')
            ->orderBy('periode')->orderBy('id')->get()
            ->map(fn (ESBTPEvaluation $e) => $this->ligne($e) + ['notes' => $e->notes_count]);

        return $this->successResponse(['total' => $evaluations->count(), 'evaluations' => $evaluations]);
    }

    /** POST /api/cli/evaluations/publier — simulation par défaut. */
    public function publier(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }
        $v = $this->perimetre($request) + $request->validate([
            'motif' => ['required', 'string', 'min:10', 'max:500'],
            'dry_run' => ['nullable', 'boolean'],
        ]);
        $dryRun = (bool) ($v['dry_run'] ?? true);

        $aTraiter = $this->requete($v)->with('matiere:id,code,name')
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->where(fn (Builder $q) => $q->where('status', '!=', ESBTPEvaluation::STATUS_COMPLETED)
                ->orWhere('is_published', false)->orWhere('notes_published', false))
            ->get();

        $lignes = [];
        $avertissements = [];
        foreach ($aTraiter as $evaluation) {
            $avant = $this->ligne($evaluation);
            if (! $dryRun) {
                $evaluation->is_published = true;
                $evaluation->notes_published = true;
                $effet = ChangementDeStatut::poser($evaluation, ESBTPEvaluation::STATUS_COMPLETED, $request->user());
                if ($effet['avertissement']) {
                    $avertissements[] = ['evaluation_id' => $evaluation->id, 'message' => $effet['avertissement']];
                }
            }
            $lignes[] = $avant;
        }

        if (! $dryRun) {
            Log::warning('CLI: evaluations publiees', [
                'classe_id' => $v['classe_id'], 'annee' => $v['annee_universitaire_id'],
                'evaluation_ids' => array_column($lignes, 'id'), 'motif' => $v['motif'],
                'caller_user_id' => $request->user()->id,
            ]);
        }

        return $this->successResponse([
            'dry_run' => $dryRun,
            'traitees' => count($lignes),
            'annulees_ignorees' => $this->requete($v)->where('status', ESBTPEvaluation::STATUS_CANCELLED)->count(),
            'evaluations_avant' => $lignes,
            'avertissements' => $avertissements,
        ], $dryRun ? 'Simulation : rien n’a été modifié.' : 'Évaluations terminées et publiées.');
    }

    private function perimetre(Request $request): array
    {
        return $request->validate([
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function requete(array $v): Builder
    {
        return ESBTPEvaluation::query()
            ->where('classe_id', $v['classe_id'])
            ->where('annee_universitaire_id', $v['annee_universitaire_id'])
            ->when($v['periode'] ?? null, fn (Builder $q, $p) => $q->where('periode', $p));
    }

    private function ligne(ESBTPEvaluation $e): array
    {
        return [
            'id' => $e->id,
            'titre' => $e->titre,
            'type' => $e->type,
            'periode' => $e->periode,
            'matiere' => $e->matiere ? trim(($e->matiere->code ?? '').' '.$e->matiere->name) : null,
            'status' => $e->status,
            'is_published' => (bool) $e->is_published,
            'notes_published' => (bool) $e->notes_published,
        ];
    }
}

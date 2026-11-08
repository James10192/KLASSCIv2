<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lecture seule des notes saisies, par évaluation.
 *
 * GET /api/cli/evaluations/notes?annee_id=&classe_id=&evaluation_id=
 * Aucune écriture. Les évaluations annulées sont exclues. Les notes supprimées
 * (soft delete) ne sont pas rendues.
 */
class CLILectureNotesController extends BaseApiController
{
    private const MAX_EVALUATIONS = 300;

    public function notes(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $v = $request->validate([
            'annee_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'classe_id' => 'nullable|integer|exists:esbtp_classes,id',
            'evaluation_id' => 'nullable|integer',
        ]);

        $requete = ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $v['annee_id'])
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED));

        if (isset($v['classe_id'])) {
            $requete->where('classe_id', $v['classe_id']);
        }
        if (isset($v['evaluation_id'])) {
            $requete->whereKey($v['evaluation_id']);
        }

        $total = (clone $requete)->count();
        if ($total > self::MAX_EVALUATIONS) {
            return $this->errorResponse(
                'Trop d’évaluations ('.$total.') : précisez classe_id ou evaluation_id (maximum '.self::MAX_EVALUATIONS.').',
                ['code' => 'TROP_D_EVALUATIONS', 'total' => $total],
                422
            );
        }

        $evaluations = $requete->with(['matiere:id,name', 'classe:id,name'])
            ->orderBy('id')
            ->get(['id', 'titre', 'type', 'periode', 'date_evaluation', 'classe_id', 'matiere_id', 'bareme', 'coefficient', 'is_published', 'status']);

        $notes = ESBTPNote::query()
            ->whereIn('evaluation_id', $evaluations->pluck('id'))
            ->orderBy('evaluation_id')
            ->get(['id', 'evaluation_id', 'etudiant_id', 'note', 'is_absent', 'updated_at']);

        $etudiants = ESBTPEtudiant::query()
            ->whereIn('id', $notes->pluck('etudiant_id')->unique())
            ->get(['id', 'matricule', 'nom', 'prenoms'])
            ->keyBy('id');

        $parEvaluation = $notes->groupBy('evaluation_id');

        $resultat = $evaluations->map(function (ESBTPEvaluation $e) use ($parEvaluation, $etudiants) {
            $lignes = ($parEvaluation->get($e->id) ?? collect())->map(function (ESBTPNote $n) use ($etudiants) {
                $etudiant = $etudiants->get($n->etudiant_id);

                return [
                    'etudiant_id' => (int) $n->etudiant_id,
                    'matricule' => $etudiant?->matricule,
                    'nom' => $etudiant?->nom,
                    'prenoms' => $etudiant?->prenoms,
                    'note' => $n->note === null ? null : (float) $n->note,
                    'is_absent' => (bool) $n->is_absent,
                    'modifiee_le' => optional($n->updated_at)->format('Y-m-d H:i'),
                ];
            })->values();

            return [
                'evaluation_id' => (int) $e->id,
                'titre' => $e->titre,
                'type' => $e->type,
                'periode' => $e->periode,
                'date' => optional($e->date_evaluation)->format('Y-m-d'),
                'classe' => $e->classe?->name,
                'matiere' => $e->matiere?->name,
                'bareme' => (float) $e->bareme,
                'coefficient' => (float) $e->coefficient,
                'publiee' => (bool) $e->is_published,
                'statut' => $e->status,
                'nb_notes' => $lignes->count(),
                'notes' => $lignes,
            ];
        });

        return $this->successResponse([
            'annee_id' => (int) $v['annee_id'],
            'nb_evaluations' => $resultat->count(),
            'nb_notes' => $notes->count(),
            'evaluations' => $resultat->values(),
        ], 'Lecture seule des notes.');
    }
}

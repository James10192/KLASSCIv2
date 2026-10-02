<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\CLI;

use App\Domain\Notes\RegularisationDeNotesLmd;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Saisie exceptionnelle, traçable et idempotente de notes LMD provenant d'une
 * fiche officielle quand les évaluations historiques n'avaient pas été créées.
 *
 * Une ligne est toujours créée dans esbtp_evaluations avant la note : écrire une
 * note orpheline ferait disparaître la provenance, les permissions et le lien
 * avec la matière du bulletin. Simulation par défaut ; dry_run=false est requis.
 */
final class CLILmdRegularisationNotesController extends BaseApiController
{
    public function enregistrer(Request $request, RegularisationDeNotesLmd $regularisation): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'date_regularisation' => ['required', 'date'],
            'motif' => ['required', 'string', 'min:20', 'max:1000'],
            'dry_run' => ['nullable', 'boolean'],
            'notes' => ['required', 'array', 'min:1', 'max:40'],
            'notes.*.matiere_id' => ['required', 'integer', 'distinct', 'exists:esbtp_matieres,id'],
            'notes.*.note' => ['required', 'numeric', 'min:0', 'max:20'],
        ]);

        $dryRun = (bool) ($v['dry_run'] ?? true);
        $resultat = $regularisation->appliquer($v, $dryRun, (int) $request->user()->id);

        if ($dryRun) {
            return $this->successResponse([
                'dry_run' => true,
                'evaluations_et_notes' => array_map(fn (array $l) => [
                    'matiere_id' => $l['matiere_id'], 'matiere' => $l['matiere'], 'note' => $l['apres'],
                    'avant' => $l['avant'], 'evaluation' => $l['evaluation'],
                ], $resultat['lignes']),
            ], 'Prévisualisation : aucune évaluation ni note n’a été écrite.');
        }

        $ecrites = array_map(fn (array $l) => [
            'evaluation_id' => $l['evaluation_id'], 'note_id' => $l['note_id'], 'matiere' => $l['matiere'], 'note' => $l['apres'],
        ], $resultat['lignes']);

        Log::warning('CLI: regularisation de notes LMD', [
            'etudiant_id' => $v['etudiant_id'],
            'classe_id' => $v['classe_id'],
            'annee' => $resultat['annee'],
            'periode' => $v['periode'],
            'lignes' => $ecrites,
            'motif' => $v['motif'],
            'caller_user_id' => $request->user()->id,
        ]);

        return $this->successResponse(['dry_run' => false, 'evaluations_et_notes' => $ecrites], 'Évaluations de régularisation et notes enregistrées.');
    }
}

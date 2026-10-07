<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Supprime logiquement les notes d'une liste explicite d'etudiants pour UNE
 * annee universitaire. Les evaluations, etudiants et inscriptions ne sont
 * jamais modifies.
 *
 * Simulation par defaut. Une execution exige a la fois `dry_run: false` et
 * la confirmation litterale `SUPPRIMER_LES_NOTES`.
 */
class CLINotesSuppressionController extends BaseApiController
{
    private const MAX_ETUDIANTS = 100;

    public function supprimer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'etudiant_ids' => 'required|array|min:1|max:'.self::MAX_ETUDIANTS,
            'etudiant_ids.*' => 'required|integer|distinct|exists:esbtp_etudiants,id',
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'motif' => 'required|string|min:10|max:500',
            'dry_run' => 'nullable|boolean',
            'confirmation' => 'nullable|string',
        ]);

        $simuler = (bool) ($v['dry_run'] ?? true);
        if (! $simuler && ($v['confirmation'] ?? null) !== 'SUPPRIMER_LES_NOTES') {
            return $this->errorResponse(
                'Confirmation invalide. Envoyez confirmation=SUPPRIMER_LES_NOTES pour exécuter.',
                [],
                422
            );
        }

        $notes = ESBTPNote::query()
            ->with([
                'etudiant:id,matricule,nom,prenoms',
                'evaluation:id,annee_universitaire_id,classe_id,matiere_id,periode,titre',
            ])
            ->whereIn('etudiant_id', $v['etudiant_ids'])
            ->whereHas('evaluation', fn ($query) => $query->where(
                'annee_universitaire_id',
                $v['annee_universitaire_id']
            ))
            ->orderBy('etudiant_id')
            ->orderBy('id')
            ->get();

        $etudiants = $notes->groupBy('etudiant_id')->map(function ($lignes, $etudiantId) {
            $etudiant = $lignes->first()->etudiant;

            return [
                'etudiant_id' => (int) $etudiantId,
                'matricule' => $etudiant?->matricule,
                'nom_complet' => trim(($etudiant?->nom ?? '').' '.($etudiant?->prenoms ?? '')),
                'notes' => $lignes->count(),
                'evaluations' => $lignes->pluck('evaluation_id')->unique()->count(),
            ];
        });

        $rapport = collect($v['etudiant_ids'])->map(function ($etudiantId) use ($etudiants) {
            return $etudiants->get((int) $etudiantId, [
                'etudiant_id' => (int) $etudiantId,
                'matricule' => null,
                'nom_complet' => null,
                'notes' => 0,
                'evaluations' => 0,
            ]);
        })->values()->all();

        if (! $simuler) {
            DB::transaction(function () use ($notes) {
                $notes->each->delete();
            });

            Log::warning('CLI: suppression ciblee de notes executee', [
                'annee_universitaire_id' => $v['annee_universitaire_id'],
                'etudiant_ids' => $v['etudiant_ids'],
                'notes_supprimees' => $notes->count(),
                'motif' => $v['motif'],
                'caller_user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
        }

        return $this->successResponse([
            'dry_run' => $simuler,
            'annee_universitaire_id' => (int) $v['annee_universitaire_id'],
            'etudiants' => $rapport,
            'etudiants_cibles' => count($v['etudiant_ids']),
            'notes' => $notes->count(),
            'evaluations_concernees' => $notes->pluck('evaluation_id')->unique()->count(),
        ], $simuler
            ? 'Aucune écriture : prévisualisation. Relancer avec dry_run=false et la confirmation pour supprimer les notes.'
            : 'Notes supprimées. Les évaluations, étudiants et inscriptions sont inchangés.');
}

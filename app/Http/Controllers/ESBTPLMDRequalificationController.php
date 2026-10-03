<?php

namespace App\Http\Controllers;

use App\Domain\Notes\RequalificationEnExamen;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Écran des notes LMD : requalifier en examen les régularisations d'une classe.
 * Même service que Nanan (`proposer_requalification_examen`).
 */
class ESBTPLMDRequalificationController extends Controller
{
    public function __construct(private readonly RequalificationEnExamen $requalification) {}

    public function inventaire(Request $request, ESBTPClasse $classe): JsonResponse
    {
        $this->assertPasEnseignantSeul();

        return response()->json([
            'success' => true,
            'lignes' => $this->requalification->inventaire($classe, $this->anneeId($request)),
        ]);
    }

    public function appliquer(Request $request, ESBTPClasse $classe): JsonResponse
    {
        $this->assertPasEnseignantSeul();
        $request->validate([
            'periode' => ['nullable', 'string', 'regex:/^semestre\d{1,2}$/'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $simulation = $request->boolean('dry_run', true);

        try {
            $lignes = $this->requalification->appliquer(
                $classe, $this->anneeId($request), $request->input('periode'), $simulation, (int) auth()->id()
            );
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->implode(' ')], 422);
        }

        $notes = array_sum(array_column($lignes, 'notes'));

        return response()->json([
            'success' => true,
            'dry_run' => $simulation,
            'lignes' => $lignes,
            'message' => $simulation
                ? sprintf('%d évaluation(s) et %d note(s) deviendront des examens.', count($lignes), $notes)
                : sprintf('%d évaluation(s) requalifiée(s) en examen, %d note(s) inchangée(s).', count($lignes), $notes),
        ]);
    }

    private function anneeId(Request $request): int
    {
        return (int) ($request->input('annee_universitaire_id')
            ?: ESBTPAnneeUniversitaire::where('is_current', true)->value('id'));
    }

    /**
     * La requalification porte sur toutes les régularisations d'une classe :
     * un enseignant, qui ne saisit que les évaluations qui lui sont confiées,
     * n'y a pas accès. Même distinction que la saisie des notes LMD.
     */
    private function assertPasEnseignantSeul(): void
    {
        $user = auth()->user();
        abort_if(
            $user && $user->can('identity.teach') && ! $user->can('identity.coordinate'),
            403,
            'La requalification des évaluations est réservée à l\'administration.'
        );
    }
}

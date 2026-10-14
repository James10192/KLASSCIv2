<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Domain\AcademicPilotage\Services\GradeSheetNoteMutationGuard;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Services\Notes\EvaluationsDeLaFiche;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Vue AJAX de gestion des notes depuis les fiches, sans second moteur de saisie.
 * Écriture des notes : ESBTPNoteController::saveNoteAjax (règles de barème,
 * cohorte, fenêtres et permissions). Aucune donnée d'un autre dossier n'est
 * autorisée par les endpoints de la fiche.
 */
final class FicheNotesController extends Controller
{
    public function etudiant(ESBTPEtudiant $etudiant)
    {
        abort_unless(auth()->user()->can('students.view') && auth()->user()->can('notes.view'), 403);

        $inscriptions = $etudiant->inscriptions()
            ->with(['classe', 'anneeUniversitaire'])
            ->orderByDesc('annee_universitaire_id')->orderByDesc('id')->get();

        return $this->render($etudiant, $inscriptions);
    }

    public function inscription(ESBTPInscription $inscription)
    {
        $this->authorize('view', $inscription);
        abort_unless(auth()->user()->can('notes.view'), 403);
        $inscription->loadMissing(['classe', 'anneeUniversitaire', 'etudiant']);

        return $this->render($inscription->etudiant, collect([$inscription]));
    }

    private function render(ESBTPEtudiant $student, $inscriptions)
    {
        $blocks = $inscriptions->map(function (ESBTPInscription $inscription) use ($student) {
            $evaluations = app(EvaluationsDeLaFiche::class)->pourInscription($inscription)
                ->with(['matiere', 'notes' => fn ($query) => $query->where('etudiant_id', $student->id)])
                ->orderBy('periode')
                ->orderByDesc('date_evaluation')
                ->get();

            $classe = $inscription->classe;
            $isLmd = ($classe?->systeme_academique ?? '') === 'LMD';

            // Matières canoniques selon le système. Ne jamais suggérer une
            // ECUE LMD dans les combinaisons BTS.
            if (! $classe) {
                $matieres = collect();
            } elseif ($isLmd) {
                $ids = ESBTPPlanificationAcademique::query()
                    ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                    ->where('filiere_id', $classe->filiere_id)
                    ->where('niveau_etude_id', $classe->niveau_etude_id)
                    ->whereNotNull('matiere_id')
                    ->pluck('matiere_id');
                $matieres = ESBTPMatiere::query()
                    ->whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
            } else {
                $matieres = ESBTPMatiere::query()
                    ->where('is_active', true)->btsOnly()
                    ->whereHas('liaisonsFilieresNiveaux', function ($query) use ($classe) {
                        $query->where('filiere_id', $classe->filiere_id)
                            ->where('niveau_etude_id', $classe->niveau_etude_id);
                    })
                    ->orderBy('name')->get(['id', 'name']);
            }

            return [
                'inscription' => $inscription,
                'evaluations' => $evaluations,
                'matieres' => $matieres,
                'periodes' => ESBTPEvaluation::periodesPourClasse($classe),
                'bulletins' => auth()->user()->can('bulletins.view')
                    ? ESBTPBulletin::query()
                        ->where('etudiant_id', $student->id)
                        ->where('classe_id', $inscription->classe_id)
                        ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                        ->orderBy('periode')->get()
                    : collect(),
            ];
        });

        return response()->view('esbtp.partials.fiche-notes-contenu', [
            'student' => $student,
            'blocks' => $blocks,
            'typesEvaluation' => ESBTPEvaluation::getTypes(),
        ]);
    }

    /**
     * Suppression sécurisée depuis la fiche : même cohorte, même année, même
     * élève, permission explicite. Le modèle conserve ses observers de recalcul.
     */
    public function supprimerNote(ESBTPInscription $inscription, ESBTPNote $note): JsonResponse
    {
        abort_unless(auth()->user()->can('notes.delete'), 403);
        $this->authorize('view', $inscription);
        $evaluation = $note->evaluation;

        abort_unless($evaluation
            && (int) $note->etudiant_id === (int) $inscription->etudiant_id
            && (int) $evaluation->classe_id === (int) $inscription->classe_id
            && (int) $evaluation->annee_universitaire_id === (int) $inscription->annee_universitaire_id,
            404);

        DB::transaction(function () use ($note, $evaluation): void {
            // A validée grade sheet or locked scheduled exam must not be
            // bypassed from the student/inscription side panel.
            app(GradeSheetNoteMutationGuard::class)
                ->assertEvaluationMutable((int) $evaluation->id, true);
            $note->delete();
        });

        return response()->json(['success' => true, 'message' => 'Note supprimée ; les résultats seront recalculés.']);
    }
}

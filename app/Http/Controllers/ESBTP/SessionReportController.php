<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPSessionReport;
use App\Models\ESBTPSessionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionReportController extends Controller
{
    /**
     * Affiche le formulaire de création du rapport de séance
     */
    public function create($seanceId)
    {
        $user = Auth::user();
        
        $seance = ESBTPSeanceCours::with(['matiere', 'classe'])
            ->where('id', $seanceId)
            ->where('teacher_id', $user->id)
            ->firstOrFail();

        // **WORKFLOW** : Vérifier que cette étape peut être exécutée
        $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);
        
        if (!$workflow->canExecuteStep('report')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous devez d\'abord compléter les appels avant de créer le rapport.');
        }

        // Vérifier s'il existe déjà un rapport pour cette séance
        $existingReport = ESBTPSessionReport::where('seance_cours_id', $seanceId)
            ->where('teacher_id', $user->id)
            ->first();

        return view('teacher.session-report.create', compact('seance', 'workflow', 'existingReport'));
    }

    /**
     * Enregistre le rapport de séance
     */
    public function store(Request $request, $seanceId)
    {
        $user = Auth::user();
        
        $seance = ESBTPSeanceCours::where('id', $seanceId)
            ->where('teacher_id', $user->id)
            ->firstOrFail();

        // **WORKFLOW** : Vérifier que cette étape peut être exécutée
        $workflow = ESBTPSessionWorkflow::getOrCreateForSession($seanceId, $user->id);
        
        if (!$workflow->canExecuteStep('report')) {
            return redirect()->route('teacher.select-call-type', $seanceId)
                ->with('error', 'Vous ne pouvez pas créer le rapport maintenant.');
        }

        // Validation des données
        $validated = $request->validate([
            'content_summary' => 'required|string|min:30',
            'teaching_methods' => 'nullable|string|max:1000',
            'student_behavior' => 'required|in:excellent,good,satisfactory,difficult',
            'difficulties_encountered' => 'nullable|string|max:1000',
            'next_session_preparation' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
            'action' => 'required|in:save_draft,submit'
        ], [
            'content_summary.required' => 'Le résumé du contenu est obligatoire.',
            'content_summary.min' => 'Le résumé du contenu doit contenir au minimum 30 caractères.',
            'student_behavior.required' => 'Veuillez sélectionner le comportement des étudiants.',
            'student_behavior.in' => 'Comportement des étudiants invalide.'
        ]);

        try {
            DB::beginTransaction();

            // Créer ou mettre à jour le rapport
            $report = ESBTPSessionReport::updateOrCreate(
                [
                    'seance_cours_id' => $seanceId,
                    'teacher_id' => $user->id
                ],
                [
                    'content_summary' => $validated['content_summary'],
                    'teaching_methods' => $validated['teaching_methods'],
                    'student_behavior' => $validated['student_behavior'],
                    'difficulties_encountered' => $validated['difficulties_encountered'],
                    'next_session_preparation' => $validated['next_session_preparation'],
                    'homework_assigned' => $validated['homework_assigned'],
                    'status' => $validated['action'] === 'submit' ? 'submitted' : 'draft'
                ]
            );

            // Si le rapport est soumis, marquer le workflow comme terminé
            if ($validated['action'] === 'submit') {
                $report->markAsSubmitted();
                $workflow->markReportSubmitted();
            }

            DB::commit();

            $message = $validated['action'] === 'submit' 
                ? 'Rapport de cours soumis avec succès. La séance est maintenant terminée.'
                : 'Brouillon du rapport sauvegardé avec succès.';

            $redirectRoute = $validated['action'] === 'submit' 
                ? 'teacher.dashboard'
                : 'teacher.select-call-type';

            $redirectParams = $validated['action'] === 'submit' ? [] : [$seanceId];

            return redirect()->route($redirectRoute, $redirectParams)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la sauvegarde du rapport: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la sauvegarde du rapport.');
        }
    }

    /**
     * Affiche la liste des rapports de l'enseignant
     */
    public function index()
    {
        $user = Auth::user();
        
        $reports = ESBTPSessionReport::with(['seanceCours.matiere', 'seanceCours.classe'])
            ->where('teacher_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('teacher.session-report.index', compact('reports'));
    }

    /**
     * Affiche un rapport spécifique
     */
    public function show($reportId)
    {
        $user = Auth::user();
        
        $report = ESBTPSessionReport::with(['seanceCours.matiere', 'seanceCours.classe', 'teacher'])
            ->where('id', $reportId)
            ->where('teacher_id', $user->id)
            ->firstOrFail();

        return view('teacher.session-report.show', compact('report'));
    }

    /**
     * Modifie un rapport (uniquement les brouillons)
     */
    public function edit($reportId)
    {
        $user = Auth::user();
        
        $report = ESBTPSessionReport::with(['seanceCours.matiere', 'seanceCours.classe'])
            ->where('id', $reportId)
            ->where('teacher_id', $user->id)
            ->where('status', 'draft')
            ->firstOrFail();

        $seance = $report->seanceCours;
        $workflow = ESBTPSessionWorkflow::where('seance_cours_id', $seance->id)
            ->where('teacher_id', $user->id)
            ->first();

        return view('teacher.session-report.edit', compact('report', 'seance', 'workflow'));
    }

    /**
     * Met à jour un rapport (uniquement les brouillons)
     */
    public function update(Request $request, $reportId)
    {
        $user = Auth::user();
        
        $report = ESBTPSessionReport::where('id', $reportId)
            ->where('teacher_id', $user->id)
            ->where('status', 'draft')
            ->firstOrFail();

        // Validation des données
        $validated = $request->validate([
            'content_summary' => 'required|string|min:30',
            'teaching_methods' => 'nullable|string|max:1000',
            'student_behavior' => 'required|in:excellent,good,satisfactory,difficult',
            'difficulties_encountered' => 'nullable|string|max:1000',
            'next_session_preparation' => 'nullable|string|max:1000',
            'homework_assigned' => 'nullable|string|max:1000',
            'action' => 'required|in:save_draft,submit'
        ], [
            'content_summary.required' => 'Le résumé du contenu est obligatoire.',
            'content_summary.min' => 'Le résumé du contenu doit contenir au minimum 30 caractères.',
            'student_behavior.required' => 'Veuillez sélectionner le comportement des étudiants.',
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour le rapport
            $report->update([
                'content_summary' => $validated['content_summary'],
                'teaching_methods' => $validated['teaching_methods'],
                'student_behavior' => $validated['student_behavior'],
                'difficulties_encountered' => $validated['difficulties_encountered'],
                'next_session_preparation' => $validated['next_session_preparation'],
                'homework_assigned' => $validated['homework_assigned'],
                'status' => $validated['action'] === 'submit' ? 'submitted' : 'draft'
            ]);

            // Si le rapport est soumis, marquer le workflow comme terminé
            if ($validated['action'] === 'submit') {
                $report->markAsSubmitted();
                
                $workflow = ESBTPSessionWorkflow::where('seance_cours_id', $report->seance_cours_id)
                    ->where('teacher_id', $user->id)
                    ->first();
                    
                if ($workflow) {
                    $workflow->markReportSubmitted();
                }
            }

            DB::commit();

            $message = $validated['action'] === 'submit' 
                ? 'Rapport de cours soumis avec succès.'
                : 'Brouillon du rapport mis à jour avec succès.';

            return redirect()->route('teacher.session-report.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la mise à jour du rapport: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du rapport.');
        }
    }
}

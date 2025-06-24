<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Etudiant;
use App\Models\Grade;
use App\Models\Matiere;
use App\Models\Teacher;
use App\Models\TeacherClasseMatiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Evaluation;
use App\Models\Note;
use Carbon\Carbon;
use App\Models\ESBTPSeanceCours;

class TeacherGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('enseignant');
    }

    /**
     * Affiche la page pour saisir des notes
     */
    public function create()
    {
        // Récupérer l'enseignant connecté
        $teacher = Teacher::where('user_id', Auth::id())->first();

        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas les autorisations nécessaires.');
        }

        // Récupérer les classes associées à cet enseignant
        $classes = Classe::whereHas('teacherClasseMatieres', function ($query) use ($teacher) {
            $query->where('teacher_id', $teacher->id);
        })->with('filiere')->get();

        // Récupérer l'année scolaire actuelle et toutes les années
        $currentAnnee = AnneeScolaire::where('active', 1)->first();
        $annees = AnneeScolaire::orderBy('created_at', 'desc')->get();

        return view('teacher.grades.create', compact('classes', 'annees', 'currentAnnee'));
    }

    /**
     * Enregistre les notes des étudiants
     */
    public function store(Request $request)
    {
        // Valider les données d'en-tête
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'type_evaluation' => 'required|string',
            'date_evaluation' => 'required|date',
            'semestre' => 'required|string',
            'annee_universitaire_id' => 'required|exists:annee_scolaires,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:etudiants,id',
            'notes' => 'required|array',
            'notes.*' => 'nullable|numeric|min:0|max:20',
        ]);

        // Récupérer l'enseignant connecté
        $teacher = Teacher::where('user_id', Auth::id())->first();

        if (!$teacher) {
            return redirect()->route('dashboard')->with('error', 'Vous n\'avez pas les autorisations nécessaires.');
        }

        // Vérifier que l'enseignant est bien assigné à cette classe et cette matière
        $teacherClasseMatiere = TeacherClasseMatiere::where([
            'teacher_id' => $teacher->id,
            'classe_id' => $request->classe_id,
            'matiere_id' => $request->matiere_id,
        ])->first();

        if (!$teacherClasseMatiere) {
            return redirect()->route('teacher.grades.create')->with('error', 'Vous n\'êtes pas autorisé à saisir des notes pour cette classe et cette matière.');
        }

        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $notes = $request->notes;
            $presents = $request->presents ?? [];

            // Tableau pour stocker les notes traitées
            $processedGrades = 0;

            // Traiter chaque étudiant
            foreach ($studentIds as $index => $studentId) {
                // Vérifier si une note est fournie et si l'étudiant est présent
                if (isset($notes[$index]) && $notes[$index] !== '' && in_array($studentId, $presents)) {
                    // Vérifier si une note existe déjà pour cet étudiant, cette matière, ce type d'évaluation et ce semestre
                    $existingGrade = Grade::where([
                        'etudiant_id' => $studentId,
                        'matiere_id' => $request->matiere_id,
                        'type_evaluation' => $request->type_evaluation,
                        'semestre' => $request->semestre,
                        'annee_scolaire_id' => $request->annee_universitaire_id,
                    ])->first();

                    if ($existingGrade) {
                        // Mettre à jour la note existante
                        $existingGrade->note = $notes[$index];
                        $existingGrade->date_evaluation = $request->date_evaluation;
                        $existingGrade->present = true;
                        $existingGrade->updated_by = Auth::id();
                        $existingGrade->save();
                    } else {
                        // Créer une nouvelle note
                        Grade::create([
                            'etudiant_id' => $studentId,
                            'matiere_id' => $request->matiere_id,
                            'classe_id' => $request->classe_id,
                            'note' => $notes[$index],
                            'type_evaluation' => $request->type_evaluation,
                            'date_evaluation' => $request->date_evaluation,
                            'semestre' => $request->semestre,
                            'annee_scolaire_id' => $request->annee_universitaire_id,
                            'present' => true,
                            'commentaire' => $request->description,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    $processedGrades++;
                } else if (!in_array($studentId, $presents)) {
                    // Marquer l'étudiant comme absent
                    $existingGrade = Grade::where([
                        'etudiant_id' => $studentId,
                        'matiere_id' => $request->matiere_id,
                        'type_evaluation' => $request->type_evaluation,
                        'semestre' => $request->semestre,
                        'annee_scolaire_id' => $request->annee_universitaire_id,
                    ])->first();

                    if ($existingGrade) {
                        // Mettre à jour l'enregistrement existant
                        $existingGrade->present = false;
                        $existingGrade->updated_by = Auth::id();
                        $existingGrade->save();
                    } else {
                        // Créer un nouvel enregistrement pour l'absence
                        Grade::create([
                            'etudiant_id' => $studentId,
                            'matiere_id' => $request->matiere_id,
                            'classe_id' => $request->classe_id,
                            'note' => null,
                            'type_evaluation' => $request->type_evaluation,
                            'date_evaluation' => $request->date_evaluation,
                            'semestre' => $request->semestre,
                            'annee_scolaire_id' => $request->annee_universitaire_id,
                            'present' => false,
                            'commentaire' => $request->description,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    $processedGrades++;
                }
            }

            DB::commit();

            if ($processedGrades > 0) {
                return redirect()->route('teacher.grades.create')->with('success', 'Les notes ont été enregistrées avec succès (' . $processedGrades . ' étudiants).');
            } else {
                return redirect()->route('teacher.grades.create')->with('error', 'Aucune note n\'a été saisie. Veuillez vérifier si vous avez saisi au moins une note ou marqué des absences.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'enregistrement des notes: ' . $e->getMessage());
            return redirect()->route('teacher.grades.create')->with('error', 'Une erreur est survenue lors de l\'enregistrement des notes: ' . $e->getMessage());
        }
    }

    /**
     * Liste des notes saisies par l'enseignant
     */
    public function index(Request $request)
    {
        $enseignant = Auth::user()->enseignant;
        $currentYear = AnneeScolaire::where('actif', 1)->first();

        // Paramètres de filtrage
        $classeId = $request->input('classe_id');
        $matiereId = $request->input('matiere_id');
        $typeEvaluation = $request->input('type_evaluation');
        $semestre = $request->input('semestre');
        $anneeScolaireId = $request->input('annee_scolaire_id', $currentYear->id);

        // Classes où l'enseignant donne des cours
        $classeIds = ESBTPSeanceCours::where('enseignant', $enseignant->name)
            ->pluck('classe_id')
            ->unique();

        $classes = Classe::whereIn('id', $classeIds)->orderBy('name')->get();

        // Toutes les matières enseignées par l'enseignant
        $matieres = Matiere::whereHas('seances', function ($query) use ($classeId, $enseignant) {
            $query->where('classe_id', $classeId)
                  ->where('enseignant', $enseignant->name);
        })->select('id', 'name', 'code')->get();

        // Si pas de résultats avec la relation seances, essayons directement avec ESBTPSeanceCours
        if ($matieres->isEmpty()) {
            $matiereIds = ESBTPSeanceCours::where('classe_id', $classeId)
                ->where('enseignant', $enseignant->name)
                ->pluck('matiere_id')
                ->unique();

            $matieres = Matiere::whereIn('id', $matiereIds)
                ->select('id', 'name', 'code')
                ->get();
        }

        // Années scolaires
        $anneesScolaires = AnneeScolaire::orderBy('annee_debut', 'desc')->get();

        // Requête de base pour les évaluations
        $evaluationsQuery = Evaluation::where('teacher_id', $enseignant->id)
            ->with(['classe', 'matiere', 'notes']);

        // Application des filtres
        if ($classeId) {
            $evaluationsQuery->where('classe_id', $classeId);
        }

        if ($matiereId) {
            $evaluationsQuery->where('matiere_id', $matiereId);
        }

        if ($typeEvaluation) {
            $evaluationsQuery->where('type', $typeEvaluation);
        }

        if ($semestre) {
            $evaluationsQuery->where('semestre', $semestre);
        }

        if ($anneeScolaireId) {
            $evaluationsQuery->where('annee_scolaire_id', $anneeScolaireId);
        }

        // Récupération des évaluations paginées
        $evaluations = $evaluationsQuery->latest('date')->paginate(10);

        // Calcul des statistiques pour chaque évaluation
        foreach ($evaluations as $evaluation) {
            $totalEtudiants = $evaluation->classe->etudiants()->count();
            $presentEtudiants = $evaluation->notes()->where('absent', false)->count();
            $absentEtudiants = $evaluation->notes()->where('absent', true)->count();
            $sommeNotes = $evaluation->notes()->where('absent', false)->sum('note');

            $evaluation->total_etudiants = $totalEtudiants;
            $evaluation->present_etudiants = $presentEtudiants;
            $evaluation->absent_etudiants = $absentEtudiants;

            // Calcul de la moyenne
            if ($presentEtudiants > 0) {
                $evaluation->moyenne = round($sommeNotes / $presentEtudiants, 2);
            } else {
                $evaluation->moyenne = 0;
            }
        }

        return view('teacher.grades.index', compact(
            'evaluations',
            'classes',
            'matieres',
            'anneesScolaires',
            'currentYear',
            'classeId',
            'matiereId',
            'typeEvaluation',
            'semestre',
            'anneeScolaireId'
        ));
    }

    /**
     * Affiche le détail d'une évaluation
     */
    public function show($id)
    {
        $evaluation = Evaluation::with(['classe', 'matiere', 'notes.etudiant'])->findOrFail($id);

        // Vérifier que l'enseignant est bien le propriétaire de l'évaluation
        if ($evaluation->teacher_id != Auth::user()->enseignant->id) {
            return redirect()->route('teacher.grades.index')->with('error', 'Vous n\'êtes pas autorisé à accéder à cette évaluation.');
        }

        // Calcul des statistiques
        $totalEtudiants = $evaluation->notes->count();
        $presentEtudiants = $evaluation->notes->where('absent', false)->count();
        $absentEtudiants = $evaluation->notes->where('absent', true)->count();

        $notes = $evaluation->notes->where('absent', false)->pluck('note')->toArray();
        $moyenne = 0;
        $noteMin = 0;
        $noteMax = 0;

        if (count($notes) > 0) {
            $moyenne = round(array_sum($notes) / count($notes), 2);
            $noteMin = min($notes);
            $noteMax = max($notes);
        }

        // Calcul de la répartition des notes (pour un graphique éventuel)
        $distribution = [
            '0-4' => 0,
            '4-8' => 0,
            '8-10' => 0,
            '10-12' => 0,
            '12-14' => 0,
            '14-16' => 0,
            '16-20' => 0,
        ];

        foreach ($notes as $note) {
            $noteRapportee = ($note * 20) / $evaluation->bareme;

            if ($noteRapportee < 4) {
                $distribution['0-4']++;
            } elseif ($noteRapportee < 8) {
                $distribution['4-8']++;
            } elseif ($noteRapportee < 10) {
                $distribution['8-10']++;
            } elseif ($noteRapportee < 12) {
                $distribution['10-12']++;
            } elseif ($noteRapportee < 14) {
                $distribution['12-14']++;
            } elseif ($noteRapportee < 16) {
                $distribution['14-16']++;
            } else {
                $distribution['16-20']++;
            }
        }

        return view('teacher.grades.show', compact(
            'evaluation',
            'totalEtudiants',
            'presentEtudiants',
            'absentEtudiants',
            'moyenne',
            'noteMin',
            'noteMax',
            'distribution'
        ));
    }

    /**
     * Permet de modifier les notes d'une évaluation
     */
    public function edit($id)
    {
        $evaluation = Evaluation::with(['classe', 'matiere', 'notes.etudiant'])->findOrFail($id);

        // Vérifier que l'enseignant est bien le propriétaire de l'évaluation
        if ($evaluation->teacher_id != Auth::user()->enseignant->id) {
            return redirect()->route('teacher.grades.index')->with('error', 'Vous n\'êtes pas autorisé à modifier cette évaluation.');
        }

        $currentYear = AnneeScolaire::where('id', $evaluation->annee_scolaire_id)->first();

        // Récupérer tous les étudiants de la classe
                    $etudiants = $evaluation->classe->etudiants()->orderBy('nom')->orderBy('prenoms')->get();

        // Préparer les données des notes pour l'affichage
        foreach ($etudiants as $etudiant) {
            $note = $evaluation->notes->where('etudiant_id', $etudiant->id)->first();
            $etudiant->note = $note ? $note->note : null;
            $etudiant->absent = $note ? $note->absent : false;
        }

        return view('teacher.grades.edit', compact('evaluation', 'etudiants', 'currentYear'));
    }

    /**
     * Met à jour les notes d'une évaluation
     */
    public function update(Request $request, $id)
    {
        // Validation des données
        $validated = $request->validate([
            'type_evaluation' => 'required|string',
            'date_evaluation' => 'required|date',
            'bareme' => 'required|numeric|min:5|max:100',
            'commentaire' => 'nullable|string|max:500',
            'grades' => 'required|array',
            'grades.*.note' => 'nullable|numeric|min:0',
            'grades.*.absent' => 'nullable|boolean',
        ]);

        $evaluation = Evaluation::findOrFail($id);

        // Vérifier que l'enseignant est bien le propriétaire de l'évaluation
        if ($evaluation->teacher_id != Auth::user()->enseignant->id) {
            return redirect()->route('teacher.grades.index')->with('error', 'Vous n\'êtes pas autorisé à modifier cette évaluation.');
        }

        try {
            DB::beginTransaction();

            // Mettre à jour l'évaluation
            $evaluation->update([
                'type' => $validated['type_evaluation'],
                'date' => $validated['date_evaluation'],
                'bareme' => $validated['bareme'],
                'commentaire' => $validated['commentaire'] ?? null,
                'updated_at' => now(),
            ]);

            // Supprimer les anciennes notes pour les remplacer
            $evaluation->notes()->delete();

            // Enregistrer les nouvelles notes pour chaque étudiant
            foreach ($validated['grades'] as $etudiantId => $gradeData) {
                // Si l'étudiant est absent, on enregistre son absence
                if (isset($gradeData['absent']) && $gradeData['absent']) {
                    Note::create([
                        'evaluation_id' => $evaluation->id,
                        'etudiant_id' => $etudiantId,
                        'note' => null,
                        'absent' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                // Sinon, on enregistre sa note
                elseif (isset($gradeData['note']) && $gradeData['note'] !== '') {
                    // Vérifier que la note ne dépasse pas le barème
                    $note = min((float) $gradeData['note'], (float) $validated['bareme']);

                    Note::create([
                        'evaluation_id' => $evaluation->id,
                        'etudiant_id' => $etudiantId,
                        'note' => $note,
                        'absent' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('teacher.grades.index')->with('success', 'L\'évaluation a été mise à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'évaluation: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Supprime une évaluation
     */
    public function destroy($id)
    {
        $evaluation = Evaluation::findOrFail($id);

        // Vérifier que l'enseignant est bien le propriétaire de l'évaluation
        if ($evaluation->teacher_id != Auth::user()->enseignant->id) {
            return redirect()->route('teacher.grades.index')->with('error', 'Vous n\'êtes pas autorisé à supprimer cette évaluation.');
        }

        try {
            DB::beginTransaction();

            // Supprimer les notes associées
            $evaluation->notes()->delete();

            // Supprimer l'évaluation
            $evaluation->delete();

            DB::commit();
            return redirect()->route('teacher.grades.index')->with('success', 'L\'évaluation a été supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de l\'évaluation: ' . $e->getMessage());
        }
    }

    /**
     * API pour récupérer les matières associées à une classe pour l'enseignant connecté
     */
    public function getClassMatieres($classeId)
    {
        $enseignant = Auth::user()->enseignant;

        $matieres = Matiere::whereHas('seances', function ($query) use ($classeId, $enseignant) {
            $query->where('classe_id', $classeId)
                  ->where('enseignant', $enseignant->name);
        })->select('id', 'name', 'code')->get();

        // Si pas de résultats avec la relation seances, essayons directement avec ESBTPSeanceCours
        if ($matieres->isEmpty()) {
            $matiereIds = ESBTPSeanceCours::where('classe_id', $classeId)
                ->where('enseignant', $enseignant->name)
                ->pluck('matiere_id')
                ->unique();

            $matieres = Matiere::whereIn('id', $matiereIds)
                ->select('id', 'name', 'code')
                ->get();
        }

        return response()->json($matieres);
    }

    /**
     * API pour récupérer les étudiants d'une classe
     */
    public function getClassStudents(Request $request, $classeId)
    {
        $matiereId = $request->input('matiere_id');
        $typeEvaluation = $request->input('type_evaluation');
        $semestre = $request->input('semestre');
        $dateEvaluation = $request->input('date_evaluation');
        $anneeScolaireId = $request->input('annee_scolaire_id');

        $enseignant = Auth::user()->enseignant;

        // Vérifier si l'évaluation existe déjà
        $evaluation = Evaluation::where([
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'type' => $typeEvaluation,
            'semestre' => $semestre,
            'annee_scolaire_id' => $anneeScolaireId,
            'teacher_id' => $enseignant->id,
        ])->whereDate('date', $dateEvaluation)->first();

        // Récupérer les étudiants de la classe
        $etudiants = Etudiant::where('classe_id', $classeId)
            ->where('active', 1)
            ->select('id', 'matricule', 'nom', 'prenom')
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        // Si l'évaluation existe, ajouter les notes aux étudiants
        if ($evaluation) {
            foreach ($etudiants as $etudiant) {
                $note = Note::where('evaluation_id', $evaluation->id)
                    ->where('etudiant_id', $etudiant->id)
                    ->first();

                if ($note) {
                    $etudiant->note = $note->note;
                    $etudiant->absent = $note->absent;
                } else {
                    $etudiant->note = null;
                    $etudiant->absent = false;
                }
            }
        } else {
            // Sinon, initialiser les notes à null
            foreach ($etudiants as $etudiant) {
                $etudiant->note = null;
                $etudiant->absent = false;
            }
        }

        return response()->json($etudiants);
    }
}

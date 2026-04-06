<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Session;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassController extends Controller
{
    /**
     * Constructeur avec middleware pour restreindre l'accès aux superadmins uniquement
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isSuperAdmin()) {
                return redirect()->route('dashboard')
                    ->with('error', 'Vous n\'avez pas accès à cette fonctionnalité.');
            }
            return $next($request);
        });
    }

    /**
     * Afficher la liste des classes.
     */
    public function index()
    {
        $classes = ClassModel::with(['session', 'sections', 'students'])->get();
        return view('classes.index', compact('classes'));
    }

    /**
     * Afficher le formulaire de création d'une classe.
     */
    public function create()
    {
        $sessions = Session::all();
        return view('classes.create', compact('sessions'));
    }

    /**
     * Enregistrer une nouvelle classe.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_id' => 'required|exists:sessions,id',
        ]);

        ClassModel::create($validated);

        return redirect()->route('classes.index')
            ->with('success', 'Classe créée avec succès.');
    }

    /**
     * Afficher les détails d'une classe.
     */
    public function show(ClassModel $class)
    {
        $class->load(['session', 'sections', 'students.user', 'subjects', 'timetable']);
        $teachers = Teacher::with('user')->get();

        return view('classes.show', compact('class', 'teachers'));
    }

    /**
     * Afficher le formulaire de modification d'une classe.
     */
    public function edit(ClassModel $class)
    {
        $sessions = Session::all();
        return view('classes.edit', compact('class', 'sessions'));
    }

    /**
     * Mettre à jour une classe.
     */
    public function update(Request $request, ClassModel $class)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $class->update($validated);

        return redirect()->route('classes.index')
            ->with('success', 'Classe mise à jour avec succès.');
    }

    /**
     * Supprimer une classe.
     */
    public function destroy(ClassModel $class)
    {
        // Vérifier si la classe a des étudiants ou des sections
        if ($class->students()->count() > 0 || $class->sections()->count() > 0) {
            return redirect()->route('classes.index')
                ->with('error', 'Impossible de supprimer cette classe car elle contient des étudiants ou des sections.');
        }

        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', 'Classe supprimée avec succès.');
    }

    /**
     * Assigner un enseignant à une classe.
     */
    public function assignTeacher(Request $request, ClassModel $class)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        // Vérifier si l'enseignant est déjà assigné à cette classe pour cette matière
        $exists = $class->subjects()->wherePivot('teacher_id', $request->teacher_id)
                        ->wherePivot('subject_id', $request->subject_id)
                        ->exists();

        if ($exists) {
            return redirect()->route('classes.show', $class)
                ->with('error', 'Cet enseignant est déjà assigné à cette classe pour cette matière.');
        }

        // Assigner l'enseignant
        $class->subjects()->attach($request->subject_id, ['teacher_id' => $request->teacher_id]);

        return redirect()->route('classes.show', $class)
            ->with('success', 'Enseignant assigné avec succès.');
    }

    /**
     * Retirer un enseignant d'une classe.
     */
    public function unassignTeacher(Request $request, ClassModel $class)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        // Retirer l'enseignant
        $class->subjects()->wherePivot('teacher_id', $request->teacher_id)
                        ->wherePivot('subject_id', $request->subject_id)
                        ->detach();

        return redirect()->route('classes.show', $class)
            ->with('success', 'Enseignant retiré avec succès.');
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    /**
     * Affiche la liste des emplois du temps.
     */
    public function index(Request $request)
    {
        $query = Timetable::with(['class', 'section', 'subject', 'teacher']);
        
        // Filtres
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        
        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }
        
        $timetables = $query->orderBy('day')->orderBy('start_time')->paginate(15);
        $classes = ClassModel::all();
        $sections = Section::all();
        
        return view('timetables.index', compact('timetables', 'classes', 'sections'));
    }
    
    /**
     * Affiche le formulaire de création d'un emploi du temps.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $sections = Section::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        return view('timetables.create', compact('classes', 'sections', 'subjects', 'teachers', 'days'));
    }
    
    /**
     * Enregistre un nouvel emploi du temps.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_number' => 'nullable|string|max:20',
        ]);
        
        // Vérifier les conflits d'horaire pour la classe et la section
        $conflictClass = Timetable::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('day', $request->day)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();
            
        if ($conflictClass) {
            return redirect()->back()
                ->with('error', 'Il y a un conflit d\'horaire pour cette classe et section.')
                ->withInput();
        }
        
        // Vérifier les conflits d'horaire pour l'enseignant
        $conflictTeacher = Timetable::where('teacher_id', $request->teacher_id)
            ->where('day', $request->day)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();
            
        if ($conflictTeacher) {
            return redirect()->back()
                ->with('error', 'Il y a un conflit d\'horaire pour cet enseignant.')
                ->withInput();
        }
        
        Timetable::create([
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room_number' => $request->room_number,
            'created_by' => Auth::id(),
        ]);
        
        return redirect()->route('timetables.index')
            ->with('success', 'Emploi du temps créé avec succès.');
    }
    
    /**
     * Affiche les détails d'un emploi du temps.
     */
    public function show(Timetable $timetable)
    {
        $timetable->load(['class', 'section', 'subject', 'teacher.user']);
        
        return view('timetables.show', compact('timetable'));
    }
    
    /**
     * Affiche le formulaire de modification d'un emploi du temps.
     */
    public function edit(Timetable $timetable)
    {
        $classes = ClassModel::all();
        $sections = Section::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        return view('timetables.edit', compact('timetable', 'classes', 'sections', 'subjects', 'teachers', 'days'));
    }
    
    /**
     * Met à jour un emploi du temps.
     */
    public function update(Request $request, Timetable $timetable)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_number' => 'nullable|string|max:20',
        ]);
        
        // Vérifier les conflits d'horaire pour la classe et la section (en excluant l'emploi du temps actuel)
        $conflictClass = Timetable::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('day', $request->day)
            ->where('id', '!=', $timetable->id)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();
            
        if ($conflictClass) {
            return redirect()->back()
                ->with('error', 'Il y a un conflit d\'horaire pour cette classe et section.')
                ->withInput();
        }
        
        // Vérifier les conflits d'horaire pour l'enseignant (en excluant l'emploi du temps actuel)
        $conflictTeacher = Timetable::where('teacher_id', $request->teacher_id)
            ->where('day', $request->day)
            ->where('id', '!=', $timetable->id)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                    ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                    ->orWhere(function($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                          ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();
            
        if ($conflictTeacher) {
            return redirect()->back()
                ->with('error', 'Il y a un conflit d\'horaire pour cet enseignant.')
                ->withInput();
        }
        
        $timetable->update([
            'class_id' => $request->class_id,
            'section_id' => $request->section_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'room_number' => $request->room_number,
            'updated_by' => Auth::id(),
        ]);
        
        return redirect()->route('timetables.index')
            ->with('success', 'Emploi du temps mis à jour avec succès.');
    }
    
    /**
     * Supprime un emploi du temps.
     */
    public function destroy(Timetable $timetable)
    {
        $timetable->delete();
        
        return redirect()->route('timetables.index')
            ->with('success', 'Emploi du temps supprimé avec succès.');
    }
    
    /**
     * Affiche l'emploi du temps d'une classe spécifique.
     */
    public function showByClass($classId)
    {
        $class = ClassModel::findOrFail($classId);
        $sections = Section::where('class_id', $classId)->get();
        
        $timetables = Timetable::where('class_id', $classId)
            ->with(['section', 'subject', 'teacher.user'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
        
        // Organiser les emplois du temps par jour
        $timetableByDay = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $timetableByDay[$day] = $timetables
                ->where('day_of_week', $day)
                ->sortBy('start_time')
                ->values()
                ->all();
        }
        
        return view('timetables.class', compact('class', 'sections', 'timetableByDay'));
    }
    
    /**
     * Affiche l'emploi du temps d'un enseignant spécifique.
     */
    public function showByTeacher($teacherId)
    {
        $teacher = Teacher::with('user')->findOrFail($teacherId);
        
        $timetables = Timetable::where('teacher_id', $teacherId)
            ->with(['class', 'section', 'subject'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
        
        // Organiser les emplois du temps par jour
        $timetableByDay = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $timetableByDay[$day] = $timetables
                ->where('day_of_week', $day)
                ->sortBy('start_time')
                ->values()
                ->all();
        }
        
        return view('timetables.teacher', compact('teacher', 'timetableByDay'));
    }
} 
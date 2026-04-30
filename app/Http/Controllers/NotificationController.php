<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Notification;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Affiche la liste des notifications.
     */
    public function index(Request $request)
    {
        $query = Notification::with(['sender']);
        
        // Filtres
        if ($request->filled('recipient_type')) {
            $query->where('recipient_type', $request->recipient_type);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }
        
        $notifications = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('notifications.index', compact('notifications'));
    }
    
    /**
     * Affiche le formulaire de création d'une notification.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $sections = Section::all();
        
        return view('notifications.create', compact('classes', 'sections'));
    }
    
    /**
     * Enregistre une nouvelle notification.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'recipient_type' => 'required|in:all,students,teachers,parents,specific_class,specific_student',
            'class_id' => 'required_if:recipient_type,specific_class|nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'student_id' => 'required_if:recipient_type,specific_student|nullable|exists:students,id',
            'is_important' => 'boolean',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Créer la notification principale
            $notification = Notification::create([
                'title' => $request->title,
                'message' => $request->message,
                'sender_id' => Auth::id(),
                'recipient_type' => $request->recipient_type,
                'recipient_id' => $request->recipient_type === 'specific_student' ? $request->student_id : null,
                'class_id' => $request->recipient_type === 'specific_class' ? $request->class_id : null,
                'section_id' => $request->section_id,
                'is_important' => $request->is_important ?? false,
            ]);
            
            // Si la notification est pour une classe spécifique, envoyer à tous les étudiants de cette classe
            if ($request->recipient_type === 'specific_class') {
                $query = Student::where('class_id', $request->class_id);
                
                if ($request->filled('section_id')) {
                    $query->where('section_id', $request->section_id);
                }
                
                $students = $query->get();
                
                // Créer des notifications individuelles pour chaque étudiant
                foreach ($students as $student) {
                    Notification::create([
                        'title' => $request->title,
                        'message' => $request->message,
                        'sender_id' => Auth::id(),
                        'recipient_type' => 'specific_student',
                        'recipient_id' => $student->id,
                        'parent_notification_id' => $notification->id,
                        'is_important' => $request->is_important ?? false,
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('notifications.index')
                ->with('success', 'Notification envoyée avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de l\'envoi de la notification: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Affiche les détails d'une notification.
     */
    public function show(Notification $notification)
    {
        $notification->load(['sender', 'recipient', 'class', 'section']);
        
        return view('notifications.show', compact('notification'));
    }
    
    /**
     * Affiche le formulaire de modification d'une notification.
     */
    public function edit(Notification $notification)
    {
        // Vérifier si la notification peut être modifiée
        if ($notification->sender_id !== Auth::id()) {
            return redirect()->route('notifications.index')
                ->with('error', 'Vous ne pouvez pas modifier cette notification.');
        }
        
        $classes = ClassModel::all();
        $sections = Section::all();
        
        return view('notifications.edit', compact('notification', 'classes', 'sections'));
    }
    
    /**
     * Met à jour une notification.
     */
    public function update(Request $request, Notification $notification)
    {
        // Vérifier si la notification peut être modifiée
        if ($notification->sender_id !== Auth::id()) {
            return redirect()->route('notifications.index')
                ->with('error', 'Vous ne pouvez pas modifier cette notification.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_important' => 'boolean',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Mettre à jour la notification principale
            $notification->update([
                'title' => $request->title,
                'message' => $request->message,
                'is_important' => $request->is_important ?? false,
            ]);
            
            // Mettre à jour les notifications enfants si elles existent
            if ($notification->parent_notification_id === null) {
                Notification::where('parent_notification_id', $notification->id)
                    ->update([
                        'title' => $request->title,
                        'message' => $request->message,
                        'is_important' => $request->is_important ?? false,
                    ]);
            }
            
            DB::commit();
            
            return redirect()->route('notifications.index')
                ->with('success', 'Notification mise à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour de la notification: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Supprime une notification.
     */
    public function destroy(Notification $notification)
    {
        // Vérifier si la notification peut être supprimée
        if ($notification->sender_id !== Auth::id()) {
            return redirect()->route('notifications.index')
                ->with('error', 'Vous ne pouvez pas supprimer cette notification.');
        }
        
        DB::beginTransaction();
        
        try {
            // Supprimer les notifications enfants si elles existent
            if ($notification->parent_notification_id === null) {
                Notification::where('parent_notification_id', $notification->id)->delete();
            }
            
            // Supprimer la notification principale
            $notification->delete();
            
            DB::commit();
            
            return redirect()->route('notifications.index')
                ->with('success', 'Notification supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression de la notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Envoie une notification.
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'recipient_type' => 'required|in:all,students,teachers,parents,specific_class,specific_student',
            'class_id' => 'required_if:recipient_type,specific_class|nullable|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'student_id' => 'required_if:recipient_type,specific_student|nullable|exists:students,id',
            'is_important' => 'boolean',
        ]);
        
        return $this->store($request);
    }
    
    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();
        
        // Vérifier si l'utilisateur est le destinataire de la notification
        if ($notification->recipient_type === 'specific_student' && $user->student && $notification->recipient_id === $user->student->id) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
            
            return redirect()->back()->with('success', 'Notification marquée comme lue.');
        } elseif ($notification->recipient_type === 'all' || $notification->recipient_type === $user->role) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
            
            return redirect()->back()->with('success', 'Notification marquée comme lue.');
        }
        
        return redirect()->back()->with('error', 'Vous ne pouvez pas marquer cette notification comme lue.');
    }
    
    /**
     * Récupère les notifications pour l'API.
     */
    public function apiNotifications(Request $request)
    {
        $user = Auth::user();
        $query = Notification::query();
        
        if ($user->can('identity.student') && $user->student) {
            $query->where(function($q) use ($user) {
                $q->where('recipient_type', 'all')
                  ->orWhere('recipient_type', 'students')
                  ->orWhere(function($sq) use ($user) {
                      $sq->where('recipient_type', 'specific_student')
                        ->where('recipient_id', $user->student->id);
                  })
                  ->orWhere(function($sq) use ($user) {
                      $sq->where('recipient_type', 'specific_class')
                        ->where('class_id', $user->student->class_id)
                        ->where(function($ssq) use ($user) {
                            $ssq->whereNull('section_id')
                                ->orWhere('section_id', $user->student->section_id);
                        });
                  });
            });
        } elseif ($user->can('identity.teach') && $user->teacher) {
            $query->where(function($q) {
                $q->where('recipient_type', 'all')
                  ->orWhere('recipient_type', 'teachers');
            });
        }
        
        $notifications = $query->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }
} 
<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Models\Filiere;
use App\Models\NiveauEtude;
use App\Models\Formation;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{
    /**
     * Constructeur qui applique le middleware auth et role:superAdmin.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    /**
     * Affiche le tableau de bord du superAdmin.
     */
    public function dashboard()
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();
        
        // Statistiques pour le tableau de bord
        $totalStudents = ESBTPEtudiant::count();
        $totalSecretaires = User::role('secretaire')->count();
        $totalFilieres = Filiere::count();
        $totalFormations = Formation::count();
        $totalNiveaux = NiveauEtude::count();
        $totalClasses = Classe::count();
        $totalMatieres = ESBTPMatiere::count();
        $totalExamens = ESBTPEvaluation::count();
        
        // Examens à venir
        $upcomingExamens = ESBTPEvaluation::with(['classe', 'matiere'])
            ->where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->take(5)
            ->get();
        
        // Messages récents
        $recentMessages = Message::with('sender')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Notifications récentes
        $recentNotifications = Notification::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        return view('dashboard.superadmin', compact(
            'user',
            'totalStudents',
            'totalSecretaires',
            'totalFilieres',
            'totalFormations',
            'totalNiveaux',
            'totalClasses',
            'totalMatieres',
            'totalExamens',
            'upcomingExamens',
            'recentMessages',
            'recentNotifications',
            'anneeEnCours'
        ));
    }
} 

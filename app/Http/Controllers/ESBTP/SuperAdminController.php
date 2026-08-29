<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Students\StudentCountService;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;

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
    public function dashboard(StudentCountService $studentCounts)
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();

        // Statistiques pour le tableau de bord — distinguer "inscrits année courante" vs "total base"
        $counts = $studentCounts->counts();
        $totalStudents = $counts['inscrits_annee_courante'];
        $totalStudentsBase = $counts['total_base'];
        $anneeLabel = $counts['annee_courante_label'];
        $totalFilieres = ESBTPFiliere::count();
        $totalClasses = ESBTPClasse::count();
        $totalMatieres = ESBTPMatiere::count();
        $totalTeachers = ESBTPTeacher::count();

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        
        return view('dashboard.superadmin', compact(
            'user',
            'totalStudents',
            'totalStudentsBase',
            'anneeLabel',
            'totalFilieres',
            'totalClasses',
            'totalMatieres',
            'totalTeachers',
            'anneeEnCours'
        ));
    }
} 

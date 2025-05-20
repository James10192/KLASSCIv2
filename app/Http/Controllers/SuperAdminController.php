<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;

use App\Models\ESBTPMatiere;
use App\Models\User;

class SuperAdminController extends Controller
{
    /**
     * Affiche le tableau de bord du super administrateur
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        // Récupérer les statistiques pour le tableau de bord
        $stats = [
            'totalEtudiants' => ESBTPEtudiant::count(),
            'totalClasses' => ESBTPClasse::count(),
            'totalFilieres' => ESBTPFiliere::count(),
            'totalNiveauxEtudes' => ESBTPNiveauEtude::count(),
            'totalMatieres' => ESBTPMatiere::count(),
            'totalUtilisateurs' => User::count(),
        ];

        // Récupérer les derniers étudiants inscrits
        $dernierEtudiants = ESBTPEtudiant::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('esbtp.admin.dashboard', compact('stats', 'dernierEtudiants'));
    }
}
<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Students\StudentCountService;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecretaireController extends Controller
{
    /**
     * Constructeur qui applique le middleware auth et role:secretaire.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:secretaire']);
    }

    /**
     * Affiche le tableau de bord du secrétaire.
     */
    public function dashboard(StudentCountService $studentCounts)
    {
        $counts = $studentCounts->counts();
        $totalStudents = $counts['inscrits_annee_courante']; // KPI principal = inscrits cette année
        $totalStudentsBase = $counts['total_base'];           // KPI complémentaire = base totale
        $anneeLabel = $counts['annee_courante_label'];
        
        // Récupérer les présences d'aujourd'hui seulement si la table existe
        try {
            $todayAttendances = Attendance::whereDate('date', today())->count();
            $pendingAttendances = Attendance::whereDate('date', today())
                ->whereNull('status')
                ->count();
        } catch (\Exception $e) {
            $todayAttendances = 0;
            $pendingAttendances = 0;
        }
        
        // Récupérer les justificatifs d'absence en attente (W5 : workflow status sur ESBTPAttendance)
        try {
            $justificationsEnAttente = \App\Models\ESBTPAttendance::where(
                'justification_status',
                \App\Enums\JustificationStatus::PENDING->value
            )->count();
        } catch (\Exception $e) {
            $justificationsEnAttente = 0;
        }
        
        // Récupérer les notifications récentes
        try {
            $recentNotifications = Notification::orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $recentNotifications = collect(); // Collection vide si erreur
        }
            
        // Récupérer les messages récents
        try {
            $recentMessages = Message::where(function($query) {
                    $query->where('recipient_type', 'secretaires')
                        ->whereNull('recipient_group');
                })
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                        ->whereNull('recipient_group');
                })
                ->orWhere('recipient_id', Auth::id())
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $recentMessages = collect(); // Collection vide si erreur
        }
        
        return view('dashboard.secretaire', compact(
            'totalStudents',
            'totalStudentsBase',
            'anneeLabel',
            'todayAttendances',
            'pendingAttendances',
            'justificationsEnAttente',
            'recentNotifications',
            'recentMessages'
        ));
    }
} 
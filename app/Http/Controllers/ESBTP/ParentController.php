<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Message;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\Payment;
use App\Models\ESBTPParent;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;

class ParentController extends Controller
{
    /**
     * Constructeur du contrôleur
     * Applique le middleware d'authentification et vérifie le rôle
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:parent']);
    }

    /**
     * Affiche le tableau de bord du parent
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Récupérer le parent
        $parent = ESBTPParent::where('user_id', $user->id)->first();

        if (!$parent) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un parent.');
        }

        // Récupérer les étudiants associés au parent
        $etudiants = ESBTPEtudiant::whereHas('parents', function($query) use ($parent) {
            $query->where('esbtp_parents.id', $parent->id);
        })->get();

        // Récupérer les IDs des étudiants
        $etudiantIds = $etudiants->pluck('id')->toArray();

        // Année universitaire en cours
        $anneeUniversitaire = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Récupérer les derniers paiements
        try {
            $recentPayments = ESBTPPaiement::whereIn('etudiant_id', $etudiantIds)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $recentPayments = collect(); // Collection vide si erreur
        }

        // Récupérer les dernières absences
        try {
            $recentAbsences = ESBTPAbsence::whereIn('etudiant_id', $etudiantIds)
                ->orderBy('date', 'desc')
                ->take(5)
                ->get();

            // Compter les absences non justifiées
            $absencesNonJustifiees = ESBTPAbsence::whereIn('etudiant_id', $etudiantIds)
                ->where('justifie', false)
                ->count();
        } catch (\Exception $e) {
            $recentAbsences = collect(); // Collection vide si erreur
            $absencesNonJustifiees = 0;
        }

        // Récupérer les derniers bulletins
        try {
            $recentBulletins = ESBTPBulletin::whereIn('etudiant_id', $etudiantIds)
                ->with(['etudiant', 'classe', 'anneeUniversitaire'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Compter les nouveaux bulletins (dernière semaine)
            $nouveauxBulletins = ESBTPBulletin::whereIn('etudiant_id', $etudiantIds)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        } catch (\Exception $e) {
            $recentBulletins = collect(); // Collection vide si erreur
            $nouveauxBulletins = 0;
        }

        // Récupérer les notifications
        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $unreadNotificationsCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();
        } catch (\Exception $e) {
            $notifications = collect(); // Collection vide si erreur
            $unreadNotificationsCount = 0;
        }

        // Récupérer les messages
        try {
            $messages = Message::where('recipient_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $unreadMessagesCount = Message::where('recipient_id', $user->id)
                ->where('is_read', false)
                ->count();
        } catch (\Exception $e) {
            $messages = collect(); // Collection vide si erreur
            $unreadMessagesCount = 0;
        }

        return view('dashboard.parent', compact(
            'user',
            'parent',
            'etudiants',
            'recentPayments',
            'recentAbsences',
            'absencesNonJustifiees',
            'recentBulletins',
            'nouveauxBulletins',
            'notifications',
            'unreadNotificationsCount',
            'messages',
            'unreadMessagesCount'
        ));
    }

    /**
     * Affiche les informations détaillées d'un étudiant
     */
    public function showStudent($id)
    {
        $user = Auth::user();
        $parent = ESBTPParent::where('user_id', $user->id)->firstOrFail();

        // Vérifier que l'étudiant appartient au parent
        $etudiant = ESBTPEtudiant::where('id', $id)
            ->whereHas('parents', function($query) use ($parent) {
                $query->where('esbtp_parents.id', $parent->id);
            })->firstOrFail();

        // Charger les relations nécessaires
        $etudiant->load([
            'inscription',
            'classe',
            'absences' => function($query) {
                $query->orderBy('date', 'desc');
            }
        ]);

        // Récupérer les statistiques d'absences
        $statsAbsences = [
            'total' => ESBTPAbsence::where('etudiant_id', $etudiant->id)->count(),
            'justifiees' => ESBTPAbsence::where('etudiant_id', $etudiant->id)->where('justifie', true)->count(),
            'non_justifiees' => ESBTPAbsence::where('etudiant_id', $etudiant->id)->where('justifie', false)->count(),
        ];

        // Récupérer les bulletins
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->orderBy('date_generation', 'desc')
            ->get();

        return view('parent.student.show', compact('parent', 'etudiant', 'statsAbsences', 'bulletins'));
    }

    /**
     * Affiche les messages reçus par le parent
     */
    public function messages()
    {
        $user = Auth::user();

        $messages = Message::where('recipient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('parent.messages.index', compact('messages'));
    }

    /**
     * Affiche les paiements pour tous les étudiants du parent
     */
    public function payments()
    {
        $user = Auth::user();

        // Récupérer les étudiants associés au parent
        $etudiants = ESBTPEtudiant::whereHas('user', function($query) use ($user) {
            $query->where('parent_id', $user->id);
        })->get();

        // Récupérer tous les paiements pour ces étudiants
        $payments = Payment::whereIn('student_id', $etudiants->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('parent.payments.index', compact('payments', 'etudiants'));
    }

    /**
     * Affiche les paramètres du compte parent
     */
    public function settings()
    {
        $user = Auth::user();
        return view('parent.settings.index', compact('user'));
    }

    /**
     * Met à jour les paramètres du compte parent
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->save();

        return redirect()->route('parent.settings')
            ->with('success', 'Vos informations ont été mises à jour avec succès.');
    }
}

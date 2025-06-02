<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use Carbon\Carbon;

class NavbarController extends Controller
{
    /**
     * Récupérer les notifications pour la navbar
     */
    public function getNotifications()
    {
        $user = auth()->user();
        $notifications = collect();

        if ($user->hasRole('superAdmin') || $user->hasRole('secretaire')) {
            // Notifications pour admin/secrétaire
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link
                    ];
                });
        } elseif ($user->hasRole('etudiant')) {
            // Notifications pour étudiant
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link
                    ];
                });
        } elseif ($user->hasRole('teacher')) {
            // Notifications pour enseignant
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link
                    ];
                });
        }

        $unreadCount = $notifications->where('read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Récupérer les messages pour la navbar
     */
    public function getMessages()
    {
        $user = auth()->user();
        $messages = collect();

        if ($user->hasRole('superAdmin') || $user->hasRole('secretaire')) {
            // Messages pour admin/secrétaire - récupérer les dernières annonces
            $messages = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => 'Système',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => false, // À implémenter selon vos besoins
                        'url' => route('esbtp.annonces.show', $annonce->id),
                        'avatar' => null
                    ];
                });
        } elseif ($user->hasRole('etudiant')) {
            // Messages pour étudiant - récupérer les annonces publiques
            $messages = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => 'Administration',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => false, // À implémenter selon vos besoins
                        'url' => route('esbtp.mes-messages.index'),
                        'avatar' => null
                    ];
                });
        } elseif ($user->hasRole('teacher')) {
            // Messages pour enseignant - récupérer les annonces
            $messages = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => 'Administration',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => false,
                        'url' => route('esbtp.annonces.show', $annonce->id),
                        'avatar' => null
                    ];
                });
        }

        $unreadCount = $messages->where('read', false)->count();

        return response()->json([
            'messages' => $messages,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Récupérer les actions rapides selon le rôle
     */
    public function getQuickActions()
    {
        $user = auth()->user();
        $actions = [];

        if ($user->hasRole('superAdmin')) {
            $actions = [
                [
                    'title' => 'Nouvel étudiant',
                    'icon' => 'fas fa-user-plus',
                    'url' => route('esbtp.inscriptions.create'),
                    'color' => 'primary'
                ],
                [
                    'title' => 'Nouvelle classe',
                    'icon' => 'fas fa-users',
                    'url' => route('esbtp.classes.create'),
                    'color' => 'info'
                ],
                [
                    'title' => 'Créer examen',
                    'icon' => 'fas fa-file-alt',
                    'url' => route('esbtp.evaluations.create'),
                    'color' => 'success'
                ],
                [
                    'title' => 'Nouvelle annonce',
                    'icon' => 'fas fa-bullhorn',
                    'url' => route('esbtp.annonces.create'),
                    'color' => 'warning'
                ],
                [
                    'title' => 'Saisie notes',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => route('esbtp.notes.index'),
                    'color' => 'secondary'
                ],
                [
                    'title' => 'Emploi du temps',
                    'icon' => 'fas fa-calendar-alt',
                    'url' => route('esbtp.emploi-temps.index'),
                    'color' => 'info'
                ]
            ];
        } elseif ($user->hasRole('secretaire')) {
            $actions = [
                [
                    'title' => 'Nouvel étudiant',
                    'icon' => 'fas fa-user-plus',
                    'url' => route('esbtp.inscriptions.create'),
                    'color' => 'primary'
                ],
                [
                    'title' => 'Saisie notes',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => route('esbtp.notes.index'),
                    'color' => 'success'
                ],
                [
                    'title' => 'Nouvelle annonce',
                    'icon' => 'fas fa-bullhorn',
                    'url' => route('esbtp.annonces.create'),
                    'color' => 'warning'
                ],
                [
                    'title' => 'Présences enseignants',
                    'icon' => 'fas fa-check-circle',
                    'url' => route('esbtp.teacher-attendance.index'),
                    'color' => 'info'
                ]
            ];
        } elseif ($user->hasRole('etudiant')) {
            $actions = [
                [
                    'title' => 'Mon emploi du temps',
                    'icon' => 'fas fa-calendar-alt',
                    'url' => route('esbtp.mon-emploi-temps.index'),
                    'color' => 'primary'
                ],
                [
                    'title' => 'Mes notes',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => route('esbtp.mes-notes.index'),
                    'color' => 'success'
                ],
                [
                    'title' => 'Mon bulletin',
                    'icon' => 'fas fa-file-invoice',
                    'url' => route('esbtp.mon-bulletin.index'),
                    'color' => 'info'
                ],
                [
                    'title' => 'Mon profil',
                    'icon' => 'fas fa-user-circle',
                    'url' => route('esbtp.mon-profil.index'),
                    'color' => 'secondary'
                ]
            ];
        } elseif ($user->hasRole('teacher')) {
            $actions = [
                [
                    'title' => 'Émargement',
                    'icon' => 'fas fa-clipboard-check',
                    'url' => route('esbtp.attendance.mark'),
                    'color' => 'primary'
                ],
                [
                    'title' => 'Mes cours',
                    'icon' => 'fas fa-chalkboard-teacher',
                    'url' => route('dashboard'),
                    'color' => 'info'
                ],
                [
                    'title' => 'Saisie notes',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => route('esbtp.notes.index'),
                    'color' => 'success'
                ],
                [
                    'title' => 'Mon profil',
                    'icon' => 'fas fa-user-circle',
                    'url' => route('admin.profile'),
                    'color' => 'secondary'
                ]
            ];
        }

        return response()->json([
            'actions' => $actions
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier que l'utilisateur peut marquer cette notification comme lue
        if ($notification->user_id === auth()->id() || $notification->type === 'global') {
            $notification->update(['is_read' => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllNotificationsAsRead()
    {
        $user = auth()->user();

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Obtenir l'icône pour un type de notification
     */
    private function getNotificationIcon($type)
    {
        $icons = [
            'inscription' => 'fas fa-user-plus',
            'note' => 'fas fa-clipboard-list',
            'annonce' => 'fas fa-bullhorn',
            'evaluation' => 'fas fa-file-alt',
            'presence' => 'fas fa-check-circle',
            'global' => 'fas fa-info-circle',
            'classe' => 'fas fa-users',
            'default' => 'fas fa-bell'
        ];

        return $icons[$type] ?? $icons['default'];
    }

    /**
     * Obtenir les statistiques du tableau de bord pour la navbar
     */
    public function getDashboardStats()
    {
        $user = auth()->user();
        $stats = [];

        if ($user->hasRole(['superAdmin', 'secretaire'])) {
            $stats = [
                'total_etudiants' => ESBTPEtudiant::count(),
                'nouvelles_inscriptions' => ESBTPEtudiant::whereDate('created_at', today())->count(),
                'evaluations_en_cours' => ESBTPEvaluation::whereDate('date', '>=', today())->count(),
                'notes_en_attente' => ESBTPNote::whereNull('valeur')->count()
            ];
        } elseif ($user->hasRole('etudiant')) {
            $etudiant = $user->etudiant;
            if ($etudiant) {
                $stats = [
                    'prochaines_evaluations' => ESBTPEvaluation::where('classe_id', $etudiant->classe_id)
                        ->whereDate('date', '>=', today())
                        ->count(),
                    'notes_recentes' => ESBTPNote::where('etudiant_id', $etudiant->id)
                        ->whereDate('created_at', '>=', now()->subDays(7))
                        ->count()
                ];
            }
        }

        return response()->json($stats);
    }
}

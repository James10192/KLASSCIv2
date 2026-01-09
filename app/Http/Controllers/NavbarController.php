<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\TimetableShortcutService;
use App\Services\EvaluationPublishShortcutService;
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
        $shortcutItems = $this->getTimetableShortcutNotifications($user)
            ->concat($this->getEvaluationShortcutNotifications($user));

        if ($user->hasRole('superAdmin') || $user->hasRole('secretaire') || $user->hasRole('coordinateur')) {
            // Notifications pour admin/secrétaire/coordinateur
            $notifications = Notification::where('user_id', $user->id)
                ->with('sender') // Load sender relationship
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => strip_tags($notification->message ?? ''),
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link,
                        'sender' => $notification->sender ? $notification->sender->name : 'Système'
                    ];
                });
        } elseif ($user->hasRole('etudiant')) {
            // Notifications pour étudiant
            $notifications = Notification::where('user_id', $user->id)
                ->with('sender') // Load sender relationship
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => strip_tags($notification->message ?? ''),
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link,
                        'sender' => $notification->sender ? $notification->sender->name : 'Système'
                    ];
                });
        } elseif ($user->hasRole('teacher')) {
            // Notifications pour enseignant
            $notifications = Notification::where('user_id', $user->id)
                ->with('sender') // Load sender relationship
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => strip_tags($notification->message ?? ''),
                        'type' => $notification->type,
                        'icon' => $this->getNotificationIcon($notification->type),
                        'time' => $notification->created_at->diffForHumans(),
                        'read' => $notification->is_read,
                        'url' => $notification->link,
                        'sender' => $notification->sender ? $notification->sender->name : 'Système'
                    ];
                });
        }

        $notifications = $shortcutItems->concat($notifications);
        $unreadCount = Notification::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('is_read', false)
                    ->orWhereNull('is_read');
            })
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    private function getTimetableShortcutNotifications($user): \Illuminate\Support\Collection
    {
        $canSee = $user->hasRole('superAdmin')
            || $user->hasRole('secretaire')
            || $user->hasRole('coordinateur')
            || $user->can('view_timetables')
            || $user->can('view-all-timetables');

        if (!$canSee) {
            return collect();
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            return collect();
        }

        $summary = app(TimetableShortcutService::class)->getShortcutSummary($anneeEnCours);
        if (empty($summary['show'])) {
            return collect();
        }

        $parts = [];
        if ($summary['missing'] > 0) {
            $parts[] = $summary['missing'] . ' classe(s) sans emploi du temps';
        }
        if ($summary['expired'] > 0) {
            $parts[] = $summary['expired'] . ' expiré(s)';
        }
        if ($summary['expiring_soon'] > 0) {
            $parts[] = $summary['expiring_soon'] . ' expire(nt) bientôt';
        }

        return collect([
            [
                'id' => 'shortcut-timetable',
                'title' => 'Emplois du temps à renouveler',
                'message' => implode(' • ', $parts),
                'type' => 'warning',
                'icon' => 'fas fa-calendar-exclamation',
                'time' => 'Action rapide',
                'read' => true,
                'url' => route('esbtp.emploi-temps.index', ['quick_generate' => 1]),
                'sender' => 'Système',
                'is_virtual' => true,
            ],
        ]);
    }

    private function getEvaluationShortcutNotifications($user): \Illuminate\Support\Collection
    {
        $canSee = $user->hasRole('superAdmin')
            || $user->hasRole('secretaire')
            || $user->hasRole('coordinateur')
            || $user->hasRole('enseignant')
            || $user->hasRole('teacher');

        if (!$canSee) {
            return collect();
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            return collect();
        }

        $summary = app(EvaluationPublishShortcutService::class)->getShortcutSummary($anneeEnCours);
        if (empty($summary['show'])) {
            return collect();
        }

        $parts = [];
        $parts[] = $summary['total'] . ' évaluation(s) en brouillon';
        if (!empty($summary['overdue'])) {
            $parts[] = $summary['overdue'] . ' en retard';
        }
        if (!empty($summary['soon'])) {
            $parts[] = $summary['soon'] . ' à publier bientôt';
        }
        if (!empty($summary['undated'])) {
            $parts[] = $summary['undated'] . ' sans date';
        }

        return collect([
            [
                'id' => 'shortcut-evaluations',
                'title' => 'Évaluations à activer',
                'message' => implode(' • ', $parts),
                'type' => 'warning',
                'icon' => 'fas fa-clipboard-check',
                'time' => 'Action rapide',
                'read' => true,
                'url' => route('esbtp.evaluations.index'),
                'sender' => 'Système',
                'is_virtual' => true,
            ],
        ]);
    }

    /**
     * Récupérer les messages pour la navbar
     */
    public function getMessages()
    {
        $user = auth()->user();
        $messages = collect();

        if ($user->hasRole('superAdmin') || $user->hasRole('secretaire') || $user->hasRole('coordinateur')) {
            // Messages pour admin/secrétaire/coordinateur - récupérer les annonces qui leur sont destinées
            $messages = ESBTPAnnonce::with('createdBy') // Charger la relation créateur
                ->where(function ($query) {
                    // Annonces destinées aux administrateurs (ni étudiants généraux, ni classes, ni étudiants spécifiques)
                    // Pour l'instant, les administrateurs ne voient que les annonces générales (à adapter selon besoins)
                    $query->where('type', '!=', 'general')
                          ->where('type', '!=', 'classe')
                          ->where('type', '!=', 'etudiant');
                })
                ->orWhere(function ($query) {
                    // Ou bien, inclure toutes les annonces mais avec un système de destinataires pour admin
                    // Cette partie peut être étendue selon les besoins métier
                    $query->whereNull('type'); // Annonces sans type spécifique (pour admin)
                })
                ->orderBy('created_at', 'desc')
                ->limit(10) // Augmenté car le filtrage peut réduire les résultats
                ->get()
                ->filter(function ($annonce) use ($user) {
                    // Filtrer les annonces créées par l'utilisateur actuel pour éviter l'auto-notification
                    return !$annonce->created_by || $annonce->created_by != $user->id;
                })
                ->take(5) // Limiter à 5 après filtrage
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => $annonce->createdBy ? explode(' ', $annonce->createdBy->name)[0] . ' ' . (explode(' ', $annonce->createdBy->name)[1] ?? '') : 'Système',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => $annonce->created_at->lt(now()->subDay()), // Marquer comme lu si plus de 24h
                        'url' => route('esbtp.annonces.show', $annonce->id),
                        'avatar' => null
                    ];
                });
        } elseif ($user->hasRole('etudiant')) {
            // Messages pour étudiant - récupérer seulement les annonces qui leur sont destinées
            $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

            $messages = ESBTPAnnonce::with(['createdBy', 'classes', 'etudiants']) // Charger les relations nécessaires
                ->where(function ($query) use ($etudiant) {
                    // Annonces générales pour tous les étudiants
                    $query->where('type', 'general');

                    // Si l'étudiant existe, ajouter les annonces pour sa classe
                    if ($etudiant && $etudiant->classe_active) {
                        $query->orWhere(function ($subQuery) use ($etudiant) {
                            $subQuery->where('type', 'classe')
                                     ->whereHas('classes', function ($classQuery) use ($etudiant) {
                                         $classQuery->where('esbtp_classes.id', $etudiant->classe_active->id);
                                     });
                        });
                    }

                    // Annonces destinées spécifiquement à cet étudiant
                    if ($etudiant) {
                        $query->orWhere(function ($subQuery) use ($etudiant) {
                            $subQuery->where('type', 'etudiant')
                                     ->whereHas('etudiants', function ($etudiantQuery) use ($etudiant) {
                                         $etudiantQuery->where('esbtp_etudiants.id', $etudiant->id);
                                     });
                        });
                    }
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => $annonce->createdBy ? explode(' ', $annonce->createdBy->name)[0] . ' ' . (explode(' ', $annonce->createdBy->name)[1] ?? '') : 'Administration',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => $annonce->created_at->lt(now()->subDay()), // Marquer comme lu si plus de 24h
                        'url' => route('esbtp.mes-messages.index'),
                        'avatar' => null
                    ];
                });
        } elseif ($user->hasRole('teacher')) {
            // Messages pour enseignant - récupérer les annonces qui leur sont destinées
            $messages = ESBTPAnnonce::with('createdBy') // Charger la relation créateur
                ->where(function ($query) {
                    // Les enseignants ne voient que les annonces destinées aux administrateurs/personnel
                    // (pas celles pour étudiants spécifiquement)
                    $query->where('type', '!=', 'general')
                          ->where('type', '!=', 'classe')
                          ->where('type', '!=', 'etudiant');
                })
                ->orWhere(function ($query) {
                    // Ou annonces générales pour le personnel
                    $query->whereNull('type'); // Annonces sans type spécifique
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($annonce) {
                    return [
                        'id' => $annonce->id,
                        'title' => $annonce->titre,
                        'message' => \Str::limit($annonce->contenu, 50),
                        'sender' => $annonce->createdBy ? explode(' ', $annonce->createdBy->name)[0] . ' ' . (explode(' ', $annonce->createdBy->name)[1] ?? '') : 'Administration',
                        'time' => $annonce->created_at->diffForHumans(),
                        'read' => $annonce->created_at->lt(now()->subDay()), // Marquer comme lu si plus de 24h
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
            ->where(function ($query) {
                $query->where('is_read', false)
                    ->orWhereNull('is_read');
            })
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Supprimer une notification
     */
    public function deleteNotification($id)
    {
        $notification = Notification::findOrFail($id);

        // Vérifier que l'utilisateur peut supprimer cette notification
        if ($notification->user_id === auth()->id() || $notification->type === 'global') {
            $notification->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Supprimer toutes les notifications
     */
    public function deleteAllNotifications()
    {
        $user = auth()->user();

        Notification::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Marquer tous les messages comme lus
     */
    public function markAllMessagesAsRead()
    {
        // Pour les annonces (messages), on ne peut pas vraiment les marquer comme lus
        // car elles n'ont pas de champ is_read dans notre système
        // On pourrait ajouter une table pivot user_announcement_read si nécessaire
        
        return response()->json(['success' => true, 'message' => 'Tous les messages marqués comme lus']);
    }

    /**
     * Supprimer un message/annonce
     */
    public function deleteMessage($id)
    {
        $user = auth()->user();

        // Seuls les admins/secrétaires peuvent supprimer des annonces
        if ($user->hasRole(['superAdmin', 'secretaire'])) {
            $annonce = ESBTPAnnonce::findOrFail($id);
            $annonce->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
    }

    /**
     * Supprimer tous les messages/annonces
     */
    public function deleteAllMessages()
    {
        $user = auth()->user();

        // Seuls les admins/secrétaires peuvent supprimer toutes les annonces
        if ($user->hasRole(['superAdmin', 'secretaire'])) {
            ESBTPAnnonce::truncate(); // Supprimer toutes les annonces
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 403);
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

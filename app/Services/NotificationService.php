<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Créer une notification personnalisée
     */
    public function createNotification(User $user, string $title, string $message, string $type = 'info', ?string $link = null, ?User $sentBy = null): ?Notification
    {
        try {
            return Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'link' => $link,
                'sent_by' => $sentBy ? $sentBy->id : null,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de notification', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Notifications pour les absences
     */
    public function notifyAbsenceJustificationSubmitted(ESBTPAttendance $absence, ESBTPEtudiant $etudiant): void
    {
        try {
            // Notifier les administrateurs et secrétaires
            $admins = User::role(['superAdmin', 'secretaire'])->get();

            $title = "Nouvelle justification d'absence";
            $message = "L'étudiant {$etudiant->nom} {$etudiant->prenoms} a soumis une justification d'absence pour le cours du " . $absence->date->format('d/m/Y');
            $link = route('esbtp.attendances.index', ['highlight' => 'absence_' . $absence->id]);

            foreach ($admins as $admin) {
                $this->createNotification($admin, $title, $message, 'warning', $link);
            }

            Log::info('Notifications envoyées pour justification d\'absence', [
                'absence_id' => $absence->id,
                'etudiant_id' => $etudiant->id,
                'admins_notified' => $admins->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications de justification', [
                'absence_id' => $absence->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyAbsenceJustificationApproved(ESBTPAttendance $absence, ESBTPEtudiant $etudiant, ?User $approvedBy = null): void
    {
        try {
            if ($etudiant->user) {
                $title = "Justification d'absence approuvée";
                $message = "Votre justification d'absence pour le cours du " . $absence->date->format('d/m/Y') . " a été approuvée.";
                $link = route('esbtp.mes-absences.index');

                $this->createNotification($etudiant->user, $title, $message, 'success', $link, $approvedBy);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification d\'approbation', [
                'absence_id' => $absence->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyAbsenceJustificationRejected(ESBTPAttendance $absence, ESBTPEtudiant $etudiant, ?string $reason = null, ?User $rejectedBy = null): void
    {
        try {
            if ($etudiant->user) {
                $title = "Justification d'absence rejetée";
                $message = "Votre justification d'absence pour le cours du " . $absence->date->format('d/m/Y') . " a été rejetée.";
                if ($reason) {
                    $message .= " Motif: " . $reason;
                }
                $link = route('esbtp.mes-absences.index');

                $this->createNotification($etudiant->user, $title, $message, 'danger', $link, $rejectedBy);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification de rejet', [
                'absence_id' => $absence->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyNewAbsence(ESBTPAttendance $absence, ESBTPEtudiant $etudiant): void
    {
        try {
            if ($etudiant->user) {
                // Déterminer le type d'activité et créer un message personnalisé
                $typeActivite = $absence->type_activite ?? 'cours';
                $dateFormatee = $absence->date->format('d/m/Y');
                $jourSemaine = $absence->date->locale('fr')->dayName;

                // Titre personnalisé selon le type d'activité
                if ($typeActivite === 'evaluation') {
                    $title = "Absence lors d'une évaluation";
                } else {
                    $title = "Nouvelle absence enregistrée";
                }

                // Message de base
                $message = "Une absence a été enregistrée le {$dateFormatee} ({$jourSemaine})";

                // Ajouter les informations supplémentaires si disponibles
                if ($absence->commentaire && strpos($absence->commentaire, 'Absence lors d\'une') === 0) {
                    // Si le commentaire contient déjà les détails enrichis, l'utiliser
                    $message = $absence->commentaire;
                } else {
                    // Sinon, construire le message avec les informations disponibles
                    if ($absence->heure_debut) {
                        $message .= " à {$absence->heure_debut}";
                    }

                    // Ajouter la matière si disponible
                    if ($absence->matiere_id) {
                        try {
                            $matiere = \App\Models\ESBTPMatiere::find($absence->matiere_id);
                            if ($matiere) {
                                $message .= "\nMatière: {$matiere->name}";
                            }
                        } catch (\Exception $e) {
                            // Ignorer l'erreur si la matière n'est pas trouvée
                        }
                    }

                    if ($absence->commentaire) {
                        $message .= "\n" . $absence->commentaire;
                    }
                }

                $message .= "\n\nVous pouvez justifier cette absence si nécessaire.";

                $link = route('esbtp.mes-absences.index');

                $this->createNotification($etudiant->user, $title, $message, 'warning', $link);

                Log::info('Notification d\'absence enrichie envoyée', [
                    'absence_id' => $absence->id ?? 'temp',
                    'etudiant_id' => $etudiant->id,
                    'type_activite' => $typeActivite,
                    'date' => $dateFormatee,
                    'matiere_id' => $absence->matiere_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification d\'absence', [
                'absence_id' => $absence->id ?? 'temp',
                'etudiant_id' => $etudiant->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notifications pour les annonces
     */
    public function notifyNewAnnouncement(ESBTPAnnonce $annonce): void
    {
        try {
            $etudiants = collect();

            // Déterminer les destinataires selon le type d'annonce
            if ($annonce->type == 'general') {
                $etudiants = ESBTPEtudiant::whereHas('user')->get();
            } elseif ($annonce->type == 'classe') {
                $etudiants = ESBTPEtudiant::whereHas('user')
                    ->whereHas('classe_active', function($query) use ($annonce) {
                        $query->whereIn('id', $annonce->classes->pluck('id'));
                    })
                    ->get();
            } elseif ($annonce->type == 'etudiant') {
                $etudiants = $annonce->etudiants()->whereHas('user')->get();
            }

            // Déterminer le type de notification selon la priorité
            $notificationType = 'info';
            if ($annonce->priorite == 1) {
                $notificationType = 'warning';
            } elseif ($annonce->priorite == 2) {
                $notificationType = 'danger';
            }

            $title = "Nouvelle annonce: " . $annonce->titre;
            $message = substr($annonce->contenu, 0, 150) . (strlen($annonce->contenu) > 150 ? '...' : '');
            $link = route('esbtp.mes-messages.index');

            $notifiedCount = 0;
            foreach ($etudiants as $etudiant) {
                if ($etudiant->user) {
                    $this->createNotification($etudiant->user, $title, $message, $notificationType, $link);
                    $notifiedCount++;
                }
            }

            Log::info('Notifications envoyées pour nouvelle annonce', [
                'annonce_id' => $annonce->id,
                'titre' => $annonce->titre,
                'type' => $annonce->type,
                'etudiants_notifies' => $notifiedCount,
                'total_etudiants' => $etudiants->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications d\'annonce', [
                'annonce_id' => $annonce->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notifications pour les enseignants
     */
    public function notifyTeacherAttendanceCodeGenerated(User $teacher, string $code, string $className, Carbon $expiresAt): void
    {
        try {
            $title = "Code d'émargement généré";
            $message = "Un code d'émargement ({$code}) a été généré pour la classe {$className}. Expire le " . $expiresAt->format('d/m/Y à H:i');
            $link = route('esbtp.teacher-attendance.index');

            $this->createNotification($teacher, $title, $message, 'info', $link);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification de code d\'émargement', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyAdminsTeacherAttendanceSigned(User $teacher, string $className): void
    {
        try {
            $admins = User::role(['superAdmin', 'secretaire'])->get();

            $title = "Émargement enseignant effectué";
            $message = "L'enseignant {$teacher->name} a effectué son émargement pour la classe {$className}";
            $link = route('esbtp.admin.attendance.index');

            foreach ($admins as $admin) {
                $this->createNotification($admin, $title, $message, 'success', $link);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification d\'émargement enseignant', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notifications système
     */
    public function notifySystemMaintenance(string $message, Carbon $scheduledAt): void
    {
        try {
            $users = User::all();
            $title = "Maintenance système programmée";
            $fullMessage = $message . " Programmée le " . $scheduledAt->format('d/m/Y à H:i');

            foreach ($users as $user) {
                $this->createNotification($user, $title, $fullMessage, 'warning');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification de maintenance', [
                'error' => $e->getMessage()
            ]);
        }
    }

    public function notifyWelcomeNewUser(User $user, string $role): void
    {
        try {
            $title = "Bienvenue dans ESBTP-yAKRO";
            $message = "Votre compte a été créé avec succès. Vous avez le rôle de {$role}. Explorez toutes les fonctionnalités disponibles.";
            $link = route('dashboard');

            $this->createNotification($user, $title, $message, 'success', $link);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification de bienvenue', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Nettoyer les anciennes notifications
     */
    public function cleanupOldNotifications(): int
    {
        try {
            // Supprimer les notifications lues de plus de 30 jours
            $deletedCount = Notification::where('is_read', true)
                ->where('read_at', '<=', Carbon::now()->subDays(30))
                ->delete();

            // Supprimer les notifications non lues de plus de 90 jours
            $deletedCount += Notification::where('is_read', false)
                ->where('created_at', '<=', Carbon::now()->subDays(90))
                ->delete();

            Log::info('Nettoyage des notifications terminé', [
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage des notifications', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsReadForUser(User $user): int
    {
        try {
            $count = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Erreur lors du marquage des notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Obtenir les statistiques de notifications
     */
    public function getNotificationStats(): array
    {
        try {
            return [
                'total' => Notification::count(),
                'unread' => Notification::where('is_read', false)->count(),
                'today' => Notification::whereDate('created_at', today())->count(),
                'this_week' => Notification::whereBetween('created_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])->count(),
                'by_type' => Notification::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray()
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des statistiques', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}

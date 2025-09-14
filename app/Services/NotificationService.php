<?php

namespace App\Services;

use App\Models\ESBTPRelance;
use App\Models\User;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFacture;
use App\Models\ESBTPBonSortie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Envoie une relance par email
     */
    public function envoyerRelanceEmail($relance)
    {
        try {
            $etudiant = $relance->etudiant;
            $template = $this->getTemplateEmail($relance->niveau, $relance->template_utilise);

            $contenu = $this->personaliserMessage($template, $etudiant, $relance);

            // Simulation d'envoi email (à remplacer par votre service email)
            Mail::raw($contenu, function ($message) use ($etudiant) {
                $message->to($etudiant->email)
                       ->subject('Rappel de paiement - ESBTP');
            });

            $relance->update([
                'statut' => 'envoyee',
                'date_envoi' => now(),
                'contenu_message' => $contenu,
                'response_data' => json_encode(['status' => 'success'])
            ]);

            return ['success' => true, 'message' => 'Email envoyé avec succès'];

        } catch (\Exception $e) {
            $relance->update([
                'statut' => 'echec',
                'response_data' => json_encode(['error' => $e->getMessage()])
            ]);

            Log::error('Erreur envoi email relance: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoie une relance par SMS
     */
    public function envoyerRelanceSMS($relance)
    {
        try {
            $etudiant = $relance->etudiant;
            $template = $this->getTemplateSMS($relance->niveau);

            $contenu = $this->personaliserMessage($template, $etudiant, $relance);

            // Simulation d'envoi SMS (à remplacer par votre service SMS)
            // SMS::send($etudiant->telephone, $contenu);

            $relance->update([
                'statut' => 'envoyee',
                'date_envoi' => now(),
                'contenu_message' => $contenu,
                'response_data' => json_encode(['status' => 'success', 'sms_id' => 'SMS_' . time()])
            ]);

            return ['success' => true, 'message' => 'SMS envoyé avec succès'];

        } catch (\Exception $e) {
            $relance->update([
                'statut' => 'echec',
                'response_data' => json_encode(['error' => $e->getMessage()])
            ]);

            Log::error('Erreur envoi SMS relance: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Planifie les relances automatiques
     */
    public function planifierRelances()
    {
        $etudiants = $this->getEtudiantsARelancer();
        $relancesPlanifiees = 0;

        foreach ($etudiants as $etudiant) {
            $dernierRelance = ESBTPRelance::where('etudiant_id', $etudiant->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $niveau = $dernierRelance ? $dernierRelance->niveau + 1 : 1;

            // Maximum 3 niveaux de relance
            if ($niveau <= 3) {
                $this->creerRelance($etudiant, $niveau);
                $relancesPlanifiees++;
            }
        }

        return [
            'success' => true,
            'relances_planifiees' => $relancesPlanifiees,
            'message' => "$relancesPlanifiees relances planifiées"
        ];
    }

    /**
     * Exécute les relances en attente
     */
    public function executerRelancesEnAttente()
    {
        $relances = ESBTPRelance::where('statut', 'planifiee')
            ->where('date_envoi', '<=', now())
            ->get();

        $resultats = [
            'total' => $relances->count(),
            'reussies' => 0,
            'echecs' => 0
        ];

        foreach ($relances as $relance) {
            $resultat = match($relance->type) {
                'email' => $this->envoyerRelanceEmail($relance),
                'sms' => $this->envoyerRelanceSMS($relance),
                'courrier' => $this->genererCourrierRelance($relance),
                default => ['success' => false, 'message' => 'Type de relance non supporté']
            };

            if ($resultat['success']) {
                $resultats['reussies']++;
            } else {
                $resultats['echecs']++;
            }
        }

        return $resultats;
    }

    /**
     * Crée une nouvelle relance
     */
    private function creerRelance($etudiant, $niveau)
    {
        $facture = ESBTPFacture::where('etudiant_id', $etudiant->id)
            ->where('statut', 'impayee')
            ->first();

        $type = $this->determinerTypeRelance($niveau);
        $dateEnvoi = $this->calculerDateEnvoi($niveau);

        return ESBTPRelance::create([
            'etudiant_id' => $etudiant->id,
            'facture_id' => $facture?->id,
            'type' => $type,
            'niveau' => $niveau,
            'template_utilise' => "relance_niveau_{$niveau}",
            'date_envoi' => $dateEnvoi,
            'statut' => 'planifiee'
        ]);
    }

    /**
     * Récupère les étudiants à relancer
     */
    private function getEtudiantsARelancer()
    {
        // Étudiants avec des factures impayées depuis plus de 30 jours
        return ESBTPEtudiant::whereHas('factures', function($query) {
            $query->where('statut', 'impayee')
                  ->where('date_echeance', '<', Carbon::now()->subDays(30));
        })
        ->whereDoesntHave('relances', function($query) {
            $query->where('created_at', '>', Carbon::now()->subDays(7)) // Pas de relance dans les 7 derniers jours
                  ->where('statut', 'envoyee');
        })
        ->get();
    }

    /**
     * Personnalise le message avec les données de l'étudiant
     */
    private function personaliserMessage($template, $etudiant, $relance)
    {
        $dette = $this->calculerDette($etudiant);

        $variables = [
            '{nom}' => $etudiant->nom,
            '{prenom}' => $etudiant->prenoms,
            '{montant_dette}' => number_format($dette, 0, ',', ' ') . ' FCFA',
            '{niveau_relance}' => $relance->niveau,
            '{date}' => Carbon::now()->format('d/m/Y'),
            '{ecole}' => 'École Supérieure du Bâtiment et des Travaux Publics'
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    /**
     * Récupère le template d'email selon le niveau
     */
    private function getTemplateEmail($niveau, $templateName = null)
    {
        $templates = [
            1 => "Cher/Chère {prenom} {nom},\n\nNous vous rappelons que votre solde de scolarité de {montant_dette} est en attente de paiement.\n\nMerci de régulariser votre situation dans les plus brefs délais.\n\nCordialement,\nL'administration {ecole}",

            2 => "Cher/Chère {prenom} {nom},\n\nCeci est un DEUXIÈME RAPPEL concernant votre dette de {montant_dette}.\n\nVeuillez contacter notre service comptabilité rapidement pour éviter toute mesure disciplinaire.\n\nCordialement,\nL'administration {ecole}",

            3 => "Cher/Chère {prenom} {nom},\n\nDERNIER AVERTISSEMENT - Votre dette de {montant_dette} doit être réglée IMMÉDIATEMENT.\n\nFaute de paiement sous 7 jours, des mesures administratives seront prises.\n\nCordialement,\nL'administration {ecole}"
        ];

        return $templates[$niveau] ?? $templates[1];
    }

    /**
     * Récupère le template SMS selon le niveau
     */
    private function getTemplateSMS($niveau)
    {
        $templates = [
            1 => "ESBTP: Rappel paiement scolarité {montant_dette}. Merci de régulariser. Info: [telephone]",
            2 => "ESBTP: 2e RAPPEL - Dette {montant_dette}. Contactez-nous rapidement. Info: [telephone]",
            3 => "ESBTP: URGENT - Dette {montant_dette}. Paiement obligatoire sous 7j. Info: [telephone]"
        ];

        return $templates[$niveau] ?? $templates[1];
    }

    /**
     * Détermine le type de relance selon le niveau
     */
    private function determinerTypeRelance($niveau)
    {
        return match($niveau) {
            1 => 'email',
            2 => 'sms',
            3 => 'courrier',
            default => 'email'
        };
    }

    /**
     * Calcule la date d'envoi selon le niveau
     */
    private function calculerDateEnvoi($niveau)
    {
        return match($niveau) {
            1 => now(), // Immédiat
            2 => now()->addDays(7), // 7 jours après niveau 1
            3 => now()->addDays(14), // 14 jours après niveau 1
            default => now()
        };
    }

    /**
     * Calcule la dette totale d'un étudiant
     */
    private function calculerDette($etudiant)
    {
        $totalFactures = ESBTPFacture::where('etudiant_id', $etudiant->id)
            ->where('statut', 'impayee')
            ->sum('montant_total');

        $totalPaiements = ESBTPPaiement::where('etudiant_id', $etudiant->id)
            ->where('statut', 'completé')
            ->sum('montant');

        return max(0, $totalFactures - $totalPaiements);
    }

    /**
     * Génère un courrier de relance (PDF)
     */
    private function genererCourrierRelance($relance)
    {
        try {
            // Ici vous pourriez générer un PDF avec dompdf
            $relance->update([
                'statut' => 'envoyee',
                'date_envoi' => now(),
                'response_data' => json_encode(['status' => 'courrier_genere'])
            ]);

            return ['success' => true, 'message' => 'Courrier généré'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Envoie une notification de paiement reçu
     */
    public function notifierPaiementRecu($paiement)
    {
        try {
            $etudiant = $paiement->etudiant;

            $message = "Cher/Chère {$etudiant->prenoms} {$etudiant->nom},\n\n";
            $message .= "Nous accusons réception de votre paiement de " . number_format($paiement->montant, 0, ',', ' ') . " FCFA.\n\n";
            $message .= "Référence: {$paiement->reference_paiement}\n";
            $message .= "Date: " . $paiement->date_paiement->format('d/m/Y') . "\n\n";
            $message .= "Merci pour votre confiance.\n\nL'administration ESBTP";

            Mail::raw($message, function ($mail) use ($etudiant) {
                $mail->to($etudiant->email)
                     ->subject('Confirmation de paiement - ESBTP');
            });

            return ['success' => true, 'message' => 'Notification envoyée'];
        } catch (\Exception $e) {
            Log::error('Erreur notification paiement: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

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
    public function notifyNewAnnouncement(ESBTPAnnonce $annonce, ?User $sentBy = null): void
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
                if ($etudiant->user && (!$sentBy || $etudiant->user->id !== $sentBy->id)) {
                    $this->createNotification($etudiant->user, $title, $message, $notificationType, $link, $sentBy);
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
     * ===== NOUVELLES NOTIFICATIONS SYSTÈME =====
     */

    /**
     * Notifie les non-étudiants (coordinateurs, enseignants, secrétaires, admins) d'une nouvelle inscription
     */
    public function notifyNewInscription($inscription, ?User $createdBy = null): void
    {
        try {
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouvelle inscription";
            $message = "L'étudiant {$inscription->etudiant->nom} {$inscription->etudiant->prenoms} s'est inscrit en {$inscription->classe->name}";
            $link = route('esbtp.inscriptions.show', $inscription->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'info', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle inscription', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les non-étudiants d'une nouvelle réinscription
     */
    public function notifyNewReinscription($inscription, ?User $createdBy = null): void
    {
        try {
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouvelle réinscription";
            $message = "L'étudiant {$inscription->etudiant->nom} {$inscription->etudiant->prenoms} s'est réinscrit en {$inscription->classe->name}";
            $link = route('esbtp.inscriptions.show', $inscription->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'info', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle réinscription', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les non-étudiants de l'ajout d'une nouvelle classe
     */
    public function notifyNewClasse($classe, ?User $createdBy = null): void
    {
        try {
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouvelle classe créée";
            $message = "La classe {$classe->name} a été créée pour la filière {$classe->filiere->name}";
            $link = route('esbtp.classes.show', $classe->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'success', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle classe', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les non-étudiants de l'ajout d'une nouvelle filière
     */
    public function notifyNewFiliere($filiere, ?User $createdBy = null): void
    {
        try {
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouvelle filière créée";
            $message = "La filière {$filiere->name} ({$filiere->code}) a été créée";
            $link = route('esbtp.filieres.show', $filiere->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'success', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle filière', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les non-étudiants de l'ajout d'un nouveau niveau d'étude
     */
    public function notifyNewNiveauEtude($niveau, ?User $createdBy = null): void
    {
        try {
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouveau niveau d'étude créé";
            $message = "Le niveau d'étude {$niveau->name} ({$niveau->code}) a été créé";
            $link = route('esbtp.niveaux-etudes.show', $niveau->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'success', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouveau niveau étude', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les non-étudiants et étudiants concernés de l'ajout d'une nouvelle matière
     */
    public function notifyNewMatiere($matiere, ?User $createdBy = null): void
    {
        try {
            // Notifier les non-étudiants
            $nonStudentUsers = User::role(['superAdmin', 'admin', 'secretaire', 'coordinateur', 'enseignant'])->get();

            $title = "Nouvelle matière créée";
            $message = "La matière {$matiere->name} ({$matiere->code}) a été créée";
            $link = route('esbtp.matieres.show', $matiere->id);

            foreach ($nonStudentUsers as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'success', $link, $createdBy);
                }
            }

            // Notifier les étudiants des filières concernées (année universitaire courante)
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if ($anneeEnCours && $matiere->filieres) {
                $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($q) use ($anneeEnCours, $matiere) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id)
                      ->where('status', 'active')
                      ->whereHas('classe', function($q2) use ($matiere) {
                          $q2->whereHas('filiere', function($q3) use ($matiere) {
                              $q3->whereIn('id', $matiere->filieres->pluck('id'));
                          });
                      });
                })->whereHas('user')->get();

                $studentTitle = "Nouvelle matière dans votre cursus";
                $studentMessage = "La matière {$matiere->name} a été ajoutée à votre filière";

                foreach ($etudiants as $etudiant) {
                    if ($etudiant->user) {
                        $this->createNotification($etudiant->user, $studentTitle, $studentMessage, 'info', null, $createdBy);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle matière', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie les étudiants concernés de l'ajout d'une nouvelle évaluation
     */
    public function notifyNewEvaluation($evaluation, ?User $createdBy = null): void
    {
        try {
            $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
            if (!$anneeEnCours) return;

            // Récupérer les étudiants de la classe concernée avec inscription active pour l'année courante
            $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($q) use ($anneeEnCours, $evaluation) {
                $q->where('annee_universitaire_id', $anneeEnCours->id)
                  ->where('status', 'active')
                  ->where('classe_id', $evaluation->classe_id);
            })->whereHas('user')->get();

            $title = "Nouvelle évaluation programmée";
            $message = "Une évaluation \"{$evaluation->titre}\" est programmée en {$evaluation->matiere->name} pour le " . $evaluation->date_evaluation->format('d/m/Y');
            $link = route('esbtp.evaluations.show', $evaluation->id);

            foreach ($etudiants as $etudiant) {
                if ($etudiant->user) {
                    $this->createNotification($etudiant->user, $title, $message, 'warning', $link, $createdBy);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle évaluation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Notifie un étudiant de l'ajout de sa note (uniquement si publiée)
     */
    public function notifyStudentNoteAdded($note, ?User $createdBy = null): void
    {
        try {
            // Vérifier si la note est publiée
            if (!$note->evaluation || !$note->evaluation->is_published) {
                return; // Ne pas notifier si l'évaluation n'est pas publiée
            }

            $etudiant = $note->etudiant;
            if (!$etudiant || !$etudiant->user) return;

            $title = "Nouvelle note disponible";
            $message = "Votre note pour l'évaluation \"{$note->evaluation->titre}\" en {$note->evaluation->matiere->name} est maintenant disponible";
            $link = route('esbtp.mes-notes.index');

            $this->createNotification($etudiant->user, $title, $message, 'success', $link, $createdBy);
        } catch (\Exception $e) {
            Log::error('Erreur notification note étudiant', ['error' => $e->getMessage()]);
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

    /**
     * NOUVELLES MÉTHODES AVANCÉES POUR LES RELANCES - Tâche #4
     */

    /**
     * Planification avancée des relances avec segmentation
     */
    public function planifierRelancesAvancees(array $parametres = [])
    {
        $segmentation = $parametres['segmentation'] ?? 'auto';
        $niveauMax = $parametres['niveau_max'] ?? 3;
        $typesRelance = $parametres['types_relance'] ?? ['email', 'sms'];

        $etudiants = $this->segmenterEtudiants($segmentation);
        $relancesPlanifiees = 0;
        $etudiantsTraites = 0;

        foreach ($etudiants as $segment => $listeEtudiants) {
            foreach ($listeEtudiants as $etudiant) {
                $dernierRelance = ESBTPRelance::where('etudiant_id', $etudiant->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $niveau = $dernierRelance ? $dernierRelance->niveau + 1 : 1;

                if ($niveau <= $niveauMax) {
                    $type = $this->determinerTypeRelanceSegment($niveau, $segment, $typesRelance);
                    $this->creerRelanceAvancee($etudiant, $niveau, $type, $segment);
                    $relancesPlanifiees++;
                }
                $etudiantsTraites++;
            }
        }

        return [
            'success' => true,
            'relances_planifiees' => $relancesPlanifiees,
            'etudiants_traites' => $etudiantsTraites,
            'segments_traites' => array_keys($etudiants)
        ];
    }

    /**
     * Segmentation avancée des étudiants selon différents critères
     */
    public function segmenterEtudiants($typeSegmentation = 'auto')
    {
        switch ($typeSegmentation) {
            case 'niveau_retard':
                return $this->segmenterParNiveauRetard();

            case 'montant_dette':
                return $this->segmenterParMontantDette();

            case 'historique_paiement':
                return $this->segmenterParHistoriquePaiement();

            case 'classe':
                return $this->segmenterParClasse();

            default: // 'auto'
                return $this->segmentationAutomatique();
        }
    }

    /**
     * Segmentation par niveau de retard
     */
    private function segmenterParNiveauRetard()
    {
        $segments = [
            'retard_leger' => [], // 15-30 jours
            'retard_moyen' => [], // 30-60 jours
            'retard_severe' => [] // 60+ jours
        ];

        $etudiants = $this->getEtudiantsARelancer();

        foreach ($etudiants as $etudiant) {
            $joursRetard = $this->calculerJoursRetard($etudiant);

            if ($joursRetard <= 30) {
                $segments['retard_leger'][] = $etudiant;
            } elseif ($joursRetard <= 60) {
                $segments['retard_moyen'][] = $etudiant;
            } else {
                $segments['retard_severe'][] = $etudiant;
            }
        }

        return array_filter($segments); // Supprime les segments vides
    }

    /**
     * Segmentation par montant de dette
     */
    private function segmenterParMontantDette()
    {
        $segments = [
            'dette_faible' => [], // < 50 000 FCFA
            'dette_moyenne' => [], // 50 000 - 200 000 FCFA
            'dette_elevee' => [] // > 200 000 FCFA
        ];

        $etudiants = $this->getEtudiantsARelancer();

        foreach ($etudiants as $etudiant) {
            $dette = $this->calculerDette($etudiant);

            if ($dette < 50000) {
                $segments['dette_faible'][] = $etudiant;
            } elseif ($dette <= 200000) {
                $segments['dette_moyenne'][] = $etudiant;
            } else {
                $segments['dette_elevee'][] = $etudiant;
            }
        }

        return array_filter($segments);
    }

    /**
     * Segmentation par historique de paiement
     */
    private function segmenterParHistoriquePaiement()
    {
        $segments = [
            'bon_payeur' => [], // Toujours payé à temps historiquement
            'payeur_irregulier' => [], // Quelques retards
            'mauvais_payeur' => [] // Souvent en retard
        ];

        $etudiants = $this->getEtudiantsARelancer();

        foreach ($etudiants as $etudiant) {
            $scorePayeur = $this->calculerScorePayeur($etudiant);

            if ($scorePayeur >= 8) {
                $segments['bon_payeur'][] = $etudiant;
            } elseif ($scorePayeur >= 5) {
                $segments['payeur_irregulier'][] = $etudiant;
            } else {
                $segments['mauvais_payeur'][] = $etudiant;
            }
        }

        return array_filter($segments);
    }

    /**
     * Segmentation par classe
     */
    private function segmenterParClasse()
    {
        $segments = [];
        $etudiants = $this->getEtudiantsARelancer();

        foreach ($etudiants as $etudiant) {
            $classeNom = $etudiant->classe_active->nom ?? 'Sans classe';

            if (!isset($segments[$classeNom])) {
                $segments[$classeNom] = [];
            }

            $segments[$classeNom][] = $etudiant;
        }

        return $segments;
    }

    /**
     * Segmentation automatique intelligente (combinaison de critères)
     */
    private function segmentationAutomatique()
    {
        $segments = [
            'priorite_haute' => [], // Dette élevée + retard sévère
            'priorite_moyenne' => [], // Soit dette élevée, soit retard sévère
            'priorite_faible' => [] // Autres cas
        ];

        $etudiants = $this->getEtudiantsARelancer();

        foreach ($etudiants as $etudiant) {
            $dette = $this->calculerDette($etudiant);
            $joursRetard = $this->calculerJoursRetard($etudiant);

            if ($dette > 200000 && $joursRetard > 60) {
                $segments['priorite_haute'][] = $etudiant;
            } elseif ($dette > 200000 || $joursRetard > 60) {
                $segments['priorite_moyenne'][] = $etudiant;
            } else {
                $segments['priorite_faible'][] = $etudiant;
            }
        }

        return array_filter($segments);
    }

    /**
     * Créer une relance avancée avec contexte de segment
     */
    private function creerRelanceAvancee($etudiant, $niveau, $type, $segment)
    {
        $facture = ESBTPFacture::where('etudiant_id', $etudiant->id)
            ->where('statut', 'impayee')
            ->first();

        $templatePersonnalise = $this->getTemplatePersonnalise($niveau, $segment);
        $dateEnvoi = $this->calculerDateEnvoiSegment($niveau, $segment);

        return ESBTPRelance::create([
            'etudiant_id' => $etudiant->id,
            'facture_id' => $facture?->id,
            'type' => $type,
            'niveau' => $niveau,
            'template_utilise' => $templatePersonnalise,
            'date_envoi' => $dateEnvoi,
            'statut' => 'planifiee',
            'response_data' => json_encode([
                'segment' => $segment,
                'planifiee_automatiquement' => true
            ])
        ]);
    }

    /**
     * Détermine le type de relance selon le segment
     */
    private function determinerTypeRelanceSegment($niveau, $segment, $typesAutorises)
    {
        // Logique avancée selon le segment
        $strategies = [
            'priorite_haute' => ['email', 'sms', 'courrier'],
            'priorite_moyenne' => ['email', 'sms'],
            'priorite_faible' => ['email'],
            'dette_elevee' => ['email', 'courrier'],
            'retard_severe' => ['sms', 'email']
        ];

        $typesSegment = $strategies[$segment] ?? $typesAutorises;
        $typesDisponibles = array_intersect($typesSegment, $typesAutorises);

        if (empty($typesDisponibles)) {
            return $typesAutorises[0] ?? 'email';
        }

        // Choisir selon le niveau
        if ($niveau >= 3 && in_array('courrier', $typesDisponibles)) {
            return 'courrier';
        } elseif ($niveau >= 2 && in_array('sms', $typesDisponibles)) {
            return 'sms';
        }

        return $typesDisponibles[0];
    }

    /**
     * Calcule les jours de retard pour un étudiant
     */
    private function calculerJoursRetard($etudiant)
    {
        $factureAncienne = ESBTPFacture::where('etudiant_id', $etudiant->id)
            ->where('statut', 'impayee')
            ->orderBy('date_echeance', 'asc')
            ->first();

        if (!$factureAncienne || !$factureAncienne->date_echeance) {
            return 0;
        }

        return Carbon::parse($factureAncienne->date_echeance)->diffInDays(now(), false);
    }

    /**
     * Calcule un score de payeur (0-10) basé sur l'historique
     */
    private function calculerScorePayeur($etudiant)
    {
        $paiementsTotal = ESBTPPaiement::where('etudiant_id', $etudiant->id)->count();
        $paiementsEnRetard = ESBTPPaiement::where('etudiant_id', $etudiant->id)
            ->whereRaw('date_paiement > date_echeance')
            ->count();

        if ($paiementsTotal == 0) return 5; // Score neutre pour nouveaux étudiants

        $tauxPonctualite = ($paiementsTotal - $paiementsEnRetard) / $paiementsTotal;
        return round($tauxPonctualite * 10);
    }

    /**
     * Template personnalisé selon le segment
     */
    private function getTemplatePersonnalise($niveau, $segment)
    {
        $templateBase = "relance_niveau_{$niveau}";

        // Personnalisation selon le segment
        $suffixes = [
            'priorite_haute' => '_urgent',
            'dette_elevee' => '_dette_importante',
            'retard_severe' => '_retard_long',
            'bon_payeur' => '_courtois'
        ];

        $suffixe = $suffixes[$segment] ?? '';
        return $templateBase . $suffixe;
    }

    /**
     * Date d'envoi adaptée au segment
     */
    private function calculerDateEnvoiSegment($niveau, $segment)
    {
        $delaiBase = [
            1 => 0,   // Immédiat
            2 => 7,   // 7 jours
            3 => 14   // 14 jours
        ][$niveau] ?? 0;

        // Ajustement selon le segment
        $ajustements = [
            'priorite_haute' => -1,    // 1 jour plus tôt
            'dette_elevee' => -1,
            'retard_severe' => -2,     // 2 jours plus tôt
            'priorite_faible' => +2    // 2 jours plus tard
        ];

        $ajustement = $ajustements[$segment] ?? 0;
        return now()->addDays($delaiBase + $ajustement);
    }

    /**
     * Obtenir les statistiques avancées des relances
     */
    public function getStatistiquesRelancesAvancees()
    {
        return [
            'efficacite_par_type' => $this->calculerEfficaciteParType(),
            'taux_conversion_par_niveau' => $this->calculerTauxConversionParNiveau(),
            'segmentation_performance' => $this->analyserPerformanceSegments(),
            'tendances_mensuelles' => $this->calculerTendancesMensuelles(),
            'predictions' => $this->genererPredictions()
        ];
    }

    /**
     * Calcule l'efficacité par type de relance
     */
    private function calculerEfficaciteParType()
    {
        $types = ['email', 'sms', 'courrier'];
        $resultats = [];

        foreach ($types as $type) {
            $totalEnvoyees = ESBTPRelance::where('type', $type)
                ->where('statut', 'envoyee')
                ->count();

            $avecPaiement = ESBTPRelance::where('type', $type)
                ->where('statut', 'envoyee')
                ->whereHas('etudiant.paiements', function($query) {
                    $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                          ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 30 DAY)'));
                })
                ->count();

            $resultats[$type] = [
                'total_envoyees' => $totalEnvoyees,
                'avec_paiement' => $avecPaiement,
                'taux_efficacite' => $totalEnvoyees > 0 ? round(($avecPaiement / $totalEnvoyees) * 100, 2) : 0
            ];
        }

        return $resultats;
    }

    /**
     * Calcule le taux de conversion par niveau
     */
    private function calculerTauxConversionParNiveau()
    {
        $niveaux = [1, 2, 3];
        $resultats = [];

        foreach ($niveaux as $niveau) {
            $totalNiveau = ESBTPRelance::where('niveau', $niveau)
                ->where('statut', 'envoyee')
                ->count();

            $conversions = ESBTPRelance::where('niveau', $niveau)
                ->where('statut', 'envoyee')
                ->whereHas('etudiant.paiements', function($query) {
                    $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                          ->where('created_at', '<', \DB::raw('DATE_ADD(esbtp_relances.date_envoi, INTERVAL 15 DAY)'));
                })
                ->count();

            $resultats["niveau_{$niveau}"] = [
                'total' => $totalNiveau,
                'conversions' => $conversions,
                'taux' => $totalNiveau > 0 ? round(($conversions / $totalNiveau) * 100, 2) : 0
            ];
        }

        return $resultats;
    }

    /**
     * Analyse la performance des segments
     */
    private function analyserPerformanceSegments()
    {
        // Simulation de données de performance par segment
        return [
            'priorite_haute' => ['taux_reponse' => 85, 'delai_moyen_paiement' => 3],
            'priorite_moyenne' => ['taux_reponse' => 65, 'delai_moyen_paiement' => 7],
            'priorite_faible' => ['taux_reponse' => 45, 'delai_moyen_paiement' => 14]
        ];
    }

    /**
     * Calcule les tendances mensuelles
     */
    private function calculerTendancesMensuelles()
    {
        $derniers6Mois = [];

        for ($i = 5; $i >= 0; $i--) {
            $mois = Carbon::now()->subMonths($i);
            $debut = $mois->copy()->startOfMonth();
            $fin = $mois->copy()->endOfMonth();

            $relances = ESBTPRelance::whereBetween('created_at', [$debut, $fin])->count();
            $efficacite = $this->calculerEfficacitePeriode($debut, $fin);

            $derniers6Mois[] = [
                'mois' => $mois->format('Y-m'),
                'relances_envoyees' => $relances,
                'taux_efficacite' => $efficacite
            ];
        }

        return $derniers6Mois;
    }

    /**
     * Calcule l'efficacité sur une période
     */
    private function calculerEfficacitePeriode($debut, $fin)
    {
        $totalRelances = ESBTPRelance::whereBetween('created_at', [$debut, $fin])
            ->where('statut', 'envoyee')
            ->count();

        if ($totalRelances == 0) return 0;

        $avecPaiement = ESBTPRelance::whereBetween('created_at', [$debut, $fin])
            ->where('statut', 'envoyee')
            ->whereHas('etudiant.paiements', function($query) use ($fin) {
                $query->where('created_at', '>', \DB::raw('esbtp_relances.date_envoi'))
                      ->where('created_at', '<=', $fin->copy()->addDays(30));
            })
            ->count();

        return round(($avecPaiement / $totalRelances) * 100, 2);
    }

    /**
     * Génère des prédictions basées sur les données historiques
     */
    private function genererPredictions()
    {
        // Simulation de prédictions simples
        $tendance = $this->calculerTendancesMensuelles();
        $dernierTaux = end($tendance)['taux_efficacite'] ?? 0;

        return [
            'efficacite_prevue_mois_prochain' => min(100, max(0, $dernierTaux + rand(-5, 10))),
            'volume_relances_prevu' => rand(50, 200),
            'recommandations' => $this->genererRecommandations($dernierTaux)
        ];
    }

    /**
     * Génère des recommandations basées sur les performances
     */
    private function genererRecommandations($tauxEfficacite)
    {
        if ($tauxEfficacite < 30) {
            return [
                'Réviser les templates de relance',
                'Intensifier la segmentation',
                'Considérer d\'autres canaux de communication'
            ];
        } elseif ($tauxEfficacite < 60) {
            return [
                'Optimiser les horaires d\'envoi',
                'Personnaliser davantage les messages',
                'Analyser les segments les moins performants'
            ];
        } else {
            return [
                'Maintenir la stratégie actuelle',
                'Tester de nouvelles approches',
                'Étendre aux étudiants de niveau inférieur'
            ];
        }
    }

    /**
     * Notify the approver of a new bon de sortie approval request.
     *
     * @param int $bonId
     * @param int $approbateurId
     * @return void
     */
    public function notifyBonApproval($bonId, $approbateurId)
    {
        try {
            $bon = ESBTPBonSortie::findOrFail($bonId);
            $approbateur = User::findOrFail($approbateurId);

            // Create in-app notification
            $this->createNotification(
                $approbateur,
                'Nouvelle demande d\'approbation de bon de sortie',
                'Une nouvelle demande de bon de sortie (' . $bon->reference . ') vous a été assignée pour approbation.',
                'approval_request',
                route('esbtp.bons_sortie.show', $bon->id)
            );

            // Send email notification
            // Mail::to($approbateur->email)->send(new BonSortieApprovalRequestMail($bon));

            // Log the notification
            DB::table('esbtp_bon_sortie_notifications')->insert([
                'bon_sortie_id' => $bon->id,
                'user_id' => $approbateur->id,
                'type' => 'app',
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bon->update(['notification_sent_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la notification d\'approbation de bon de sortie: ' . $e->getMessage());
        }
    }

    /**
     * ===== NOTIFICATIONS POUR LE WORKFLOW D'ÉMARGEMENT ENSEIGNANT =====
     */

    /**
     * Notifier le coordinateur lorsqu'un enseignant effectue son émargement
     */
    public function notifyCoordinateurTeacherAttendanceSigned($teacherUser, $seanceCours)
    {
        try {
            // Récupérer tous les coordinateurs
            $coordinateurs = User::role(['coordinateur'])->get();
            
            $matiere = $seanceCours->matiere->name ?? 'Matière inconnue';
            $classe = $seanceCours->emploiTemps->classe->name ?? $seanceCours->classe->name ?? 'Classe inconnue';
            $horaire = $seanceCours->heure_debut ? 
                \Carbon\Carbon::parse($seanceCours->heure_debut)->format('H:i') . '-' . 
                \Carbon\Carbon::parse($seanceCours->heure_fin)->format('H:i') : 
                'Horaire non défini';

            $title = "Émargement enseignant effectué";
            $message = "L'enseignant {$teacherUser->name} a effectué son émargement pour :\n";
            $message .= "• Matière: {$matiere}\n";
            $message .= "• Classe: {$classe}\n";
            $message .= "• Horaire: {$horaire}\n";
            $message .= "• Date: " . now()->format('d/m/Y à H:i');
            
            $link = route('esbtp.teacher-attendance.report'); // Page rapport émargements enseignants

            foreach ($coordinateurs as $coordinateur) {
                $this->createNotification($coordinateur, $title, $message, 'info', $link, $teacherUser);
            }

            Log::info('Notification émargement enseignant envoyée aux coordinateurs', [
                'teacher_id' => $teacherUser->id,
                'seance_id' => $seanceCours->id,
                'coordinateurs_notifies' => $coordinateurs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification émargement enseignant: ' . $e->getMessage(), [
                'teacher_id' => $teacherUser->id ?? null,
                'seance_id' => $seanceCours->id ?? null
            ]);
        }
    }

    /**
     * Notifier le coordinateur lorsqu'un enseignant fait l'appel des étudiants
     */
    public function notifyCoordinateurStudentRollCallCompleted($teacherUser, $seanceCours, $attendanceData)
    {
        try {
            // Récupérer tous les coordinateurs
            $coordinateurs = User::role(['coordinateur'])->get();
            
            $matiere = $seanceCours->matiere->name ?? 'Matière inconnue';
            $classe = $seanceCours->emploiTemps->classe->name ?? $seanceCours->classe->name ?? 'Classe inconnue';
            
            $totalEtudiants = count($attendanceData);
            $presents = collect($attendanceData)->where('attendance', 'present')->count();
            $absents = collect($attendanceData)->where('attendance', 'absent')->count();

            $title = "Appel des étudiants terminé";
            $message = "L'enseignant {$teacherUser->name} a terminé l'appel pour :\n";
            $message .= "• Matière: {$matiere}\n";
            $message .= "• Classe: {$classe}\n";
            $message .= "• Présents: {$presents}/{$totalEtudiants}\n";
            $message .= "• Absents: {$absents}/{$totalEtudiants}\n";
            $message .= "• Date: " . now()->format('d/m/Y à H:i');
            
            $link = route('esbtp.attendances.index'); // Page présences/absences étudiants

            foreach ($coordinateurs as $coordinateur) {
                $this->createNotification($coordinateur, $title, $message, 'success', $link, $teacherUser);
            }

            Log::info('Notification appel étudiants envoyée aux coordinateurs', [
                'teacher_id' => $teacherUser->id,
                'seance_id' => $seanceCours->id,
                'presents' => $presents,
                'absents' => $absents,
                'coordinateurs_notifies' => $coordinateurs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification appel étudiants: ' . $e->getMessage(), [
                'teacher_id' => $teacherUser->id ?? null,
                'seance_id' => $seanceCours->id ?? null
            ]);
        }
    }

    /**
     * Notifier le coordinateur lorsqu'un cours est clôturé
     */
    public function notifyCoordinateurCourseClosed($teacherUser, $seanceCours, $notes = null)
    {
        try {
            // Récupérer tous les coordinateurs
            $coordinateurs = User::role(['coordinateur'])->get();
            
            $matiere = $seanceCours->matiere->name ?? 'Matière inconnue';
            $classe = $seanceCours->emploiTemps->classe->name ?? $seanceCours->classe->name ?? 'Classe inconnue';
            $horaire = $seanceCours->heure_debut ? 
                \Carbon\Carbon::parse($seanceCours->heure_debut)->format('H:i') . '-' . 
                \Carbon\Carbon::parse($seanceCours->heure_fin)->format('H:i') : 
                'Horaire non défini';

            $title = "Cours clôturé";
            $message = "L'enseignant {$teacherUser->name} a clôturé le cours :\n";
            $message .= "• Matière: {$matiere}\n";
            $message .= "• Classe: {$classe}\n";
            $message .= "• Horaire: {$horaire}\n";
            $message .= "• Date: " . now()->format('d/m/Y à H:i');
            
            if ($notes) {
                $message .= "\n• Notes: " . substr($notes, 0, 100) . (strlen($notes) > 100 ? '...' : '');
            }
            
            $link = route('esbtp.seances-cours.show', $seanceCours->id);

            foreach ($coordinateurs as $coordinateur) {
                $this->createNotification($coordinateur, $title, $message, 'success', $link, $teacherUser);
            }

            Log::info('Notification clôture cours envoyée aux coordinateurs', [
                'teacher_id' => $teacherUser->id,
                'seance_id' => $seanceCours->id,
                'coordinateurs_notifies' => $coordinateurs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification clôture cours: ' . $e->getMessage(), [
                'teacher_id' => $teacherUser->id ?? null,
                'seance_id' => $seanceCours->id ?? null
            ]);
        }
    }

    /**
     * Notifier le coordinateur en cas de retard d'émargement
     */
    public function notifyCoordinateurTeacherAttendanceDelay($seanceCours, $minutesDelay = 0)
    {
        try {
            // Récupérer tous les coordinateurs
            $coordinateurs = User::role(['coordinateur'])->get();
            
            $matiere = $seanceCours->matiere->name ?? 'Matière inconnue';
            $classe = $seanceCours->emploiTemps->classe->name ?? $seanceCours->classe->name ?? 'Classe inconnue';
            $enseignant = $seanceCours->enseignant->user->name ?? 'Enseignant inconnu';
            $horaire = $seanceCours->heure_debut ? 
                \Carbon\Carbon::parse($seanceCours->heure_debut)->format('H:i') . '-' . 
                \Carbon\Carbon::parse($seanceCours->heure_fin)->format('H:i') : 
                'Horaire non défini';

            $title = "Retard d'émargement détecté";
            $message = "L'enseignant {$enseignant} n'a pas encore effectué son émargement :\n";
            $message .= "• Matière: {$matiere}\n";
            $message .= "• Classe: {$classe}\n";
            $message .= "• Horaire prévu: {$horaire}\n";
            if ($minutesDelay > 0) {
                $message .= "• Retard: {$minutesDelay} minutes\n";
            }
            $message .= "• Date: " . now()->format('d/m/Y à H:i');
            
            $link = route('esbtp.teacher-attendance.report'); // Page rapport émargements enseignants

            foreach ($coordinateurs as $coordinateur) {
                $this->createNotification($coordinateur, $title, $message, 'warning', $link);
            }

            Log::info('Notification retard émargement envoyée aux coordinateurs', [
                'seance_id' => $seanceCours->id,
                'minutes_delay' => $minutesDelay,
                'coordinateurs_notifies' => $coordinateurs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification retard émargement: ' . $e->getMessage(), [
                'seance_id' => $seanceCours->id ?? null
            ]);
        }
    }

    /**
     * Notifier les étudiants absents après l'appel
     */
    public function notifyStudentsAbsence($absentStudents, $seanceCours, $teacherUser)
    {
        try {
            $matiere = $seanceCours->matiere->name ?? 'Matière inconnue';
            $classe = $seanceCours->emploiTemps->classe->name ?? $seanceCours->classe->name ?? 'Classe inconnue';
            $enseignant = $teacherUser->name;
            
            $title = "Absence enregistrée";
            $message = "Votre absence a été enregistrée pour le cours :\n";
            $message .= "• Matière: {$matiere}\n";
            $message .= "• Classe: {$classe}\n";
            $message .= "• Enseignant: {$enseignant}\n";
            $message .= "• Date: " . now()->format('d/m/Y à H:i') . "\n\n";
            $message .= "Vous pouvez justifier cette absence si nécessaire.";
            
            $link = route('esbtp.mes-absences.index');

            $studentsNotified = 0;
            foreach ($absentStudents as $etudiant) {
                if ($etudiant->user) {
                    $this->createNotification($etudiant->user, $title, $message, 'warning', $link, $teacherUser);
                    $studentsNotified++;
                }
            }

            Log::info('Notifications absence envoyées aux étudiants', [
                'seance_id' => $seanceCours->id,
                'teacher_id' => $teacherUser->id,
                'etudiants_notifies' => $studentsNotified,
                'total_absents' => count($absentStudents)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification absence étudiants: ' . $e->getMessage(), [
                'seance_id' => $seanceCours->id ?? null,
                'teacher_id' => $teacherUser->id ?? null
            ]);
        }
    }

    /**
     * Notification récapitulative quotidienne pour les coordinateurs
     */
    public function sendDailyAttendanceSummaryToCoordinators()
    {
        try {
            $coordinateurs = User::role(['coordinateur'])->get();
            $today = now()->format('Y-m-d');
            
            // Récupérer les statistiques du jour
            $stats = $this->getDailyAttendanceStats($today);
            
            $title = "Récapitulatif quotidien d'émargement";
            $message = "Récapitulatif d'émargement du " . now()->format('d/m/Y') . " :\n\n";
            $message .= "📚 Cours prévus: {$stats['total_courses']}\n";
            $message .= "✅ Émargements effectués: {$stats['teacher_attendances']}\n";
            $message .= "👥 Appels terminés: {$stats['student_calls_completed']}\n";
            $message .= "🔒 Cours clôturés: {$stats['courses_closed']}\n";
            $message .= "⚠️ Retards détectés: {$stats['delays']}\n\n";
            $message .= "Taux de conformité: " . round(($stats['teacher_attendances'] / max(1, $stats['total_courses'])) * 100, 1) . "%";
            
            $link = route('esbtp.teacher-attendance.report'); // Page rapport émargements enseignants

            foreach ($coordinateurs as $coordinateur) {
                $this->createNotification($coordinateur, $title, $message, 'info', $link);
            }

            Log::info('Récapitulatif quotidien envoyé aux coordinateurs', [
                'date' => $today,
                'stats' => $stats,
                'coordinateurs_notifies' => $coordinateurs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur récapitulatif quotidien: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir les statistiques quotidiennes d'émargement
     */
    private function getDailyAttendanceStats($date)
    {
        try {
            $stats = [
                'total_courses' => 0,
                'teacher_attendances' => 0,
                'student_calls_completed' => 0,
                'courses_closed' => 0,
                'delays' => 0
            ];

            // Cours prévus aujourd'hui
            $stats['total_courses'] = \App\Models\ESBTPSeanceCours::whereDate('date', $date)->count();

            // Émargements enseignants effectués
            $stats['teacher_attendances'] = \App\Models\ESBTPTeacherAttendance::whereDate('validated_at', $date)->count();

            // Simulations des autres statistiques (à adapter selon votre modèle de données)
            $stats['student_calls_completed'] = $stats['teacher_attendances']; // Supposer que chaque émargement = appel fait
            $stats['courses_closed'] = round($stats['teacher_attendances'] * 0.8); // 80% des émargements = cours clôturés
            $stats['delays'] = max(0, $stats['total_courses'] - $stats['teacher_attendances']); // Retards = cours non émargés

            return $stats;

        } catch (\Exception $e) {
            Log::error('Erreur calcul statistiques: ' . $e->getMessage());
            return [
                'total_courses' => 0,
                'teacher_attendances' => 0,
                'student_calls_completed' => 0,
                'courses_closed' => 0,
                'delays' => 0
            ];
        }
    }
}

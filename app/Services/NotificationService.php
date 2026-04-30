<?php

namespace App\Services;

use App\Models\ESBTPRelance;
use App\Models\ESBTPReliquat;
use App\Models\User;
use App\Models\Notification;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFacture;
use App\Models\ESBTPBonSortie;
use App\Models\ParentNotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationService
{
    protected $whatsappService;
    protected $smsService;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
        $this->smsService = new SmsService();
    }

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

            // SMS non implémenté — ne pas mentir au user
            $relance->update([
                'statut' => 'echec',
                'contenu_message' => $contenu,
                'response_data' => json_encode(['error' => 'SMS non configuré — aucun fournisseur SMS actif'])
            ]);

            Log::warning('Tentative envoi SMS relance sans fournisseur configuré', ['relance_id' => $relance->id]);
            return ['success' => false, 'message' => 'Envoi SMS non disponible — aucun fournisseur configuré'];

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
        $type = $this->determinerTypeRelance($niveau);
        $dateEnvoi = $this->calculerDateEnvoi($niveau);

        return ESBTPRelance::create([
            'etudiant_id' => $etudiant->id,
            'type' => $type,
            'niveau' => $niveau,
            'template_utilise' => "relance_niveau_{$niveau}",
            'date_envoi' => $dateEnvoi,
            'statut' => 'planifiee'
        ]);
    }

    /**
     * Récupère les étudiants à relancer — utilise le vrai système de frais
     * (FraisSubscription/FraisCategory), pas ESBTPFacture.
     */
    private function getEtudiantsARelancer()
    {
        $calcService = app(RelanceCalculationService::class);

        // Lire le seuil depuis settings
        $montantMinimum = (int) (DB::table('settings')->where('key', 'relances.montant_minimum')->value('value') ?? 50000);

        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeActive) return collect();

        // Inscriptions actives avec workflow complet
        $inscriptions = \App\Models\ESBTPInscription::with([
            'etudiant',
            'fraisSubscriptions',
            'paiements' => fn($q) => $q->where('status', 'validé')->whereNull('deleted_at'),
        ])
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get();

        if ($inscriptions->isEmpty()) return collect();

        $calcService->preloadForInscriptions($inscriptions);

        // Lire le délai du 1er rappel depuis settings (jours de retard minimum)
        $delaiNiveau1 = (int) (DB::table('settings')->where('key', 'relances.delai_niveau_1')->value('value') ?? 60);

        // Filtrer : dette >= seuil + échéance dépassée + pas de relance récente
        return $inscriptions
            ->filter(function ($ins) use ($calcService, $montantMinimum, $delaiNiveau1) {
                $totalDu   = $calcService->calculerTotalDu($ins);
                $totalPaye = $ins->paiements->sum('montant');
                $dette     = max(0, $totalDu - $totalPaye);

                if ($dette < $montantMinimum) return false;

                // Vérifier que l'échéance est dépassée (jours de retard >= seuil configuré)
                $joursRetard = $calcService->getJoursRetard($ins);
                if ($joursRetard < $delaiNiveau1) return false;

                // Pas de relance récente (7 jours)
                $relanceRecente = ESBTPRelance::where('etudiant_id', $ins->etudiant_id)
                    ->where('created_at', '>', Carbon::now()->subDays(7))
                    ->where('statut', 'envoyee')
                    ->exists();

                return !$relanceRecente;
            })
            ->map(fn($ins) => $ins->etudiant)
            ->unique('id')
            ->values();
    }

    /**
     * Personnalise le message avec les données de l'étudiant
     */
    private function personaliserMessage($template, $etudiant, $relance)
    {
        $dette = $this->calculerDette($etudiant);

        // Calculer la date d'échéance réelle depuis l'inscription active
        $dateEcheance = 'N/A';
        $joursRetard = 0;
        $calcService = app(RelanceCalculationService::class);

        $inscriptionActive = $etudiant->inscription;
        if ($inscriptionActive) {
            $calcService->preloadForSingle($inscriptionActive);
            $dateEcheance = $calcService->getDateEcheance($inscriptionActive)->format('d/m/Y');
            $joursRetard = $calcService->getJoursRetard($inscriptionActive);
        }

        $nomEcole = \App\Models\Setting::get('school_name', config('app.name'));

        $variables = [
            '{nom}' => $etudiant->nom,
            '{prenom}' => $etudiant->prenoms,
            '{montant_dette}' => number_format($dette, 0, ',', ' ') . ' FCFA',
            '{niveau_relance}' => $relance->niveau,
            '{date}' => Carbon::now()->format('d/m/Y'),
            '{date_echeance}' => $dateEcheance,
            '{jours_retard}' => $joursRetard,
            '{ecole}' => $nomEcole,
            '{nom_ecole}' => $nomEcole,
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
        // Lire depuis settings (source unique de vérité)
        $delaiKey = "relances.delai_niveau_{$niveau}";
        $delaiSettings = DB::table('settings')->where('key', $delaiKey)->value('value');

        if ($delaiSettings !== null) {
            return now()->addDays((int) $delaiSettings);
        }

        // Fallback si settings non configurés
        return match($niveau) {
            1 => now(),
            2 => now()->addDays(7),
            3 => now()->addDays(14), // 14 jours après niveau 1
            default => now()
        };
    }

    /**
     * Calcule la dette totale d'un étudiant via le vrai système de frais.
     */
    public function calculerDette($etudiant)
    {
        return app(RelanceCalculationService::class)->calculerDetteEtudiant($etudiant);
    }

    /**
     * Génère un courrier de relance (PDF)
     */
    private function genererCourrierRelance($relance)
    {
        // Courrier PDF non implémenté — ne pas mentir au user
        $relance->update([
            'statut' => 'echec',
            'response_data' => json_encode(['error' => 'Génération courrier PDF non implémentée'])
        ]);

        Log::warning('Tentative génération courrier relance non implémentée', ['relance_id' => $relance->id]);
        return ['success' => false, 'message' => 'Génération de courrier non disponible'];
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
                    ->whereHas('inscriptions', function($query) use ($annonce) {
                        $query->anneeEnCours()
                            ->whereIn('classe_id', $annonce->classes->pluck('id'));
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
     * Notifie les administrateurs de la création d'une nouvelle annonce
     */
    public function notifyAdminsNewAnnouncement(ESBTPAnnonce $annonce, ?User $createdBy = null): void
    {
        try {
            // Récupérer les utilisateurs administratifs (sauf celui qui a créé l'annonce)
            $admins = User::role(['superAdmin', 'secretaire', 'coordinateur'])->get();

            // Déterminer le type de destinataires pour le message
            $destinataireText = '';
            $destinataireCount = 0;

            if ($annonce->type == 'general') {
                $destinataireCount = ESBTPEtudiant::whereHas('user')->count();
                $destinataireText = "tous les étudiants ({$destinataireCount} étudiants)";
            } elseif ($annonce->type == 'classe') {
                $classes = $annonce->classes;
                $destinataireCount = ESBTPEtudiant::whereHas('user')
                    ->whereHas('inscriptions', function($query) use ($annonce) {
                        $query->anneeEnCours()
                            ->whereIn('classe_id', $annonce->classes->pluck('id'));
                    })
                    ->count();
                $classNames = $classes->pluck('name')->join(', ');
                $destinataireText = "les classes {$classNames} ({$destinataireCount} étudiants)";
            } elseif ($annonce->type == 'etudiant') {
                $etudiants = $annonce->etudiants;
                $destinataireCount = $etudiants->count();
                if ($destinataireCount <= 3) {
                    $etudiantNames = $etudiants->map(function($e) { return $e->nom . ' ' . $e->prenoms; })->join(', ');
                    $destinataireText = "les étudiants {$etudiantNames}";
                } else {
                    $destinataireText = "{$destinataireCount} étudiants spécifiques";
                }
            }

            $creatorName = $createdBy ? $createdBy->name : 'Système';
            $title = "Nouvelle annonce publiée";
            $message = "{$creatorName} a publié l'annonce \"{$annonce->titre}\" pour {$destinataireText}";
            $link = route('esbtp.annonces.show', $annonce->id);

            $notifiedCount = 0;
            foreach ($admins as $admin) {
                // Ne pas notifier le créateur de l'annonce
                if (!$createdBy || $admin->id !== $createdBy->id) {
                    $this->createNotification($admin, $title, $message, 'info', $link, $createdBy);
                    $notifiedCount++;
                }
            }

            Log::info('Notifications administratives envoyées pour nouvelle annonce', [
                'annonce_id' => $annonce->id,
                'titre' => $annonce->titre,
                'type' => $annonce->type,
                'destinataires' => $destinataireText,
                'admins_notifies' => $notifiedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications administratives d\'annonce', [
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
            $title = "Bienvenue dans " . \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI');
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
                ->where('updated_at', '<=', Carbon::now()->subDays(30))
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
                ->update(['is_read' => true]);

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
        $templatePersonnalise = $this->getTemplatePersonnalise($niveau, $segment);
        $dateEnvoi = $this->calculerDateEnvoiSegment($niveau, $segment);

        return ESBTPRelance::create([
            'etudiant_id' => $etudiant->id,
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
        // Basé sur la première relance envoyée, ou la date d'inscription
        $premiereRelance = ESBTPRelance::where('etudiant_id', $etudiant->id)
            ->where('statut', 'envoyee')
            ->orderBy('date_envoi', 'asc')
            ->first();

        if ($premiereRelance && $premiereRelance->date_envoi) {
            return $premiereRelance->date_envoi->diffInDays(now(), false);
        }

        // Sinon, jours depuis le début de l'année universitaire
        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if ($anneeActive && $anneeActive->date_debut) {
            return Carbon::parse($anneeActive->date_debut)->diffInDays(now(), false);
        }

        return 0;
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

    /**
     * Notifier les super-admins, coordonnateurs et secrétaires lors d'une nouvelle inscription
     */
    public function notifyInscriptionCreated($inscription, ?User $createdBy = null): void
    {
        try {
            $users = User::role(['superAdmin', 'coordinateur', 'secretaire'])->get();

            $etudiant = $inscription->etudiant;
            $classe = $inscription->classe;
            $filiere = $inscription->filiere;

            $workflowLabels = [
                'prospect' => 'Prospect',
                'documents_complets' => 'Documents complets',
                'en_validation' => 'En validation',
                'valide' => 'Validé',
                'etudiant_cree' => 'Étudiant créé'
            ];

            $statusLabels = [
                'en_attente' => 'En attente',
                'active' => 'Active',
                'annulée' => 'Annulée',
                'terminée' => 'Terminée'
            ];

            $workflowLabel = $workflowLabels[$inscription->workflow_step] ?? $inscription->workflow_step;
            $statusLabel = $statusLabels[$inscription->status] ?? $inscription->status;

            $paiements = $inscription->paiements;
            $paiementInfo = 'Non renseigné';

            if ($paiements->count() > 0) {
                $paiementsEnAttente = $paiements->where('status', 'en_attente')->count();
                $paiementsValides = $paiements->where('status', 'validé')->count();

                if ($paiementsEnAttente > 0) {
                    $paiementInfo = $paiementsEnAttente . ' paiement(s) en attente';
                } elseif ($paiementsValides > 0) {
                    $paiementInfo = $paiementsValides . ' paiement(s) validé(s)';
                }
            }

            $title = "Nouvelle inscription - {$workflowLabel}";
            $message = "L'étudiant {$etudiant->nom} {$etudiant->prenoms} s'est inscrit en {$filiere->name} - {$classe->name}.\n";
            $message .= "<i class='fas fa-info-circle'></i> Statut: {$statusLabel} | <i class='fas fa-clipboard-check'></i> Étape: {$workflowLabel}\n";
            $message .= "<i class='fas fa-money-bill-wave'></i> Paiement: {$paiementInfo}\n";
            $message .= "<i class='fas fa-arrow-right'></i> Cliquez pour consulter le dossier complet.";

            $link = route('esbtp.inscriptions.show', $inscription->id);

            foreach ($users as $user) {
                if (!$createdBy || $user->id !== $createdBy->id) {
                    $this->createNotification($user, $title, $message, 'info', $link, $createdBy);
                }
            }

            Log::info('Notifications nouvelle inscription envoyées', [
                'inscription_id' => $inscription->id,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification nouvelle inscription: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les super-admins d'un nouveau paiement en attente
     */
    public function notifyPaiementCreated(ESBTPPaiement $paiement, ?User $createdBy = null): void
    {
        try {
            $superAdmins = User::role(['superAdmin'])->get();

            $etudiant = $paiement->etudiant;
            $montant = number_format($paiement->montant, 0, ',', ' ') . ' FCFA';

            $title = "Nouveau paiement en attente de validation";
            $message = "L'étudiant {$etudiant->nom} {$etudiant->prenoms} a effectué un paiement de {$montant}.\n";
            $message .= "<i class='fas fa-credit-card'></i> Type: {$paiement->type_paiement} | <i class='fas fa-mobile-alt'></i> Mode: {$paiement->mode_paiement}\n";
            $message .= "<i class='fas fa-calendar'></i> Date: " . $paiement->date_paiement->format('d/m/Y') . "\n";
            $message .= "<i class='fas fa-exclamation-triangle'></i> Ce paiement nécessite votre validation.";

            $link = route('paiements.show', $paiement->id);

            foreach ($superAdmins as $admin) {
                if (!$createdBy || $admin->id !== $createdBy->id) {
                    $this->createNotification($admin, $title, $message, 'warning', $link, $createdBy);
                }
            }

            Log::info('Notifications nouveau paiement envoyées', [
                'paiement_id' => $paiement->id,
                'admins_notified' => $superAdmins->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification nouveau paiement: ' . $e->getMessage());
        }
    }

    /**
     * Notifier l'étudiant de la validation de son paiement
     */
    public function notifyPaiementValide(ESBTPPaiement $paiement, User $validatedBy): void
    {
        try {
            $etudiant = $paiement->etudiant;
            if (!$etudiant || !$etudiant->user) return;

            $montant = number_format($paiement->montant, 0, ',', ' ') . ' FCFA';

            $title = "Paiement validé";
            $message = "Votre paiement de {$montant} a été validé avec succès.\n";
            $message .= "<i class='fas fa-receipt'></i> Référence: {$paiement->reference_paiement}\n";
            $message .= "<i class='fas fa-file-invoice'></i> Numéro de reçu: {$paiement->numero_recu}";

            $link = route('esbtp.mes-paiements.index');

            $this->createNotification($etudiant->user, $title, $message, 'success', $link, $validatedBy);

            Log::info('Notification validation paiement envoyée', [
                'paiement_id' => $paiement->id,
                'etudiant_id' => $etudiant->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification validation paiement: ' . $e->getMessage());
        }
    }

    /**
     * Notifier l'étudiant du rejet de son paiement
     */
    public function notifyPaiementRejete(ESBTPPaiement $paiement, User $rejectedBy, ?string $motif = null): void
    {
        try {
            $etudiant = $paiement->etudiant;
            if (!$etudiant || !$etudiant->user) return;

            $montant = number_format($paiement->montant, 0, ',', ' ') . ' FCFA';

            $title = "Paiement rejeté";
            $message = "Votre paiement de {$montant} a été rejeté.\n";

            if ($motif) {
                $message .= "<i class='fas fa-comment-dots'></i> Motif: {$motif}\n";
            }

            $message .= "<i class='fas fa-phone'></i> Veuillez contacter le service comptabilité.";

            $link = route('esbtp.mes-paiements.index');

            $this->createNotification($etudiant->user, $title, $message, 'error', $link, $rejectedBy);

            Log::info('Notification rejet paiement envoyée', [
                'paiement_id' => $paiement->id,
                'etudiant_id' => $etudiant->id,
                'motif' => $motif
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur notification rejet paiement: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer un rappel pour une inscription en attente
     */
    public function sendInscriptionReminder($inscription, int $daysPending, int $reminderCount): void
    {
        try {
            $superAdmins = User::role(['superAdmin'])->get();

            $etudiant = $inscription->etudiant;
            $classe = $inscription->classe;
            $filiere = $inscription->filiere;

            $workflowLabels = [
                'prospect' => 'Prospect',
                'documents_complets' => 'Documents complets',
                'en_validation' => 'En validation',
                'valide' => 'Validé',
                'etudiant_cree' => 'Étudiant créé'
            ];

            $workflowLabel = $workflowLabels[$inscription->workflow_step] ?? $inscription->workflow_step;

            $paiements = $inscription->paiements;
            $paiementInfo = 'Aucun paiement renseigné. Demandez à l\'étudiant d\'effectuer un paiement.';

            if ($paiements->count() > 0) {
                $paiementsEnAttente = $paiements->where('status', 'en_attente');
                $paiementsValides = $paiements->where('status', 'validé');

                if ($paiementsEnAttente->count() > 0) {
                    $montantTotal = number_format($paiementsEnAttente->sum('montant'), 0, ',', ' ');
                    $paiementInfo = "{$paiementsEnAttente->count()} paiement(s) en attente ({$montantTotal} FCFA)";
                } elseif ($paiementsValides->count() > 0) {
                    $montantTotal = number_format($paiementsValides->sum('montant'), 0, ',', ' ');
                    $paiementInfo = "Paiement validé ({$montantTotal} FCFA). L'inscription peut maintenant être validée.";
                }
            }

            $title = "<i class='fas fa-clock'></i> Rappel #{$reminderCount}: Inscription en attente depuis {$daysPending} jours";
            $message = "L'inscription de {$etudiant->nom} {$etudiant->prenoms} ({$filiere->name} - {$classe->name}) est en attente depuis {$daysPending} jours.\n";
            $message .= "<i class='fas fa-tasks'></i> Étape actuelle: {$workflowLabel}\n";
            $message .= "<i class='fas fa-wallet'></i> {$paiementInfo}\n";
            $message .= "<i class='fas fa-hand-point-right'></i> Action requise: Valider l'inscription ou le paiement.";

            $link = route('esbtp.inscriptions.show', $inscription->id);

            foreach ($superAdmins as $admin) {
                $this->createNotification($admin, $title, $message, 'warning', $link, null);
            }

            Log::info('Rappel inscription envoyé', [
                'inscription_id' => $inscription->id,
                'days_pending' => $daysPending,
                'reminder_count' => $reminderCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi rappel inscription: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer un rappel pour un paiement en attente
     */
    public function sendPaiementReminder(ESBTPPaiement $paiement, int $daysPending, int $reminderCount): void
    {
        try {
            $superAdmins = User::role(['superAdmin'])->get();

            $etudiant = $paiement->etudiant;
            $montant = number_format($paiement->montant, 0, ',', ' ') . ' FCFA';

            $title = "<i class='fas fa-clock'></i> Rappel #{$reminderCount}: Paiement en attente depuis {$daysPending} jours";
            $message = "Le paiement de {$montant} de {$etudiant->nom} {$etudiant->prenoms} attend validation depuis {$daysPending} jours.\n";
            $message .= "<i class='fas fa-credit-card'></i> Type: {$paiement->type_paiement}\n";
            $message .= "<i class='fas fa-calendar'></i> Date soumission: " . $paiement->created_at->format('d/m/Y') . "\n";
            $message .= "<i class='fas fa-hand-point-right'></i> Action requise: Valider ou rejeter ce paiement.";

            $link = route('paiements.show', $paiement->id);

            foreach ($superAdmins as $admin) {
                $this->createNotification($admin, $title, $message, 'warning', $link, null);
            }

            Log::info('Rappel paiement envoyé', [
                'paiement_id' => $paiement->id,
                'days_pending' => $daysPending,
                'reminder_count' => $reminderCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur envoi rappel paiement: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer des notifications pour les alertes critiques du dashboard coordinateur
     *
     * @param array $alerts - Tableau d'alertes avec type, title, message, details
     * @param Carbon $date - Date des alertes
     * @return void
     */
    public function notifyCoordinateurCriticalAlerts(array $alerts, Carbon $date): void
    {
        try {
            if (empty($alerts)) {
                return;
            }

            // Récupérer les superAdmin et coordinateurs
            $recipients = User::role(['superAdmin', 'coordinateur'])->get();

            if ($recipients->isEmpty()) {
                Log::warning('Aucun destinataire trouvé pour les alertes critiques');
                return;
            }

            $dateFormatted = $date->format('d/m/Y');

            foreach ($alerts as $alert) {
                // Ne notifier que les alertes de type 'danger' (critiques)
                if ($alert['type'] !== 'danger') {
                    continue;
                }

                $title = "🚨 " . ($alert['title'] ?? 'Alerte Critique');
                $message = "Date: {$dateFormatted}\n\n";
                $message .= ($alert['message'] ?? '') . "\n\n";

                if (!empty($alert['details'])) {
                    $message .= "Détails:\n";
                    foreach ($alert['details'] as $detail) {
                        $message .= "• {$detail}\n";
                    }
                }

                $link = route('coordinateur.attendance-dashboard');

                // Envoyer à tous les destinataires
                foreach ($recipients as $recipient) {
                    // Vérifier si une notification identique n'a pas déjà été envoyée aujourd'hui
                    $existingNotification = Notification::where('user_id', $recipient->id)
                        ->where('title', $title)
                        ->whereDate('created_at', $date)
                        ->first();

                    if (!$existingNotification) {
                        $this->createNotification(
                            $recipient,
                            $title,
                            $message,
                            'danger',
                            $link,
                            null
                        );
                    }
                }

                Log::info('Alerte critique envoyée', [
                    'type' => $alert['type'],
                    'title' => $alert['title'],
                    'recipients_count' => $recipients->count(),
                    'date' => $dateFormatted
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi notifications alertes critiques: ' . $e->getMessage());
        }
    }

    /**
     * =================================================================
     * MÉTHODES DE NOTIFICATION PARENTS
     * Ajoutées le 9 octobre 2025
     * =================================================================
     */

    /**
     * Récupérer les paramètres de l'établissement depuis les settings
     */
    private function getSchoolSettings()
    {
        $logoPath = \App\Helpers\SettingsHelper::get('school_logo', '');
        $logoFullPath = null;

        // Récupérer le chemin complet du logo pour embed dans les emails
        // IMPORTANT: Utiliser public_path() au lieu de storage_path() pour $message->embed()
        if ($logoPath) {
            // Le logo est dans storage/app/public/logos/xxx.png
            // Accessible via public/storage/logos/xxx.png grâce au symlink
            $publicPath = public_path('storage/' . $logoPath);
            if (file_exists($publicPath)) {
                $logoFullPath = $publicPath;
            }
        }

        return [
            'school_name' => \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI'),
            'school_address' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'school_phone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'school_email' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'school_logo' => null, // Pour le template (on utilisera $message->embed)
            'schoolLogoPath' => $logoFullPath, // Chemin complet pour embed
        ];
    }

    /**
     * Envoyer notification multi-canal avec tracking
     *
     * @param object $tuteur Le parent/tuteur
     * @param object $etudiant L'étudiant concerné
     * @param string $notificationType Type de notification (inscription, paiement_valide, etc.)
     * @param array $data Données pour les templates
     * @param object $preferences Préférences de notification du parent
     * @return array Résultat par canal ['email' => bool, 'whatsapp' => bool, 'sms' => bool]
     */
    private function sendMultiChannelNotification($tuteur, $etudiant, $notificationType, $data, $preferences)
    {
        $results = [
            'email' => false,
            'whatsapp' => false,
            'sms' => false,
        ];

        try {
            // 1. EMAIL (toujours prioritaire si activé)
            if ($preferences->hasChannel('email') && $tuteur->email) {
                $results['email'] = $this->sendEmailNotification($tuteur, $etudiant, $notificationType, $data);
            }

            // 2. WHATSAPP (si activé et configuré)
            if (env('WHATSAPP_ENABLED', false) && $preferences->hasChannel('whatsapp') && $tuteur->telephone) {
                $results['whatsapp'] = $this->sendWhatsAppNotification($tuteur, $etudiant, $notificationType, $data);
            }

            // 3. SMS (fallback uniquement si WhatsApp échoue ou parent sans WhatsApp)
            if (env('SMS_ENABLED', false) && $preferences->hasChannel('sms') && $tuteur->telephone) {
                // Envoyer SMS uniquement si WhatsApp a échoué OU si pas de WhatsApp
                if (!$preferences->hasChannel('whatsapp') || !$results['whatsapp']) {
                    $results['sms'] = $this->sendSmsNotification($tuteur, $etudiant, $notificationType, $data);
                }
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Erreur notification multi-canal', [
                'error' => $e->getMessage(),
                'type' => $notificationType,
                'parent_id' => $tuteur->id ?? null,
            ]);
            return $results;
        }
    }

    /**
     * Envoyer notification par email avec logging
     */
    private function sendEmailNotification($tuteur, $etudiant, $notificationType, $data)
    {
        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'email',
                'status' => 'pending',
                'recipient' => $tuteur->email,
                'cost_fcfa' => 0, // Email gratuit
            ]);

            // Envoyer l'email selon le type
            $mailClass = match($notificationType) {
                'inscription' => \App\Mail\Parents\InscriptionConfirmationMail::class,
                'paiement_valide' => \App\Mail\Parents\PaiementValideMail::class,
                'paiement_rejete' => \App\Mail\Parents\PaiementRejeteMail::class,
                'absence' => \App\Mail\Parents\AbsenceNotificationMail::class,
                'bulletin_publie' => \App\Mail\Parents\BulletinPublishedMail::class,
                'notes_faibles' => \App\Mail\Parents\LowGradesMail::class,
                default => null,
            };

            if ($mailClass) {
                Mail::to($tuteur->email)->send(new $mailClass($data));
                $log->markAsSent();
                return true;
            }

            $log->markAsFailed('Type de notification non supporté');
            return false;

        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error('Erreur envoi email parent', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Envoyer notification par WhatsApp avec logging
     */
    private function sendWhatsAppNotification($tuteur, $etudiant, $notificationType, $data)
    {
        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'whatsapp',
                'status' => 'pending',
                'recipient' => $tuteur->telephone,
                'cost_fcfa' => 3.3, // Coût moyen utility message Afrique (hors fenêtre gratuite)
            ]);

            // Envoyer via WhatsApp selon le type
            $result = match($notificationType) {
                'inscription' => $this->whatsappService->sendInscriptionNotification($tuteur->telephone, $data),
                'paiement_valide' => $this->whatsappService->sendPaiementValideNotification($tuteur->telephone, $data),
                'paiement_rejete' => $this->whatsappService->sendPaiementRejeteNotification($tuteur->telephone, $data),
                'absence' => $this->whatsappService->sendAbsenceNotification($tuteur->telephone, $data),
                'bulletin_publie' => $this->whatsappService->sendBulletinPublishedNotification($tuteur->telephone, $data),
                'notes_faibles' => $this->whatsappService->sendLowGradesNotification($tuteur->telephone, $data),
                default => false,
            };

            if ($result) {
                $externalId = $result['messages'][0]['id'] ?? null;
                $log->markAsSent($externalId);
                return true;
            }

            $log->markAsFailed('Échec envoi WhatsApp');
            return false;

        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error('Erreur envoi WhatsApp parent', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Envoyer notification par SMS avec logging
     */
    private function sendSmsNotification($tuteur, $etudiant, $notificationType, $data)
    {
        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'sms',
                'status' => 'pending',
                'recipient' => $tuteur->telephone,
                'cost_fcfa' => 7, // Coût moyen SMS Côte d'Ivoire
            ]);

            // Envoyer via SMS selon le type
            $result = match($notificationType) {
                'inscription' => $this->smsService->sendInscriptionNotification($tuteur->telephone, $data),
                'paiement_valide' => $this->smsService->sendPaiementValideNotification($tuteur->telephone, $data),
                'paiement_rejete' => $this->smsService->sendPaiementRejeteNotification($tuteur->telephone, $data),
                'absence' => $this->smsService->sendAbsenceNotification($tuteur->telephone, $data),
                'bulletin_publie' => $this->smsService->sendBulletinPublishedNotification($tuteur->telephone, $data),
                'notes_faibles' => $this->smsService->sendLowGradesNotification($tuteur->telephone, $data),
                default => false,
            };

            if ($result) {
                $log->markAsSent();
                return true;
            }

            $log->markAsFailed('Échec envoi SMS');
            return false;

        } catch (\Exception $e) {
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
            Log::error('Erreur envoi SMS parent', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Notifier les parents lors de la création d'une inscription
     */
    public function notifyParentsInscriptionCreated($inscription, $credentials)
    {
        try {
            // Charger toutes les relations nécessaires
            $inscription->load(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire', 'etudiant']);

            $etudiant = $inscription->etudiant;

            // Le parent utilise le compte de l'étudiant
            if (!$etudiant->user) {
                Log::info('Pas de compte utilisateur pour l\'étudiant', ['etudiant_id' => $etudiant->id]);
                return;
            }

            $tuteur = $etudiant->tuteur;
            if (!$tuteur) {
                Log::info('Pas de tuteur pour l\'étudiant', ['etudiant_id' => $etudiant->id]);
                return;
            }

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('inscriptions')) {
                return;
            }

            $schoolSettings = $this->getSchoolSettings();

            // Calculer situation financière (comme dans previewSituationFinanciere)
            // 1. Frais souscrits pour l'année courante
            $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('is_active', true)
                ->get();
            $totalFraisAnnee = $fraisSouscrits->sum('amount');

            // 2. Reliquats entrants d'années précédentes
            $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
                ->actifs()
                ->get();
            $totalReliquats = $reliquatsEntrants->sum('solde_restant');

            // 3. Total attendu = Frais année + Reliquats
            $totalAttendu = $totalFraisAnnee + $totalReliquats;

            // 4. Total payé (tous les paiements validés)
            $totalPaye = \App\Models\ESBTPPaiement::where('inscription_id', $inscription->id)
                ->where('status', 'validé')
                ->sum('montant');

            // 5. Solde restant
            $soldeRestant = $totalAttendu - $totalPaye;

            $data = [
                'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'matricule' => $etudiant->matricule ?? 'N/A',
                'classe' => $inscription->classe->name ?? 'N/A',
                'filiere' => $inscription->classe->filiere->name ?? 'N/A',
                'niveauEtude' => $inscription->classe->niveauEtude->name ?? 'N/A',
                'anneeUniversitaire' => $inscription->anneeUniversitaire->name ?? 'N/A',
                'dateInscription' => $inscription->created_at ? $inscription->created_at->format('d/m/Y') : date('d/m/Y'),
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'platformUrl' => route('dashboard'),
                'montantTotal' => $totalAttendu,
                'montantPaye' => $totalPaye,
                'montantDu' => max(0, $soldeRestant), // Pas de montant négatif
                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app (utilise le compte de l'étudiant)
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'inscription_confirmation',
                'title' => 'Inscription confirmée',
                'message' => "L'inscription de {$etudiant->prenoms} {$etudiant->nom} a été enregistrée pour l'année {$data['anneeUniversitaire']}.",
                'is_read' => false,
            ]);

            // Email envoyé au parent si canal activé
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\InscriptionConfirmationMail($data));
            }

            $preferences->incrementNotificationCount();

            Log::info('Notification inscription envoyée aux parents', ['parent_id' => $tuteur->id, 'email' => $tuteur->email]);

        } catch (\Exception $e) {
            Log::error('Erreur notification inscription parent: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les parents lors de la validation d'un paiement
     */
    public function notifyParentsPaiementValide($paiement)
    {
        try {
            $inscription = $paiement->inscription;
            if (!$inscription) return;

            $etudiant = $inscription->etudiant;
            $tuteur = $etudiant->tuteur;

            if (!$etudiant->user || !$tuteur) {
                return;
            }

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('paiements')) {
                return;
            }

            $schoolSettings = $this->getSchoolSettings();

            // Calculer situation financière (comme dans previewSituationFinanciere)
            $fraisSouscrits = \App\Models\ESBTPFraisSubscription::where('inscription_id', $inscription->id)
                ->where('is_active', true)
                ->get();
            $totalFraisAnnee = $fraisSouscrits->sum('amount');

            $reliquatsEntrants = \App\Models\ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
                ->actifs()
                ->get();
            $totalReliquats = $reliquatsEntrants->sum('solde_restant');

            $totalAttendu = $totalFraisAnnee + $totalReliquats;

            $totalPaye = \App\Models\ESBTPPaiement::where('inscription_id', $inscription->id)
                ->where('status', 'validé')
                ->sum('montant');

            $soldeRestant = $totalAttendu - $totalPaye;

            $data = [
                'parentName' => $tuteur->nom . ' ' . $tuteur->prenoms,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'montant' => $paiement->montant,
                'reference' => $paiement->reference_paiement ?? 'N/A',
                'numeroRecu' => $paiement->numero_recu ?? 'En cours de génération',
                'modePaiement' => $paiement->mode_paiement,
                'datePaiement' => $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : 'N/A',
                'dateValidation' => $paiement->updated_at ? $paiement->updated_at->format('d/m/Y H:i') : 'N/A',
                'validePar' => $paiement->validatedBy ? $paiement->validatedBy->name : 'Système',
                'montantTotal' => $totalAttendu,
                'montantPaye' => $totalPaye,
                'resteDu' => max(0, $soldeRestant),
                'pourcentagePaye' => $totalAttendu > 0
                    ? round(($totalPaye / $totalAttendu) * 100, 2)
                    : 0,
                'recuUrl' => route('esbtp.mes-paiements.index'),

                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'paiement_valide',
                'title' => 'Paiement validé',
                'message' => "Le paiement de {$paiement->montant} FCFA pour {$etudiant->prenoms} {$etudiant->nom} a été validé.",
                'is_read' => false,
            ]);

            // Email
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\PaiementValideMail($data));
            }

            $preferences->incrementNotificationCount();

        } catch (\Exception $e) {
            Log::error('Erreur notification paiement validé parent: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les parents lors du rejet d'un paiement
     */
    public function notifyParentsPaiementRejete($paiement)
    {
        try {
            $inscription = $paiement->inscription;
            if (!$inscription) return;

            $etudiant = $inscription->etudiant;
            $tuteur = $etudiant->tuteur;

            if (!$etudiant->user || !$tuteur) return;

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('paiements')) return;
            $schoolSettings = $this->getSchoolSettings();


            $data = [
                'parentName' => $tuteur->nom . ' ' . $tuteur->prenoms,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'montant' => $paiement->montant,
                'reference' => $paiement->reference_paiement ?? 'N/A',
                'dateSoumission' => $paiement->created_at ? $paiement->created_at->format('d/m/Y H:i') : 'N/A',
                'dateRejet' => $paiement->updated_at ? $paiement->updated_at->format('d/m/Y H:i') : 'N/A',
                'motifRejet' => $paiement->motif_rejet ?? $paiement->commentaire ?? 'Aucun motif spécifié',
                'paiementUrl' => route('esbtp.mes-paiements.index'),

                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'paiement_rejete',
                'title' => 'Paiement rejeté',
                'message' => "Le paiement de {$paiement->montant} FCFA a été rejeté. Motif: {$data['motifRejet']}",
                'is_read' => false,
            ]);

            // Email
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\PaiementRejeteMail($data));
            }

            $preferences->incrementNotificationCount();

        } catch (\Exception $e) {
            Log::error('Erreur notification paiement rejeté parent: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les parents d'une absence
     */
    public function notifyParentsAbsence($attendance)
    {
        try {
            $etudiant = ESBTPEtudiant::find($attendance->etudiant_id);
            if (!$etudiant) return;

            $tuteur = $etudiant->tuteur;
            if (!$etudiant->user || !$tuteur) return;

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('absences')) return;

            // Calculer stats mensuelles
            $moisActuel = now()->startOfMonth();
            $absences = ESBTPAttendance::where('etudiant_id', $etudiant->id)
                ->where('date', '>=', $moisActuel)
                ->where('statut', 'absent')
                ->get();

            $justifiees = $absences->where('justification_status', 'approuve')->count();
            $nonJustifiees = $absences->where('justification_status', '!=', 'approuve')->count();
            $totalPresences = ESBTPAttendance::where('etudiant_id', $etudiant->id)
                ->where('date', '>=', $moisActuel)
                ->count();
            $tauxPresence = $totalPresences > 0 ? round((($totalPresences - $absences->count()) / $totalPresences) * 100, 2) : 100;
            $schoolSettings = $this->getSchoolSettings();


            $data = [
                'parentName' => $tuteur->nom . ' ' . $tuteur->prenoms,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'classe' => $etudiant->inscriptions()->latest()->first()->classe->nom ?? 'N/A',
                'date' => \Carbon\Carbon::parse($attendance->date)->format('d/m/Y'),
                'heureDebut' => $attendance->heure_debut ?? '08:00',
                'heureFin' => $attendance->heure_fin ?? '17:00',
                'matiere' => $attendance->matiere->nom ?? $attendance->commentaire ?? 'Cours',
                'typeActivite' => $attendance->type_activite ?? 'Cours magistral',
                'commentaire' => $attendance->commentaire,
                'periodeStats' => now()->format('F Y'),
                'absencesJustifiees' => $justifiees,
                'absencesNonJustifiees' => $nonJustifiees,
                'totalAbsences' => $absences->count(),
                'tauxPresence' => $tauxPresence,
                'justificationUrl' => route('esbtp.mes-absences.index'),

                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'absence',
                'title' => 'Absence enregistrée',
                'message' => "{$etudiant->prenoms} {$etudiant->nom} a été absent(e) le {$data['date']}.",
                'is_read' => false,
            ]);

            // Email
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\AbsenceNotificationMail($data));
            }

            // Alerte si taux de présence faible
            if ($tauxPresence < $preferences->attendance_rate_threshold) {
                if ($preferences->hasChannel('email') && $tuteur->email) {
                    Mail::to($tuteur->email)->send(new \App\Mail\Parents\LowAttendanceMail($data));
                }
            }

            $preferences->incrementNotificationCount();

        } catch (\Exception $e) {
            Log::error('Erreur notification absence parent: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les parents de la publication d'un bulletin
     */
    public function notifyParentsBulletinPublished($bulletin)
    {
        try {
            $etudiant = $bulletin->etudiant;
            if (!$etudiant) return;

            $tuteur = $etudiant->tuteur;
            if (!$etudiant->user || !$tuteur) return;

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('bulletins')) return;

            $mention = $bulletin->mention ?? 'N/A';
            $mentionColor = $this->getMentionColor($mention);
            $schoolSettings = $this->getSchoolSettings();


            $data = [
                'parentName' => $tuteur->nom . ' ' . $tuteur->prenoms,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'classe' => $bulletin->classe->nom ?? 'N/A',
                'periode' => $bulletin->periode,
                'anneeUniversitaire' => $bulletin->anneeUniversitaire->annee ?? 'N/A',
                'moyenneGenerale' => $bulletin->moyenne_generale ?? 0,
                'rang' => $bulletin->rang ?? 'N/A',
                'effectifClasse' => $bulletin->classe->nombre_etudiants ?? 'N/A',
                'totalAbsences' => $bulletin->total_absences ?? 0,
                'noteAssiduite' => $bulletin->note_assiduite,
                'mention' => $mention,
                'mentionColor' => $mentionColor,
                'appreciationGenerale' => $bulletin->appreciation_generale,
                'decision' => $bulletin->decision,
                'requiresSignature' => true,
                'bulletinUrl' => route('esbtp.mon-bulletin.index'),

                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'bulletin_publie',
                'title' => 'Bulletin disponible',
                'message' => "Le bulletin de {$etudiant->prenoms} {$etudiant->nom} est disponible (Moyenne: {$bulletin->moyenne_generale}).",
                'is_read' => false,
            ]);

            // Email
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\BulletinPublishedMail($data));
            }

            $preferences->incrementNotificationCount();

        } catch (\Exception $e) {
            Log::error('Erreur notification bulletin publié parent: ' . $e->getMessage());
        }
    }

    /**
     * Notifier les parents de notes faibles
     */
    public function notifyParentsLowGrades($bulletin)
    {
        try {
            $etudiant = $bulletin->etudiant;
            if (!$etudiant) return;

            $tuteur = $etudiant->tuteur;
            if (!$etudiant->user || !$tuteur) return;

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('notes')) return;

            // Vérifier si moyenne < seuil OU matières en échec
            $seuilNote = $preferences->grade_threshold;
            $moyenneGenerale = $bulletin->moyenne_generale ?? 0;

            $matieresFaibles = [];
            if ($bulletin->notes && is_array($bulletin->notes)) {
                foreach ($bulletin->notes as $note) {
                    if (isset($note['moyenne']) && $note['moyenne'] < 10) {
                        $matieresFaibles[] = [
                            'matiere' => $note['matiere'] ?? 'N/A',
                            'moyenne' => $note['moyenne'],
                        ];
                    }
                }
            }

            $schoolSettings = $this->getSchoolSettings();

            // Envoyer alerte seulement si performance faible
            if ($moyenneGenerale < 10 || count($matieresFaibles) > 0) {
                $data = [
                    'parentName' => $tuteur->nom . ' ' . $tuteur->prenoms,
                    'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                    'classe' => $bulletin->classe->nom ?? 'N/A',
                    'periode' => $bulletin->periode,
                    'moyenneGenerale' => $moyenneGenerale,
                    'rang' => $bulletin->rang ?? 'N/A',
                    'effectifClasse' => $bulletin->classe->nombre_etudiants ?? 'N/A',
                    'decision' => $bulletin->decision,
                    'matieresEnDifficulte' => $matieresFaibles,
                    'tauxPresence' => 85,  // À calculer depuis les absences si nécessaire
                    'coursDisponibles' => true,
                    'bulletinUrl' => route('esbtp.mes-notes.index'),
                    'contactUrl' => route('esbtp.mon-profil.index'),

                    'schoolName' => $schoolSettings['school_name'],
                    'schoolAddress' => $schoolSettings['school_address'],
                    'schoolPhone' => $schoolSettings['school_phone'],
                    'schoolEmail' => $schoolSettings['school_email'],
                    'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
                ];

                // Notification in-app
                Notification::create([
                    'user_id' => $etudiant->user_id,
                    'type' => 'notes_faibles',
                    'title' => 'Alerte notes faibles',
                    'message' => "{$etudiant->prenoms} {$etudiant->nom} a des difficultés académiques (Moyenne: {$moyenneGenerale}).",
                    'is_read' => false,
                ]);

                // Email
                if ($preferences->hasChannel('email') && $tuteur->email) {
                    Mail::to($tuteur->email)->send(new \App\Mail\Parents\LowGradesMail($data));
                }

                $preferences->incrementNotificationCount();
            }

        } catch (\Exception $e) {
            Log::error('Erreur notification moyennes faibles parent: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir la couleur de la mention
     */
    private function getMentionColor($mention)
    {
        $colors = [
            'Excellent' => 'success',
            'Très Bien' => 'success',
            'Bien' => 'info',
            'Assez Bien' => 'info',
            'Passable' => 'warning',
            'Insuffisant' => 'danger',
        ];
        return $colors[$mention] ?? 'secondary';
    }

    /**
     * Notifier les parents lors d'une réinscription
     */
    public function notifyParentsReinscriptionCreated($inscription, $decision = 'passage', $reliquatMontant = 0)
    {
        try {
            // Charger toutes les relations nécessaires
            $inscription->load(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire', 'etudiant']);

            $etudiant = $inscription->etudiant;

            // Le parent utilise le compte de l'étudiant
            if (!$etudiant->user) {
                Log::info('Pas de compte utilisateur pour l\'étudiant (réinscription)', ['etudiant_id' => $etudiant->id]);
                return;
            }

            $tuteur = $etudiant->tuteur;
            if (!$tuteur) {
                Log::info('Pas de tuteur pour l\'étudiant (réinscription)', ['etudiant_id' => $etudiant->id]);
                return;
            }

            $preferences = $tuteur->getOrCreateNotificationPreferences();
            if (!$preferences->isNotificationEnabled('inscriptions')) {
                return;
            }

            $schoolSettings = $this->getSchoolSettings();

            $data = [
                'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
                'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
                'matricule' => $etudiant->matricule ?? 'N/A',
                'classe' => $inscription->classe->name ?? 'N/A',
                'filiere' => $inscription->classe->filiere->name ?? 'N/A',
                'niveauEtude' => $inscription->classe->niveauEtude->name ?? 'N/A',
                'anneeUniversitaire' => $inscription->anneeUniversitaire->name ?? 'N/A',
                'dateReinscription' => $inscription->created_at ? $inscription->created_at->format('d/m/Y') : date('d/m/Y'),
                'decision' => $decision,
                'reliquatMontant' => $reliquatMontant,
                'platformUrl' => route('dashboard'),
                'schoolName' => $schoolSettings['school_name'],
                'schoolAddress' => $schoolSettings['school_address'],
                'schoolPhone' => $schoolSettings['school_phone'],
                'schoolEmail' => $schoolSettings['school_email'],
                'schoolLogoPath' => $schoolSettings['schoolLogoPath'],
            ];

            // Notification in-app (utilise le compte de l'étudiant)
            Notification::create([
                'user_id' => $etudiant->user_id,
                'type' => 'reinscription_confirmation',
                'title' => 'Réinscription confirmée',
                'message' => "La réinscription de {$etudiant->prenoms} {$etudiant->nom} a été enregistrée pour l'année {$data['anneeUniversitaire']} en classe {$data['classe']}.",
                'is_read' => false,
            ]);

            // Email envoyé au parent si canal activé
            if ($preferences->hasChannel('email') && $tuteur->email) {
                Mail::to($tuteur->email)->send(new \App\Mail\Parents\ReinscriptionConfirmationMail($data));
            }

            $preferences->incrementNotificationCount();

            Log::info('Notification réinscription envoyée aux parents', ['parent_id' => $tuteur->id, 'email' => $tuteur->email]);

        } catch (\Exception $e) {
            Log::error('Erreur notification réinscription parent: ' . $e->getMessage());
        }
    }
}

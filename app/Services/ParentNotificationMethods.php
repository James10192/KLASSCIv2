<?php

// Ce fichier contient les méthodes à ajouter au NotificationService
// Ces méthodes seront copiées manuellement dans NotificationService.php

/**
 * =================================================================
 * MÉTHODES DE NOTIFICATION PARENTS
 * =================================================================
 * Ces méthodes envoient des notifications aux parents via:
 * - Notifications in-app (toujours)
 * - Email (si activé dans préférences)
 * - WhatsApp/SMS (Phase 2/3)
 */

/**
 * Notifier les parents lors de la création d'une inscription
 */
public function notifyParentsInscriptionCreated($inscription, $credentials)
{
    try {
        $etudiant = $inscription->etudiant;
        $tuteur = $etudiant->tuteur;

        if (!$tuteur || !$tuteur->user) {
            Log::info('Pas de tuteur avec compte utilisateur pour l\'étudiant', ['etudiant_id' => $etudiant->id]);
            return;
        }

        // Récupérer ou créer les préférences
        $preferences = $tuteur->getOrCreateNotificationPreferences();

        // Vérifier si les notifications d'inscription sont activées
        if (!$preferences->isNotificationEnabled('inscriptions')) {
            return;
        }

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'matricule' => $etudiant->matricule,
            'classe' => $inscription->classe->name ?? 'N/A',
            'filiere' => $inscription->classe->filiere->nom ?? 'N/A',
            'niveauEtude' => $inscription->classe->niveauEtude->nom ?? 'N/A',
            'anneeUniversitaire' => $inscription->anneeUniversitaire->name ?? 'N/A',
            'dateInscription' => $inscription->date_inscription ? $inscription->date_inscription->format('d/m/Y') : now()->format('d/m/Y'),
            'username' => $credentials['username'] ?? 'N/A',
            'password' => $credentials['password'] ?? 'N/A',
            'platformUrl' => url('/'),
            'montantTotal' => $inscription->frais_scolarite ?? 0,
            'montantPaye' => $inscription->montant_paye ?? 0,
            'montantDu' => ($inscription->frais_scolarite ?? 0) - ($inscription->montant_paye ?? 0),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Inscription confirmée - {$data['studentName']}",
            "<i class='fas fa-user-graduate'></i> Votre enfant {$data['studentName']} a été inscrit(e) avec succès pour l'année {$data['anneeUniversitaire']} en classe {$data['classe']}.",
            'success',
            route('parent.dashboard')
        );

        // Email (si canal activé)
        if ($preferences->hasChannel('email') && $tuteur->email) {
            Mail::to($tuteur->email)->send(new \App\Mail\Parents\InscriptionConfirmationMail($data));
            Log::info('Email inscription envoyé au parent', ['parent_id' => $tuteur->id, 'email' => $tuteur->email]);
        }

        // Incrémenter le compteur
        $preferences->incrementNotificationCount();

        Log::info('Notification inscription parent créée', ['parent_id' => $tuteur->id, 'inscription_id' => $inscription->id]);

    } catch (\Exception $e) {
        Log::error('Erreur notification inscription parent: ' . $e->getMessage(), [
            'inscription_id' => $inscription->id ?? null,
            'trace' => $e->getTraceAsString()
        ]);
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

        if (!$tuteur || !$tuteur->user) return;

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (!$preferences->isNotificationEnabled('paiements')) return;

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'montant' => $paiement->montant,
            'reference' => $paiement->reference_paiement,
            'numeroRecu' => $paiement->numero_recu ?? 'En cours de génération',
            'modePaiement' => $paiement->mode_paiement,
            'datePaiement' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A',
            'dateValidation' => $paiement->validated_at ? $paiement->validated_at->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i'),
            'validePar' => $paiement->validatedBy->name ?? 'Administration',
            'montantTotal' => $inscription->frais_scolarite ?? 0,
            'montantPaye' => $inscription->montant_paye ?? 0,
            'resteDu' => ($inscription->frais_scolarite ?? 0) - ($inscription->montant_paye ?? 0),
            'pourcentagePaye' => ($inscription->frais_scolarite > 0) ? round((($inscription->montant_paye ?? 0) / $inscription->frais_scolarite) * 100) : 0,
            'recuUrl' => route('esbtp.paiements.recu', $paiement->id),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Paiement validé - {$data['montant']} FCFA",
            "<i class='fas fa-check-circle'></i> Le paiement de {$data['montant']} FCFA pour {$data['studentName']} a été validé. Reçu: {$data['numeroRecu']}",
            'success',
            $data['recuUrl']
        );

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

        if (!$tuteur || !$tuteur->user) return;

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (!$preferences->isNotificationEnabled('paiements')) return;

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'montant' => $paiement->montant,
            'reference' => $paiement->reference_paiement,
            'dateSoumission' => $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'N/A',
            'dateRejet' => $paiement->rejected_at ? $paiement->rejected_at->format('d/m/Y à H:i') : now()->format('d/m/Y à H:i'),
            'motifRejet' => $paiement->rejection_reason ?? 'Non spécifié',
            'paiementUrl' => route('esbtp.paiements.create', ['inscription_id' => $inscription->id]),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Paiement rejeté - {$data['montant']} FCFA",
            "<i class='fas fa-times-circle'></i> Le paiement de {$data['montant']} FCFA pour {$data['studentName']} a été rejeté. Motif: {$data['motifRejet']}",
            'danger',
            $data['paiementUrl']
        );

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
        $etudiant = $attendance->etudiant;
        $tuteur = $etudiant->tuteur;

        if (!$tuteur || !$tuteur->user) return;

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (!$preferences->isNotificationEnabled('absences')) return;

        // Calculer les statistiques du mois
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        $absencesMois = \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
            ->where('statut', 'absent')
            ->whereBetween('date', [$debutMois, $finMois])
            ->get();

        $absencesJustifiees = $absencesMois->where('is_justified', true)->count();
        $absencesNonJustifiees = $absencesMois->where('is_justified', false)->count();
        $totalAbsences = $absencesMois->count();

        // Calculer taux de présence
        $totalSeances = \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant->id)
            ->whereBetween('date', [$debutMois, $finMois])
            ->count();
        $presences = $totalSeances - $totalAbsences;
        $tauxPresence = $totalSeances > 0 ? round(($presences / $totalSeances) * 100) : 100;

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'classe' => $etudiant->classe_active->name ?? 'N/A',
            'date' => $attendance->date->format('d/m/Y'),
            'heureDebut' => $attendance->heure_debut ?? 'N/A',
            'heureFin' => $attendance->heure_fin ?? 'N/A',
            'matiere' => $attendance->matiere->name ?? 'Non spécifié',
            'typeActivite' => $attendance->type_activite ?? 'Cours',
            'commentaire' => $attendance->commentaire,
            'absencesJustifiees' => $absencesJustifiees,
            'absencesNonJustifiees' => $absencesNonJustifiees,
            'totalAbsences' => $totalAbsences,
            'tauxPresence' => $tauxPresence,
            'periodeStats' => 'Ce mois',
            'justificationUrl' => route('parent.absences.justify', $attendance->id),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Absence - {$data['studentName']}",
            "<i class='fas fa-user-clock'></i> {$data['studentName']} a été marqué(e) absent(e) le {$data['date']} en {$data['matiere']}.",
            'warning',
            $data['justificationUrl']
        );

        // Email
        if ($preferences->hasChannel('email') && $tuteur->email) {
            Mail::to($tuteur->email)->send(new \App\Mail\Parents\AbsenceNotificationMail($data));
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
        $tuteur = $etudiant->tuteur;

        if (!$tuteur || !$tuteur->user) return;

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (!$preferences->isNotificationEnabled('bulletins')) return;

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'classe' => $bulletin->classe->name ?? 'N/A',
            'periode' => $bulletin->periode == '1' ? 'Semestre 1' : 'Semestre 2',
            'anneeUniversitaire' => $bulletin->anneeUniversitaire->name ?? 'N/A',
            'moyenneGenerale' => $bulletin->moyenne_generale ?? 0,
            'rang' => $bulletin->rang ?? 0,
            'effectifClasse' => $bulletin->effectif_classe ?? 0,
            'totalAbsences' => $bulletin->total_absences ?? 0,
            'noteAssiduite' => $bulletin->note_assiduite,
            'mention' => $bulletin->mention,
            'mentionColor' => $this->getMentionColor($bulletin->mention),
            'appreciationGenerale' => $bulletin->appreciation_generale,
            'decision' => $bulletin->decision,
            'requiresSignature' => !$bulletin->signature_parent,
            'bulletinUrl' => route('esbtp.bulletins.pdf-params', [
                'bulletin' => $bulletin->id,
                'classe_id' => $bulletin->classe_id,
                'periode' => $bulletin->periode,
                'annee_universitaire_id' => $bulletin->annee_universitaire_id
            ]),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Bulletin disponible - {$data['periode']}",
            "<i class='fas fa-file-alt'></i> Le bulletin de {$data['studentName']} pour {$data['periode']} est disponible. Moyenne: {$data['moyenneGenerale']}/20",
            'info',
            $data['bulletinUrl']
        );

        // Email
        if ($preferences->hasChannel('email') && $tuteur->email) {
            Mail::to($tuteur->email)->send(new \App\Mail\Parents\BulletinPublishedMail($data));
        }

        $preferences->incrementNotificationCount();

    } catch (\Exception $e) {
        Log::error('Erreur notification bulletin parent: ' . $e->getMessage());
    }
}

/**
 * Notifier les parents des moyennes faibles
 */
public function notifyParentsLowGrades($bulletin)
{
    try {
        $etudiant = $bulletin->etudiant;
        $tuteur = $etudiant->tuteur;

        if (!$tuteur || !$tuteur->user) return;

        $preferences = $tuteur->getOrCreateNotificationPreferences();
        if (!$preferences->isNotificationEnabled('notes')) return;

        // Vérifier le seuil personnalisé
        if ($bulletin->moyenne_generale >= $preferences->grade_threshold) {
            return; // Pas d'alerte si au-dessus du seuil
        }

        // Récupérer les matières en difficulté
        $matieresEnDifficulte = [];
        if ($bulletin->details_notes) {
            $details = is_string($bulletin->details_notes) ? json_decode($bulletin->details_notes, true) : $bulletin->details_notes;
            foreach ($details as $detail) {
                if (isset($detail['moyenne']) && $detail['moyenne'] < 10) {
                    $matieresEnDifficulte[] = [
                        'nom' => $detail['matiere'] ?? 'N/A',
                        'moyenne' => $detail['moyenne'],
                        'coefficient' => $detail['coefficient'] ?? 1
                    ];
                }
            }
        }

        $data = [
            'parentName' => $tuteur->prenoms . ' ' . $tuteur->nom,
            'studentName' => $etudiant->prenoms . ' ' . $etudiant->nom,
            'classe' => $bulletin->classe->name ?? 'N/A',
            'periode' => $bulletin->periode == '1' ? 'Semestre 1' : 'Semestre 2',
            'moyenneGenerale' => $bulletin->moyenne_generale ?? 0,
            'rang' => $bulletin->rang ?? 0,
            'effectifClasse' => $bulletin->effectif_classe ?? 0,
            'decision' => $bulletin->decision,
            'tauxPresence' => $bulletin->taux_presence ?? 'N/A',
            'matieresEnDifficulte' => $matieresEnDifficulte,
            'coursDisponibles' => true,
            'bulletinUrl' => route('esbtp.bulletins.pdf-params', [
                'bulletin' => $bulletin->id,
                'classe_id' => $bulletin->classe_id,
                'periode' => $bulletin->periode,
                'annee_universitaire_id' => $bulletin->annee_universitaire_id
            ]),
            'contactUrl' => route('parent.contact.coordinateur'),
            'schoolName' => \App\Helpers\SettingsHelper::get('school_name', 'ESBTP-yAKRO'),
            'schoolAddress' => \App\Helpers\SettingsHelper::get('school_address', ''),
            'schoolPhone' => \App\Helpers\SettingsHelper::get('school_phone', ''),
            'schoolEmail' => \App\Helpers\SettingsHelper::get('school_email', ''),
            'schoolLogo' => \App\Helpers\SettingsHelper::get('school_logo') ? asset('storage/' . \App\Helpers\SettingsHelper::get('school_logo')) : null,
        ];

        // Notification in-app
        $this->createNotification(
            $tuteur->user,
            "Alerte performance - {$data['studentName']}",
            "<i class='fas fa-exclamation-triangle'></i> Le bulletin de {$data['studentName']} indique une moyenne de {$data['moyenneGenerale']}/20, en dessous de la moyenne.",
            'danger',
            $data['bulletinUrl']
        );

        // Email
        if ($preferences->hasChannel('email') && $tuteur->email) {
            Mail::to($tuteur->email)->send(new \App\Mail\Parents\LowGradesMail($data));
        }

        $preferences->incrementNotificationCount();

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

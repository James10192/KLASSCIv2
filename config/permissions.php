<?php

/**
 * Registry centralisé des rôles et permissions KLASSCI (Lot 2).
 *
 * Source unique de vérité pour :
 * - Les rôles canoniques + métadonnées UI (label FR, icône, groupe)
 * - Les permissions canoniques en dot.notation + leurs aliases legacy
 * - Les permissions par défaut de chaque rôle (role_defaults)
 * - La matrice "qui peut gérer qui" (role_management)
 * - Les permissions deprecated (à supprimer au Lot 7)
 *
 * À lire via App\Services\PermissionRegistry, pas en direct dans le code.
 *
 * Conventions :
 * - Permissions canoniques : domaine.action[.qualificateur] en snake_case ASCII
 * - Aliases : noms legacy qui doivent continuer de fonctionner pendant la
 *   migration progressive (Lot 6) — supprimés au Lot 7
 * - Labels FR : visibles dans la page admin /esbtp/roles-permissions, doivent
 *   être compréhensibles pour un utilisateur lambda (pas d'anglais, pas de code)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Rôles canoniques
    |--------------------------------------------------------------------------
    | visible_in_ui : si le rôle apparaît dans la page admin de configuration
    |                 (false pour serviceTechnique qui est géré hors UI tenant)
    */

    'roles' => [
        'superAdmin' => [
            'label' => 'Super Administrateur',
            'description' => 'Accès complet à toutes les fonctionnalités de l\'application',
            'icon' => 'fa-crown',
            'group' => 'Administration',
            'visible_in_ui' => true,
        ],
        'secretaire' => [
            'label' => 'Secrétaire',
            'description' => 'Gestion administrative : étudiants, inscriptions, classes, communication',
            'icon' => 'fa-clipboard-list',
            'group' => 'Administration',
            'visible_in_ui' => true,
        ],
        'responsableScolarite' => [
            'label' => 'Responsable scolarité',
            'description' => 'Valide les documents de scolarite, ouvre les fenetres de notes et pilote le service scolarité. Sans finance ni configuration systeme.',
            'icon' => 'fa-user-check',
            'group' => 'Administration',
            'visible_in_ui' => true,
        ],
        'serviceScolarite' => [
            'label' => 'Service scolarité',
            'description' => 'Saisit les notes pendant la fenetre ouverte par le responsable et imprime les documents approuves. Sans inscriptions ni finance.',
            'icon' => 'fa-print',
            'group' => 'Administration',
            'visible_in_ui' => true,
        ],
        'agentInscription' => [
            'label' => 'Agent d\'inscription',
            'description' => 'Cree, edite et valide les inscriptions apres encaissement. Marque les fournitures deposees. Sans finance, notes, personnel ni configuration systeme.',
            'icon' => 'fa-user-plus',
            'group' => 'Administration',
            'visible_in_ui' => true,
        ],
        'chargeCommunication' => [
            'label' => 'Chargé de communication',
            'description' => 'Annonces, messages et envois MailPulse (email / WhatsApp). Voit les étudiants pour cibler. Sans finance, notes, planning ni configuration système.',
            'icon' => 'fa-bullhorn',
            'group' => 'Communication',
            'visible_in_ui' => true,
        ],
        'comptable' => [
            'label' => 'Comptable',
            'description' => 'Gestion financière complète : paiements, frais, relances, rapports',
            'icon' => 'fa-calculator',
            'group' => 'Finance',
            'visible_in_ui' => true,
        ],
        'caissier' => [
            'label' => 'Caissier',
            'description' => 'Pré-inscriptions et encaissements de paiements',
            'icon' => 'fa-cash-register',
            'group' => 'Finance',
            'visible_in_ui' => true,
        ],
        'coordinateur' => [
            'label' => 'Coordinateur Pédagogique',
            'description' => 'Coordination académique : étudiants, classes, planning, notes',
            'icon' => 'fa-user-tie',
            'group' => 'Pédagogie',
            'visible_in_ui' => true,
        ],
        'directeurEtudes' => [
            'label' => 'Directeur des études',
            'description' => 'Pilotage pédagogique de l\'établissement : classes, notes, bulletins, LMD, planning. Sans finance ni configuration système.',
            'icon' => 'fa-graduation-cap',
            'group' => 'Pédagogie',
            'visible_in_ui' => true,
        ],
        'enseignant' => [
            'label' => 'Enseignant',
            'description' => 'Saisie des notes, prise de présence, accès aux classes assignées',
            'icon' => 'fa-chalkboard-teacher',
            'group' => 'Pédagogie',
            'visible_in_ui' => true,
        ],
        'etudiant' => [
            'label' => 'Étudiant',
            'description' => 'Consultation de ses notes, bulletins, présences et emploi du temps',
            'icon' => 'fa-user-graduate',
            'group' => 'Étudiants',
            'visible_in_ui' => true,
        ],
        'serviceTechnique' => [
            'label' => 'Service Technique',
            'description' => 'Maintenance et support technique (African Digit Consulting)',
            'icon' => 'fa-shield-alt',
            'group' => 'Système',
            'visible_in_ui' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions canoniques (dot.notation)
    |--------------------------------------------------------------------------
    | Format : 'canonical.name' => ['label', 'description', 'group', 'icon', 'aliases' (optionnel)]
    | - aliases : permissions legacy qui pointent vers la canonique pendant la migration
    */

    'permissions' => [

        // ===== Tableau de bord (universel) =====
        'dashboard.view' => [
            'label' => 'Voir le tableau de bord',
            'group' => 'Tableau de bord',
            'icon' => 'fa-tachometer-alt',
            'aliases' => ['view_dashboard'],
        ],

        // ===== Administration =====
        'admin.access' => [
            'label' => 'Accéder à l\'espace administration',
            'group' => 'Administration',
            'icon' => 'fa-cog',
            'aliases' => ['access_admin'],
        ],
        'users.manage' => [
            'label' => 'Gérer les utilisateurs (créer, modifier, supprimer)',
            'group' => 'Administration',
            'icon' => 'fa-users-cog',
            'aliases' => ['manage-users', 'manage_users'],
        ],
        'system.manage' => [
            'label' => 'Gérer la configuration système',
            'group' => 'Administration',
            'icon' => 'fa-server',
            'aliases' => ['manage_system'],
        ],
        'parent_chatbot.manage' => [
            'label' => 'Gérer le chatbot parent',
            'group' => 'Communication',
            'icon' => 'fa-comments',
        ],
        'sod.bypass' => [
            'label' => 'Contourner la separation des devoirs',
            'description' => 'Autorise un acteur habilite a executer une action meme lorsqu une regle maker-checker est active. A reserver aux super administrateurs et procedures de secours.',
            'group' => 'Administration',
            'icon' => 'fa-user-shield',
        ],        'settings.view' => [
            'label' => 'Voir les paramètres',
            'group' => 'Administration',
            'icon' => 'fa-sliders-h',
            'aliases' => ['view_settings'],
        ],
        'settings.edit' => [
            'label' => 'Modifier les paramètres',
            'group' => 'Administration',
            'icon' => 'fa-edit',
            'aliases' => ['edit_settings'],
        ],
        'settings.pdf.manage' => [
            'label' => 'Personnaliser le rendu PDF (couleurs, logo, marges, footer, watermark)',
            'group' => 'Administration',
            'icon' => 'fa-file-pdf',
        ],
        'exports.schedules.manage' => [
            'label' => 'Gérer les exports programmés (créer, modifier, supprimer)',
            'group' => 'Administration',
            'icon' => 'fa-calendar-alt',
        ],
        'exports.schedules.send_external' => [
            'label' => 'Envoyer un export programmé vers un email externe au domaine de l\'école',
            'group' => 'Administration',
            'icon' => 'fa-paper-plane',
        ],

        // ===== Étudiants =====
        'students.view' => [
            'label' => 'Voir tous les étudiants',
            'group' => 'Étudiants',
            'icon' => 'fa-user-graduate',
            'aliases' => ['view_students', 'view students'],
        ],
        'students.view_own' => [
            'label' => 'Voir uniquement ses propres étudiants',
            'group' => 'Étudiants',
            'icon' => 'fa-user',
            'aliases' => ['view_own_students'],
        ],
        'students.create' => [
            'label' => 'Créer un nouvel étudiant',
            'group' => 'Étudiants',
            'icon' => 'fa-user-plus',
            'aliases' => ['create_students'],
        ],
        'students.edit' => [
            'label' => 'Modifier un étudiant',
            'group' => 'Étudiants',
            'icon' => 'fa-user-edit',
            'aliases' => ['edit_students'],
        ],
        'students.delete' => [
            'label' => 'Supprimer un étudiant',
            'group' => 'Étudiants',
            'icon' => 'fa-user-minus',
            'aliases' => ['delete_students'],
        ],
        // ===== Corbeille (sous-lot C+) =====
        'trash.view' => [
            'label' => 'Accéder à la corbeille (étudiants, inscriptions, paiements soft-deleted)',
            'group' => 'Corbeille',
            'icon' => 'fa-trash-restore',
        ],
        'students.restore' => [
            'label' => 'Restaurer un étudiant supprimé',
            'group' => 'Corbeille',
            'icon' => 'fa-rotate-left',
        ],
        'students.force_delete' => [
            'label' => 'Supprimer définitivement un étudiant (corbeille)',
            'description' => 'Action destructive irréversible — efface l\'étudiant et ses dépendances cascade',
            'group' => 'Corbeille',
            'icon' => 'fa-fire',
        ],
        'students.force_delete_cascade' => [
            'label' => 'Forcer suppression cascade d\'un étudiant (corbeille)',
            'description' => 'Action exceptionnelle — supprime l\'étudiant ET ses enfants directement cascadables (inscriptions, paiements non validés, présences). Bloquée si paiements validés actifs OU si notes existent (sauf permission de bypass). Motif texte ≥ 30 chars obligatoire (audit OHADA).',
            'group' => 'Corbeille',
            'icon' => 'fa-skull-crossbones',
        ],
        'students.force_delete_bypass_blocking' => [
            'label' => 'Forcer suppression d\'un étudiant malgré dépendances bloquantes (notes)',
            'description' => 'Permet de contourner le blocage de suppression définitive d\'un étudiant en corbeille quand des notes (ou autres dépendances bloquantes hors paiements validés) existent. Action irréversible avec motif obligatoire ≥ 30 chars. Loggée. Les paiements validés actifs restent toujours bloquants (intégrité OHADA — non bypassable).',
            'group' => 'Corbeille',
            'icon' => 'fa-shield-virus',
        ],
        'inscriptions.restore' => [
            'label' => 'Restaurer une inscription supprimée',
            'group' => 'Corbeille',
            'icon' => 'fa-rotate-left',
        ],
        'inscriptions.force_delete' => [
            'label' => 'Supprimer définitivement une inscription (corbeille)',
            'group' => 'Corbeille',
            'icon' => 'fa-fire',
        ],
        'paiements.restore' => [
            'label' => 'Restaurer un paiement supprimé',
            'group' => 'Corbeille',
            'icon' => 'fa-rotate-left',
        ],
        'paiements.force_delete' => [
            'label' => 'Supprimer définitivement un paiement (corbeille)',
            'group' => 'Corbeille',
            'icon' => 'fa-fire',
        ],

        // ===== Accessibilité étudiants (handicap, aménagements) =====
        'students.accessibility.view' => [
            'label' => 'Voir le résumé d\'accessibilité (aménagements, tiers-temps)',
            'group' => 'Étudiants',
            'icon' => 'fa-universal-access',
        ],
        'students.accessibility.view_full' => [
            'label' => 'Voir le détail médical complet et documents joints',
            'group' => 'Étudiants',
            'icon' => 'fa-notes-medical',
        ],
        'students.accessibility.edit' => [
            'label' => 'Créer ou modifier le profil d\'accessibilité d\'un étudiant',
            'group' => 'Étudiants',
            'icon' => 'fa-edit',
        ],
        'students.accessibility.export' => [
            'label' => 'Inclure les aménagements dans les exports PDF/Excel',
            'group' => 'Étudiants',
            'icon' => 'fa-file-export',
        ],
        'students.accessibility.view_own' => [
            'label' => 'Voir son propre profil d\'accessibilité (étudiant)',
            'group' => 'Étudiants',
            'icon' => 'fa-user-shield',
        ],

        'lmd.credit_wallet.view' => [
            'label' => 'Voir le portefeuille de crédits LMD',
            'description' => 'Consulter les crédits capitalisés issus des bulletins LMD publiés',
            'group' => 'LMD',
            'icon' => 'fa-award',
        ],

        // ===== Inscriptions =====
        'inscriptions.view' => [
            'label' => 'Voir les inscriptions',
            'group' => 'Inscriptions',
            'icon' => 'fa-file-signature',
            'aliases' => ['view_inscriptions'],
        ],
        'inscriptions.create' => [
            'label' => 'Créer une nouvelle inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-plus',
            'aliases' => ['create_inscriptions'],
        ],
        'inscriptions.edit' => [
            'label' => 'Modifier une inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-edit',
            'aliases' => ['edit_inscriptions', 'edit inscriptions'],
        ],
        'inscriptions.in_kind.mark' => [
            'label' => 'Marquer une fourniture déposée (ramette, chemise) sans accès finance',
            'group' => 'Inscriptions',
            'icon' => 'fa-box',
        ],
        'inscriptions.validate' => [
            'label' => 'Valider une inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-check-circle',
            'aliases' => ['valider inscriptions', 'approve_inscriptions'],
        ],
        'inscriptions.cancel' => [
            'label' => 'Annuler une inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-ban',
            'aliases' => ['annuler inscriptions'],
        ],
        'inscriptions.reject' => [
            'label' => 'Rejeter une inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-times-circle',
            'aliases' => ['reject_inscriptions'],
        ],
        'inscriptions.delete' => [
            'label' => 'Supprimer une inscription',
            'group' => 'Inscriptions',
            'icon' => 'fa-trash',
            'aliases' => ['delete_inscriptions', 'delete inscriptions'],
        ],
        'reinscriptions.demandes.view' => [
            'label' => 'Voir les demandes de réinscription en ligne',
            'group' => 'Inscriptions',
            'icon' => 'fa-inbox',
        ],
        'reinscriptions.demandes.process' => [
            'label' => 'Traiter une demande de réinscription en ligne',
            'group' => 'Inscriptions',
            'icon' => 'fa-user-check',
        ],
        'inscriptions.candidatures.view' => [
            'label' => 'Voir les candidatures des nouveaux étudiants',
            'group' => 'Inscriptions',
            'icon' => 'fa-address-card',
        ],
        'inscriptions.candidatures.process' => [
            'label' => 'Traiter une candidature de nouvel étudiant',
            'group' => 'Inscriptions',
            'icon' => 'fa-user-plus',
        ],
        'inscriptions.manage' => [
            'label' => 'Gérer toutes les inscriptions (action globale)',
            'group' => 'Inscriptions',
            'icon' => 'fa-tasks',
        ],
        'inscriptions.pieces.view' => [
            'label' => 'Voir les pièces du dossier d\'une inscription',
            'description' => 'Affiche le panneau « Pièces du dossier » sur la fiche d\'inscription : liste des pièces attendues et compteur',
            'group' => 'Inscriptions',
            'icon' => 'fa-folder-open',
        ],
        'inscriptions.pieces.manage' => [
            'label' => 'Marquer une pièce du dossier comme fournie',
            'description' => 'Coche et décoche les pièces reçues au guichet. N\'autorise ni la validation de l\'inscription ni la configuration du catalogue',
            'group' => 'Inscriptions',
            'icon' => 'fa-clipboard-check',
        ],
        'inscriptions.specialisation.manage' => [
            'label' => 'Orienter un étudiant en Tronc Commun vers une spécialité (BTS UEMOA)',
            'description' => 'Permet l\'accès à /specialisation (workflow officiel TC → spécialisation : choix filière + classe cible, transition tracée + audit)',
            'group' => 'Inscriptions',
            'icon' => 'fa-route',
        ],

        // ===== Cycles =====
        'cycles.view' => [
            'label' => 'Voir les cycles',
            'group' => 'Académique',
            'icon' => 'fa-sitemap',
            'aliases' => ['view cycles'],
        ],
        'cycles.create' => [
            'label' => 'Créer un cycle',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create cycles'],
        ],
        'cycles.edit' => [
            'label' => 'Modifier un cycle',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit cycles'],
        ],
        'cycles.delete' => [
            'label' => 'Supprimer un cycle',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete cycles'],
        ],
        'cycles.restore' => [
            'label' => 'Restaurer un cycle supprimé',
            'group' => 'Académique',
            'icon' => 'fa-undo',
            'aliases' => ['restore cycles'],
        ],
        'cycles.force_delete' => [
            'label' => 'Suppression définitive d\'un cycle',
            'group' => 'Académique',
            'icon' => 'fa-fire',
            'aliases' => ['force delete cycles'],
        ],

        // ===== Classes =====
        'classes.view' => [
            'label' => 'Voir les classes',
            'group' => 'Académique',
            'icon' => 'fa-school',
            'aliases' => ['view_classes', 'view classes'],
        ],
        'classes.create' => [
            'label' => 'Créer une classe',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create_classes', 'create_classe', 'create classes'],
        ],
        'classes.edit' => [
            'label' => 'Modifier une classe',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit_classes', 'edit classes'],
        ],
        'classes.delete' => [
            'label' => 'Supprimer une classe',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete_classes', 'delete classes'],
        ],
        'bts_tronc_commun.view' => [
            'label' => 'Voir le parcours BTS tronc commun',
            'group' => 'Académique',
            'icon' => 'fa-road',
        ],
        'bts_tronc_commun.orient' => [
            'label' => 'Orienter un étudiant BTS tronc commun',
            'group' => 'Académique',
            'icon' => 'fa-route',
        ],
        'bts_tronc_commun.manage_targets' => [
            'label' => 'Configurer les sorties BTS tronc commun',
            'group' => 'Académique',
            'icon' => 'fa-diagram-project',
        ],
        'bts_tronc_commun.view_history' => [
            'label' => "Voir l'historique BTS tronc commun",
            'group' => 'Académique',
            'icon' => 'fa-clock-rotate-left',
        ],

        // ===== Filières =====
        'filieres.view' => [
            'label' => 'Voir les filières',
            'group' => 'Académique',
            'icon' => 'fa-stream',
            'aliases' => ['view_filieres', 'view filieres'],
        ],
        'filieres.create' => [
            'label' => 'Créer une filière',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create_filieres'],
        ],
        'filieres.edit' => [
            'label' => 'Modifier une filière',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit_filieres'],
        ],
        'filieres.delete' => [
            'label' => 'Supprimer une filière',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete_filieres'],
        ],

        // ===== Années universitaires =====
        'annees.view' => [
            'label' => 'Voir les années universitaires',
            'group' => 'Académique',
            'icon' => 'fa-calendar-alt',
            'aliases' => ['view_annees_universitaires'],
        ],
        'annees.create' => [
            'label' => 'Créer une année universitaire',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create_annees_universitaires'],
        ],
        'annees.edit' => [
            'label' => 'Modifier une année universitaire',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit_annees_universitaires'],
        ],
        'annees.delete' => [
            'label' => 'Supprimer une année universitaire',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete_annees_universitaires'],
        ],
        'annees.set_current' => [
            'label' => 'Définir l\'année universitaire en cours',
            'group' => 'Académique',
            'icon' => 'fa-star',
            'aliases' => ['set_current_annee_universitaire'],
        ],

        // ===== Niveaux d'études =====
        'niveaux.view' => [
            'label' => 'Voir les niveaux d\'études',
            'group' => 'Académique',
            'icon' => 'fa-layer-group',
            'aliases' => ['view_niveaux_etudes'],
        ],
        'niveaux.create' => [
            'label' => 'Créer un niveau d\'études',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create_niveaux_etudes'],
        ],
        'niveaux.edit' => [
            'label' => 'Modifier un niveau d\'études',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit_niveaux_etudes'],
        ],
        'niveaux.delete' => [
            'label' => 'Supprimer un niveau d\'études',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete_niveaux_etudes'],
        ],

        // ===== Matières =====
        'matieres.view' => [
            'label' => 'Voir les matières',
            'group' => 'Académique',
            'icon' => 'fa-book',
            'aliases' => ['view_matieres', 'view matieres'],
        ],
        'matieres.create' => [
            'label' => 'Créer une matière',
            'group' => 'Académique',
            'icon' => 'fa-plus',
            'aliases' => ['create_matieres'],
        ],
        'matieres.edit' => [
            'label' => 'Modifier une matière',
            'group' => 'Académique',
            'icon' => 'fa-edit',
            'aliases' => ['edit_matieres', 'edit matieres'],
        ],
        'matieres.delete' => [
            'label' => 'Supprimer une matière',
            'group' => 'Académique',
            'icon' => 'fa-trash',
            'aliases' => ['delete_matieres', 'delete matieres'],
        ],

        // ===== Notes & évaluations =====
        'notes.view' => [
            'label' => 'Voir toutes les notes',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-clipboard-list',
            'aliases' => ['view_notes', 'view_grades'],
        ],
        'notes.view_own' => [
            'label' => 'Voir ses propres notes',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-clipboard-check',
            'aliases' => ['view_own_notes', 'view_own_grades'],
        ],
        'notes.create' => [
            'label' => 'Saisir une note',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-plus',
            'aliases' => ['create_notes', 'create_grades', 'create_grade'],
        ],
        'notes.edit' => [
            'label' => 'Modifier une note',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-edit',
            'aliases' => ['edit_notes', 'edit_grades', 'edit_existing_notes'],
        ],
        'notes.delete' => [
            'label' => 'Supprimer une note',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-trash',
            'aliases' => ['delete_grades'],
        ],
        'notes.manage_own' => [
            'label' => 'Gérer ses propres notes saisies',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-cog',
            'aliases' => ['manage_own_notes'],
        ],
        'notes.import_excel' => [
            'label' => 'Importer des notes depuis un fichier Excel',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-file-import',
            'aliases' => [],
        ],

        // ===== Évaluations =====
        'evaluations.view' => [
            'label' => 'Voir les évaluations',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-tasks',
            'aliases' => ['view_evaluations'],
        ],
        'evaluations.create' => [
            'label' => 'Créer une évaluation',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-plus',
            'aliases' => ['create_evaluations'],
        ],
        'evaluations.edit' => [
            'label' => 'Modifier une évaluation',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-edit',
            'aliases' => ['edit_evaluations'],
        ],
        'evaluations.edit_locked' => [
            'label' => 'Modifier une évaluation verrouillée (en cours, terminée, annulée)',
            'description' => "Bypass la règle métier qui interdit la modification d'une évaluation déjà passée. À donner avec parcimonie (correction typos urgents, recalibrage tardif).",
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-unlock',
        ],
        'exams.view' => [
            'label' => 'Voir les examens',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-graduation-cap',
            'aliases' => ['view_exams'],
        ],
        'exams.view_own' => [
            'label' => 'Voir ses propres examens',
            'group' => 'Notes & Évaluations',
            'icon' => 'fa-graduation-cap',
            'aliases' => ['view_own_exams'],
        ],

        // ===== Pilotage académique =====
        'academic_pilotage.view' => [
            'label' => 'Voir le pilotage académique',
            'group' => 'Pilotage académique',
            'icon' => 'fa-chart-line',
        ],
        'academic_pilotage.view_all' => [
            'label' => 'Voir le pilotage académique de toutes les filières',
            'group' => 'Pilotage académique',
            'icon' => 'fa-layer-group',
        ],
        'academic_pilotage.configure' => [
            'label' => 'Configurer le pilotage académique',
            'group' => 'Pilotage académique',
            'icon' => 'fa-sliders-h',
        ],
        'academic_sheets.view' => [
            'label' => 'Voir toutes les fiches académiques',
            'group' => 'Fiches académiques',
            'icon' => 'fa-clipboard-list',
        ],
        'academic_sheets.view_own' => [
            'label' => 'Voir les fiches académiques de son périmètre',
            'group' => 'Fiches académiques',
            'icon' => 'fa-clipboard',
        ],
        'academic_sheets.create' => [
            'label' => 'Créer une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-plus',
        ],
        'academic_sheets.submit' => [
            'label' => 'Soumettre une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-paper-plane',
        ],
        'academic_sheets.receive' => [
            'label' => 'Réceptionner une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-inbox',
        ],
        'academic_sheets.enter' => [
            'label' => 'Saisir les notes d\'une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-keyboard',
        ],
        'academic_sheets.control' => [
            'label' => 'Contrôler une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-clipboard-check',
        ],
        'academic_sheets.validate' => [
            'label' => 'Valider une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-check-double',
        ],
        'academic_sheets.assign' => [
            'label' => 'Affecter une fiche académique',
            'group' => 'Fiches académiques',
            'icon' => 'fa-user-check',
        ],
        'academic_alerts.view' => [
            'label' => 'Voir les alertes académiques',
            'group' => 'Alertes académiques',
            'icon' => 'fa-bell',
        ],
        'academic_alerts.view_own' => [
            'label' => 'Voir les alertes académiques de son périmètre',
            'group' => 'Alertes académiques',
            'icon' => 'fa-bell',
        ],
        'academic_alerts.acknowledge' => [
            'label' => 'Prendre en charge une alerte académique',
            'group' => 'Alertes académiques',
            'icon' => 'fa-eye',
        ],
        'academic_alerts.resolve' => [
            'label' => 'Résoudre une alerte académique',
            'group' => 'Alertes académiques',
            'icon' => 'fa-check-circle',
        ],
        'academic_health.view' => [
            'label' => 'Voir les indicateurs de santé académique',
            'group' => 'Santé académique',
            'icon' => 'fa-heartbeat',
        ],
        'academic_health.view_own' => [
            'label' => 'Voir la santé académique de son périmètre',
            'group' => 'Santé académique',
            'icon' => 'fa-heartbeat',
        ],
        'academic_health.recalculate' => [
            'label' => 'Recalculer les indicateurs de santé académique',
            'group' => 'Santé académique',
            'icon' => 'fa-sync-alt',
        ],

        // ===== Bulletins =====
        'bulletins.view' => [
            'label' => 'Voir les bulletins',
            'group' => 'Bulletins',
            'icon' => 'fa-file-alt',
            'aliases' => ['view_bulletins'],
        ],
        'bulletins.view_own' => [
            'label' => 'Voir son propre bulletin',
            'group' => 'Bulletins',
            'icon' => 'fa-file-alt',
            'aliases' => ['view_own_bulletin'],
        ],
        'bulletins.create' => [
            'label' => 'Créer un bulletin',
            'group' => 'Bulletins',
            'icon' => 'fa-plus',
            'aliases' => ['create_bulletins'],
        ],
        'bulletins.generate' => [
            'label' => 'Générer un bulletin',
            'group' => 'Bulletins',
            'icon' => 'fa-cogs',
            'aliases' => ['generate_bulletins', 'generate_bulletin'],
        ],
        'bulletins.generate_incomplete' => [
            'label' => 'Générer un bulletin avec des notes incomplètes',
            'group' => 'Bulletins',
            'icon' => 'fa-exclamation-triangle',
        ],
        'bulletins.edit' => [
            'label' => 'Modifier un bulletin',
            'group' => 'Bulletins',
            'icon' => 'fa-edit',
            'aliases' => ['edit_bulletins'],
        ],
        'bulletins.delete' => [
            'label' => 'Supprimer un bulletin',
            'group' => 'Bulletins',
            'icon' => 'fa-trash',
            'aliases' => ['delete_bulletins'],
        ],
        'bulletins.configure' => [
            'label' => 'Configurer le format des bulletins',
            'group' => 'Bulletins',
            'icon' => 'fa-sliders-h',
            'aliases' => ['bulletin.configure'],
        ],
        'bulletins.publish.bulk' => [
            'label' => 'Publier des bulletins en masse',
            'group' => 'Bulletins',
            'icon' => 'fa-paper-plane',
        ],
        'bulletins.regenerate.bulk' => [
            'label' => 'Régénérer des bulletins en masse',
            'group' => 'Bulletins',
            'icon' => 'fa-arrows-rotate',
        ],
        'bulletins.export.bulk' => [
            'label' => 'Exporter les bulletins filtrés en un seul PDF',
            'group' => 'Bulletins',
            'icon' => 'fa-file-export',
        ],

        // ===== Présences =====
        'attendances.view' => [
            'label' => 'Voir les présences',
            'group' => 'Présences',
            'icon' => 'fa-clipboard-check',
            'aliases' => ['view_attendances'],
        ],
        'attendances.view_own' => [
            'label' => 'Voir ses propres présences',
            'group' => 'Présences',
            'icon' => 'fa-clipboard-check',
            'aliases' => ['view_own_attendances', 'view_own_attendance'],
        ],
        'attendances.create' => [
            'label' => 'Saisir une présence',
            'group' => 'Présences',
            'icon' => 'fa-plus',
            'aliases' => ['create_attendance', 'create_attendances'],
        ],
        'attendances.edit' => [
            'label' => 'Modifier une présence',
            'group' => 'Présences',
            'icon' => 'fa-edit',
            'aliases' => ['edit_attendances'],
        ],
        'attendances.delete' => [
            'label' => 'Supprimer une présence',
            'group' => 'Présences',
            'icon' => 'fa-trash',
            'aliases' => ['delete_attendances'],
        ],
        'attendances.sign' => [
            'label' => 'Signer une feuille de présence',
            'group' => 'Présences',
            'icon' => 'fa-signature',
            'aliases' => ['sign_attendance'],
        ],
        'attendances.generate_codes' => [
            'label' => 'Générer les codes d\'émargement',
            'group' => 'Présences',
            'icon' => 'fa-qrcode',
            'aliases' => ['generate-attendance-codes', 'generate-attendance-code'],
        ],
        'attendances.view_reports' => [
            'label' => 'Voir les rapports de présence',
            'group' => 'Présences',
            'icon' => 'fa-chart-bar',
            'aliases' => ['view-attendance-reports'],
        ],
        'attendances.view_statistics' => [
            'label' => 'Voir les statistiques de présence',
            'group' => 'Présences',
            'icon' => 'fa-chart-pie',
            'aliases' => ['view-attendance-statistics'],
        ],
        'attendances.justify_own' => [
            'label' => 'Justifier ses propres absences (étudiant)',
            'description' => 'Permet à l\'étudiant de soumettre une justification + document pour ses absences',
            'group' => 'Présences',
            'icon' => 'fa-file-medical',
        ],
        'attendances.justify_process' => [
            'label' => 'Traiter les justifications d\'absence',
            'description' => 'Permet de valider ou rejeter une justification soumise par un étudiant',
            'group' => 'Présences',
            'icon' => 'fa-clipboard-check',
        ],
        'session_reports.view' => [
            'label' => 'Voir tous les rapports de cours soumis par les enseignants',
            'group' => 'Présences',
            'icon' => 'fa-file-alt',
        ],
        'session_reports.view_own' => [
            'label' => 'Voir uniquement ses propres rapports de cours',
            'group' => 'Présences',
            'icon' => 'fa-file-alt',
        ],

        // ===== Paiements =====
        'paiements.view' => [
            'label' => 'Voir tous les paiements',
            'group' => 'Paiements',
            'icon' => 'fa-money-bill',
            'aliases' => ['view_payments'],
        ],
        'paiements.view_own' => [
            'label' => 'Voir uniquement ses propres paiements créés',
            'group' => 'Paiements',
            'icon' => 'fa-user-tag',
            'aliases' => ['view_own_payments'],
        ],
        'paiements.export' => [
            'label' => 'Exporter les paiements (PDF / Excel)',
            'group' => 'Paiements',
            'icon' => 'fa-download',
        ],
        'paiements.create' => [
            'label' => 'Enregistrer un paiement',
            'group' => 'Paiements',
            'icon' => 'fa-plus',
            'aliases' => ['create_payments', 'create-paiements'],
        ],
        'paiements.create.mobile_money' => [
            'label' => 'Enregistrer un paiement mobile money',
            'description' => 'Autorise uniquement Wave, Orange Money, MTN, Moov et le mobile money generique. La caisse garde tous les modes via paiements.create.',
            'group' => 'Paiements',
            'icon' => 'fa-mobile-alt',
        ],
        'paiements.edit' => [
            'label' => 'Modifier un paiement',
            'group' => 'Paiements',
            'icon' => 'fa-edit',
            'aliases' => ['edit_payments', 'edit-paiements'],
        ],
        'paiements.delete' => [
            'label' => 'Supprimer un paiement',
            'group' => 'Paiements',
            'icon' => 'fa-trash',
        ],
        'paiements.validate' => [
            'label' => 'Valider un paiement',
            'group' => 'Paiements',
            'icon' => 'fa-check',
        ],
        'paiements.validate.self_override' => [
            'label' => 'Auto-valider ses propres paiements (exception, déconseillé)',
            'description' => 'Permet à un user de valider un paiement qu\'il a lui-même créé. Bypasse le principe de séparation des tâches (anti-fraude). À réserver aux très petites écoles avec un seul user comptable.',
            'group' => 'Paiements',
            'icon' => 'fa-shield-virus',
        ],
        'paiements.reventiler' => [
            'label' => 'Corriger la ventilation d\'un paiement (sur quels frais il est imputé)',
            'description' => 'Réécrit la répartition d\'un versement déjà encaissé entre les frais, sans en changer le montant ni le numéro de reçu. Sert quand un versement a été imputé au mauvais frais, ou quand le bon frais n\'existait pas encore. Écriture sur de l\'argent déjà reçu : motif obligatoire, ancienne et nouvelle ventilation conservées à l\'audit.',
            'group' => 'Paiements',
            'icon' => 'fa-code-branch',
        ],
        'paiements.manage' => [
            'label' => 'Gérer tous les paiements (action globale)',
            'group' => 'Paiements',
            'icon' => 'fa-tasks',
        ],
        // Un versement valide ne se reecrit plus depuis l'ecran de saisie, et
        // c'est la bonne regle : une ecriture comptable ne se retouche pas.
        //
        // Mais le mode de reglement n'est pas une ecriture, c'est une
        // etiquette : elle ne change ni le montant, ni le frais, ni l'etudiant,
        // ni ce que doit l'ecole. Une erreur de saisie au guichet — cheque coche
        // a la place d'especes — n'avait pourtant aucune issue autre qu'une
        // session de reconciliation entiere, disproportionnee pour un champ.
        //
        // Le montant, lui, reste hors de portee : le corriger changerait ce que
        // l'etudiant a paye, et cela releve bien de la reconciliation.
        'paiements.correct_mode' => [
            'label' => 'Corriger le mode de règlement d\'un versement validé',
            'description' => 'Permet de rectifier une erreur de saisie sur le mode (espèces, chèque, mobile money…) d\'un versement déjà validé, sans toucher au montant, au frais ni à la date. Motif obligatoire, et la correction est inscrite au journal d\'audit avec l\'ancienne et la nouvelle valeur. Reste bloquée sur une période comptable clôturée et sur un versement déjà réconcilié.',
            'group' => 'Paiements',
            'icon' => 'fa-right-left',
        ],

        'paiements.avoir' => [
            'label' => 'Émettre un avoir (crédit ou remboursement)',
            'description' => 'Pièce inverse liée à un paiement validé. Crédit = réduit le dû sans sortir de caisse. Remboursement = sortie de caisse au journal du jour. Porte sur N\'IMPORTE QUEL versement, y compris ceux d\'un autre agent.',
            'group' => 'Paiements',
            'icon' => 'fa-file-invoice',
        ],

        // Cette permission n'est accordée à personne par défaut, et c'est
        // volontaire.
        //
        // La fenêtre anti-erreur existait sans droit propre : elle découlait de
        // « paiements.create », donc tout agent de caisse pouvait effacer son
        // encaissement des cinq dernières minutes. Une école qui ne veut pas
        // qu'un guichet défasse ses propres écritures n'avait aucun moyen de le
        // dire, sinon retirer le droit d'encaisser.
        //
        // Le geste reste utile là où on le veut : une somme mal saisie se
        // corrige mieux à chaud que par un avoir. Mais c'est désormais une
        // décision d'établissement, prise dans la configuration des rôles, pas
        // un effet de bord du droit d'encaisser.
        'paiements.cancel_own' => [
            'label' => 'Annuler son propre versement récent (fenêtre anti-erreur)',
            'description' => 'Permet à l\'agent qui vient de saisir un versement encore EN ATTENTE de l\'annuler lui-même, dans la fenêtre définie par le réglage « comptabilite.cancel_own_window_minutes » (5 minutes par défaut). Ne porte jamais sur un versement validé, ni sur celui d\'un autre agent. Non accordée par défaut.',
            'group' => 'Paiements',
            'icon' => 'fa-rotate-left',
        ],

        // ===== Frais =====
        'frais.view' => [
            'label' => 'Voir les frais',
            'group' => 'Frais',
            'icon' => 'fa-euro-sign',
        ],
        'frais.create' => [
            'label' => 'Créer une catégorie de frais',
            'group' => 'Frais',
            'icon' => 'fa-plus',
        ],
        'frais.edit' => [
            'label' => 'Modifier une catégorie de frais',
            'group' => 'Frais',
            'icon' => 'fa-edit',
        ],
        'frais.delete' => [
            'label' => 'Supprimer une catégorie de frais',
            'group' => 'Frais',
            'icon' => 'fa-trash',
        ],
        'frais.configure' => [
            'label' => 'Configurer le détail des frais d\'une catégorie',
            'group' => 'Frais',
            'icon' => 'fa-cog',
        ],

        // ===== Comptabilité =====
        'comptabilite.access' => [
            'label' => 'Accès à l\'espace Comptabilité',
            'group' => 'Comptabilité',
            'icon' => 'fa-calculator',
            'aliases' => ['access_comptabilite_module'],
        ],
        'comptabilite.manage' => [
            'label' => 'Gérer la comptabilité (action globale)',
            'group' => 'Comptabilité',
            'icon' => 'fa-tasks',
        ],
        'comptabilite.dashboard.view' => [
            'label' => 'Voir le tableau de bord comptable',
            'group' => 'Comptabilité',
            'icon' => 'fa-chart-line',
        ],
        'comptabilite.analytics.view' => [
            'label' => 'Voir les prédictions analytics (cash-flow, défauts, anomalies)',
            'group' => 'Comptabilité',
            'icon' => 'fa-brain',
        ],
        'comptabilite.analytics.refresh' => [
            'label' => 'Forcer le recalcul synchrone des prédictions analytics',
            'group' => 'Comptabilité',
            'icon' => 'fa-sync',
        ],
        'comptabilite.analytics.run_now' => [
            'label' => 'Déclencher manuellement le job de calcul analytics',
            'group' => 'Comptabilité',
            'icon' => 'fa-play',
        ],
        'comptabilite.analytics.configure' => [
            'label' => 'Configurer les seuils & poids du moteur analytics',
            'group' => 'Comptabilité',
            'icon' => 'fa-sliders-h',
        ],
        'comptabilite.recouvrement.access' => [
            'label' => 'Accéder à la page Recouvrement quotidien (liste actionnable)',
            'group' => 'Comptabilité',
            'icon' => 'fa-hand-holding-usd',
        ],
        'comptabilite.relances.send' => [
            'label' => 'Envoyer des relances de paiement',
            'group' => 'Comptabilité',
            'icon' => 'fa-paper-plane',
        ],
        'comptabilite.reports.export' => [
            'label' => 'Exporter les rapports comptables',
            'group' => 'Comptabilité',
            'icon' => 'fa-download',
        ],
        'comptabilite.config.manage' => [
            'label' => 'Gérer la configuration comptable',
            'group' => 'Comptabilité',
            'icon' => 'fa-sliders-h',
        ],
        'comptabilite.paiements.view' => [
            'label' => 'Voir les paiements (espace comptable)',
            'group' => 'Comptabilité',
            'icon' => 'fa-eye',
        ],
        'comptabilite.paiements.validate' => [
            'label' => 'Valider les paiements (espace comptable)',
            'group' => 'Comptabilité',
            'icon' => 'fa-check-double',
        ],
        'comptabilite.frais.view' => [
            'label' => 'Voir les frais (espace comptable)',
            'group' => 'Comptabilité',
            'icon' => 'fa-eye',
        ],
        'comptabilite.frais.configure' => [
            'label' => 'Configurer les frais (espace comptable)',
            'group' => 'Comptabilité',
            'icon' => 'fa-cog',
        ],
        'comptabilite.audit.view' => [
            'label' => 'Voir le journal d\'audit comptable',
            'description' => 'Permet de consulter qui a créé/modifié/validé/rejeté les paiements et configuré les frais. Permission assignable à un rôle custom (ex: Directeur Financier, Auditeur Interne) via /esbtp/custom-roles.',
            'group' => 'Comptabilité',
            'icon' => 'fa-history',
        ],
        'comptabilite.period.close' => [
            'label' => 'Verrouiller / déverrouiller une période comptable',
            'description' => 'Permet de fermer un mois (ou plus) pour empêcher toute modification rétroactive. Une fois la période verrouillée, plus aucun paiement antérieur ne peut être modifié, supprimé ou rejeté. Garantit la traçabilité comptable / fiscalité.',
            'group' => 'Comptabilité',
            'icon' => 'fa-lock',
        ],
        'comptabilite.period.bypass_lock' => [
            'label' => 'Contourner le verrouillage de période (exception)',
            'description' => 'Permet de modifier un paiement antérieur à la date de verrouillage. À réserver à un cas d\'erreur exceptionnel (ex: correction d\'une faute frappe découverte tardivement). Toujours loggé.',
            'group' => 'Comptabilité',
            'icon' => 'fa-key',
        ],
        'comptabilite.reconciliation.view' => [
            'label' => 'Voir les sessions de réconciliation caisse',
            'description' => 'Donne accès à la liste et au détail des sessions de réconciliation. N\'autorise pas à modifier — lecture seule.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-balance-scale',
        ],
        'comptabilite.reconciliation.open' => [
            'label' => 'Ouvrir une session de réconciliation',
            'description' => 'Permet de démarrer une nouvelle session quotidienne / hebdomadaire / mensuelle de bouclage caisse. L\'ouvreur saisit ensuite ses comptages physiques par mode.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-folder-open',
        ],
        'comptabilite.reconciliation.resolve' => [
            'label' => 'Résoudre les écarts de réconciliation',
            'description' => 'Permet d\'agir sur les écarts détectés : ajuster un paiement existant, créer un correctif, annuler, ou assumer l\'écart avec motif.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-tools',
        ],
        'comptabilite.reconciliation.approve' => [
            'label' => 'Approuver une session de réconciliation (2e validation)',
            'description' => 'Permet la 2e validation (séparation des devoirs OHADA) d\'une session après revue. Si le setting `comptabilite.reconciliation.require_separation_of_duties` est activé, l\'approbateur DOIT être différent de l\'ouvreur.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-check-double',
        ],
        'comptabilite.reconciliation.export' => [
            'label' => 'Exporter le PV de réconciliation',
            'description' => 'Génère et télécharge le PV PDF officiel signable d\'une session clôturée. Pour archivage légal et contrôle fiscal.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-file-pdf',
        ],
        'comptabilite.reconciliation.bypass_lock' => [
            'label' => 'Forcer modification d\'un paiement réconcilié (exception)',
            'description' => 'Une fois qu\'un paiement est verrouillé par une réconciliation clôturée, il n\'est plus modifiable. Cette permission permet le bypass exceptionnel, toujours loggé.',
            'group' => 'Comptabilité — Réconciliation',
            'icon' => 'fa-key',
        ],
        'comptabilite.notifications.high_amount' => [
            'label' => 'Recevoir une notification quand un gros paiement est validé',
            'description' => 'Notification email + cloche déclenchée à chaque validation de paiement supérieur au seuil tenant configuré (default 5 000 000 FCFA). Vise typiquement le directeur ou le directeur financier qui surveille les grosses entrées sans être lui-même comptable. Permission assignable via UI custom roles.',
            'group' => 'Comptabilité',
            'icon' => 'fa-bell',
        ],
        'comptabilite.journal.view' => [
            'label' => 'Consulter le journal de caisse OHADA',
            'description' => 'Donne accès au journal des recettes chronologique, conforme aux standards OHADA. Affiche par défaut les encaissements du mois en cours avec filtres par période/filière/mode de paiement. Exportable en PDF format officiel. Permission assignable à un rôle custom (Directeur Financier, Auditeur Externe).',
            'group' => 'Comptabilité',
            'icon' => 'fa-book',
        ],
        'comptabilite.sensitive.access' => [
            'label' => 'Accès aux données comptables sensibles',
            'group' => 'Comptabilité',
            'icon' => 'fa-lock',
        ],

        // ===== Comptabilité — Paie enseignants =====
        'comptabilite.salaires.view' => [
            'label' => 'Voir la paie des enseignants',
            'description' => 'Donne accès à la page « Paie enseignants » : heures facturables, taux, montants dûs, bulletins. Lecture seule. Assignable à un rôle custom (Directeur Financier, Auditeur).',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-money-check-dollar',
        ],
        'comptabilite.salaires.create' => [
            'label' => 'Préparer / calculer la paie d\'un enseignant',
            'description' => 'Permet de générer un bulletin de paie pour une période : calcul des heures réelles × taux par type, primes, retenues (impôt ITS, CNPS…). Statut « en attente » jusqu\'à validation.',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-calculator',
        ],
        'comptabilite.salaires.validate' => [
            'label' => 'Valider la paie (2e validation)',
            'description' => 'Validation OHADA (séparation des devoirs) d\'un bulletin préparé. Par défaut, le validateur DOIT être différent du préparateur, sauf si le rôle dispose aussi de « Valider sa propre paie ».',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-check-double',
        ],
        'comptabilite.salaires.validate_own' => [
            'label' => 'Valider sa propre préparation de paie',
            'description' => 'Lève la séparation des devoirs : permet à la même personne de préparer ET valider un bulletin (pour les petites écoles à une seule comptable). À attribuer avec discernement.',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-user-check',
        ],
        'comptabilite.salaires.pay' => [
            'label' => 'Marquer une paie comme payée',
            'description' => 'Enregistre le règlement effectif d\'un bulletin validé (date, mode, référence). Verrouille le bulletin.',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-hand-holding-dollar',
        ],
        'comptabilite.salaires.configure' => [
            'label' => 'Configurer les paramètres de paie',
            'description' => 'Règle le barème fiscal d\'imposition (ITS), le taux CNPS, la période de grâce de ponctualité et les options de dépassement horaire payé.',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-sliders',
        ],
        'comptabilite.salaires.export' => [
            'label' => 'Exporter la paie (PDF / Excel)',
            'description' => 'Génère et télécharge les bulletins de paie et états récapitulatifs.',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-file-export',
        ],
        'comptabilite.salaires.set_rate' => [
            'label' => 'Définir les taux horaires d\'un enseignant',
            'description' => 'Rend visible et modifiable la section « Rémunération / taux par type de séance » dans la fiche enseignant. Sans cette permission, un coordinateur peut éditer un enseignant sans voir les montants (séparation pédagogie / comptabilité).',
            'group' => 'Comptabilité — Paie enseignants',
            'icon' => 'fa-coins',
        ],

        // ===== Personnel & enseignants =====
        'teachers.view' => [
            'label' => 'Voir les enseignants',
            'group' => 'Personnel',
            'icon' => 'fa-chalkboard-teacher',
            'aliases' => ['view_teachers', 'view teachers'],
        ],
        'teachers.create' => [
            'label' => 'Créer un enseignant',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_teachers'],
        ],
        'teachers.edit' => [
            'label' => 'Modifier un enseignant',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_teachers', 'edit_enseignants'],
        ],
        'teachers.delete' => [
            'label' => 'Supprimer un enseignant',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_teachers', 'delete_enseignants'],
        ],
        'personnel.view' => [
            'label' => 'Voir le personnel',
            'group' => 'Personnel',
            'icon' => 'fa-users',
            'aliases' => ['view_personnel'],
        ],
        'personnel.manage' => [
            'label' => 'Gérer le personnel',
            'group' => 'Personnel',
            'icon' => 'fa-users-cog',
            'aliases' => ['manage_personnel'],
        ],
        'profile.view_own' => [
            'label' => 'Voir son propre profil',
            'group' => 'Personnel',
            'icon' => 'fa-user',
            'aliases' => ['view_own_profile'],
        ],

        // ===== Coordinateurs =====
        'coordinateurs.view' => [
            'label' => 'Voir les coordinateurs',
            'group' => 'Personnel',
            'icon' => 'fa-user-tie',
            'aliases' => ['view_coordinateurs'],
        ],
        'coordinateurs.create' => [
            'label' => 'Créer un coordinateur',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_coordinateurs'],
        ],
        'coordinateurs.edit' => [
            'label' => 'Modifier un coordinateur',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_coordinateurs'],
        ],
        'coordinateurs.delete' => [
            'label' => 'Supprimer un coordinateur',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_coordinateurs'],
        ],
        'directeurs_etudes.view' => [
            'label' => 'Voir les directeurs des études',
            'group' => 'Personnel',
            'icon' => 'fa-graduation-cap',
            'aliases' => ['view_directeurs_etudes'],
        ],
        'directeurs_etudes.create' => [
            'label' => 'Créer un directeur des études',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_directeurs_etudes'],
        ],
        'directeurs_etudes.edit' => [
            'label' => 'Modifier un directeur des études',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_directeurs_etudes'],
        ],
        'directeurs_etudes.delete' => [
            'label' => 'Supprimer un directeur des études',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_directeurs_etudes'],
        ],
        'responsables_scolarite.view' => [
            'label' => 'Voir les responsables scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-user-check',
        ],
        'responsables_scolarite.create' => [
            'label' => 'Creer un responsable scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
        ],
        'responsables_scolarite.edit' => [
            'label' => 'Modifier un responsable scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
        ],
        'responsables_scolarite.delete' => [
            'label' => 'Supprimer un responsable scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
        ],
        'services_scolarite.view' => [
            'label' => 'Voir le service scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-print',
        ],
        'services_scolarite.create' => [
            'label' => 'Creer un agent du service scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
        ],
        'services_scolarite.edit' => [
            'label' => 'Modifier un agent du service scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
        ],
        'services_scolarite.delete' => [
            'label' => 'Supprimer un agent du service scolarité',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
        ],
        'agents_inscription.view' => [
            'label' => 'Voir les agents d\'inscription',
            'group' => 'Personnel',
            'icon' => 'fa-user-plus',
        ],
        'agents_inscription.create' => [
            'label' => 'Créer un agent d\'inscription',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
        ],
        'agents_inscription.edit' => [
            'label' => 'Modifier un agent d\'inscription',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
        ],
        'agents_inscription.delete' => [
            'label' => 'Supprimer un agent d\'inscription',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
        ],
        'secretaires.view' => [
            'label' => 'Voir les secrétaires',
            'group' => 'Personnel',
            'icon' => 'fa-user-shield',
            'aliases' => ['view_secretaires'],
        ],
        'secretaires.create' => [
            'label' => 'Créer un secrétaire',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_secretaires'],
        ],
        'secretaires.edit' => [
            'label' => 'Modifier un secrétaire',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_secretaires'],
        ],
        'secretaires.delete' => [
            'label' => 'Supprimer un secrétaire',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_secretaires'],
        ],
        'comptables.view' => [
            'label' => 'Voir les comptables',
            'group' => 'Personnel',
            'icon' => 'fa-calculator',
            'aliases' => ['view_comptables'],
        ],
        'comptables.create' => [
            'label' => 'Créer un comptable',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_comptables'],
        ],
        'comptables.edit' => [
            'label' => 'Modifier un comptable',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_comptables'],
        ],
        'comptables.delete' => [
            'label' => 'Supprimer un comptable',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_comptables'],
        ],
        'caissiers.view' => [
            'label' => 'Voir les caissiers',
            'group' => 'Personnel',
            'icon' => 'fa-cash-register',
            'aliases' => ['view_caissiers'],
        ],
        'caissiers.create' => [
            'label' => 'Créer un caissier',
            'group' => 'Personnel',
            'icon' => 'fa-plus',
            'aliases' => ['create_caissiers'],
        ],
        'caissiers.edit' => [
            'label' => 'Modifier un caissier',
            'group' => 'Personnel',
            'icon' => 'fa-edit',
            'aliases' => ['edit_caissiers'],
        ],
        'caissiers.delete' => [
            'label' => 'Supprimer un caissier',
            'group' => 'Personnel',
            'icon' => 'fa-trash',
            'aliases' => ['delete_caissiers'],
        ],

        // ===== Planning & Emploi du temps =====
        'planning.view' => [
            'label' => 'Voir le planning général',
            'group' => 'Planning',
            'icon' => 'fa-calendar',
            'aliases' => ['view_planning_general'],
        ],
        'planning.edit' => [
            'label' => 'Modifier le planning général',
            'group' => 'Planning',
            'icon' => 'fa-edit',
            'aliases' => ['edit_planning_general'],
        ],
        'planning.manage' => [
            'label' => 'Gérer la planification académique',
            'group' => 'Planning',
            'icon' => 'fa-tasks',
            'aliases' => ['manage-planning'],
        ],

        // ===== Planning LMD (UEMOA) =====
        'lmd.planning.view' => [
            'label' => 'Voir le planning LMD (UE/ECUE par parcours)',
            'group' => 'LMD',
            'icon' => 'fa-graduation-cap',
        ],
        'lmd.planning.edit' => [
            'label' => 'Modifier le planning LMD (volumes, crédits, coefficients)',
            'group' => 'LMD',
            'icon' => 'fa-edit',
        ],

        // ===== Examens LMD (PR9 — workflow UEMOA scolarité) =====
        'lmd.examens.view' => [
            'label' => 'Voir les examens planifiés',
            'group' => 'LMD',
            'icon' => 'fa-pen-ruler',
        ],
        'lmd.examens.manage' => [
            'label' => 'Gérer les examens (créer, modifier, supprimer, assigner surveillants)',
            'group' => 'LMD',
            'icon' => 'fa-edit',
        ],
        'lmd.examens.notes_lock' => [
            'label' => 'Verrouiller les notes d\'un examen (anti-tampering)',
            'group' => 'LMD',
            'icon' => 'fa-lock',
        ],

        // ===== Rattrapage LMD (PR10 — sessions 2e session) =====
        'lmd.rattrapage.view' => [
            'label' => 'Voir les sessions de rattrapage',
            'group' => 'LMD',
            'icon' => 'fa-rotate-right',
        ],
        'lmd.rattrapage.manage' => [
            'label' => 'Gérer les sessions de rattrapage (génération, recalcul)',
            'group' => 'LMD',
            'icon' => 'fa-cog',
        ],

        // ===== Jury de délibération LMD (PR11-PR13) =====
        'lmd.jury.view' => [
            'label' => 'Voir les jurys de délibération',
            'group' => 'LMD',
            'icon' => 'fa-gavel',
        ],
        'lmd.jury.preside' => [
            'label' => 'Présider un jury (composition, validation quorum)',
            'group' => 'LMD',
            'icon' => 'fa-user-tie',
        ],
        'lmd.jury.deliberate' => [
            'label' => 'Délibérer (override décision étudiant avec motif)',
            'group' => 'LMD',
            'icon' => 'fa-scale-balanced',
        ],
        'lmd.jury.publish' => [
            'label' => 'Publier décisions jury + générer PV PDF officiel',
            'group' => 'LMD',
            'icon' => 'fa-file-signature',
        ],
        'lmd.jury.documents.reconcile' => [
            'label' => 'Réconcilier un PV historique dans le registre officiel',
            'group' => 'LMD',
            'icon' => 'fa-file-shield',
        ],
        'lmd.pv.export' => [
            'label' => 'Exporter le PV annuel (Excel et PDF)',
            'group' => 'LMD',
            'icon' => 'fa-file-excel',
        ],
        'lmd.ajournes.view' => [
            'label' => 'Voir la liste des étudiants ajournés et les UE/IE non validées',
            'group' => 'LMD',
            'icon' => 'fa-user-times',
        ],
        'inscriptions.fiche.print' => [
            'label' => 'Imprimer la fiche d\'inscription (double exemplaire)',
            'group' => 'Inscriptions',
            'icon' => 'fa-print',
        ],

        // ===== Réconciliation des doublons UE/ECUE LMD =====
        'lmd.reconciliation.manage' => [
            'label' => 'Détecter et fusionner les doublons UE/ECUE LMD',
            'description' => 'Permet de détecter les quasi-doublons d\'UE et d\'ECUE issus d\'imports de maquettes multi-parcours, puis de les fusionner en une entité partagée liée à plusieurs parcours.',
            'group' => 'LMD',
            'icon' => 'fa-object-group',
        ],

        // ===== Structure pedagogique LMD (domaines, mentions, parcours, UE, ECUE) =====
        // Ces permissions manquaient : la garde de groupe etait le seul filtre, donc
        // tout role franchissant `module.lmd.access` pouvait supprimer un domaine
        // entier et sa descendance. La lecture, l'ecriture et la suppression sont
        // desormais trois droits distincts.
        'lmd.structure.view' => [
            'label' => 'Consulter la structure pédagogique (domaines, mentions, parcours, unités)',
            'group' => 'LMD',
            'icon' => 'fa-sitemap',
        ],
        'lmd.structure.manage' => [
            'label' => 'Créer et modifier la structure pédagogique (domaines, mentions, parcours, unités)',
            'description' => 'Couvre la création et la modification des domaines, mentions, parcours, unités d\'enseignement et éléments constitutifs, ainsi que leurs rattachements aux classes.',
            'group' => 'LMD',
            'icon' => 'fa-pen-to-square',
        ],
        'lmd.structure.delete' => [
            'label' => 'Supprimer un domaine, une mention, un parcours, une unité ou un élément constitutif',
            'description' => 'Opération destructive : la suppression d\'un domaine entraîne celle de sa descendance. À réserver aux profils qui pilotent la maquette pédagogique.',
            'group' => 'LMD',
            'icon' => 'fa-trash',
        ],

        // ===== Notes et résultats LMD =====
        'lmd.notes.view' => [
            'label' => 'Consulter les notes des éléments constitutifs',
            'group' => 'LMD',
            'icon' => 'fa-list-ol',
        ],
        'lmd.notes.manage' => [
            'label' => 'Saisir et modifier les notes des éléments constitutifs',
            'description' => 'Ouvre l\'écran de saisie groupée. Le détail (créer une note ou en corriger une existante) reste filtré par notes.create et notes.edit.',
            'group' => 'LMD',
            'icon' => 'fa-pen-to-square',
        ],
        'lmd.resultats.view' => [
            'label' => 'Consulter les résultats par unité et par élément constitutif',
            'group' => 'LMD',
            'icon' => 'fa-chart-column',
        ],

        // ===== Bulletins LMD =====
        'lmd.bulletins.view' => [
            'label' => 'Consulter les bulletins semestriels',
            'group' => 'LMD',
            'icon' => 'fa-file-lines',
        ],
        'lmd.bulletins.generate' => [
            'label' => 'Générer les bulletins semestriels (individuel ou classe entière)',
            'group' => 'LMD',
            'icon' => 'fa-gears',
        ],
        'lmd.bulletins.publish' => [
            'label' => 'Publier ou dépublier un bulletin semestriel',
            'description' => 'La publication rend le bulletin visible de l\'étudiant. À réserver au profil qui arrête les résultats.',
            'group' => 'LMD',
            'icon' => 'fa-bullhorn',
        ],
        'lmd.bulletins.delete' => [
            'label' => 'Supprimer un bulletin semestriel',
            'group' => 'LMD',
            'icon' => 'fa-trash',
        ],

        // ===== Relevé de notes (document officiel) =====
        'lmd.releve.view' => [
            'label' => 'Consulter et télécharger un relevé de notes',
            'group' => 'LMD',
            'icon' => 'fa-file-lines',
        ],
        'lmd.releve.issue' => [
            'label' => 'Émettre un relevé de notes officiel',
            'description' => 'L\'émission crée une pièce officielle immuable et versionnée. À réserver au profil qui publie déjà les délibérations.',
            'group' => 'LMD',
            'icon' => 'fa-file-circle-check',
        ],

        // ===== Seconde session : saisie des notes =====
        'lmd.rattrapage.notes.saisir' => [
            'label' => 'Saisir les notes de seconde session',
            'description' => 'Additive : ouvre la seule saisie des notes de rattrapage, sans le pilotage de la session (lancement, publication) que porte lmd.rattrapage.manage. Non attribuée par défaut : chaque école la coche sur le rôle de son choix.',
            'group' => 'LMD — Rattrapage',
            'icon' => 'fa-pen-to-square',
        ],

        // ===== Signature d'un procès-verbal de délibération =====
        'lmd.jury.sign' => [
            'label' => 'Signer un procès-verbal de délibération dont on est membre',
            'description' => 'Permet la seule apposition de signature. Le service vérifie que le signataire est bien le membre concerné : ce droit n\'ouvre ni la délibération ni la modification d\'une décision.',
            'group' => 'LMD',
            'icon' => 'fa-signature',
        ],

        'timetables.view' => [
            'label' => 'Voir les emplois du temps',
            'group' => 'Planning',
            'icon' => 'fa-calendar-alt',
            'aliases' => ['view_timetables'],
        ],
        'timetables.view_all' => [
            'label' => 'Voir tous les emplois du temps',
            'group' => 'Planning',
            'icon' => 'fa-calendar-alt',
            'aliases' => ['view-all-timetables'],
        ],
        'timetables.view_own' => [
            'label' => 'Voir son propre emploi du temps',
            'group' => 'Planning',
            'icon' => 'fa-calendar-day',
            'aliases' => ['view_own_timetable'],
        ],
        'timetables.create' => [
            'label' => 'Créer un emploi du temps',
            'group' => 'Planning',
            'icon' => 'fa-plus',
            'aliases' => ['create_timetable'],
        ],
        'timetables.edit' => [
            'label' => 'Modifier un emploi du temps',
            'group' => 'Planning',
            'icon' => 'fa-edit',
            'aliases' => ['edit_timetables'],
        ],
        'timetables.delete' => [
            'label' => 'Supprimer un emploi du temps',
            'group' => 'Planning',
            'icon' => 'fa-trash',
            'aliases' => ['delete_timetables'],
        ],
        'schedules.view' => [
            'label' => 'Voir les séances de cours',
            'group' => 'Planning',
            'icon' => 'fa-clock',
            'aliases' => ['view_schedules'],
        ],
        'schedules.view_own' => [
            'label' => 'Voir ses propres séances',
            'group' => 'Planning',
            'icon' => 'fa-clock',
            'aliases' => ['view_own_schedule'],
        ],
        'schedules.create' => [
            'label' => 'Créer une séance',
            'group' => 'Planning',
            'icon' => 'fa-plus',
            'aliases' => ['create_schedules'],
        ],
        'schedules.edit' => [
            'label' => 'Modifier une séance',
            'group' => 'Planning',
            'icon' => 'fa-edit',
            'aliases' => ['edit_schedules'],
        ],

        // ===== Communication =====
        'messages.send' => [
            'label' => 'Envoyer des messages',
            'group' => 'Communication',
            'icon' => 'fa-paper-plane',
            'aliases' => ['send_messages'],
        ],
        'messages.receive' => [
            'label' => 'Recevoir des messages',
            'group' => 'Communication',
            'icon' => 'fa-inbox',
            'aliases' => ['receive_messages'],
        ],
        'annonces.view' => [
            'label' => 'Voir les annonces',
            'group' => 'Communication',
            'icon' => 'fa-bullhorn',
            'aliases' => ['view_annonces'],
        ],
        'annonces.create' => [
            'label' => 'Créer une annonce',
            'group' => 'Communication',
            'icon' => 'fa-plus',
            'aliases' => ['create_annonces'],
        ],
        'annonces.edit' => [
            'label' => 'Modifier une annonce',
            'group' => 'Communication',
            'icon' => 'fa-edit',
            'aliases' => ['edit_annonces'],
        ],
        'mailpulse.view' => [
            'label' => 'Voir l\'état MailPulse',
            'group' => 'Communication',
            'icon' => 'fa-envelope',
        ],
        'mailpulse.send' => [
            'label' => 'Envoyer un test ou un message MailPulse',
            'group' => 'Communication',
            'icon' => 'fa-paper-plane',
        ],

        // ===== Rapports =====
        'reports.view' => [
            'label' => 'Voir les rapports',
            'group' => 'Rapports',
            'icon' => 'fa-chart-bar',
            'aliases' => ['view_reports'],
        ],
        'reports.generate' => [
            'label' => 'Générer un rapport',
            'group' => 'Rapports',
            'icon' => 'fa-file-export',
            'aliases' => ['generate_reports'],
        ],
        'reports.academic.rentree' => [
            'label' => 'Voir le rapport de rentree',
            'group' => 'Rapports',
            'icon' => 'fa-door-open',
        ],
        'reports.academic.trimestre' => [
            'label' => 'Voir le rapport de fin de semestre',
            'group' => 'Rapports',
            'icon' => 'fa-calendar-check',
        ],
        'reports.academic.annuel' => [
            'label' => 'Voir le rapport annuel pedagogique',
            'group' => 'Rapports',
            'icon' => 'fa-book',
        ],
        'documents.view' => [
            'label' => 'Voir les documents de scolarite',
            'group' => 'Documents',
            'icon' => 'fa-file-alt',
        ],
        'documents.approve' => [
            'label' => 'Approuver un document avant impression',
            'group' => 'Documents',
            'icon' => 'fa-stamp',
        ],
        'documents.print' => [
            'label' => 'Imprimer un document de scolarite',
            'group' => 'Documents',
            'icon' => 'fa-print',
        ],
        'notes.window.manage' => [
            'label' => 'Ouvrir ou fermer une fenetre de saisie des notes',
            'group' => 'Notes',
            'icon' => 'fa-hourglass-half',
        ],
        'finance.unpaid_count.view' => [
            'label' => 'Voir le nombre d etudiants non soldes, sans les montants',
            'group' => 'Pilotage',
            'icon' => 'fa-user-clock',
        ],

        // ===== Performance personnel =====
        'performance.view' => [
            'label' => 'Voir les scores de performance accessibles',
            'group' => 'Performance personnel',
            'icon' => 'fa-chart-line',
        ],
        'performance.view_all' => [
            'label' => 'Voir tous les scores de performance du personnel',
            'group' => 'Performance personnel',
            'icon' => 'fa-ranking-star',
        ],
        'performance.recalculate' => [
            'label' => 'Recalculer les scores de performance',
            'group' => 'Performance personnel',
            'icon' => 'fa-arrows-rotate',
        ],
        'performance.configure' => [
            'label' => 'Configurer les règles de scoring du personnel',
            'group' => 'Performance personnel',
            'icon' => 'fa-sliders',
        ],

        // ===== Résultats =====
        'resultats.view' => [
            'label' => 'Voir les résultats académiques',
            'group' => 'Résultats',
            'icon' => 'fa-trophy',
            'aliases' => ['view_resultats'],
        ],
        'resultats.edit' => [
            'label' => 'Modifier les résultats',
            'group' => 'Résultats',
            'icon' => 'fa-edit',
            'aliases' => ['edit_resultats'],
        ],
        'resultats.export' => [
            'label' => 'Exporter les résultats',
            'group' => 'Résultats',
            'icon' => 'fa-download',
        ],

        // ===== Identité (routing par identité, pas granularité) =====
        'identity.teach' => [
            'label' => 'Identité enseignant (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-chalkboard-teacher',
            'aliases' => ['can_teach'],
        ],
        'identity.student' => [
            'label' => 'Identité étudiant (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-user-graduate',
            'aliases' => ['can_view_student_features'],
        ],
        'identity.school_manager' => [
            'label' => 'Identité gestionnaire école (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-school',
            'aliases' => ['can_manage_school'],
        ],
        'identity.coordinate' => [
            'label' => 'Identité coordinateur (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-user-tie',
            'aliases' => ['can_coordinate_academics'],
        ],
        'identity.direct_studies' => [
            'label' => 'Identité directeur des études (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-graduation-cap',
            'aliases' => ['can_direct_studies'],
        ],
        'identity.registrar' => [
            'label' => 'Identite responsable scolarité (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-user-check',
        ],
        'identity.registrar_clerk' => [
            'label' => 'Identite service scolarité (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-print',
        ],
        'identity.enrollment_officer' => [
            'label' => 'Identité agent d\'inscription (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-user-plus',
        ],
        'identity.communicate' => [
            'label' => 'Identité chargé de communication (routing UI)',
            'group' => 'Identité',
            'icon' => 'fa-bullhorn',
        ],

        // ===== Modules (toggles d'abonnement par tenant) =====
        'module.academique.access' => [
            'label' => 'Module : Académique (filières, classes, niveaux, matières)',
            'group' => 'Modules',
            'icon' => 'fa-graduation-cap',
        ],
        'module.academic_pilotage.access' => [
            'label' => 'Module : Pilotage académique',
            'group' => 'Modules',
            'icon' => 'fa-chart-line',
        ],
        'module.etudiants.access' => [
            'label' => 'Module : Étudiants & Inscriptions',
            'group' => 'Modules',
            'icon' => 'fa-user-graduate',
        ],
        'module.enseignants.access' => [
            'label' => 'Module : Enseignants',
            'group' => 'Modules',
            'icon' => 'fa-chalkboard-teacher',
        ],
        'module.notes_evaluations.access' => [
            'label' => 'Module : Notes & Évaluations',
            'group' => 'Modules',
            'icon' => 'fa-clipboard-list',
        ],
        'module.emploi_temps.access' => [
            'label' => 'Module : Emploi du temps',
            'group' => 'Modules',
            'icon' => 'fa-calendar-alt',
        ],
        'module.presences.access' => [
            'label' => 'Module : Présences',
            'group' => 'Modules',
            'icon' => 'fa-clipboard-check',
        ],
        'module.lmd.access' => [
            'label' => 'Module : LMD (Licence-Master-Doctorat)',
            'group' => 'Modules',
            'icon' => 'fa-university',
        ],
        'module.comptabilite.access' => [
            'label' => 'Module : Comptabilité',
            'group' => 'Modules',
            'icon' => 'fa-calculator',
        ],
        'module.caisse.access' => [
            'label' => 'Module : Caisse (caissier)',
            'group' => 'Modules',
            'icon' => 'fa-cash-register',
        ],
        'cash_session.manage' => [
            'label' => 'Gérer sa caisse du jour (clôture et bordereau)',
            'group' => 'Caisse',
            'icon' => 'fa-lock',
        ],
        'module.communication.access' => [
            'label' => 'Module : Communication (annonces, messages)',
            'group' => 'Modules',
            'icon' => 'fa-comments',
        ],
        'module.technical_support.access' => [
            'label' => 'Module : Support technique',
            'group' => 'Modules',
            'icon' => 'fa-tools',
        ],
        'module.tpe.access' => [
            'label' => 'Module : TPE — Journal & validation (LMD)',
            'description' => 'Auto-déclaration étudiant des heures de Travail Personnel Étudiant. '
                . 'Workflow validation prof activable via Setting tpe.validation.enabled.',
            'group' => 'Modules',
            'icon' => 'fa-book-reader',
        ],

        // ===== TPE (Travail Personnel Étudiant — LMD UEMOA) =====
        'tpe.declare' => [
            'label' => 'Déclarer ses heures TPE',
            'description' => 'Permet à un étudiant de saisir des heures TPE par ECUE et par semaine.',
            'group' => 'TPE',
            'icon' => 'fa-pen-to-square',
        ],
        'tpe.validate' => [
            'label' => 'Valider les heures TPE des étudiants',
            'description' => 'Permet à un enseignant de valider/rejeter les déclarations TPE '
                . 'des étudiants pour les ECUE dont il est responsable. '
                . 'Workflow activé uniquement quand Setting tpe.validation.enabled = true.',
            'group' => 'TPE',
            'icon' => 'fa-check-double',
        ],
        'tpe.view_all' => [
            'label' => 'Voir toutes les déclarations TPE',
            'description' => 'Vue admin/coordinateur : toutes les déclarations toutes ECUE confondues.',
            'group' => 'TPE',
            'icon' => 'fa-list-check',
        ],

        // ===== Service Technique (paywall) =====
        'paywall.configure' => [
            'label' => 'Configurer le paywall',
            'group' => 'Système',
            'icon' => 'fa-lock',
        ],
        'paywall.manage' => [
            'label' => 'Gérer le paywall',
            'group' => 'Système',
            'icon' => 'fa-key',
        ],
        'paywall.manage_subscriptions' => [
            'label' => 'Gérer les abonnements paywall',
            'group' => 'Système',
            'icon' => 'fa-credit-card',
        ],
        'paywall.extend_subscriptions' => [
            'label' => 'Prolonger un abonnement paywall',
            'group' => 'Système',
            'icon' => 'fa-clock',
        ],
        'paywall.view_all_stats' => [
            'label' => 'Voir toutes les statistiques paywall',
            'group' => 'Système',
            'icon' => 'fa-chart-pie',
        ],
        'system.technical_access' => [
            'label' => 'Accès technique au système',
            'group' => 'Système',
            'icon' => 'fa-shield-alt',
        ],
        'system.emergency_override' => [
            'label' => 'Override d\'urgence (service technique)',
            'group' => 'Système',
            'icon' => 'fa-exclamation-triangle',
        ],

        // ===== Sécurité & audit =====
        'security.audit.view' => [
            'label' => 'Voir le journal d\'audit sécurité',
            'group' => 'Sécurité',
            'icon' => 'fa-history',
        ],
        'security.audit.export' => [
            'label' => 'Exporter le journal d\'audit',
            'group' => 'Sécurité',
            'icon' => 'fa-download',
        ],
        'security.users.monitor' => [
            'label' => 'Surveiller l\'activité des utilisateurs',
            'group' => 'Sécurité',
            'icon' => 'fa-user-shield',
        ],
        'security.events.view' => [
            'label' => 'Voir les événements de sécurité',
            'group' => 'Sécurité',
            'icon' => 'fa-shield-alt',
        ],
        'security.backup.view' => [
            'label' => 'Voir les sauvegardes',
            'group' => 'Sécurité',
            'icon' => 'fa-database',
        ],
        'security.backup.create' => [
            'label' => 'Créer une sauvegarde',
            'group' => 'Sécurité',
            'icon' => 'fa-save',
        ],
        'security.backup.restore' => [
            'label' => 'Restaurer une sauvegarde',
            'group' => 'Sécurité',
            'icon' => 'fa-undo',
        ],
        'admin.system.security' => [
            'label' => 'Administration sécurité système',
            'group' => 'Sécurité',
            'icon' => 'fa-shield-virus',
        ],

        // ===== Workflow comptable =====
        'comptabilite.bons.approve' => [
            'label' => 'Approuver les bons comptables (workflow)',
            'group' => 'Comptabilité',
            'icon' => 'fa-check-double',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions par défaut par rôle
    |--------------------------------------------------------------------------
    | Utilisé par fix_permissions.php (Lot 3) pour syncPermissions().
    | superAdmin reçoit '*' = TOUTES les permissions canoniques.
    | Lecture via PermissionRegistry::defaultPermissionsFor($role).
    */

    'role_defaults' => [
        'superAdmin' => ['*'],

        'serviceTechnique' => ['*'],

        'secretaire' => [
            'dashboard.view', 'admin.access', 'parent_chatbot.manage',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'students.accessibility.view', 'students.accessibility.edit', 'students.accessibility.export',
            'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.validate',
            'inscriptions.fiche.print', 'inscriptions.in_kind.mark',
            'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
            'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
            'inscriptions.cancel', 'inscriptions.manage', 'inscriptions.specialisation.manage',
            'inscriptions.pieces.view', 'inscriptions.pieces.manage',
            // Sous-lot C+ : corbeille (restore tout, force_delete réservé superAdmin via Gate::before)
            'trash.view', 'students.restore', 'inscriptions.restore', 'paiements.restore',
            'cycles.view', 'cycles.create', 'cycles.edit', 'cycles.delete',
            'classes.view', 'classes.create', 'classes.edit',
            'filieres.view', 'filieres.create', 'filieres.edit',
            'niveaux.view', 'niveaux.create', 'niveaux.edit',
            'matieres.view',
            'notes.view', 'notes.create', 'notes.edit', 'notes.import_excel',
            'notes.window.manage',
            'evaluations.view', 'evaluations.create', 'evaluations.edit', 'exams.view',
            'bulletins.view', 'bulletins.generate', 'bulletins.edit', 'bulletins.delete', 'bulletins.configure',
            'documents.view', 'documents.approve', 'documents.print',
            'bulletins.publish.bulk', 'bulletins.regenerate.bulk', 'bulletins.export.bulk',
            'attendances.view', 'attendances.create', 'attendances.edit', 'attendances.delete',
            'attendances.generate_codes', 'attendances.justify_process',
            'session_reports.view',
            'paiements.view', 'paiements.create', 'paiements.validate',
            'frais.view', 'frais.create', 'frais.edit',
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            'personnel.view', 'personnel.manage',
            'coordinateurs.view', 'coordinateurs.create', 'coordinateurs.edit', 'coordinateurs.delete',
            'secretaires.view', 'secretaires.create', 'secretaires.edit', 'secretaires.delete',
            'comptables.view', 'comptables.create', 'comptables.edit', 'comptables.delete',
            'caissiers.view', 'caissiers.create', 'caissiers.edit', 'caissiers.delete',
            'planning.view', 'planning.manage',
            'timetables.view', 'timetables.view_all', 'timetables.create', 'timetables.edit', 'timetables.delete',
            'schedules.view', 'schedules.create', 'schedules.edit',
            'messages.send', 'messages.receive',
            'annonces.view', 'annonces.create', 'annonces.edit',
            'reports.view',
            'performance.view', 'performance.view_all', 'performance.recalculate',
            'resultats.view', 'resultats.export',
            'paiements.export',  // Lot 15
            'settings.pdf.manage',  // Phase 9 — customisation PDF tenant
            'exports.schedules.manage', 'exports.schedules.send_external',  // Phase 8 — exports programmés
            'users.manage',
            'identity.school_manager',
            'module.academic_pilotage.access',
            'academic_pilotage.view', 'academic_pilotage.view_all',
            'academic_sheets.view', 'academic_sheets.create',
            'academic_sheets.submit', 'academic_sheets.receive',
            'academic_sheets.enter', 'academic_sheets.control', 'academic_sheets.validate',
            'academic_sheets.assign',
            'academic_alerts.view', 'academic_alerts.acknowledge', 'academic_alerts.resolve',
            'academic_health.view', 'academic_health.recalculate',
            // Modules toggle
            'module.academique.access', 'module.etudiants.access', 'module.enseignants.access',
            'module.notes_evaluations.access', 'module.emploi_temps.access', 'module.presences.access',
            'module.lmd.access', 'module.comptabilite.access', 'module.communication.access',
            // Lot 9, perimetre arrete au Lot 10 — avant le decoupage fin, les routes
            // /esbtp/lmd n'etaient gardees que par module.lmd.access : ce role pouvait
            // donc editer la maquette ET produire les bulletins LMD. Le perimetre est
            // desormais aligne sur ce qu'il fait deja en BTS :
            //  - il GENERE les bulletins (il detient bulletins.generate en BTS) ;
            //  - il n'edite PAS la maquette (il n'a ni matieres.create ni filieres.create) ;
            //  - il ne PUBLIE pas : en LMD ce sont les resultats du jury qui sont
            //    arretes en deliberation, pas un acte de scolarite (voir la description
            //    de lmd.bulletins.publish).
            'lmd.structure.view',
            'lmd.notes.view',
            'lmd.resultats.view',
            'lmd.bulletins.view', 'lmd.bulletins.generate',
            'lmd.releve.view',
            // TPE — admin observe toutes les déclarations (dormant tant que module désactivé)
            'tpe.view_all',
        ],

        'comptable' => [
            'dashboard.view', 'admin.access',
            'comptabilite.access', 'comptabilite.dashboard.view', 'comptabilite.relances.send',
            'comptabilite.reports.export', 'comptabilite.config.manage',
            'comptabilite.paiements.view', 'comptabilite.paiements.validate',
            'comptabilite.frais.view', 'comptabilite.frais.configure',
            'comptabilite.analytics.view', 'comptabilite.analytics.refresh',
            'comptabilite.analytics.run_now', 'comptabilite.analytics.configure',
            'comptabilite.recouvrement.access',
            'comptabilite.audit.view',  // QW6 mai 2026 — comptable doit auditer son module
            'comptabilite.journal.view',  // S1.3 mai 2026 — comptable consulte le journal de caisse OHADA
            // PR1 réconciliation — view+open+resolve+export. Approve réservé au coordinateur (séparation OHADA).
            'comptabilite.reconciliation.view', 'comptabilite.reconciliation.open',
            'comptabilite.reconciliation.resolve', 'comptabilite.reconciliation.export',
            // Paie enseignants — préparation + export + taux + config. Validation séparée
            // attribuée par l'école (sinon validate_own pour une comptable unique).
            'comptabilite.salaires.view', 'comptabilite.salaires.create',
            'comptabilite.salaires.pay', 'comptabilite.salaires.configure',
            'comptabilite.salaires.export', 'comptabilite.salaires.set_rate',
            'paiements.view', 'paiements.create.mobile_money', 'paiements.edit', 'paiements.validate',
            'paiements.avoir',
            'paiements.correct_mode',
            'paiements.export',  // Lot 15
            'frais.view', 'frais.create', 'frais.edit', 'frais.configure',
            'students.view', 'inscriptions.view',
            'reports.view', 'reports.generate',
            'performance.view',
            'exports.schedules.manage', 'exports.schedules.send_external',  // Phase 8 — exports programmés
            'messages.send', 'messages.receive', 'annonces.view',
            'module.comptabilite.access', 'module.communication.access',
        ],

        'caissier' => [
            'dashboard.view', 'admin.access',
            'students.view', 'inscriptions.view',
            'inscriptions.create',  // pré-inscription
            // Lot 13 : caissier voit UNIQUEMENT ses propres encaissements (pas paiements.view)
            'paiements.view_own', 'paiements.create', 'paiements.edit', 'paiements.validate',
            // Corriger un mode mal saisi au guichet : c'est la ou l'erreur se
            // produit, donc c'est la qu'il faut pouvoir la reprendre.
            'paiements.correct_mode',
            'comptabilite.access', 'comptabilite.dashboard.view',
            'comptabilite.relances.send',
            'comptabilite.paiements.view', 'comptabilite.paiements.validate',
            'frais.view',
            'messages.send', 'messages.receive', 'annonces.view',
            'performance.view',
            'module.caisse.access',
            'cash_session.manage',
        ],

        'coordinateur' => [
            'admin.access', 'dashboard.view',
            'students.view', 'students.view_own',
            'students.create', 'students.edit', 'students.delete',
            'students.accessibility.view', 'students.accessibility.view_full',
            'students.accessibility.edit', 'students.accessibility.export',
            'inscriptions.view', 'inscriptions.create', 'inscriptions.edit',
            'inscriptions.validate', 'inscriptions.cancel', 'inscriptions.reject',
            'inscriptions.in_kind.mark',
            'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
            'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
            'inscriptions.pieces.view', 'inscriptions.pieces.manage',
            'paiements.view', 'frais.view',
            // PR1 réconciliation — view + approve (séparation OHADA : approve ≠ comptable qui a ouvert)
            'comptabilite.reconciliation.view', 'comptabilite.reconciliation.approve',
            'cycles.view', 'cycles.edit',
            'classes.view',
            'filieres.view', 'niveaux.view',
            'matieres.view', 'matieres.create', 'matieres.edit',
            'notes.view', 'notes.create', 'notes.import_excel',
            'evaluations.view', 'evaluations.create', 'evaluations.edit',
            'exams.view',
            'bulletins.view', 'bulletins.generate', 'bulletins.edit',
            'bulletins.publish.bulk', 'bulletins.regenerate.bulk', 'bulletins.export.bulk',
            'attendances.view', 'attendances.create', 'attendances.edit', 'attendances.delete',
            'attendances.generate_codes',
            'session_reports.view',
            'planning.view', 'planning.edit', 'planning.manage',
            'lmd.planning.view', 'lmd.planning.edit',
            'lmd.examens.view', 'lmd.examens.manage', 'lmd.examens.notes_lock',
            'lmd.rattrapage.view', 'lmd.rattrapage.manage',
            'lmd.credit_wallet.view',
            'lmd.jury.view', 'lmd.jury.preside', 'lmd.jury.deliberate', 'lmd.jury.publish',
            'lmd.jury.documents.reconcile', 'lmd.jury.sign',
            'lmd.pv.export', 'lmd.ajournes.view',
            // Socle pose au Lot 9 : les routes du module portent desormais une
            // permission chacune, au lieu de s'en remettre a la seule garde de groupe.
            'lmd.structure.view', 'lmd.structure.manage', 'lmd.structure.delete',
            'lmd.notes.view', 'lmd.notes.manage',
            'lmd.resultats.view',
            'lmd.bulletins.view', 'lmd.bulletins.generate',
            'lmd.bulletins.publish', 'lmd.bulletins.delete',
            'lmd.releve.view', 'lmd.releve.issue',
            'timetables.view', 'timetables.view_all', 'timetables.create', 'timetables.edit', 'timetables.delete',
            'schedules.view', 'schedules.create', 'schedules.edit',
            'personnel.view', 'personnel.manage',
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            'coordinateurs.view', 'coordinateurs.create', 'coordinateurs.edit', 'coordinateurs.delete',
            'secretaires.view', 'secretaires.create', 'secretaires.edit', 'secretaires.delete',
            'comptables.view', 'comptables.create', 'comptables.edit', 'comptables.delete',
            'caissiers.view', 'caissiers.create', 'caissiers.edit', 'caissiers.delete',
            'messages.send', 'messages.receive',
            'annonces.view', 'annonces.create', 'annonces.edit',
            'reports.view', 'reports.generate',
            'performance.view', 'performance.view_all', 'performance.recalculate',
            'resultats.view', 'resultats.edit',
            'users.manage',
            'identity.coordinate',
            'module.academic_pilotage.access',
            'academic_pilotage.view', 'academic_pilotage.view_all',
            'academic_sheets.view', 'academic_sheets.create',
            'academic_sheets.submit', 'academic_sheets.receive',
            'academic_sheets.enter', 'academic_sheets.control', 'academic_sheets.validate',
            'academic_sheets.assign',
            'academic_alerts.view', 'academic_alerts.acknowledge', 'academic_alerts.resolve',
            'academic_health.view', 'academic_health.recalculate',
            'module.academique.access', 'module.etudiants.access', 'module.enseignants.access',
            'module.notes_evaluations.access', 'module.emploi_temps.access', 'module.presences.access',
            'module.lmd.access', 'module.communication.access',
            // TPE — coordinateur observe toutes les déclarations (dormant)
            'tpe.view_all',
        ],

        'directeurEtudes' => [
            'dashboard.view',
            'students.view', 'students.view_own',
            'students.accessibility.view', 'students.accessibility.view_full',
            'students.accessibility.export',
            'inscriptions.view',
            'cycles.view',
            'classes.view',
            'filieres.view', 'niveaux.view',
            'annees.view',
            'bts_tronc_commun.view', 'bts_tronc_commun.view_history',
            'matieres.view',
            'notes.view',
            'evaluations.view',
            'exams.view',
            'bulletins.view',
            'attendances.view',
            'session_reports.view',
            'planning.view', 'planning.edit', 'planning.manage',
            'lmd.planning.view', 'lmd.planning.edit',
            'lmd.examens.view',
            'lmd.rattrapage.view',
            'lmd.credit_wallet.view',
            'lmd.jury.view', 'lmd.jury.sign',
            'lmd.pv.export', 'lmd.ajournes.view',
            // Socle Lot 9 : consultation large et generation, mais ni suppression
            // de maquette ni publication de bulletin (cf. separation des devoirs).
            'lmd.structure.view', 'lmd.structure.manage',
            'lmd.notes.view',
            'lmd.resultats.view',
            'lmd.bulletins.view', 'lmd.bulletins.generate',
            'lmd.releve.view',
            'timetables.view', 'timetables.view_all', 'timetables.create', 'timetables.edit', 'timetables.delete',
            'schedules.view', 'schedules.create', 'schedules.edit',
            'personnel.view',
            'teachers.view',
            'coordinateurs.view',
            'directeurs_etudes.view',
            'messages.send', 'messages.receive',
            'annonces.view', 'annonces.create', 'annonces.edit',
            'reports.view', 'reports.generate',
            'reports.academic.rentree', 'reports.academic.trimestre', 'reports.academic.annuel',
            'finance.unpaid_count.view',
            'performance.view', 'performance.view_all',
            'resultats.view',
            'identity.coordinate',
            'identity.direct_studies',
            'module.academic_pilotage.access',
            'academic_pilotage.view', 'academic_pilotage.view_all',
            'academic_sheets.view',
            'academic_alerts.view', 'academic_alerts.acknowledge',
            'academic_health.view',
            'module.academique.access', 'module.etudiants.access', 'module.enseignants.access',
            'module.notes_evaluations.access', 'module.emploi_temps.access', 'module.presences.access',
            'module.lmd.access', 'module.communication.access',
            'tpe.view_all',
        ],


        'responsableScolarite' => [
            'dashboard.view',
            'students.view', 'students.create', 'students.edit',
            'students.accessibility.view', 'students.accessibility.edit', 'students.accessibility.export',
            'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.validate',
            'inscriptions.fiche.print', 'inscriptions.in_kind.mark',
            'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
            'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
            'inscriptions.cancel', 'inscriptions.manage', 'inscriptions.specialisation.manage',
            'inscriptions.pieces.view', 'inscriptions.pieces.manage',
            'cycles.view',
            'classes.view',
            'filieres.view', 'niveaux.view',
            'matieres.view',
            'notes.view', 'notes.create', 'notes.edit', 'notes.import_excel',
            'notes.window.manage',
            'evaluations.view', 'evaluations.create', 'evaluations.edit', 'exams.view',
            'bulletins.view', 'bulletins.generate', 'bulletins.edit',
            'bulletins.publish.bulk', 'bulletins.export.bulk',
            'documents.view', 'documents.approve', 'documents.print',
            'attendances.view', 'attendances.create', 'attendances.edit',
            'session_reports.view',
            'teachers.view',
            'personnel.view',
            'services_scolarite.view', 'services_scolarite.create', 'services_scolarite.edit',
            'planning.view',
            'timetables.view', 'timetables.view_all',
            'schedules.view',
            'messages.send', 'messages.receive',
            'annonces.view', 'annonces.create', 'annonces.edit',
            'reports.view',
            'performance.view',
            'resultats.view', 'resultats.export',
            'identity.registrar',
            'module.academic_pilotage.access',
            'academic_pilotage.view', 'academic_pilotage.view_all',
            'academic_sheets.view', 'academic_sheets.create',
            'academic_sheets.submit', 'academic_sheets.receive',
            'academic_sheets.enter', 'academic_sheets.control', 'academic_sheets.validate',
            'academic_sheets.assign',
            'academic_alerts.view', 'academic_alerts.acknowledge', 'academic_alerts.resolve',
            'academic_health.view',
            'module.academique.access', 'module.etudiants.access', 'module.enseignants.access',
            'module.notes_evaluations.access', 'module.emploi_temps.access', 'module.presences.access',
            'module.lmd.access', 'module.communication.access',
            // Lot 9, perimetre arrete au Lot 10 — meme raisonnement que pour
            // 'secretaire', dont ce role est l'un des deux eclats : il genere les
            // bulletins LMD comme il genere deja ceux du BTS, mais il n'edite pas la
            // maquette et ne publie pas (la publication suit la deliberation du jury).
            'lmd.structure.view',
            'lmd.notes.view',
            'lmd.resultats.view',
            'lmd.bulletins.view', 'lmd.bulletins.generate',
            'lmd.releve.view',
        ],

        'serviceScolarite' => [
            'dashboard.view',
            'students.view',
            'students.accessibility.view',
            'inscriptions.view',
            'inscriptions.in_kind.mark',
            // Le guichet de scolarite est celui qui recoit les pieces : il les
            // coche, sans pour autant valider l'inscription.
            'inscriptions.pieces.view', 'inscriptions.pieces.manage',
            // Lecture seule sur la file du portail, coherent avec
            // inscriptions.view : ce role constate, il ne convertit pas. La
            // conversion cree une inscription et genere des frais, elle
            // appartient a qui detient deja inscriptions.create.
            'reinscriptions.demandes.view',
            'classes.view',
            'filieres.view', 'niveaux.view',
            'matieres.view',
            'notes.view', 'notes.create', 'notes.edit',
            'evaluations.view',
            'bulletins.view',
            'documents.view', 'documents.print',
            'attendances.view',
            'timetables.view',
            'schedules.view',
            'messages.receive',
            'annonces.view',
            'resultats.view',
            'identity.registrar_clerk',
            'module.etudiants.access',
            'module.notes_evaluations.access',
            'module.communication.access',
        ],

        'chargeCommunication' => [
            'dashboard.view',
            'students.view',
            'students.accessibility.view',
            'messages.send', 'messages.receive',
            'annonces.view', 'annonces.create', 'annonces.edit',
            'mailpulse.view', 'mailpulse.send',
            'identity.communicate',
            'module.etudiants.access',
            'module.communication.access',
        ],

        'agentInscription' => [
            'dashboard.view',
            'students.view', 'students.create', 'students.edit',
            'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.validate',
            'inscriptions.fiche.print', 'inscriptions.in_kind.mark',
            'reinscriptions.demandes.view', 'reinscriptions.demandes.process',
            'inscriptions.candidatures.view', 'inscriptions.candidatures.process',
            'inscriptions.pieces.view', 'inscriptions.pieces.manage',
            'classes.view',
            'filieres.view', 'niveaux.view',
            'identity.enrollment_officer',
            'module.etudiants.access',
            'module.academique.access',
        ],
        'enseignant' => [
            'admin.access', 'dashboard.view',
            'students.view_own',
            'students.accessibility.view', 'students.accessibility.export',
            'classes.view',
            'notes.view', 'notes.view_own', 'notes.create', 'notes.edit', 'notes.manage_own', 'notes.import_excel',
            'evaluations.view', 'evaluations.create', 'evaluations.edit',
            'bulletins.view',
            'attendances.view', 'attendances.create', 'attendances.edit',
            'attendances.view_own', 'attendances.sign',
            'session_reports.view_own',
            'schedules.view_own',
            'messages.send', 'messages.receive', 'annonces.view',
            'performance.view',
            'identity.teach',
            'module.academic_pilotage.access', 'academic_pilotage.view',
            'academic_sheets.view_own', 'academic_sheets.create',
            'academic_sheets.submit', 'academic_sheets.enter',
            'academic_alerts.view_own', 'academic_health.view_own',
            'lmd.credit_wallet.view',
            // Lot 9 : le toggle du module lui manquait — il portait admin.access mais
            // echouait au second etage de la garde. Les droits restent etroits, et
            // ce sont les controleurs qui les bornent, pas la seule permission :
            //  - lmd.notes.manage ouvre la saisie, mais ESBTPLMDNoteController
            //    refuse toute evaluation qui n'est pas confiee a l'enseignant
            //    (evaluation le nommant, ou affectation active a la matiere) ;
            //  - lmd.jury.sign ne vaut que pour le PV du jury dont il est membre,
            //    le service verifiant l'appartenance ;
            //  - lmd.jury.view, sans preside ni deliberate ni publish, ne montre
            //    que les jurys dont il est membre : une deliberation ne se lit pas
            //    en dehors du jury qui l'a prononcee (ESBTPLMDJuryController).
            // Le reste est en lecture seule. Rien de destructeur.
            'module.lmd.access',
            'lmd.structure.view',
            'lmd.notes.view', 'lmd.notes.manage',
            'lmd.resultats.view',
            'lmd.jury.view', 'lmd.jury.sign',
            'module.notes_evaluations.access', 'module.presences.access', 'module.communication.access',
            // TPE — workflow validation (dormant tant que tpe.validation.enabled = false)
            'tpe.validate',
        ],

        'etudiant' => [
            'dashboard.view',
            'notes.view_own',
            'bulletins.view_own',
            'lmd.credit_wallet.view',
            'attendances.view_own',
            'attendances.justify_own',
            'schedules.view_own', 'timetables.view_own',
            'profile.view_own',
            'students.accessibility.view_own',
            'exams.view_own',
            'messages.receive', 'annonces.view',
            'identity.student',
            // TPE — déclaration auto (dormant tant que module.tpe.access désactivé)
            'tpe.declare',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Matrice de gestion utilisateurs : qui peut gérer qui
    |--------------------------------------------------------------------------
    | role_management['actor'] = liste des rôles que actor peut gérer.
    | Lu par App\Services\UserManagementService (Lot 5).
    | Configurable par l'admin via UI dans /esbtp/roles-permissions (Lot 4).
    */

    'role_management' => [
        'superAdmin'       => ['secretaire', 'responsableScolarite', 'serviceScolarite', 'agentInscription', 'chargeCommunication', 'comptable', 'caissier', 'coordinateur', 'directeurEtudes', 'enseignant', 'etudiant'],
        'serviceTechnique' => ['superAdmin', 'secretaire', 'responsableScolarite', 'serviceScolarite', 'agentInscription', 'chargeCommunication', 'comptable', 'caissier', 'coordinateur', 'directeurEtudes', 'enseignant', 'etudiant'],
        'secretaire'       => ['enseignant', 'etudiant', 'caissier', 'chargeCommunication'],
        'responsableScolarite' => ['serviceScolarite', 'enseignant', 'etudiant'],
        'serviceScolarite' => ['enseignant'],
        'agentInscription' => [],
        'chargeCommunication' => [],
        'coordinateur'     => ['enseignant', 'etudiant'],
        'directeurEtudes'  => ['coordinateur', 'enseignant', 'etudiant'],
        'comptable'        => [],
        'caissier'         => ['etudiant'],  // pour la pré-inscription
        'enseignant'       => [],
        'etudiant'         => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions deprecated (à supprimer Lot 7)
    |--------------------------------------------------------------------------
    | Format : 'name' => ['since' => '...', 'reason' => '...']
    | Conservées temporairement pour rétrocompat. Audit warning à chaque usage.
    */

    'deprecated' => [
        'view_frais_scolarite'   => ['since' => '2026-04', 'reason' => 'Frais dynamiques par catégorie, plus de hardcode'],
        'create_frais_scolarite' => ['since' => '2026-04', 'reason' => 'Frais dynamiques par catégorie'],
        'edit_frais_scolarite'   => ['since' => '2026-04', 'reason' => 'Frais dynamiques par catégorie'],
        'delete_frais_scolarite' => ['since' => '2026-04', 'reason' => 'Frais dynamiques par catégorie'],
        'view_bourses'           => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'create_bourses'         => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'edit_bourses'           => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'delete_bourses'         => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'view_depenses'          => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'create_depenses'        => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'edit_depenses'          => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'delete_depenses'        => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'view_salaires'          => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'create_salaires'        => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'edit_salaires'          => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'delete_salaires'        => ['since' => '2026-04', 'reason' => 'Feature non implémentée — YAGNI'],
        'view_reporting_financier'   => ['since' => '2026-04', 'reason' => 'Feature non implémentée'],
        'export_reporting_financier' => ['since' => '2026-04', 'reason' => 'Feature non implémentée'],
        'manage_attendance_codes' => ['since' => '2026-04', 'reason' => 'Remplacé par attendances.generate_codes'],
        'validate_attendance'     => ['since' => '2026-04', 'reason' => 'Remplacé par attendances.sign'],
        'view_all_attendance'     => ['since' => '2026-04', 'reason' => 'Remplacé par attendances.view'],
        'view_comptabilite'       => ['since' => '2026-04', 'reason' => 'Remplacé par comptabilite.access'],
        'manage_comptabilite'     => ['since' => '2026-04', 'reason' => 'Remplacé par comptabilite.access + role_defaults'],
        'admin'                   => ['since' => '2026-04', 'reason' => 'Rôle doublon de superAdmin — à fusionner Lot 6j'],
        'teacher'                 => ['since' => '2026-04', 'reason' => 'Rôle doublon de enseignant — à fusionner Lot 6j'],
    ],

];

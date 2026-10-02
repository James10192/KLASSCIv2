<?php

/*
 * Outils de l'assistant IA.
 *
 * - enabled / any_permissions / all_permissions / allowed_roles : qui peut
 *   utiliser l'outil (ChatbotTool::isAvailableFor), vérifié à la déclaration
 *   au modèle ET à l'exécution.
 * - libelle    : texte montré pendant l'exécution (« Recherche des étudiants… »).
 * - suggestion : question d'exemple de l'écran d'accueil, proposée seulement
 *   aux utilisateurs qui ont accès à l'outil.
 */
return [
    'tools' => [
        'search_students' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['students.view', 'students.view_own'],
            'libelle' => 'Recherche des étudiants…',
            'suggestion' => 'Retrouve un étudiant par son nom',
        ],
        'search_notes' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['notes.view', 'notes.view_own'],
            'libelle' => 'Lecture des notes…',
            'suggestion' => 'Quelles sont les dernières notes saisies ?',
        ],
        'search_payments' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['paiements.view', 'paiements.view_own'],
            'libelle' => 'Recherche des paiements…',
            'suggestion' => 'Quels paiements sont en attente de validation ?',
        ],
        'search_inscriptions' => [
            'enabled' => true,
            'any_permissions' => ['inscriptions.view'],
            'libelle' => 'Recherche des inscriptions…',
            'suggestion' => 'Combien d\'inscriptions cette année ?',
        ],
        'search_fees' => [
            'enabled' => true,
            'any_permissions' => ['frais.view'],
            'libelle' => 'Lecture des frais configurés…',
            'suggestion' => 'Quels frais sont configurés ?',
        ],
        'search_classes' => [
            'enabled' => true,
            'any_permissions' => ['classes.view'],
            'libelle' => 'Recherche des classes…',
            'suggestion' => 'Liste les classes actives',
        ],
        'search_evaluations' => [
            'enabled' => true,
            'any_permissions' => ['evaluations.view', 'exams.view'],
            'libelle' => 'Recherche des évaluations…',
            'suggestion' => 'Quelles évaluations sont prévues cette semaine ?',
        ],
        'proposer_saisie_notes' => [
            'enabled' => true,
            'any_permissions' => ['notes.create', 'notes.edit', 'notes.manage_own'],
            'libelle' => 'Préparation des notes à enregistrer…',
        ],
        'proposer_supprimer_moyennes_sans_note' => [
            'enabled' => true,
            'any_permissions' => ['bulletins.delete'],
            'libelle' => 'Préparation du nettoyage des moyennes sans note…',
        ],
        'proposer_configuration_maquette_bts' => [
            'enabled' => true,
            'all_permissions' => ['matieres.edit', 'bulletins.configure'],
            'libelle' => 'Préparation de la maquette BTS…',
            'suggestion' => 'Configure la maquette BTS de cette filière et de ce niveau',
        ],
        'proposer_creation_evaluation' => [
            'enabled' => true,
            'all_permissions' => ['evaluations.create'],
            'libelle' => 'Préparation de l\'évaluation…',
            'suggestion' => 'Crée un devoir pour une classe et une matière',
        ],
        'proposer_publication_notes' => [
            'enabled' => true,
            'all_permissions' => ['evaluations.edit'],
            'libelle' => 'Préparation de la publication des notes…',
            'suggestion' => 'Publie les notes des évaluations terminées de ma classe',
        ],
        'diagnostiquer_reinscription' => [
            'enabled' => true,
            'any_permissions' => ['inscriptions.view', 'students.view'],
            'libelle' => 'Lecture du dossier de réinscription…',
            'suggestion' => 'Pourquoi la réinscription de cet étudiant est bloquée ?',
        ],
        'proposer_liaison_ue_parcours' => [
            'enabled' => true,
            'all_permissions' => ['lmd.structure.manage'],
            'libelle' => 'Préparation de la liaison UE ↔ parcours…',
        ],
        'proposer_creation_classes' => [
            'enabled' => true,
            'all_permissions' => ['classes.create'],
            'libelle' => 'Préparation des nouvelles classes…',
            'suggestion' => 'Ajoute une classe de plus à chaque filière et niveau',
        ],
        'proposer_modification_classes' => [
            'enabled' => true,
            'all_permissions' => ['classes.edit'],
            'libelle' => 'Préparation de la modification des classes…',
            'suggestion' => 'Passe les classes de 1re année à 60 places',
        ],
        // Structure académique (lot B) : mêmes droits que l'écran qui fait le même geste.
        'lire_structure_academique' => [
            'enabled' => true,
            'any_permissions' => ['annees.view', 'filieres.view', 'niveaux.view'],
            'libelle' => 'Lecture de la structure académique…',
        ],
        'proposer_creation_annee' => [
            'enabled' => true,
            'all_permissions' => ['annees.create'],
            'libelle' => 'Préparation de la nouvelle année universitaire…',
            'suggestion' => 'Crée l’année universitaire 2027-2028',
        ],
        'proposer_annee_courante' => [
            'enabled' => true,
            'all_permissions' => ['annees.set_current'],
            'libelle' => 'Préparation du changement d’année en cours…',
        ],
        'proposer_filieres' => [
            'enabled' => true,
            'all_permissions' => ['filieres.create'],
            'libelle' => 'Préparation des filières…',
        ],
        'proposer_niveaux' => [
            'enabled' => true,
            'all_permissions' => ['niveaux.create'],
            'libelle' => 'Préparation des niveaux d’études…',
        ],
        'proposer_annee_niveau' => [
            'enabled' => true,
            'all_permissions' => ['niveaux.edit'],
            'libelle' => 'Préparation de la correction du niveau…',
        ],
        'proposer_tronc_commun_filiere' => [
            'enabled' => true,
            'all_permissions' => ['filieres.edit'],
            'libelle' => 'Préparation du tronc commun…',
        ],
        'proposer_sortie_tronc_commun' => [
            'enabled' => true,
            'all_permissions' => ['bts_tronc_commun.manage_targets'],
            'libelle' => 'Préparation des sorties de tronc commun…',
        ],
        'proposer_orientation_bts' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.specialisation.manage'],
            'libelle' => 'Préparation de l’orientation…',
        ],
        'proposer_retrait_maquette_bts' => [
            'enabled' => true,
            'all_permissions' => ['matieres.edit'],
            'libelle' => 'Préparation du retrait de la maquette…',
        ],
        // Lot C : les permissions sont celles de l'écran qui fait la même chose.
        'proposer_correction_notes' => [
            'enabled' => true,
            'all_permissions' => ['notes.edit'],
            'libelle' => 'Préparation de la correction des notes…',
            'suggestion' => 'Corrige la note de cet étudiant suite à sa réclamation',
        ],
        'proposer_releve_notes_lmd' => [
            'enabled' => true,
            'all_permissions' => ['notes.edit', 'evaluations.create'],
            'libelle' => 'Préparation de la saisie du relevé…',
            'suggestion' => 'Saisis le relevé de notes du semestre 1 de cette classe pour 2025-2026',
        ],
        'proposer_saisie_moyennes' => [
            'enabled' => true,
            'all_permissions' => ['bulletins.edit'],
            'libelle' => 'Préparation des moyennes à enregistrer…',
        ],
        'proposer_deplacement_periode' => [
            'enabled' => true,
            'all_permissions' => ['evaluations.edit'],
            'libelle' => 'Préparation du changement de semestre…',
            'suggestion' => 'Range ces devoirs au semestre 2',
        ],
        'proposer_changement_matiere_evaluation' => [
            'enabled' => true,
            'all_permissions' => ['evaluations.edit'],
            'libelle' => 'Préparation du changement de matière…',
        ],
        // Même garde que la route de lancement de l'écran Bulletins.
        'proposer_generation_bulletins' => [
            'enabled' => true,
            'any_permissions' => ['admin.access', 'identity.direct_studies', 'identity.registrar', 'identity.registrar_clerk'],
            'libelle' => 'Pré-contrôle de la génération des bulletins…',
            'suggestion' => 'Génère les bulletins manquants de cette classe',
        ],
        'proposer_hierarchie_lmd' => [
            'enabled' => true,
            'all_permissions' => ['lmd.structure.manage'],
            'libelle' => 'Préparation de la structure LMD…',
        ],
        'proposer_import_maquette_lmd' => [
            'enabled' => true,
            'all_permissions' => ['lmd.structure.manage'],
            'libelle' => 'Lecture de la maquette LMD…',
            'suggestion' => 'Importe la maquette LMD que je joins',
        ],
        'proposer_rattachement_classes_parcours' => [
            'enabled' => true,
            'all_permissions' => ['classes.edit'],
            'libelle' => 'Préparation du rattachement des classes…',
        ],
        'proposer_affectation_enseignants_lmd' => [
            'enabled' => true,
            'all_permissions' => ['lmd.planning.edit'],
            'libelle' => 'Préparation des affectations d’enseignants…',
        ],
        // La pièce appartient à qui l'a déposée (PiecesJointes::pour) : ces droits
        // ne disent que les métiers où lire un tableau joint a un sens.
        'chercher_dans_piece' => [
            'enabled' => true,
            'any_permissions' => ['notes.create', 'notes.edit', 'notes.manage_own', 'inscriptions.view', 'students.view', 'paiements.view', 'frais.view', 'lmd.structure.manage', 'lmd.planning.edit'],
            'libelle' => 'Recherche dans le fichier joint…',
        ],
        'proposer_ajustement_souscription' => [
            'enabled' => true,
            'all_permissions' => ['frais.souscriptions.ajuster'],
            'libelle' => 'Préparation de l’ajustement du montant dû…',
        ],
        // Lot A (inscriptions, paiements, frais) : mêmes droits que la route de l'écran.
        'proposer_validation_inscriptions' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.validate'],
            'libelle' => 'Préparation de la validation des inscriptions…',
            'suggestion' => 'Valide les inscriptions de cette classe dont le versement est validé',
        ],
        'proposer_deplacement_etudiants' => [
            'enabled' => true,
            'all_permissions' => ['students.edit'],
            'libelle' => 'Préparation du changement de classe…',
        ],
        // Même droit que la fiche et la liste (route esbtp.inscriptions.redoublant.*).
        'proposer_statut_redoublant' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.redoublant.confirm'],
            'libelle' => 'Préparation du statut redoublant…',
            'suggestion' => 'Quels statuts redoublant restent à confirmer dans cette classe ?',
        ],
        'proposer_annulation_versement' => [
            'enabled' => true,
            'all_permissions' => ['paiements.avoir'],
            'libelle' => 'Préparation de l’annulation du versement…',
        ],
        'proposer_restauration_versement' => [
            'enabled' => true,
            'all_permissions' => ['trash.view', 'paiements.restore'],
            'libelle' => 'Préparation de la restauration du versement…',
        ],
        'proposer_annulation_depot_nature' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.in_kind.mark'],
            'libelle' => 'Préparation de l’annulation du dépôt en nature…',
        ],
        'proposer_repartition_trop_percu' => [
            'enabled' => true,
            'all_permissions' => ['paiements.reventiler'],
            'libelle' => 'Préparation de la répartition des versements…',
        ],
        'proposer_pose_bareme' => [
            'enabled' => true,
            'all_permissions' => ['frais.configure', 'frais.create', 'frais.edit'],
            'libelle' => 'Préparation du barème des frais…',
        ],
        // Réglages : les droits de l'écran des paramètres (/esbtp/settings).
        'lire_reglages' => [
            'enabled' => true,
            'all_permissions' => ['system.manage'],
            'libelle' => 'Lecture des réglages…',
        ],
        'proposer_modification_reglages' => [
            'enabled' => true,
            'all_permissions' => ['system.manage'],
            'libelle' => 'Préparation de la modification des réglages…',
            'suggestion' => 'Change le nom du directeur sur les documents',
        ],
        'proposer_image_reglage' => [
            'enabled' => true,
            'all_permissions' => ['system.manage'],
            'libelle' => 'Préparation de la nouvelle image…',
        ],
        // Rendez-vous d'inscription : les droits de l'écran des rendez-vous.
        'lire_rendez_vous' => [
            'enabled' => true,
            'any_permissions' => ['inscriptions.rdv.view', 'inscriptions.rdv.manage'],
            'libelle' => 'Lecture des rendez-vous…',
            'suggestion' => 'Où en sont les rendez-vous d’inscription ?',
        ],
        'proposer_generation_creneaux_rdv' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.rdv.manage'],
            'libelle' => 'Préparation des créneaux de rendez-vous…',
        ],
        'proposer_placement_dossiers_rdv' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.rdv.manage'],
            'libelle' => 'Préparation du placement des dossiers…',
        ],
        'proposer_convocations_rdv' => [
            'enabled' => true,
            'all_permissions' => ['inscriptions.rdv.manage'],
            'libelle' => 'Préparation des convocations…',
        ],
        'search_attendances' => [
            'enabled' => true,
            'any_permissions' => ['attendances.view'],
            'libelle' => 'Lecture des présences…',
            'suggestion' => 'Qui était absent aujourd\'hui ?',
        ],
        'search_teachers' => [
            'enabled' => true,
            'any_permissions' => ['teachers.view'],
            'libelle' => 'Recherche des enseignants…',
            'suggestion' => 'Liste les enseignants',
        ],
        'search_results' => [
            'enabled' => true,
            'any_permissions' => ['resultats.view'],
            'libelle' => 'Lecture des résultats…',
            'suggestion' => 'Quels sont les résultats du premier semestre ?',
        ],
        'search_timetable' => [
            'enabled' => true,
            'any_permissions' => ['module.emploi_temps.access'],
            'libelle' => 'Lecture de l\'emploi du temps…',
            'suggestion' => 'Montre l\'emploi du temps d\'une classe',
        ],
        'search_subjects' => [
            'enabled' => true,
            'any_permissions' => ['matieres.view'],
            'libelle' => 'Recherche des matières…',
            'suggestion' => 'Quelles matières sont enseignées en 1re année ?',
        ],
        'search_debtors' => [
            'enabled' => true,
            'any_permissions' => ['paiements.view'],
            'allowed_roles' => ['superAdmin', 'comptable', 'secretaire'],
            'libelle' => 'Recherche des retards de paiement…',
            'suggestion' => 'Quels étudiants sont en retard de paiement ?',
        ],
        'search_bulletins' => [
            'enabled' => true,
            'any_permissions' => ['bulletins.view', 'bulletins.view_own'],
            'libelle' => 'Recherche des bulletins…',
            'suggestion' => 'Où en sont les bulletins du semestre ?',
        ],
        'search_absences_summary' => [
            'enabled' => true,
            'any_permissions' => ['attendances.view'],
            'libelle' => 'Synthèse des absences…',
            'suggestion' => 'Quelle classe a le plus d\'absences ce mois-ci ?',
        ],
        'get_financial_summary' => [
            'enabled' => true,
            'any_permissions' => ['comptabilite.dashboard.view'],
            'allowed_roles' => ['superAdmin', 'comptable', 'secretaire'],
            'libelle' => 'Calcul du résumé financier…',
            'suggestion' => 'Fais-moi le point sur les encaissements',
        ],
        'repartition_effectifs' => [
            'enabled' => true,
            'any_permissions' => ['inscriptions.view', 'students.view'],
            'libelle' => 'Répartition des inscrits…',
            'suggestion' => 'Combien d\'inscrits par filière cette année ?',
        ],
        'evolution_encaissements' => [
            'enabled' => true,
            'any_permissions' => ['comptabilite.dashboard.view', 'paiements.view'],
            'libelle' => 'Calcul des encaissements par mois…',
            'suggestion' => 'Montre-moi les encaissements des six derniers mois',
        ],
        'get_dashboard_kpis' => [
            'enabled' => true,
            'any_permissions' => ['dashboard.view'],
            'libelle' => 'Lecture des indicateurs…',
            'suggestion' => 'Donne-moi les chiffres clés de l\'école',
        ],
        'get_setup_guide' => [
            'enabled' => true,
            'any_permissions' => ['dashboard.view'],
            'libelle' => 'Vérification de la configuration…',
            'suggestion' => 'Que me reste-t-il à configurer ?',
        ],
        'navigate_to_page' => [
            'enabled' => true,
            'any_permissions' => ['dashboard.view'],
            'libelle' => 'Recherche de la bonne page…',
            'suggestion' => 'Comment créer une inscription ?',
        ],
    ],
];

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

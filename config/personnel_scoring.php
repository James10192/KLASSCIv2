<?php

return [
    'periods' => [
        'month' => 'Mois',
        'quarter' => 'Trimestre',
        'year' => 'Annee',
    ],

    'levels' => [
        'excellent' => ['min' => 85, 'label' => 'Excellent', 'class' => 'success'],
        'good' => ['min' => 70, 'label' => 'Bon', 'class' => 'primary'],
        'watch' => ['min' => 50, 'label' => 'A surveiller', 'class' => 'warning'],
        'critical' => ['min' => 1, 'label' => 'Critique', 'class' => 'danger'],
        'insufficient_data' => ['min' => 0, 'label' => 'Donnees insuffisantes', 'class' => 'muted'],
    ],

    'dimensions' => [
        'teacher_attendance' => [
            'label' => 'Assiduite enseignant',
            'description' => 'Emargements, retards et absences sur les seances planifiees.',
            'weight' => 30,
            'permissions' => ['attendances.sign', 'attendances.view_own'],
            'calculator' => 'teacher',
        ],
        'teacher_delivery' => [
            'label' => 'Execution pedagogique',
            'description' => 'Cours planifies, appels et rapports de seance.',
            'weight' => 25,
            'permissions' => ['session_reports.view_own', 'attendances.create'],
            'calculator' => 'teacher',
        ],
        'grades_activity' => [
            'label' => 'Notes et evaluations',
            'description' => 'Evaluations creees, notes saisies ou importees.',
            'weight' => 20,
            'permissions' => ['notes.create', 'notes.edit', 'notes.import_excel', 'evaluations.create'],
            'calculator' => 'teacher',
        ],
        'payments_collection' => [
            'label' => 'Encaissements',
            'description' => 'Paiements crees, volume et qualite des encaissements.',
            'weight' => 30,
            'permissions' => ['paiements.create', 'paiements.view_own'],
            'calculator' => 'cashier',
        ],
        'payments_validation' => [
            'label' => 'Validation financiere',
            'description' => 'Paiements valides/rejetes et delais de traitement.',
            'weight' => 30,
            'permissions' => ['paiements.validate', 'comptabilite.paiements.validate'],
            'calculator' => 'finance',
        ],
        'finance_reporting' => [
            'label' => 'Pilotage financier',
            'description' => 'Rapports, exports, reconciliation et activite comptable.',
            'weight' => 20,
            'permissions' => ['reports.generate', 'paiements.export', 'comptabilite.reports.export', 'comptabilite.reconciliation.view'],
            'calculator' => 'finance',
        ],
        'academic_coordination' => [
            'label' => 'Coordination pedagogique',
            'description' => 'Planning, matieres, evaluations et suivi academique.',
            'weight' => 30,
            'permissions' => ['planning.manage', 'timetables.create', 'matieres.create', 'evaluations.create'],
            'calculator' => 'academic',
        ],
        'attendance_supervision' => [
            'label' => 'Supervision presences',
            'description' => 'Suivi des presences et traitement des alertes.',
            'weight' => 20,
            'permissions' => ['attendances.view', 'attendances.edit', 'attendances.justify_process'],
            'calculator' => 'academic',
        ],
        'administrative_activity' => [
            'label' => 'Activite administrative',
            'description' => 'Etudiants, inscriptions et dossiers administratifs.',
            'weight' => 30,
            'permissions' => ['students.create', 'students.edit', 'inscriptions.create', 'inscriptions.validate'],
            'calculator' => 'administrative',
        ],
        'communication_activity' => [
            'label' => 'Communication',
            'description' => 'Annonces, messages et activite de communication.',
            'weight' => 10,
            'permissions' => ['annonces.create', 'messages.send'],
            'calculator' => 'administrative',
        ],
        'platform_activity' => [
            'label' => 'Regularite KLASSCI',
            'description' => 'Connexion et presence recente dans la plateforme.',
            'weight' => 10,
            'permissions' => ['dashboard.view'],
            'calculator' => 'administrative',
        ],
    ],
];

<?php

return [
    'engine_version' => '2.1.0-academic-obligations',
    'minimum_obligations_for_full_confidence' => 5,

    'periods' => [
        'month' => 'Mois',
        'quarter' => 'Trimestre',
        'year' => 'Année',
    ],

    'levels' => [
        'excellent' => ['min' => 85, 'label' => 'Excellent', 'class' => 'success'],
        'good' => ['min' => 70, 'label' => 'Bon', 'class' => 'primary'],
        'watch' => ['min' => 50, 'label' => 'À surveiller', 'class' => 'warning'],
        'critical' => ['min' => 0, 'label' => 'Critique', 'class' => 'danger'],
        'insufficient_data' => ['min' => 0, 'label' => 'Données insuffisantes', 'class' => 'muted'],
    ],

    'dimensions' => [
        'teacher_attendance' => [
            'label' => 'Assiduité enseignant',
            'description' => 'Émargements, retards et absences sur les séances planifiées.',
            'weight' => 30,
            'permissions' => ['attendances.sign', 'attendances.view_own'],
            'calculator' => 'teacher',
        ],
        'teacher_delivery' => [
            'label' => 'Exécution pédagogique',
            'description' => 'Cours planifiés, appels et rapports de séance.',
            'weight' => 25,
            'permissions' => ['session_reports.view_own', 'attendances.create'],
            'calculator' => 'teacher',
        ],
        'grades_activity' => [
            'label' => 'Notes et évaluations',
            'description' => 'Évaluations créées, notes saisies ou importées.',
            'weight' => 20,
            'permissions' => ['notes.create', 'notes.edit', 'notes.import_excel', 'evaluations.create'],
            'calculator' => 'teacher',
        ],
        'payments_collection' => [
            'label' => 'Encaissements',
            'description' => 'Paiements créés, volume et qualité des encaissements.',
            'weight' => 30,
            'permissions' => ['paiements.create', 'paiements.view_own'],
            'calculator' => 'cashier',
        ],
        'payments_validation' => [
            'label' => 'Validation financière',
            'description' => 'Paiements validés ou rejetés et délais de traitement.',
            'weight' => 30,
            'permissions' => ['paiements.validate', 'comptabilite.paiements.validate'],
            'calculator' => 'finance',
        ],
        'finance_reporting' => [
            'label' => 'Pilotage financier',
            'description' => 'Rapports, exports, réconciliation et activité comptable.',
            'weight' => 20,
            'permissions' => ['reports.generate', 'paiements.export', 'comptabilite.reports.export', 'comptabilite.reconciliation.view'],
            'calculator' => 'finance',
        ],
        'academic_coordination' => [
            'label' => 'Coordination pédagogique',
            'description' => 'Planning, matières, évaluations et suivi académique.',
            'weight' => 30,
            'permissions' => ['planning.manage', 'timetables.create', 'matieres.create', 'evaluations.create'],
            'calculator' => 'academic',
        ],
        'attendance_supervision' => [
            'label' => 'Supervision des présences',
            'description' => 'Suivi des présences et traitement des alertes.',
            'weight' => 20,
            'permissions' => ['attendances.view', 'attendances.edit', 'attendances.justify_process'],
            'calculator' => 'academic',
        ],
        'academic_workflow' => [
            'label' => 'Saisie académique déléguée',
            'description' => 'Fiches papier reçues, explicitement affectées et saisies.',
            'weight' => 20,
            'permissions' => ['academic_sheets.enter'],
            'calculator' => 'academic',
        ],
        'administrative_activity' => [
            'label' => 'Activité administrative',
            'description' => 'Étudiants, inscriptions et dossiers administratifs.',
            'weight' => 30,
            'permissions' => ['students.create', 'students.edit', 'inscriptions.create', 'inscriptions.validate'],
            'calculator' => 'administrative',
        ],
        'communication_activity' => [
            'label' => 'Communication',
            'description' => 'Annonces, messages et activité de communication.',
            'weight' => 10,
            'permissions' => ['annonces.create', 'messages.send'],
            'calculator' => 'administrative',
        ],
        'platform_activity' => [
            'label' => 'Régularité KLASSCI',
            'description' => 'Connexion et présence récente dans la plateforme.',
            'weight' => 10,
            'permissions' => ['dashboard.view'],
            'calculator' => 'administrative',
        ],
    ],
];

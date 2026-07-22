<?php

return [
    'tools' => [
        'search_students' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['students.view', 'students.view_own'],
        ],
        'search_notes' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['notes.view', 'notes.view_own'],
        ],
        'search_payments' => [
            'enabled' => env('CHATBOT_SENSITIVE_TOOLS_ENABLED', false),
            'any_permissions' => ['paiements.view', 'paiements.view_own'],
        ],
        'search_inscriptions' => ['enabled' => true, 'any_permissions' => ['inscriptions.view']],
        'search_fees' => ['enabled' => true, 'any_permissions' => ['frais.view']],
        'search_classes' => ['enabled' => true, 'any_permissions' => ['classes.view']],
        'search_evaluations' => ['enabled' => true, 'any_permissions' => ['evaluations.view', 'exams.view']],
        'search_attendances' => ['enabled' => true, 'any_permissions' => ['attendances.view']],
        'search_teachers' => ['enabled' => true, 'any_permissions' => ['teachers.view']],
        'search_results' => ['enabled' => true, 'any_permissions' => ['resultats.view']],
        'search_timetable' => ['enabled' => true, 'any_permissions' => ['module.emploi_temps.access']],
        'search_subjects' => ['enabled' => true, 'any_permissions' => ['matieres.view']],
        'search_debtors' => [
            'enabled' => true,
            'any_permissions' => ['paiements.view'],
            'allowed_roles' => ['superAdmin', 'comptable', 'secretaire'],
        ],
        'search_bulletins' => ['enabled' => true, 'any_permissions' => ['bulletins.view', 'bulletins.view_own']],
        'search_absences_summary' => ['enabled' => true, 'any_permissions' => ['attendances.view']],
        'get_financial_summary' => [
            'enabled' => true,
            'any_permissions' => ['comptabilite.dashboard.view'],
            'allowed_roles' => ['superAdmin', 'comptable', 'secretaire'],
        ],
        'get_dashboard_kpis' => ['enabled' => true, 'any_permissions' => ['dashboard.view']],
        'get_setup_guide' => ['enabled' => true, 'any_permissions' => ['dashboard.view']],
        'navigate_to_page' => ['enabled' => true, 'any_permissions' => ['dashboard.view']],
    ],
];

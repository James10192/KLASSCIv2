<?php

declare(strict_types=1);

return [
    'student_health' => [
        'minimum_coverage_pct' => 60,
        'weights' => [
            'academic_performance' => 30,
            'assessment_completion' => 25,
            'attendance' => 20,
            'progression' => 15,
            'open_alerts' => 10,
        ],
        'levels' => [
            'healthy' => 80,
            'watch' => 65,
            'at_risk' => 50,
        ],
    ],
    'engine_version' => '1.0.0',
    'observers_enabled' => env('ACADEMIC_PILOTAGE_OBSERVERS_ENABLED', true),
    'refresh' => [
        'max_dirty_batch' => 250,
        'max_classes_per_manual_sync' => 10,
        'claim_ttl_minutes' => 10,
    ],
    'lmd' => [
        'expected_credits_per_semester' => 30,
    ],
];

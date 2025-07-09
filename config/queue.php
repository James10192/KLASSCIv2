<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        // Configuration pour files d'attente prioritaires
        'database_high' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'high',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'database_medium' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'medium',
            'retry_after' => 120,
            'after_commit' => false,
        ],

        'database_low' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'low',
            'retry_after' => 180,
            'after_commit' => false,
        ],

        // Configuration pour jobs de sauvegarde (moins prioritaires)
        'database_backup' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'backup',
            'retry_after' => 300,
            'after_commit' => false,
        ],

        // Configuration pour rapports (traitement long)
        'database_reports' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'reports',
            'retry_after' => 600,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Configuration Redis avec files prioritaires
        'redis_high' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'high',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

        'redis_medium' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'medium',
            'retry_after' => 120,
            'block_for' => null,
            'after_commit' => false,
        ],

        'redis_low' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'low',
            'retry_after' => 180,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Priority Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des priorités pour les différents types de jobs.
    | Les valeurs plus élevées sont traitées en premier.
    |
    */
    'priorities' => [
        'critical' => 100,  // Relances urgentes, notifications critiques
        'high' => 80,       // KPIs, paiements, notifications
        'medium' => 50,     // Rapports, exports
        'low' => 20,        // Sauvegardes, nettoyage
        'batch' => 10,      // Traitements par lots
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Job Mapping
    |--------------------------------------------------------------------------
    |
    | Mappage des types de jobs vers leurs files d'attente respectives
    |
    */
    'job_queues' => [
        'App\Jobs\EnvoyerRelanceJob' => 'high',
        'App\Jobs\CalculerKPIsJob' => 'medium',
        'App\Jobs\GenererRapportJob' => 'reports',
        'App\Jobs\PlanifierRelancesJob' => 'medium',
        'App\Jobs\SauvegardeDataJob' => 'backup',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Workers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des workers pour traitement optimal
    |
    */
    'workers' => [
        'high' => [
            'processes' => 3,
            'sleep' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
        'medium' => [
            'processes' => 2,
            'sleep' => 5,
            'tries' => 3,
            'timeout' => 120,
        ],
        'low' => [
            'processes' => 1,
            'sleep' => 10,
            'tries' => 2,
            'timeout' => 300,
        ],
        'reports' => [
            'processes' => 1,
            'sleep' => 10,
            'tries' => 2,
            'timeout' => 600,
        ],
        'backup' => [
            'processes' => 1,
            'sleep' => 30,
            'tries' => 2,
            'timeout' => 1800,
        ],
    ],

];

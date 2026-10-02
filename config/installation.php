<?php

/*
|--------------------------------------------------------------------------
| Installation de l'instance
|--------------------------------------------------------------------------
|
| Lu par InstallationHelper et InstallController. Ces valeurs vivaient dans
| des appels env() posés dans le code : sous `php artisan config:cache`,
| env() rend null hors de config/, et `APP_INSTALLED` serait retombé à false
| sur une instance en service. Les défauts sont ceux d'avant.
|
| `base_de_donnees` reprend les variables brutes, sans les défauts de
| config/database.php (127.0.0.1, 3306…) : l'assistant d'installation les
| lisait ainsi, null compris.
|
*/

return [

    'installee' => env('APP_INSTALLED', false),

    'base_de_donnees' => [
        'host' => env('DB_HOST'),
        'port' => env('DB_PORT'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],

];

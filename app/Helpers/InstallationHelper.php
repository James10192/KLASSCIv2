<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class InstallationHelper
{
    /**
     * Check if the application is installed
     *
     * This function checks multiple conditions to determine if the application
     * has been properly installed:
     * 1. The .env file exists
     * 2. The APP_INSTALLED flag is set to true in .env
     * 3. Database connection is working
     * 4. Required tables exist in the database
     * 5. Migration tables match existing tables
     *
     * @return bool
     */
    public static function isInstalled(): bool
    {
        try {
            // Vérifier si le fichier .env indique que l'application est installée
            $envInstalled = env('APP_INSTALLED', false);

            // Vérifier si la base de données est configurée et si au moins un superAdmin existe
            $dbConfigured = self::isDatabaseConfigured();
            $hasAdminUser = self::hasAdminUser();

            // Journaliser l'état de l'installation
            \Log::info(
                "Installation status check: ENV=" . ($envInstalled ? 'true' : 'false') .
                ", DB=" . ($dbConfigured ? 'true' : 'false') .
                ", AdminUser=" . ($hasAdminUser ? 'true' : 'false')
            );

            // L'application est considérée comme installée si toutes les conditions sont remplies
            return $envInstalled && $dbConfigured && $hasAdminUser;
        } catch (\Exception $e) {
            \Log::error("Error checking if app is installed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark the application as installed in the .env file
     *
     * This function updates the .env file to set APP_INSTALLED=true
     *
     * @return bool
     */
    public static function markAsInstalled(): bool
    {
        try {
            $envPath = base_path('.env');

            if (File::exists($envPath)) {
                $envContent = File::get($envPath);

                // Check if APP_INSTALLED already exists in .env
                if (strpos($envContent, 'APP_INSTALLED') !== false) {
                    // Replace existing APP_INSTALLED value
                    $envContent = preg_replace(
                        '/APP_INSTALLED=(true|false|null)/',
                        'APP_INSTALLED=true',
                        $envContent
                    );
                } else {
                    // Add APP_INSTALLED=true to .env
                    $envContent .= "\nAPP_INSTALLED=true\n";
                }

                // Write updated content back to .env file
                File::put($envPath, $envContent);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the database is properly configured
     *
     * @return bool
     */
    public static function isDatabaseConfigured(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur de connexion à la base de données: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the number of tables in the database
     *
     * @return int
     */
    public static function getTableCount(): int
    {
        try {
            if (!self::isDatabaseConfigured()) {
                return 0;
            }

            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if an admin user exists
     *
     * @return bool
     */
    public static function hasAdminUser(): bool
    {
        try {
            // Vérifier d'abord si les tables nécessaires existent
            if (!Schema::hasTable('users') || !Schema::hasTable('roles') || !Schema::hasTable('model_has_roles')) {
                return false;
            }

            // Vérifier si un utilisateur avec le rôle 'superAdmin' existe
            $superAdminExists = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', '=', 'superAdmin')
                ->where('model_has_roles.model_type', '=', User::class)
                ->exists();

            Log::info('Utilisateur superAdmin existe: ' . ($superAdminExists ? 'Oui' : 'Non'));

            return $superAdminExists;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification de l'existence de l'administrateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère l'état complet de l'installation
     *
     * @return array Tableau associatif avec les statuts d'installation et le pourcentage de correspondance des tables
     */
    public static function getInstallationStatus()
    {
        $isEnvInstalled = self::isInstalled();
        $isDatabaseConfigured = self::isDatabaseConfigured();
        $hasAdminUser = self::hasAdminUser();
        $allTablesPresent = false;
        $requiredTables = ['users', 'roles', 'permissions', 'model_has_roles'];
        $allRequiredTablesExist = false;

        // Initialisation des variables pour le calcul du match_percentage
        $matchPercentage = 0;
        $missingTables = [];
        $extraTables = [];
        $migrationTables = [];
        $existingTables = [];
        $matchingTables = [];
        $tableCount = 0;

        if ($isDatabaseConfigured) {
            try {
                // Vérifier les tables requises
                $allRequiredTablesExist = self::checkRequiredTables($requiredTables);
                $tableCount = self::getTableCount();

                // Récupérer les tables de migration et existantes
                $migrationTables = self::getMigrationTableNames();
                $existingTables = self::getExistingTables();

                // Calculer les tables manquantes et supplémentaires
                $matchingTables = array_intersect($migrationTables, $existingTables);
                $missingTables = array_diff($migrationTables, $existingTables);
                $extraTables = array_diff($existingTables, $migrationTables);

                // Calculer le pourcentage de correspondance
                $matchPercentage = count($migrationTables) > 0
                    ? round((count($matchingTables) / count($migrationTables)) * 100)
                    : 0;

                $allTablesPresent = count($missingTables) === 0;

                // Journaliser les résultats pour le débogage
                \Log::info("Match percentage: {$matchPercentage}%");
                \Log::info("Missing tables: " . count($missingTables));
                \Log::info("Extra tables: " . count($extraTables));
            } catch (\Exception $e) {
                \Log::error("Erreur lors du calcul du statut d'installation: " . $e->getMessage());
            }
        }

        // L'application est considérée comme installée si toutes les conditions sont remplies
        $installed = $isEnvInstalled && $isDatabaseConfigured && $hasAdminUser && $allRequiredTablesExist;

        return [
            'env_installed' => $isEnvInstalled,
            'db_configured' => $isDatabaseConfigured,
            'has_admin_user' => $hasAdminUser,
            'all_tables_present' => $allTablesPresent,
            'all_required_tables_exist' => $allRequiredTablesExist,
            'all_tables_exist' => $allRequiredTablesExist,
            'table_count' => $tableCount,
            'match_percentage' => $matchPercentage,
            'missing_tables' => $missingTables,
            'extra_tables' => $extraTables,
            'migration_tables_count' => count($migrationTables),
            'existing_tables_count' => count($existingTables),
            'matching_tables_count' => count($matchingTables),
            'installed' => $installed
        ];
    }

    /**
     * Vérifie si toutes les tables requises existent
     *
     * @param array $requiredTables
     * @return bool
     */
    private static function checkRequiredTables($requiredTables)
    {
        try {
            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    \Log::info("Table '$table' does not exist");
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            \Log::error("Error checking required tables: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normaliser un nom de table pour la vérification de correspondance
     * Cette fonction prend en charge les variations de noms ESBTP
     *
     * @param string $tableName
     * @return string
     */
    public static function normalizeTableName($tableName)
    {
        // Normaliser les tables esbtp pour assurer une correspondance correcte
        if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
            return str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
        }

        return $tableName;
    }

    /**
     * Extraction améliorée des noms de tables depuis les fichiers de migration
     *
     * @return array
     */
    public static function getExpectedTables()
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        $tableNames = [];

        foreach ($migrationFiles as $file) {
            $filename = basename($file);

            // Extraire le nom de la table à partir du nom du fichier
            if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
                $tableName = $matches[1];
                $tableName = self::normalizeTableName($tableName);
                $tableNames[] = $tableName;
            }

            // Analyser le contenu du fichier pour trouver tous les appels Schema::create
            $content = file_get_contents($file);
            if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $contentMatches)) {
                foreach ($contentMatches[1] as $tableName) {
                    $tableName = self::normalizeTableName($tableName);
                    if (!in_array($tableName, $tableNames)) {
                        $tableNames[] = $tableName;
                    }
                }
            }
        }

        // Log pour le débogage
        Log::info('Tables attendues: ' . implode(', ', $tableNames));

        return $tableNames;
    }

    /**
     * Vérifier si toutes les tables ESBTP existent
     *
     * @return bool
     */
    public static function allESBTPTablesExist()
    {
        try {
            if (!self::isDatabaseConfigured()) {
                Log::info("allESBTPTablesExist: La base de données n'est pas configurée");
                return false;
            }

            $esbtpTables = [
                'esbtp_inscriptions',
                'esbtp_etudiants',
                'esbtp_parents',
                'esbtp_etudiant_parent',
                'esbtp_paiements',
                'esbtp_classes',
                'esbtp_matieres',
                'esbtp_evaluations',
                'esbtp_notes',
                'esbtp_bulletins',
                'esbtp_resultat_matieres',
                'esbtp_annonces',
                'esbtp_annonce_classe',
                'esbtp_annonce_etudiant',
                'esbtp_emploi_temps',
                'esbtp_seance_cours',
                'esbtp_attendances'
            ];

            foreach ($esbtpTables as $table) {
                $exists = Schema::hasTable($table);
                Log::info("Table {$table} existe: " . ($exists ? 'Oui' : 'Non'));

                if (!$exists) {
                    Log::info("Table ESBTP manquante: {$table}");
                    return false;
                }
            }

            Log::info("Toutes les tables ESBTP existent");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification des tables ESBTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si toutes les tables requises existent dans la base de données
     * Cette fonction parcourt tous les fichiers de migration pour extraire les noms de tables
     * et vérifie leur existence
     *
     * @return array
     */
    public static function checkAllRequiredTables()
    {
        try {
            if (!self::isDatabaseConfigured()) {
                Log::info("checkAllRequiredTables: La base de données n'est pas configurée");
                return [
                    'all_exist' => false,
                    'missing_tables' => [],
                    'existing_tables' => [],
                    'total_tables' => 0,
                    'missing_count' => 0,
                    'existing_count' => 0
                ];
            }

            // Extraire les tables attendues de tous les fichiers de migration
            $expectedTables = self::getExpectedTables();

            $existingTables = [];
            $missingTables = [];

            // Vérifier l'existence de chaque table
            foreach ($expectedTables as $table) {
                $exists = Schema::hasTable($table);
                Log::info("Table {$table} existe: " . ($exists ? 'Oui' : 'Non'));

                if ($exists) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            }

            $result = [
                'all_exist' => count($missingTables) === 0,
                'missing_tables' => $missingTables,
                'existing_tables' => $existingTables,
                'total_tables' => count($expectedTables),
                'missing_count' => count($missingTables),
                'existing_count' => count($existingTables)
            ];

            // Journalisation du résultat
            Log::info("Vérification des tables requises: " . count($existingTables) . "/" . count($expectedTables) . " tables existent");
            if (count($missingTables) > 0) {
                Log::info("Tables manquantes: " . implode(', ', $missingTables));
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification des tables requises: " . $e->getMessage());
            return [
                'all_exist' => false,
                'missing_tables' => [],
                'existing_tables' => [],
                'total_tables' => 0,
                'missing_count' => 0,
                'existing_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier les tables par catégorie (modules)
     * Cette fonction vérifie l'existence des tables regroupées par module
     *
     * @return array
     */
    public static function checkTablesByCategory()
    {
        try {
            if (!self::isDatabaseConfigured()) {
                Log::info("checkTablesByCategory: La base de données n'est pas configurée");
                return [
                    'categories' => [],
                    'all_complete' => false
                ];
            }

            // Définir les catégories de tables (modules)
            $categories = [
                'esbtp' => [
                    'name' => 'KLASSCI',
                    'description' => 'Module métier KLASSCI (étudiants, classes, paiements, notes)',
                    'tables' => [
                        'esbtp_inscriptions',
                        'esbtp_etudiants',
                        'esbtp_parents',
                        'esbtp_etudiant_parent',
                        'esbtp_paiements',
                        'esbtp_classes',
                        'esbtp_matieres',
                        'esbtp_evaluations',
                        'esbtp_notes',
                        'esbtp_bulletins',
                        'esbtp_resultat_matieres',
                        'esbtp_annonces',
                        'esbtp_annonce_classe',
                        'esbtp_annonce_etudiant',
                        'esbtp_emploi_temps',
                        'esbtp_seance_cours',
                        'esbtp_attendances'
                    ]
                ],
                'core' => [
                    'name' => 'Noyau',
                    'description' => 'Tables fondamentales du système',
                    'tables' => [
                        'users',
                        'roles',
                        'permissions',
                        'model_has_roles',
                        'model_has_permissions',
                        'role_has_permissions',
                        'migrations'
                    ]
                ],
                'school' => [
                    'name' => 'École',
                    'description' => 'Gestion académique',
                    'tables' => [
                        'departments',
                        'students',
                        'teachers',
                        'courses',
                        'attendances',
                        'school_classes',
                        'class_courses'
                    ]
                ],
                'admin' => [
                    'name' => 'Administration',
                    'description' => 'Gestion administrative',
                    'tables' => [
                        'notifications',
                        'personal_access_tokens',
                        'sessions'
                    ]
                ]
            ];

            $results = [];
            $allComplete = true;

            // Vérifier chaque catégorie
            foreach ($categories as $key => $category) {
                $missing = [];
                $existing = [];

                foreach ($category['tables'] as $table) {
                    $exists = Schema::hasTable($table);

                    if ($exists) {
                        $existing[] = $table;
                    } else {
                        $missing[] = $table;
                        $allComplete = false;
                    }
                }

                $results[$key] = [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'total' => count($category['tables']),
                    'existing' => $existing,
                    'missing' => $missing,
                    'complete' => count($missing) === 0,
                    'percentage' => count($category['tables']) > 0
                        ? round((count($existing) / count($category['tables'])) * 100)
                        : 0
                ];

                Log::info("Catégorie {$category['name']}: {$results[$key]['percentage']}% complet");
            }

            return [
                'categories' => $results,
                'all_complete' => $allComplete
            ];
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification des tables par catégorie: " . $e->getMessage());
            return [
                'categories' => [],
                'all_complete' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupère la liste des tables existantes dans la base de données
     *
     * @return array
     */
    public static function getExistingTables()
    {
        try {
            if (!self::isDatabaseConfigured()) {
                Log::warning('Impossible de récupérer les tables existantes: la base de données n\'est pas configurée');
                return [];
            }

            $tables = [];
            $results = DB::select('SHOW TABLES');

            if ($results) {
                foreach ($results as $result) {
                    $tables[] = reset($result); // Récupère la première valeur de l'objet
                }
            }

            Log::info('Tables existantes récupérées: ' . count($tables));
            return $tables;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des tables existantes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les noms des tables à partir des fichiers de migration
     *
     * @return array
     */
    public static function getMigrationTableNames()
    {
        try {
            $migrationFiles = self::getMigrationFiles();
            $tableNames = [];

            foreach ($migrationFiles as $file) {
                $filename = basename($file);
                $tablesInFile = [];

                // D'abord, extraire le nom de la table à partir du nom de fichier
                if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
                    $tableName = $matches[1];
                    // Normaliser le nom de la table
                    if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                        $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                    }

                    $tablesInFile[] = $tableName;
                }

                // Ensuite, analyser le contenu du fichier pour trouver les appels Schema::create
                $content = file_get_contents($file);
                if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $contentMatches)) {
                    foreach ($contentMatches[1] as $tableName) {
                        // Normaliser le nom de la table
                        if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                            $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                        }

                        if (!in_array($tableName, $tablesInFile)) {
                            $tablesInFile[] = $tableName;
                        }
                    }
                }

                // Vérifier également la méthode createTable qui pourrait être utilisée dans certaines migrations
                if (preg_match_all('/->createTable\([\'"]([^\'"]+)[\'"]/', $content, $createTableMatches)) {
                    foreach ($createTableMatches[1] as $tableName) {
                        // Normaliser le nom de la table
                        if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                            $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                        }

                        if (!in_array($tableName, $tablesInFile)) {
                            $tablesInFile[] = $tableName;
                        }
                    }
                }

                // Ajouter les tables trouvées dans ce fichier à nos collections
                foreach ($tablesInFile as $tableName) {
                    if (!in_array($tableName, $tableNames)) {
                        $tableNames[] = $tableName;
                    }
                }
            }

            // Journaliser pour le débogage
            \Log::info('Fichiers de migration trouvés: ' . count($migrationFiles));
            \Log::info('Noms de tables extraits: ' . count($tableNames));

            return $tableNames;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'extraction des noms de tables: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les fichiers de migration de Laravel et ESBTP
     *
     * @return array
     */
    private static function getMigrationFiles()
    {
        try {
            $migrationPaths = [
                database_path('migrations'),
                database_path('migrations/esbtp')
            ];

            $files = [];

            foreach ($migrationPaths as $path) {
                if (is_dir($path)) {
                    $directoryFiles = glob($path . '/*.php');
                    if ($directoryFiles !== false) {
                        $files = array_merge($files, $directoryFiles);
                    }
                }
            }

            return $files;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des fichiers de migration: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie que les données de base ESBTP sont présentes dans la base de données
     *
     * @return array Tableau contenant le statut et les détails des données manquantes
     */
    public static function checkESBTPData()
    {
        try {
            // Résultat par défaut
            $result = [
                'success' => true,
                'filieres' => true,
                'niveaux' => true,
                'annees' => true,
                'etudiants' => true,
                'parents' => true,
                'paiements' => true,
                'missing_data' => []
            ];

            // Vérifier les filières
            $filieresCount = DB::table('esbtp_filieres')->count();
            if ($filieresCount < 5) { // 5 filières au total pour ESBTP
                $result['success'] = false;
                $result['filieres'] = false;
                $result['missing_data'][] = 'Filières ESBTP';
            }

            // Vérifier les niveaux d'études
            $niveauxCount = DB::table('esbtp_niveau_etudes')->count();
            if ($niveauxCount < 2) { // 2 niveaux (BTS1, BTS2)
                $result['success'] = false;
                $result['niveaux'] = false;
                $result['missing_data'][] = 'Niveaux d\'études ESBTP';
            }

            // Vérifier les années universitaires
            $anneesCount = DB::table('esbtp_annee_universitaires')->count();
            if ($anneesCount < 1) { // Au moins une année universitaire
                $result['success'] = false;
                $result['annees'] = false;
                $result['missing_data'][] = 'Années universitaires ESBTP';
            }

            // Vérifier la table étudiants
            if (Schema::hasTable('esbtp_etudiants')) {
                \Log::info('Table esbtp_etudiants existe');
            } else {
                $result['success'] = false;
                $result['etudiants'] = false;
                $result['missing_data'][] = 'Table étudiants ESBTP';
                \Log::warning('Table esbtp_etudiants n\'existe pas');
            }

            // Vérifier la table parents
            if (Schema::hasTable('esbtp_parents')) {
                \Log::info('Table esbtp_parents existe');
            } else {
                $result['success'] = false;
                $result['parents'] = false;
                $result['missing_data'][] = 'Table parents ESBTP';
                \Log::warning('Table esbtp_parents n\'existe pas');
            }

            // Vérifier la table paiements
            if (Schema::hasTable('esbtp_paiements')) {
                \Log::info('Table esbtp_paiements existe');
            } else {
                $result['success'] = false;
                $result['paiements'] = false;
                $result['missing_data'][] = 'Table paiements ESBTP';
                \Log::warning('Table esbtp_paiements n\'existe pas');
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification des données ESBTP: ' . $e->getMessage());
            return [
                'success' => false,
                'filieres' => false,
                'niveaux' => false,
                'annees' => false,
                'etudiants' => false,
                'parents' => false,
                'paiements' => false,
                'missing_data' => ['Données ESBTP (erreur de connexion)'],
                'error' => $e->getMessage()
            ];
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use App\Helpers\InstallationHelper;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class InstallController extends Controller
{
    private const REQUIRED_PHP_VERSION = '8.3.0';

    /**
     * Display the installation welcome page
     */
    public function index()
    {
        // Marquer que nous sommes dans le processus d'installation
        session(['installation_in_progress' => true]);
        
        // Vérifier si l'application est déjà installée
        if (InstallationHelper::isInstalled()) {
            return redirect('/')->with('error', 'L\'application est déjà installée.');
        }

        // Journalisation de l'accès à la page d'installation
        Log::info('Accès à la page d\'installation');

        // Redirection vers l'étape appropriée en fonction de l'état d'installation
        $status = InstallationHelper::getInstallationStatus();
        
        if ($status['db_configured'] && $status['all_tables_exist'] && !$status['has_admin_user']) {
            // Si la BDD est configurée, les tables existent mais pas d'admin, aller à l'étape admin
            return redirect()->route('install.admin');
        } elseif ($status['db_configured'] && !$status['all_tables_exist']) {
            // Si la BDD est configurée mais les tables n'existent pas, aller à l'étape migration
            return redirect()->route('install.migration');
        } elseif (!$status['db_configured']) {
            // Si la BDD n'est pas configurée, rester à l'étape database
            return view('install.welcome');
        }

        return view('install.welcome');
    }

    /**
     * Display the database configuration page
     */
    public function database()
    {
        // Vérifier si l'application est déjà installée ET s'il existe un utilisateur admin
        $installationStatus = InstallationHelper::getInstallationStatus();
        $hasAdminUser = InstallationHelper::hasAdminUser();
        
        // Ne rediriger vers login que si l'application est installée ET qu'un admin existe
        if ($installationStatus['installed'] && $hasAdminUser) {
            \Log::info("InstallController database - Redirecting to login (installed and admin exists)");
            return redirect('/login');
        }

        return view('install.database');
    }

    /**
     * Retourne les prérequis réels du serveur avant installation.
     */
    public function requirements()
    {
        $requiredExtensions = ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml'];
        $extensions = collect($requiredExtensions)->map(fn ($extension) => [
            'name' => $extension,
            'ok' => extension_loaded($extension),
        ])->values();

        $paths = collect([
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            '.env' => base_path('.env'),
        ])->map(fn ($path, $label) => [
            'name' => $label,
            'path' => $path,
            'ok' => file_exists($path) && is_writable($path),
        ])->values();

        $checks = [
            [
                'key' => 'php',
                'label' => 'Version PHP',
                'ok' => version_compare(PHP_VERSION, self::REQUIRED_PHP_VERSION, '>='),
                'detail' => 'PHP ' . PHP_VERSION . ' détecté, PHP ' . self::REQUIRED_PHP_VERSION . ' minimum requis.',
            ],
            [
                'key' => 'extensions',
                'label' => 'Extensions PHP',
                'ok' => $extensions->every(fn ($item) => $item['ok']),
                'detail' => $extensions->where('ok', false)->pluck('name')->values()->all(),
            ],
            [
                'key' => 'writable_paths',
                'label' => 'Dossiers inscriptibles',
                'ok' => $paths->every(fn ($item) => $item['ok']),
                'detail' => $paths->where('ok', false)->pluck('name')->values()->all(),
            ],
            [
                'key' => 'vendor',
                'label' => 'Dépendances Composer',
                'ok' => file_exists(base_path('vendor/autoload.php')),
                'detail' => file_exists(base_path('vendor/autoload.php')) ? 'vendor/autoload.php présent.' : 'Exécutez composer install sur le serveur avant de continuer.',
            ],
            [
                'key' => 'document_root',
                'label' => 'Document root',
                'ok' => str_ends_with(str_replace('\\', '/', (string) public_path()), '/public'),
                'detail' => public_path(),
            ],
        ];

        $allOk = collect($checks)->every(fn ($check) => (bool) $check['ok']);

        return $this->installJson($allOk, $allOk ? 'install.requirements.ready' : 'install.requirements.blocked', [
            'checks' => $checks,
            'tenant' => [
                'code' => config('app.tenant_code'),
                'url' => config('app.url'),
                'name' => config('app.name'),
            ],
            'cpanel_prerequisites' => [
                'subdomain' => 'uic.klassci.com',
                'document_root' => 'public_html/uic/public',
                'database' => 'À créer dans cPanel ou via UAPI avant la connexion DB.',
            ],
        ], $allOk ? 'Prérequis serveur validés.' : 'Certains prérequis serveur bloquent l’installation.');
    }

    /**
     * Process the database configuration
     */
    public function setupDatabase(Request $request)
    {
        // Validate the request
        $request->validate([
            'host' => 'required',
            'port' => 'required',
            'database' => 'required',
            'username' => 'required',
            'password' => 'nullable',
            'app_name' => 'nullable|string|max:120',
            'app_url' => 'nullable|url|max:255',
            'tenant_code' => 'nullable|regex:/^[a-z0-9-]+$/|max:80',
        ]);

        try {
            // Debug information
            \Log::info('Setup database request received');
            \Log::info('Host: ' . $request->host);
            \Log::info('Database: ' . $request->database);
            
            // Test the database connection
            $connection = $this->testDatabaseConnection(
                $request->host,
                $request->port,
                $request->username,
                $request->password,
                $request->database
            );

            if ($connection['status'] === 'success') {
                // Update the .env file with database credentials
                $this->updateEnvironmentFile([
                    'APP_NAME' => $request->input('app_name', config('app.name')),
                    'APP_URL' => $request->input('app_url', config('app.url')),
                    'TENANT_CODE' => $request->input('tenant_code', config('app.tenant_code')),
                    'DB_HOST' => $request->host,
                    'DB_PORT' => $request->port,
                    'DB_DATABASE' => $request->database,
                    'DB_USERNAME' => $request->username,
                    'DB_PASSWORD' => $request->password,
                ]);

                // Clear config cache and regenerate config cache
                Artisan::call('config:clear');
                
                // Store database configuration in session to ensure it's available.
                // Note (audit sécurité 2026-05-21) : db_password retiré de la session.
                // Le mot de passe est déjà persisté dans .env via updateEnvironmentFile()
                // ci-dessus, inutile de le stocker en plus dans le session file (driver
                // file = lisible si compromission FS).
                session([
                    'db_configured' => true,
                    'db_host' => $request->host,
                    'db_port' => $request->port,
                    'db_database' => $request->database,
                    'db_username' => $request->username,
                ]);
                
                // Debug information
                \Log::info('Database connection successful');
                \Log::info('Session db_configured set to: ' . (session('db_configured') ? 'true' : 'false'));

                return $this->installJson(true, 'install.database.connected', [
                    'database_exists' => $connection['database_exists'],
                    'tables_exist' => $connection['tables_exist'] ?? false,
                ], 'Connexion à la base de données validée.', route('install.migration'));
            }

            return $this->installJson(false, 'install.database.connection_failed', [], $connection['message'], null, 422);
        } catch (Exception $e) {
            // Log détaillé uniquement en local — pas exposer le contenu d'erreur DB
            // au client en prod (peut révéler version MySQL, structure, etc.).
            \Log::error('Database connection failed', [
                'error' => app()->environment('local') ? $e->getMessage() : 'redacted',
            ]);

            return response()->json([
                'status' => 'error',
                // Pas de $e->getMessage() en prod — détail de l'erreur DB seulement en local.
                'message' => app()->environment('local')
                    ? 'Database connection failed: ' . $e->getMessage()
                    : 'Database connection failed. Verify the credentials and host reachability.',
            ], 422);
        }
    }

    /**
     * Display the migration page
     */
    public function migration()
    {
        // Vérifier si l'application est déjà installée ET s'il existe un utilisateur admin
        $installationStatus = InstallationHelper::getInstallationStatus();
        $hasAdminUser = InstallationHelper::hasAdminUser();
        
        // Ne rediriger vers login que si l'application est installée ET qu'un admin existe
        if ($installationStatus['installed'] && $hasAdminUser) {
            \Log::info("InstallController migration - Redirecting to login (installed and admin exists)");
            return redirect('/login');
        }

        // Check if database is configured
        if (!session('db_configured')) {
            return redirect()->route('install.database')
                ->with('error', 'Veuillez configurer la base de données avant de continuer.');
        }
        
        // Check database status
        $dbStatus = $this->checkDatabaseStatus();
        
        // Get installation status to check migration match percentage
        $installationStatus = InstallationHelper::getInstallationStatus();
        
        // Check table status by module (category)
        $moduleStatus = InstallationHelper::checkTablesByCategory();
        
        // Get complete table status
        $allTablesStatus = InstallationHelper::checkAllRequiredTables();
        
        // Merge all status information
        $dbStatus = array_merge($dbStatus, [
            'match_percentage' => $installationStatus['match_percentage'],
            'can_skip_migration' => $installationStatus['match_percentage'] == 100,
            'installation_status' => $installationStatus,
            'module_status' => $moduleStatus,
            'all_tables_status' => $allTablesStatus
        ]);
        
        // Log the migration status
        \Log::info("Migration page - DB Status: " . json_encode($dbStatus));
        
        return view('install.migration', [
            'dbStatus' => $dbStatus
        ]);
    }

    /**
     * Exécute les migrations pour créer les tables dans la base de données.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function runMigration(Request $request)
    {
        try {
            ini_set('max_execution_time', 600);
            set_time_limit(600);

            session()->forget(['migration_errors', 'db_connection_error']);

            if (!InstallationHelper::isDatabaseConfigured()) {
                return $this->installJson(
                    false,
                    'install.migration.database_not_configured',
                    [],
                    'La base de données n’est pas configurée. Revenez à l’étape précédente.',
                    route('install.database'),
                    422
                );
            }

            $terminal = [];
            $databaseCreated = false;
            $databaseExists = session('database_exists', false);

            if (!$databaseExists) {
                $terminal[] = 'Base absente, tentative de création avec les droits MySQL fournis.';
                $dbConfig = config('database.connections.mysql');
                $databaseCreated = $this->createDatabase(
                    $dbConfig['host'],
                    $dbConfig['port'],
                    $dbConfig['username'],
                    $dbConfig['password'],
                    $dbConfig['database']
                );

                if (!$databaseCreated) {
                    return $this->installJson(
                        false,
                        'install.migration.database_create_failed',
                        ['terminal' => $terminal],
                        session('db_connection_error') ?? 'Impossible de créer la base. Créez-la dans cPanel, puis relancez.',
                        null,
                        422
                    );
                }
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $terminal[] = trim(Artisan::output()) ?: 'Caches Laravel vidés.';

            Artisan::call('migrate', ['--force' => true]);
            $terminal[] = trim(Artisan::output()) ?: 'Migrations vérifiées.';

            $setupResult = $this->runSetupScript();
            $terminal = array_merge($terminal, $setupResult['output']);

            if (!$setupResult['ok']) {
                session(['migration_errors' => $setupResult['message']]);

                return $this->installJson(
                    false,
                    'install.setup.failed',
                    ['terminal' => $terminal, 'exit_code' => $setupResult['exit_code']],
                    $setupResult['message'],
                    null,
                    422
                );
            }

            $esbtpDataCheck = InstallationHelper::checkESBTPData();
            session(['esbtp_data_check' => $esbtpDataCheck]);

            return $this->installJson(true, 'install.migration.completed', [
                'database_created' => $databaseCreated,
                'esbtp_data_check' => $esbtpDataCheck,
                'terminal' => $terminal,
            ], 'Migrations, permissions, paramètres et seeders terminés.', route('install.admin'));
        } catch (\Exception $e) {
            \Log::error("Erreur critique lors de l'exécution des migrations: " . $e->getMessage());
            session(['migration_errors' => $e->getMessage()]);

            return $this->installJson(
                false,
                'install.migration.exception',
                ['terminal' => [$e->getMessage()]],
                'Une erreur est survenue pendant l’installation Laravel.',
                null,
                500
            );
        }
    }

    /**
     * Vérifie l'état des migrations pour déterminer si on peut sauter cette étape
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMigrations()
    {
        // Vérifier l'état de l'installation
        $installationStatus = InstallationHelper::getInstallationStatus();
        
        // Vérifier l'état des modules
        $moduleStatus = InstallationHelper::checkTablesByCategory();
        
        // Vérifier toutes les tables requises
        $allTablesStatus = InstallationHelper::checkAllRequiredTables();
        
        // Déterminer si on peut sauter la migration
        $canSkipMigration = false;
        
        // Critères pour autoriser le skip:
        // 1. Plus de 95% des tables sont créées
        // 2. OU tous les modules critiques sont complets (core, admin, user, school)
        if ($installationStatus['match_percentage'] >= 95) {
            $canSkipMigration = true;
            \Log::info("Migration skip autorisé par pourcentage: {$installationStatus['match_percentage']}%");
        } elseif (
            isset($moduleStatus['categories']['core']) && $moduleStatus['categories']['core']['complete'] &&
            isset($moduleStatus['categories']['admin']) && $moduleStatus['categories']['admin']['complete'] &&
            isset($moduleStatus['categories']['user']) && $moduleStatus['categories']['user']['complete'] &&
            isset($moduleStatus['categories']['school']) && $moduleStatus['categories']['school']['complete']
        ) {
            $canSkipMigration = true;
            \Log::info("Migration skip autorisé par modules critiques complets");
        }
        
        // Détails sur les tables manquantes
        $missingTables = [];
        if (!empty($allTablesStatus['missing_tables'])) {
            $missingTables = $allTablesStatus['missing_tables'];
        }
        
        // Retourner le résultat
        return response()->json([
            'can_skip_migration' => $canSkipMigration,
            'match_percentage' => $installationStatus['match_percentage'],
            'modules_status' => $moduleStatus,
            'all_tables_status' => [
                'missing_tables_count' => count($missingTables),
                'missing_tables' => $missingTables
            ],
            'message' => $canSkipMigration 
                ? 'La base de données est suffisamment complète pour continuer.' 
                : 'Des tables importantes sont manquantes. Veuillez exécuter les migrations.'
        ]);
    }

    /**
     * Display the admin creation page
     */
    public function admin()
    {
        // Vérifier si l'application est déjà installée ET s'il existe un utilisateur admin
        $installationStatus = InstallationHelper::getInstallationStatus();
        $hasAdminUser = InstallationHelper::hasAdminUser();
        
        // Ne rediriger vers login que si l'application est installée ET qu'un admin existe
        if ($installationStatus['installed'] && $hasAdminUser) {
            \Log::info("InstallController admin - Redirecting to login (installed and admin exists)");
            return redirect('/login');
        }

        // Check if database is migrated
        if (!$this->isDatabaseMigrated()) {
            return redirect()->route('install.migration')
                ->with('error', 'Please run the migrations first');
        }

        return view('install.admin');
    }

    /**
     * Create the admin user
     */
    public function setupAdmin(Request $request)
    {
        try {
            // Validation des champs
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            // Création de l'utilisateur admin
            $admin = new User();
            $admin->name = $validated['name'];
            $admin->username = $validated['username'];
            $admin->email = $validated['email'];
            $admin->password = Hash::make($validated['password']);
            $admin->save();
            
            // Assignation du rôle superAdmin
            try {
                if (Schema::hasTable('roles')) {
                    $superAdminRole = Role::where('name', 'superAdmin')->first();
                    if ($superAdminRole) {
                        $admin->assignRole($superAdminRole);
                    } else {
                        \Log::warning("Rôle superAdmin non trouvé. Création d'un nouveau rôle.");
                        $superAdminRole = Role::create(['name' => 'superAdmin']);
                        $admin->assignRole($superAdminRole);
                    }
                } else {
                    \Log::warning("La table des rôles n'existe pas encore.");
                }
            } catch (\Exception $e) {
                \Log::error("Erreur lors de l'assignation du rôle: " . $e->getMessage());
                // Continue execution - role assignment is not critical for installation
            }
            
            $request->session()->put('admin_created', true);
            $request->session()->put('admin_name', $validated['name']);
            $request->session()->put('admin_username', $validated['username']);
            $request->session()->put('admin_email', $validated['email']);
            
            // Journalisation
            \Log::info("Admin user created successfully: {$validated['username']}");
            
            // Réponse en fonction du type de requête
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Administrateur créé avec succès',
                    'redirect' => route('install.complete')
                ]);
            }

            return redirect()->route('install.complete')->with('success', 'Administrateur créé avec succès');
        } catch (\Exception $e) {
            \Log::error("Error creating admin: " . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création de l\'administrateur: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors(['error' => 'Erreur lors de la création de l\'administrateur: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the completion page
     */
    public function complete()
    {
        // Vérifier si l'application est déjà installée ET s'il existe un utilisateur admin
        $installationStatus = InstallationHelper::getInstallationStatus();
        $hasAdminUser = InstallationHelper::hasAdminUser();
        
        // Ne rediriger vers login que si l'application est installée ET qu'un admin existe
        if ($installationStatus['installed'] && $hasAdminUser) {
            \Log::info("InstallController complete - Redirecting to login (installed and admin exists)");
            return redirect('/login');
        }

        // Check if admin user exists
        if (!InstallationHelper::hasAdminUser()) {
            return redirect()->route('install.admin')
                ->with('error', 'Please create an admin user first');
        }

        // Generate application key if not already generated
        if (env('APP_KEY') == '') {
            Artisan::call('key:generate');
        }

        return view('install.complete');
    }

    /**
     * Finaliser l'installation
     */
    public function finalize(Request $request)
    {
        try {
            // Vérifier si un admin existe déjà
            if (!InstallationHelper::hasAdminUser()) {
                Log::error("Tentative de finalisation sans utilisateur admin");
                return redirect()->route('install.admin')
                    ->with('error', 'Vous devez d\'abord créer un compte administrateur');
            }
            
            // Marquer l'application comme installée UNIQUEMENT si un admin existe
            InstallationHelper::markAsInstalled();
            
            // Nettoyer la session d'installation
            session()->forget('installation_in_progress');
            
            // Rediriger vers la page de connexion avec un message de succès
            return redirect('/login')
                ->with('success', 'Installation terminée avec succès! Veuillez vous connecter.');
                
        } catch (\Exception $e) {
            Log::error("Erreur lors de la finalisation de l'installation: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur s\'est produite lors de la finalisation de l\'installation: ' . $e->getMessage());
        }
    }

    /**
     * Check if the database is migrated
     */
    private function isDatabaseMigrated()
    {
        try {
            return Schema::hasTable('users') && Schema::hasTable('roles');
        } catch (Exception $e) {
            return false;
        }
    }

    private function installJson(bool $ok, string $code, array $data = [], string $message = '', ?string $nextUrl = null, int $status = 200)
    {
        return response()->json([
            'ok' => $ok,
            'success' => $ok,
            'status' => $ok ? 'success' : 'error',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'next_url' => $nextUrl,
            'redirect' => $nextUrl,
        ], $status);
    }

    private function runSetupScript(): array
    {
        $script = base_path('setup.php');

        if (!File::exists($script)) {
            return [
                'ok' => false,
                'message' => 'Le fichier setup.php est introuvable à la racine du projet.',
                'exit_code' => 127,
                'output' => ['setup.php introuvable.'],
            ];
        }

        $command = '"' . PHP_BINARY . '" "' . $script . '" --force 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        $cleanOutput = array_values(array_filter(array_map(
            fn ($line) => trim(preg_replace('/\x1b\[[0-9;]*m/', '', (string) $line)),
            $output
        )));

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0
                ? 'Initialisation setup.php terminée.'
                : 'setup.php a échoué. Consultez la sortie terminal et corrigez le point bloquant.',
            'exit_code' => $exitCode,
            'output' => $cleanOutput,
        ];
    }

    /**
     * Test the database connection
     *
     * @param string $host Hôte de la base de données
     * @param string $port Port de la base de données
     * @param string $username Nom d'utilisateur de la base de données
     * @param string $password Mot de passe de la base de données
     * @param string $database Nom de la base de données
     * @return array Retourne un tableau associatif avec le statut de la connexion et d'autres informations
     */
    private function testDatabaseConnection($host, $port, $username, $password, $database)
    {
        // Créer le DSN pour la connexion sans spécifier de base de données
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        
        try {
            // Effacer les sessions précédentes
            session()->forget(['db_connection_error', 'database_created']);
            
            // Essayer de se connecter au serveur MySQL sans spécifier de base de données
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            
            // Vérifier si la base de données existe
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$database}'");
            $databaseExists = $stmt->rowCount() > 0;
            
            if (!$databaseExists) {
                // La base de données n'existe pas, on mémorise qu'elle devra être créée
                \Log::info("Base de données '{$database}' introuvable. Elle sera créée lors de la migration.");
                session(['database_exists' => false]);
                
                return [
                    'status' => 'success',
                    'message' => 'Connexion au serveur réussie. La base de données sera créée lors de la migration.',
                    'database_exists' => false,
                    'tables_exist' => false
                ];
            }
            
            // Tenter de se connecter à la base de données spécifique
            $dsnWithDb = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdoWithDb = new \PDO($dsnWithDb, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ]);
            
            // Vérifier si des tables existent dans la base de données
            $stmt = $pdoWithDb->query("SHOW TABLES");
            $tablesExist = $stmt->rowCount() > 0;
            
            // Mémoriser l'état de la base de données en session
            session([
                'database_exists' => true,
                'tables_exist' => $tablesExist
            ]);
            
            // La connexion est établie et la base existe
            return [
                'status' => 'success',
                'message' => 'Connexion à la base de données réussie.',
                'database_exists' => true,
                'tables_exist' => $tablesExist
            ];
            
        } catch (\PDOException $e) {
            // Journaliser l'erreur de connexion
            $errorCode = $e->getCode();
            $errorMessage = "";
            
            // Traiter les erreurs courantes de manière spécifique
            switch ($errorCode) {
                case 1045: // Access denied for user
                    $errorMessage = "Accès refusé pour l'utilisateur '{$username}'. Vérifiez vos identifiants MySQL.";
                    break;
                case 2002: // Connection refused
                    $errorMessage = "Impossible de se connecter au serveur MySQL ({$host}:{$port}). Vérifiez que le serveur est bien démarré.";
                    break;
                case 1049: // Unknown database
                    $errorMessage = "La base de données '{$database}' n'existe pas encore. Elle sera créée automatiquement lors de la migration.";
                    session(['database_exists' => false]);
                    return [
                        'status' => 'success',
                        'message' => $errorMessage,
                        'database_exists' => false,
                        'tables_exist' => false
                    ];
                default:
                    $errorMessage = "Erreur de connexion à la base de données: " . $e->getMessage();
            }
            
            \Log::error($errorMessage);
            
            // Stocker l'erreur en session pour l'afficher à l'utilisateur
            session(['db_connection_error' => $errorMessage]);
            
            return [
                'status' => 'error',
                'message' => $errorMessage,
                'error_code' => $errorCode
            ];
        } catch (\Exception $e) {
            // Pour toute autre exception
            $errorMessage = "Erreur inattendue lors de la connexion à la base de données: " . $e->getMessage();
            \Log::error($errorMessage);
            
            // Stocker l'erreur en session pour l'afficher à l'utilisateur
            session(['db_connection_error' => $errorMessage]);
            
            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }

    /**
     * Get table names from migration files by analyzing their content
     */
    private function getMigrationTableNames()
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        $tableNames = [];
        $migrationTableMap = [];
        
        foreach ($migrationFiles as $file) {
            $filename = basename($file);
            $tablesInFile = [];
            
            // First, try to extract table name from filename using regex
            if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
                $tableName = $matches[1];
                // Normaliser le nom de la table en supprimant les underscores dans le préfixe pour les tables ESBTP
                if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                    $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                }
                
                $tablesInFile[] = $tableName;
            }
            
            // Then, analyze file content to find all Schema::create calls
            $content = file_get_contents($file);
            if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $contentMatches)) {
                foreach ($contentMatches[1] as $tableName) {
                    // Normaliser le nom de la table en supprimant les underscores dans le préfixe pour les tables ESBTP
                    if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                        $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                    }
                    
                    if (!in_array($tableName, $tablesInFile)) {
                        $tablesInFile[] = $tableName;
                    }
                }
            }
            
            // Also check for createTable method which might be used in some migrations
            if (preg_match_all('/->createTable\([\'"]([^\'"]+)[\'"]/', $content, $createTableMatches)) {
                foreach ($createTableMatches[1] as $tableName) {
                    // Normaliser le nom de la table en supprimant les underscores dans le préfixe pour les tables ESBTP
                    if (strpos($tableName, 'esbtp_') === 0 || strpos($tableName, 'e_s_b_t_p_') === 0) {
                        $tableName = str_replace('e_s_b_t_p_', 'esbtp_', $tableName);
                    }
                    
                    if (!in_array($tableName, $tablesInFile)) {
                        $tablesInFile[] = $tableName;
                    }
                }
            }
            
            // Add tables found in this file to our collections
            foreach ($tablesInFile as $tableName) {
                if (!in_array($tableName, $tableNames)) {
                    $tableNames[] = $tableName;
                }
                
                if (!isset($migrationTableMap[$filename])) {
                    $migrationTableMap[$filename] = [];
                }
                $migrationTableMap[$filename][] = $tableName;
            }
        }
        
        // Log for debugging
        \Log::info('Migration files found: ' . count($migrationFiles));
        \Log::info('Table names extracted: ' . count($tableNames));
        \Log::info('Table names: ' . implode(', ', $tableNames));
        
        // Also log migrations with multiple tables
        $multiTableMigrations = array_filter($migrationTableMap, function($tables) {
            return count($tables) > 1;
        });
        
        if (count($multiTableMigrations) > 0) {
            foreach ($multiTableMigrations as $file => $tables) {
                \Log::info("Migration {$file} creates multiple tables: " . implode(', ', $tables));
            }
        }
        
        return [
            'table_names' => $tableNames,
            'migration_table_map' => $migrationTableMap,
            'migration_files' => $migrationFiles,
            'multi_table_migrations' => $multiTableMigrations
        ];
    }

    /**
     * Check database connection and status
     */
    private function checkDatabaseStatus()
    {
        try {
            // Get database configuration from session
            $host = session('db_host', env('DB_HOST'));
            $port = session('db_port', env('DB_PORT'));
            $database = session('db_database', env('DB_DATABASE'));
            $username = session('db_username', env('DB_USERNAME'));
            $password = session('db_password', env('DB_PASSWORD'));
            
            // Test database connection
            $connection = $this->testDatabaseConnection($host, $port, $username, $password, $database);
            
            if ($connection['status'] === 'success') {
                // Get migration table names
                $migrationData = $this->getMigrationTableNames();
                $migrationTables = $migrationData['table_names'];
                $migrationTableMap = $migrationData['migration_table_map'];
                $migrationFiles = $migrationData['migration_files'];
                $multiTableMigrations = $migrationData['multi_table_migrations'];
                
                // Get existing tables
                $existingTables = [];
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = reset($table); // Get the first value from the object
                    $existingTables[] = $tableName;
                }
                
                // Calculate match percentage
                $migrationTablesCount = count($migrationTables);
                $existingTablesCount = count($existingTables);
                
                // Find missing tables (in migrations but not in database)
                $missingTables = array_diff($migrationTables, $existingTables);
                
                // Find extra tables (in database but not in migrations)
                $extraTables = array_diff($existingTables, $migrationTables);
                
                // Calculate match percentage
                $matchingTables = array_intersect($migrationTables, $existingTables);
                $matchCount = count($matchingTables);
                $matchPercentage = ($migrationTablesCount > 0) 
                    ? round(($matchCount / $migrationTablesCount) * 100, 2) 
                    : 0;
                
                // Log detailed information for debugging
                \Log::info("Database status check:");
                \Log::info("- Migration tables: {$migrationTablesCount}");
                \Log::info("- Existing tables: {$existingTablesCount}");
                \Log::info("- Matching tables: {$matchCount}");
                \Log::info("- Match percentage: {$matchPercentage}%");
                \Log::info("- Missing tables: " . implode(', ', $missingTables));
                \Log::info("- Extra tables: " . implode(', ', $extraTables));
                
                // Determine if all required tables exist
                $allTablesExist = empty($missingTables);
                
                return [
                    'connected' => true,
                    'database_exists' => $connection['database_exists'],
                    'tables_exist' => $connection['tables_exist'] ?? false,
                    'all_tables_exist' => $allTablesExist,
                    'migration_tables_count' => $migrationTablesCount,
                    'existing_tables_count' => $existingTablesCount,
                    'matching_tables_count' => $matchCount,
                    'match_percentage' => $matchPercentage,
                    'missing_tables' => $missingTables,
                    'extra_tables' => $extraTables,
                    'migration_files_count' => count($migrationFiles),
                    'multi_table_migrations_count' => count($multiTableMigrations)
                ];
            }
            
            return [
                'connected' => false,
                'message' => $connection['message']
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update the environment file
     */
    private function updateEnvironmentFile($data)
    {
        $path = base_path('.env');

        if (File::exists($path)) {
            $content = File::get($path);

            foreach ($data as $key => $value) {
                // If the key exists, replace it
                if (strpos($content, $key . '=') !== false) {
                    $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $key . '=' . $this->formatEnvValue($value), $content);
                } else {
                    // Otherwise, add it
                    $content .= "\n" . $key . '=' . $value;
                }
            }

            File::put($path, $content);
        } else {
            // If .env doesn't exist, create it from .env.example
            $example = base_path('.env.example');
            if (File::exists($example)) {
                $content = File::get($example);

                foreach ($data as $key => $value) {
                    $content = preg_replace('/' . $key . '=(.*)/', $key . '=' . $value, $content);
                }

                File::put($path, $content);
            }
        }
    }

    /**
     * Crée une base de données si elle n'existe pas.
     *
     * @param  string  $host
     * @param  string  $port
     * @param  string  $username
     * @param  string  $password
     * @param  string  $database
     * @return bool
     */
    private function createDatabase($host, $port, $username, $password, $database)
    {
        try {
            // Connexion sans spécifier de base de données
            $pdo = new \PDO("mysql:host={$host};port={$port}", $username, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Conserver le nom original pour les logs
            $dbName = $database;
            
            // Échapper correctement le nom de la base de données en utilisant la méthode de citation de PDO
            // Ceci est important pour gérer les noms de bases de données avec des caractères spéciaux
            $safeDatabase = $pdo->quote($database);
            // Retirer les guillemets simples ajoutés par quote() car ils sont déjà inclus dans la requête SQL
            $safeDatabase = trim($safeDatabase, "'");
            
            // Créer la base de données avec une syntaxe SQL correcte
            $sql = "CREATE DATABASE IF NOT EXISTS `{$safeDatabase}`";
            \Log::info("Exécution de la requête SQL: " . $sql);
            $pdo->exec($sql);
            
            \Log::info("Base de données {$dbName} créée avec succès");
            return true;
        } catch (\PDOException $e) {
            $errorCode = $e->getCode();
            $errorMessage = "";
            
            // Traiter les erreurs courantes de manière spécifique
            switch ($errorCode) {
                case 1045: // Access denied
                    $errorMessage = "Accès refusé pour l'utilisateur '{$username}'. Vérifiez que cet utilisateur a les droits de création de base de données.";
                    break;
                case 1007: // Database exists
                    \Log::info("La base de données {$database} existe déjà. Continuons avec celle-ci.");
                    return true;
                default:
                    $errorMessage = "Erreur lors de la création de la base de données {$database}: " . $e->getMessage();
            }
            
            \Log::error($errorMessage);
            session(['db_connection_error' => $errorMessage]);
            return false;
        } catch (\Exception $e) {
            $errorMessage = "Erreur inattendue lors de la création de la base de données {$database}: " . $e->getMessage();
            \Log::error($errorMessage);
            session(['db_connection_error' => $errorMessage]);
            return false;
        }
    }
} 
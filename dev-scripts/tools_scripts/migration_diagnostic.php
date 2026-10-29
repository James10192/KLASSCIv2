<?php
// Migration Diagnostic Tool
// This script helps diagnose and fix migration issues in Laravel applications

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Function to get Laravel version
function getLaravelVersion() {
    $composerPath = __DIR__ . '/../composer.json';
    if (file_exists($composerPath)) {
        $composer = json_decode(file_get_contents($composerPath), true);
        if (isset($composer['require']['laravel/framework'])) {
            return $composer['require']['laravel/framework'];
        }
    }
    return 'Unknown';
}

// Function to check migration files
function checkMigrationFiles() {
    $migrationsPath = __DIR__ . '/../database/migrations';
    if (!is_dir($migrationsPath)) {
        return [
            'success' => false,
            'message' => 'Migration directory not found',
            'files' => []
        ];
    }
    
    $files = scandir($migrationsPath);
    $migrationFiles = [];
    
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && strpos($file, '.php') !== false) {
            $filePath = $migrationsPath . '/' . $file;
            $content = file_get_contents($filePath);
            
            // Extract class name
            preg_match('/class\s+(\w+)\s+extends\s+Migration/i', $content, $classMatches);
            $className = isset($classMatches[1]) ? $classMatches[1] : 'Unknown';
            
            // Extract table name
            preg_match('/Schema::create\([\'"]([^\'"]+)[\'"]/i', $content, $tableMatches);
            $tableName = isset($tableMatches[1]) ? $tableMatches[1] : 'Unknown';
            
            $migrationFiles[] = [
                'filename' => $file,
                'class_name' => $className,
                'table_name' => $tableName,
                'size' => filesize($filePath),
                'modified' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }
    }
    
    // Check for duplicate class names
    $classNames = [];
    $duplicateClasses = [];
    
    foreach ($migrationFiles as $file) {
        $className = $file['class_name'];
        if (isset($classNames[$className])) {
            if (!isset($duplicateClasses[$className])) {
                $duplicateClasses[$className] = [$classNames[$className]];
            }
            $duplicateClasses[$className][] = $file['filename'];
        } else {
            $classNames[$className] = $file['filename'];
        }
    }
    
    return [
        'success' => true,
        'message' => 'Migration files analyzed',
        'files' => $migrationFiles,
        'duplicates' => $duplicateClasses
    ];
}

// Function to check database connection
function checkDatabaseConnection() {
    $envPath = __DIR__ . '/../.env';
    if (!file_exists($envPath)) {
        return [
            'success' => false,
            'message' => '.env file not found'
        ];
    }
    
    $env = file_get_contents($envPath);
    preg_match('/DB_CONNECTION=([^\n]+)/i', $env, $connectionMatches);
    preg_match('/DB_HOST=([^\n]+)/i', $env, $hostMatches);
    preg_match('/DB_PORT=([^\n]+)/i', $env, $portMatches);
    preg_match('/DB_DATABASE=([^\n]+)/i', $env, $databaseMatches);
    preg_match('/DB_USERNAME=([^\n]+)/i', $env, $usernameMatches);
    
    $connection = isset($connectionMatches[1]) ? $connectionMatches[1] : 'Unknown';
    $host = isset($hostMatches[1]) ? $hostMatches[1] : 'Unknown';
    $port = isset($portMatches[1]) ? $portMatches[1] : 'Unknown';
    $database = isset($databaseMatches[1]) ? $databaseMatches[1] : 'Unknown';
    $username = isset($usernameMatches[1]) ? $usernameMatches[1] : 'Unknown';
    
    try {
        // Try to connect to the database
        $dsn = "{$connection}:host={$host};port={$port};dbname={$database}";
        $pdo = new PDO($dsn, $username, isset($_POST['db_password']) ? $_POST['db_password'] : '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if migrations table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
        $migrationsTableExists = $stmt->rowCount() > 0;
        
        if ($migrationsTableExists) {
            // Get migration status
            $stmt = $pdo->query("SELECT * FROM migrations ORDER BY batch DESC, migration ASC");
            $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $migrations = [];
        }
        
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'connection' => [
                'driver' => $connection,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username
            ],
            'migrations_table_exists' => $migrationsTableExists,
            'migrations' => $migrations
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'connection' => [
                'driver' => $connection,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username
            ]
        ];
    }
}

// Process form submission
$migrationCheck = null;
$dbCheck = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'check_migrations':
                $migrationCheck = checkMigrationFiles();
                break;
            case 'check_database':
                $dbCheck = checkDatabaseConnection();
                break;
        }
    }
}

// Get Laravel version
$laravelVersion = getLaravelVersion();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Diagnostic Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Migration Diagnostic Tool</h1>
            <p class="text-gray-600 mb-6">Laravel Version: <?php echo htmlspecialchars($laravelVersion); ?></p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Check Migrations -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Check Migration Files</h2>
                    <p class="text-gray-600 mb-4">Analyze migration files to detect potential issues.</p>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="action" value="check_migrations">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Analyze Migrations
                        </button>
                    </form>
                </div>
                
                <!-- Check Database -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Check Database Connection</h2>
                    <p class="text-gray-600 mb-4">Verify database connection and migration status.</p>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="action" value="check_database">
                        <div class="mb-4">
                            <label for="db_password" class="block text-gray-700 text-sm font-bold mb-2">Database Password:</label>
                            <input type="password" id="db_password" name="db_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Check Database
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Migration Check Results -->
            <?php if ($migrationCheck): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Migration Files Analysis</h2>
                
                <?php if (!$migrationCheck['success']): ?>
                <div class="p-4 bg-red-100 border border-red-200 rounded-lg mb-4">
                    <p class="text-red-800"><?php echo htmlspecialchars($migrationCheck['message']); ?></p>
                </div>
                <?php else: ?>
                
                <!-- Duplicate Classes -->
                <?php if (!empty($migrationCheck['duplicates'])): ?>
                <div class="p-4 bg-red-100 border border-red-200 rounded-lg mb-6">
                    <h3 class="font-medium text-red-800 mb-2">Duplicate Migration Classes Detected</h3>
                    <div class="space-y-4">
                        <?php foreach ($migrationCheck['duplicates'] as $className => $files): ?>
                        <div class="p-3 bg-red-50 border border-red-200 rounded">
                            <h4 class="font-medium text-red-800 mb-1">Class: <?php echo htmlspecialchars($className); ?></h4>
                            <ul class="list-disc list-inside text-sm text-red-700">
                                <?php foreach ($files as $file): ?>
                                <li><?php echo htmlspecialchars($file); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <p class="text-red-800">
                            <strong>Solution:</strong> Use the <a href="fix_migrations.php" class="underline">Fix Migrations Tool</a> to resolve these conflicts.
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Migration Files Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Filename
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Class Name
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Table Name
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Last Modified
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($migrationCheck['files'] as $file): ?>
                            <tr>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($file['filename']); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php 
                                    $isDuplicate = false;
                                    foreach ($migrationCheck['duplicates'] as $className => $files) {
                                        if ($className === $file['class_name']) {
                                            echo '<span class="text-red-600">' . htmlspecialchars($file['class_name']) . '</span>';
                                            $isDuplicate = true;
                                            break;
                                        }
                                    }
                                    if (!$isDuplicate) {
                                        echo htmlspecialchars($file['class_name']);
                                    }
                                    ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($file['table_name']); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($file['modified']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Database Check Results -->
            <?php if ($dbCheck): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Database Connection Check</h2>
                
                <div class="p-4 <?php echo $dbCheck['success'] ? 'bg-green-100 border border-green-200' : 'bg-red-100 border border-red-200'; ?> rounded-lg mb-4">
                    <p class="<?php echo $dbCheck['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                        <?php echo htmlspecialchars($dbCheck['message']); ?>
                    </p>
                </div>
                
                <div class="mb-6">
                    <h3 class="font-medium mb-2">Connection Details</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="p-2 bg-gray-50">Driver:</div>
                        <div class="p-2"><?php echo htmlspecialchars($dbCheck['connection']['driver']); ?></div>
                        
                        <div class="p-2 bg-gray-50">Host:</div>
                        <div class="p-2"><?php echo htmlspecialchars($dbCheck['connection']['host']); ?></div>
                        
                        <div class="p-2 bg-gray-50">Port:</div>
                        <div class="p-2"><?php echo htmlspecialchars($dbCheck['connection']['port']); ?></div>
                        
                        <div class="p-2 bg-gray-50">Database:</div>
                        <div class="p-2"><?php echo htmlspecialchars($dbCheck['connection']['database']); ?></div>
                        
                        <div class="p-2 bg-gray-50">Username:</div>
                        <div class="p-2"><?php echo htmlspecialchars($dbCheck['connection']['username']); ?></div>
                    </div>
                </div>
                
                <?php if ($dbCheck['success'] && isset($dbCheck['migrations_table_exists'])): ?>
                <div class="mb-4">
                    <h3 class="font-medium mb-2">Migrations Table</h3>
                    <p class="text-sm mb-2">
                        Status: 
                        <?php if ($dbCheck['migrations_table_exists']): ?>
                        <span class="text-green-600">Exists</span>
                        <?php else: ?>
                        <span class="text-red-600">Does not exist</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($dbCheck['migrations_table_exists'] && !empty($dbCheck['migrations'])): ?>
                    <div class="overflow-x-auto mt-2">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        ID
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Migration
                                    </th>
                                    <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Batch
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbCheck['migrations'] as $migration): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                        <?php echo htmlspecialchars($migration['id']); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                        <?php echo htmlspecialchars($migration['migration']); ?>
                                    </td>
                                    <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                        <?php echo htmlspecialchars($migration['batch']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif ($dbCheck['migrations_table_exists']): ?>
                    <p class="text-sm text-gray-600">No migrations have been run yet.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Tools & Resources</h2>
                <div class="space-y-2">
                    <a href="fix_migrations.php" class="block p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                        <h3 class="font-medium text-blue-800">Fix Migrations Tool</h3>
                        <p class="text-sm text-blue-600">Resolve duplicate migration classes and fix migration issues</p>
                    </a>
                    <a href="phpinfo.php" class="block p-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100">
                        <h3 class="font-medium text-green-800">PHP Information</h3>
                        <p class="text-sm text-green-600">View detailed PHP configuration information</p>
                    </a>
                    <a href="/" class="block p-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100">
                        <h3 class="font-medium text-purple-800">Installation Page</h3>
                        <p class="text-sm text-purple-600">Return to the main installation page</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
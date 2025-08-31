<?php
// Fix Migrations Script

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Function to check if a file is writable
function is_really_writable($file)
{
    // If we're on a Unix server with safe_mode off, check using is_writable
    if (DIRECTORY_SEPARATOR === '/' && @ini_get('safe_mode') == false) {
        return is_writable($file);
    }

    // For Windows servers and safe_mode on, check if the file is writable by attempting to open it
    if (is_file($file)) {
        if (($fp = @fopen($file, 'r+')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }

    if (is_dir($file)) {
        if (($fp = @fopen($file . '/' . uniqid(mt_rand()) . '.tmp', 'w')) === false) {
            return false;
        }
        fclose($fp);
        @unlink($file . '/' . uniqid(mt_rand()) . '.tmp');
        return true;
    }

    return false;
}

// Function to clear Laravel cache
function clear_laravel_cache()
{
    $basePath = __DIR__ . '/..';
    $artisanPath = $basePath . '/artisan';
    
    if (!file_exists($artisanPath)) {
        return [
            'success' => false,
            'message' => 'Le fichier artisan n\'existe pas à l\'emplacement attendu.'
        ];
    }
    
    $commands = [
        'php ' . escapeshellarg($artisanPath) . ' config:clear',
        'php ' . escapeshellarg($artisanPath) . ' cache:clear',
        'php ' . escapeshellarg($artisanPath) . ' view:clear',
        'php ' . escapeshellarg($artisanPath) . ' route:clear'
    ];
    
    $output = [];
    $success = true;
    
    foreach ($commands as $command) {
        exec($command . ' 2>&1', $commandOutput, $returnCode);
        $output[] = $command . ': ' . ($returnCode === 0 ? 'Succès' : 'Échec');
        if ($returnCode !== 0) {
            $success = false;
        }
    }
    
    return [
        'success' => $success,
        'message' => 'Résultat du nettoyage du cache Laravel:',
        'details' => $output
    ];
}

// Function to fix migration files
function fix_migration_files()
{
    $migrationsPath = __DIR__ . '/../database/migrations';
    
    if (!is_dir($migrationsPath)) {
        return [
            'success' => false,
            'message' => 'Le dossier des migrations n\'existe pas à l\'emplacement attendu.'
        ];
    }
    
    $duplicates = [
        // Format: [filename => [new_class_name, new_table_name, new_filename]]
        '2025_02_28_000006_create_teachers_table.php' => [
            'CreateSchoolTeachersTable',
            'school_teachers',
            '2025_02_28_000006_create_school_teachers_table.php'
        ],
        '2025_02_28_000007_create_departments_table.php' => [
            'CreateSchoolDepartmentsTable',
            'school_departments',
            '2025_02_28_000007_create_school_departments_table.php'
        ]
    ];
    
    $results = [];
    
    foreach ($duplicates as $filename => $newInfo) {
        $filePath = $migrationsPath . '/' . $filename;
        $newFilePath = $migrationsPath . '/' . $newInfo[2];
        
        // Check if the original file exists
        if (!file_exists($filePath) && file_exists($newFilePath)) {
            $results[] = "Le fichier $filename a déjà été renommé en {$newInfo[2]}.";
            continue;
        }
        
        if (!file_exists($filePath)) {
            $results[] = "Le fichier $filename n'existe pas.";
            continue;
        }
        
        // Check if the file is writable
        if (!is_really_writable($filePath)) {
            $results[] = "Le fichier $filename n'est pas accessible en écriture.";
            continue;
        }
        
        // Read the file content
        $content = file_get_contents($filePath);
        
        // Replace the class name
        $oldClassName = 'class Create' . ucfirst(str_replace('create_', '', str_replace('_table.php', '', str_replace('2025_02_28_000006_', '', $filename)))) . 'Table';
        $newClassName = 'class ' . $newInfo[0];
        $content = str_replace($oldClassName, $newClassName, $content);
        
        // Replace the table name
        $oldTableName = "Schema::create('" . str_replace('create_', '', str_replace('_table.php', '', str_replace('2025_02_28_000006_', '', $filename))) . "'";
        $newTableName = "Schema::create('" . $newInfo[1] . "'";
        $content = str_replace($oldTableName, $newTableName, $content);
        
        // Replace the table name in dropIfExists
        $oldDropTableName = "Schema::dropIfExists('" . str_replace('create_', '', str_replace('_table.php', '', str_replace('2025_02_28_000006_', '', $filename))) . "'";
        $newDropTableName = "Schema::dropIfExists('" . $newInfo[1] . "'";
        $content = str_replace($oldDropTableName, $newDropTableName, $content);
        
        // Save the modified content
        if (file_put_contents($filePath, $content)) {
            // Rename the file
            if (rename($filePath, $newFilePath)) {
                $results[] = "Le fichier $filename a été modifié et renommé en {$newInfo[2]} avec succès.";
            } else {
                $results[] = "Le fichier $filename a été modifié mais n'a pas pu être renommé.";
            }
        } else {
            $results[] = "Impossible de modifier le contenu du fichier $filename.";
        }
    }
    
    return [
        'success' => true,
        'message' => 'Résultat de la correction des fichiers de migration:',
        'details' => $results
    ];
}

// Process form submission
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'fix_migrations':
                $result = fix_migration_files();
                break;
            case 'clear_cache':
                $result = clear_laravel_cache();
                break;
        }
    }
}

// Check migration files
$migrationsPath = __DIR__ . '/../database/migrations';
$migrationFiles = [];
if (is_dir($migrationsPath)) {
    $files = scandir($migrationsPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && strpos($file, '.php') !== false) {
            $migrationFiles[] = $file;
        }
    }
}

// Check for duplicate class names
$duplicateClasses = [];
$classNames = [];
foreach ($migrationFiles as $file) {
    $content = file_get_contents($migrationsPath . '/' . $file);
    if (preg_match('/class\s+(\w+)\s+extends\s+Migration/i', $content, $matches)) {
        $className = $matches[1];
        if (isset($classNames[$className])) {
            $duplicateClasses[$className][] = $file;
            $duplicateClasses[$className][] = $classNames[$className];
        } else {
            $classNames[$className] = $file;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des migrations</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Correction des migrations</h1>
            
            <!-- Migration Files -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Fichiers de migration</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Nom du fichier
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($migrationFiles as $file): ?>
                            <tr>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php echo htmlspecialchars($file); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 text-sm">
                                    <?php 
                                    $isDuplicate = false;
                                    foreach ($duplicateClasses as $className => $files) {
                                        if (in_array($file, $files)) {
                                            echo '<span class="text-red-600">Classe en double: ' . htmlspecialchars($className) . '</span>';
                                            $isDuplicate = true;
                                            break;
                                        }
                                    }
                                    if (!$isDuplicate) {
                                        echo '<span class="text-green-600">OK</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Duplicate Classes -->
            <?php if (!empty($duplicateClasses)): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Classes en double</h2>
                <div class="space-y-4">
                    <?php foreach ($duplicateClasses as $className => $files): ?>
                    <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <h3 class="font-medium text-red-800 mb-2">Classe: <?php echo htmlspecialchars($className); ?></h3>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            <?php foreach ($files as $file): ?>
                            <li><?php echo htmlspecialchars($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Actions</h2>
                
                <div class="space-y-4">
                    <!-- Fix Migrations -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium mb-2">1. Corriger les fichiers de migration</h3>
                        <p class="text-gray-600 mb-4">Cette action modifiera les fichiers de migration pour éviter les conflits de noms de classe.</p>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="action" value="fix_migrations">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Corriger les migrations
                            </button>
                        </form>
                    </div>
                    
                    <!-- Clear Cache -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium mb-2">2. Vider le cache Laravel</h3>
                        <p class="text-gray-600 mb-4">Cette action videra tous les caches de Laravel pour appliquer les modifications.</p>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Vider le cache
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Result -->
            <?php if ($result): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Résultat</h2>
                <div class="p-4 rounded-lg <?php echo $result['success'] ? 'bg-green-100 border border-green-200' : 'bg-red-100 border border-red-200'; ?>">
                    <p class="<?php echo $result['success'] ? 'text-green-800' : 'text-red-800'; ?> font-medium">
                        <?php echo htmlspecialchars($result['message']); ?>
                    </p>
                    <?php if (isset($result['details'])): ?>
                    <div class="mt-2 text-sm">
                        <ul class="list-disc list-inside">
                            <?php foreach ($result['details'] as $detail): ?>
                            <li><?php echo htmlspecialchars($detail); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Étapes suivantes</h2>
                <p class="text-gray-700 mb-4">Après avoir effectué les actions ci-dessus:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Retournez à la <a href="/" class="text-blue-600 hover:underline">page d'installation</a> et essayez à nouveau</li>
                    <li>Si vous rencontrez toujours des problèmes, consultez les <a href="/phpinfo.php" class="text-blue-600 hover:underline">informations PHP détaillées</a></li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html> 
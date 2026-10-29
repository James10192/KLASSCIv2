<?php
// Tools Directory Index
// This file provides a navigation interface for the diagnostic and fix tools

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Get the list of tools
$tools = [
    [
        'name' => 'Fix Migrations Tool',
        'file' => 'fix_migrations.php',
        'description' => 'Automatically fix duplicate migration classes and rename files',
        'icon' => 'wrench',
        'color' => 'blue'
    ],
    [
        'name' => 'Migration Diagnostic Tool',
        'file' => 'migration_diagnostic.php',
        'description' => 'Analyze migration files and database connection',
        'icon' => 'search',
        'color' => 'green'
    ],
    [
        'name' => 'PHP Version Check',
        'file' => 'php_version_check.php',
        'description' => 'Verify your PHP version and configuration meets requirements',
        'icon' => 'code',
        'color' => 'indigo'
    ],
    [
        'name' => 'PHP Information',
        'file' => 'phpinfo.php',
        'description' => 'View detailed PHP configuration information',
        'icon' => 'info-circle',
        'color' => 'purple'
    ]
];

// Function to get Laravel version
function getLaravelVersion() {
    $composerPath = __DIR__ . '/../../composer.json';
    if (file_exists($composerPath)) {
        $composer = json_decode(file_get_contents($composerPath), true);
        if (isset($composer['require']['laravel/framework'])) {
            return $composer['require']['laravel/framework'];
        }
    }
    return 'Unknown';
}

// Get Laravel version
$laravelVersion = getLaravelVersion();

// Get PHP version
$phpVersion = phpversion();

// Get server information
$serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart School - Diagnostic Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Smart School Diagnostic Tools</h1>
                    <p class="text-gray-600">Fix installation and migration issues</p>
                </div>
                <a href="/" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded flex items-center">
                    <i class="fas fa-home mr-2"></i> Return to Home
                </a>
            </div>
            
            <!-- System Information -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">System Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-full mr-3">
                                <i class="fab fa-laravel text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-blue-800">Laravel Version</h3>
                                <p class="text-blue-600"><?php echo htmlspecialchars($laravelVersion); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="bg-purple-100 p-3 rounded-full mr-3">
                                <i class="fab fa-php text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-purple-800">PHP Version</h3>
                                <p class="text-purple-600"><?php echo htmlspecialchars($phpVersion); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg md:col-span-2">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-full mr-3">
                                <i class="fas fa-server text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-green-800">Server</h3>
                                <p class="text-green-600"><?php echo htmlspecialchars($serverSoftware); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tools -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Available Tools</h2>
                <div class="space-y-4">
                    <?php foreach ($tools as $tool): ?>
                    <a href="<?php echo htmlspecialchars($tool['file']); ?>" class="block p-4 border border-gray-200 rounded-lg hover:bg-<?php echo $tool['color']; ?>-50 transition duration-200">
                        <div class="flex items-start">
                            <div class="bg-<?php echo $tool['color']; ?>-100 p-3 rounded-full mr-4">
                                <i class="fas fa-<?php echo $tool['icon']; ?> text-<?php echo $tool['color']; ?>-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-<?php echo $tool['color']; ?>-800 text-lg"><?php echo htmlspecialchars($tool['name']); ?></h3>
                                <p class="text-gray-600"><?php echo htmlspecialchars($tool['description']); ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Documentation -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Documentation</h2>
                <div class="prose max-w-none">
                    <p class="mb-4">
                        These tools are designed to help you diagnose and fix common issues with the Smart School installation,
                        particularly problems related to database migrations.
                    </p>
                    
                    <h3 class="font-medium text-lg mb-2">Common Issues</h3>
                    <ul class="list-disc list-inside space-y-2 mb-4">
                        <li><strong>Duplicate Migration Classes</strong>: When two migration files declare the same class name, causing PHP errors</li>
                        <li><strong>Database Connection Issues</strong>: Problems connecting to the database due to incorrect credentials or configuration</li>
                        <li><strong>PHP Version Compatibility</strong>: Issues related to PHP version requirements</li>
                    </ul>
                    
                    <h3 class="font-medium text-lg mb-2">Using the Tools</h3>
                    <ol class="list-decimal list-inside space-y-2">
                        <li>Start with the <strong>PHP Version Check</strong> to ensure your PHP configuration meets requirements</li>
                        <li>Use the <strong>Migration Diagnostic Tool</strong> to identify migration issues</li>
                        <li>Use the <strong>Fix Migrations Tool</strong> to automatically resolve duplicate class issues</li>
                        <li>Check <strong>PHP Information</strong> for detailed configuration information if needed</li>
                        <li>Return to the installation page and try again</li>
                    </ol>
                    
                    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h3 class="font-medium text-yellow-800 mb-2">Documentation</h3>
                        <p class="text-yellow-700">
                            For detailed information about the migration issues and solutions, please refer to the 
                            <a href="/MIGRATION_FIX.md" class="text-blue-600 hover:underline" target="_blank">MIGRATION_FIX.md</a> 
                            documentation file.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
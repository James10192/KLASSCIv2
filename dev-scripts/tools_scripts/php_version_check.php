<?php
// PHP Version Check Tool
// This script checks if your PHP version meets the requirements for the application

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Required PHP version
$requiredVersion = '8.0.0';

// Current PHP version
$currentVersion = phpversion();

// Check if current version meets requirements
$versionCheck = version_compare($currentVersion, $requiredVersion, '>=');

// Get PHP extensions
$extensions = get_loaded_extensions();
sort($extensions);

// Required extensions
$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'mbstring',
    'openssl',
    'tokenizer',
    'xml',
    'ctype',
    'json',
    'bcmath',
    'fileinfo'
];

// Check required extensions
$missingExtensions = [];
foreach ($requiredExtensions as $extension) {
    if (!in_array(strtolower($extension), array_map('strtolower', $extensions))) {
        $missingExtensions[] = $extension;
    }
}

// Get PHP configuration
$config = [
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
    'file_uploads' => ini_get('file_uploads'),
    'date.timezone' => ini_get('date.timezone')
];

// Recommended configuration
$recommendedConfig = [
    'max_execution_time' => '120',
    'memory_limit' => '256M',
    'upload_max_filesize' => '10M',
    'post_max_size' => '10M',
    'display_errors' => 'Off',
    'file_uploads' => 'On',
    'date.timezone' => 'UTC'
];

// Check configuration
$configIssues = [];
foreach ($recommendedConfig as $key => $value) {
    if ($key === 'memory_limit' || $key === 'upload_max_filesize' || $key === 'post_max_size') {
        // Convert memory values to bytes for comparison
        $currentBytes = convertToBytes($config[$key]);
        $recommendedBytes = convertToBytes($value);
        
        if ($currentBytes < $recommendedBytes) {
            $configIssues[$key] = [
                'current' => $config[$key],
                'recommended' => $value
            ];
        }
    } elseif ($config[$key] != $value) {
        $configIssues[$key] = [
            'current' => $config[$key],
            'recommended' => $value
        ];
    }
}

// Function to convert memory string to bytes
function convertToBytes($memoryString) {
    $memoryString = trim($memoryString);
    $lastChar = strtolower(substr($memoryString, -1));
    $value = (int) $memoryString;
    
    switch ($lastChar) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

// Get WAMP PHP versions if available
$wampPhpVersions = [];
if (file_exists('C:/wamp64/bin/php')) {
    $phpDirs = glob('C:/wamp64/bin/php/*', GLOB_ONLYDIR);
    foreach ($phpDirs as $phpDir) {
        $wampPhpVersions[] = basename($phpDir);
    }
}

// Get current PHP path
$phpPath = PHP_BINARY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Version Check</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">PHP Version Check</h1>
                    <p class="text-gray-600">Verify your PHP configuration meets requirements</p>
                </div>
                <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Tools
                </a>
            </div>
            
            <!-- PHP Version -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">PHP Version</h2>
                <div class="p-4 <?php echo $versionCheck ? 'bg-green-100' : 'bg-red-100'; ?> rounded-lg">
                    <div class="flex items-center">
                        <div class="<?php echo $versionCheck ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'; ?> p-3 rounded-full mr-4">
                            <i class="fas fa-<?php echo $versionCheck ? 'check' : 'times'; ?> text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-medium <?php echo $versionCheck ? 'text-green-800' : 'text-red-800'; ?>">
                                Current PHP Version: <?php echo htmlspecialchars($currentVersion); ?>
                            </h3>
                            <p class="<?php echo $versionCheck ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $versionCheck ? 'Your PHP version meets the requirements.' : 'Your PHP version does not meet the minimum requirement of PHP ' . htmlspecialchars($requiredVersion); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <?php if (!$versionCheck && !empty($wampPhpVersions)): ?>
                <div class="mt-4 p-4 bg-blue-100 rounded-lg">
                    <h3 class="font-medium text-blue-800 mb-2">WAMP PHP Versions Available</h3>
                    <p class="text-blue-600 mb-2">You have the following PHP versions installed with WAMP:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($wampPhpVersions as $version): ?>
                        <li class="text-blue-600">
                            PHP <?php echo htmlspecialchars($version); ?>
                            <?php if (strpos($phpPath, $version) !== false): ?>
                            <span class="bg-blue-200 text-blue-800 px-2 py-0.5 rounded text-xs">Current</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-3">
                        <p class="text-blue-800 font-medium">How to switch PHP version in WAMP:</p>
                        <ol class="list-decimal list-inside text-blue-600 space-y-1 mt-1">
                            <li>Left-click on the WAMP icon in the system tray</li>
                            <li>Go to PHP → Version</li>
                            <li>Select a version that is 8.0.0 or higher</li>
                            <li>Wait for WAMP to restart services</li>
                            <li>Refresh this page to verify the change</li>
                        </ol>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- PHP Extensions -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">PHP Extensions</h2>
                
                <?php if (empty($missingExtensions)): ?>
                <div class="p-4 bg-green-100 rounded-lg mb-4">
                    <div class="flex items-center">
                        <div class="bg-green-200 text-green-800 p-3 rounded-full mr-4">
                            <i class="fas fa-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-green-800">All Required Extensions Installed</h3>
                            <p class="text-green-600">Your PHP installation has all the required extensions.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="p-4 bg-red-100 rounded-lg mb-4">
                    <div class="flex items-center">
                        <div class="bg-red-200 text-red-800 p-3 rounded-full mr-4">
                            <i class="fas fa-times text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-red-800">Missing Required Extensions</h3>
                            <p class="text-red-600">Your PHP installation is missing some required extensions.</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-red-800 font-medium">Missing Extensions:</p>
                        <ul class="list-disc list-inside text-red-600 mt-1">
                            <?php foreach ($missingExtensions as $extension): ?>
                            <li><?php echo htmlspecialchars($extension); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <h3 class="font-medium mb-2">Required Extensions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        <?php foreach ($requiredExtensions as $extension): ?>
                        <div class="p-2 <?php echo in_array(strtolower($extension), array_map('strtolower', $extensions)) ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?> rounded">
                            <div class="flex items-center">
                                <i class="fas fa-<?php echo in_array(strtolower($extension), array_map('strtolower', $extensions)) ? 'check text-green-600' : 'times text-red-600'; ?> mr-2"></i>
                                <?php echo htmlspecialchars($extension); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="font-medium mb-2">All Installed Extensions</h3>
                    <div class="p-3 bg-gray-50 rounded-lg max-h-40 overflow-y-auto">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            <?php foreach ($extensions as $extension): ?>
                            <div class="text-sm text-gray-700"><?php echo htmlspecialchars($extension); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PHP Configuration -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">PHP Configuration</h2>
                
                <?php if (empty($configIssues)): ?>
                <div class="p-4 bg-green-100 rounded-lg mb-4">
                    <div class="flex items-center">
                        <div class="bg-green-200 text-green-800 p-3 rounded-full mr-4">
                            <i class="fas fa-check text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-green-800">Configuration Meets Recommendations</h3>
                            <p class="text-green-600">Your PHP configuration meets all recommended settings.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="p-4 bg-yellow-100 rounded-lg mb-4">
                    <div class="flex items-center">
                        <div class="bg-yellow-200 text-yellow-800 p-3 rounded-full mr-4">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-medium text-yellow-800">Configuration Issues Detected</h3>
                            <p class="text-yellow-600">Some of your PHP settings may need adjustment for optimal performance.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Setting
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Current Value
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Recommended
                                </th>
                                <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recommendedConfig as $key => $value): ?>
                            <tr>
                                <td class="py-2 px-4 border-b border-gray-200">
                                    <?php echo htmlspecialchars($key); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200">
                                    <?php echo htmlspecialchars($config[$key] ?: 'Not set'); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200">
                                    <?php echo htmlspecialchars($value); ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200">
                                    <?php if (isset($configIssues[$key])): ?>
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-xs">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Adjustment Recommended
                                    </span>
                                    <?php else: ?>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                                        <i class="fas fa-check mr-1"></i> OK
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($configIssues)): ?>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-medium text-blue-800 mb-2">How to Adjust PHP Configuration</h3>
                    <p class="text-blue-600 mb-2">To adjust these settings, you need to modify your php.ini file:</p>
                    <ol class="list-decimal list-inside text-blue-600 space-y-1">
                        <li>Locate your php.ini file (typically in C:\wamp64\bin\php\php[version]\php.ini)</li>
                        <li>Open the file in a text editor</li>
                        <li>Find and modify the settings listed above</li>
                        <li>Save the file and restart your web server</li>
                    </ol>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- PHP Path Information -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">PHP Path Information</h2>
                <div class="space-y-4">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <h3 class="font-medium mb-1">PHP Binary Path</h3>
                        <p class="text-gray-700 break-all"><?php echo htmlspecialchars($phpPath); ?></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <h3 class="font-medium mb-1">PHP Configuration File (php.ini)</h3>
                        <p class="text-gray-700 break-all"><?php echo htmlspecialchars(php_ini_loaded_file()); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
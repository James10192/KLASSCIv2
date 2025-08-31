<?php

// Diagnostic script for checking Laravel Tinker functionality
echo "=== LARAVEL TINKER DIAGNOSTIC TOOL ===\n\n";

// 1. Check if the application can bootstrap correctly
try {
    // Initialize the Laravel application
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✅ Laravel application bootstrapped successfully\n";
} catch (\Exception $e) {
    echo "❌ Error bootstrapping Laravel application: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check if Tinker service provider is registered
echo "\nChecking Tinker service provider...\n";
$registeredProviders = $app->getLoadedProviders();
$tinkerProviderClass = 'Laravel\Tinker\TinkerServiceProvider';

if (isset($registeredProviders[$tinkerProviderClass]) || class_exists($tinkerProviderClass)) {
    echo "✅ TinkerServiceProvider appears to be registered\n";
} else {
    echo "❌ TinkerServiceProvider does not appear to be registered properly\n";
    echo "   Adding TinkerServiceProvider to config/app.php might be required\n";
}

// 3. Check if Tinker commands are registered
echo "\nChecking Tinker command registration...\n";
try {
    $commands = $kernel->all();
    $tinkerCommandFound = false;
    
    foreach ($commands as $command => $class) {
        if ($command === 'tinker' || (is_string($class) && strpos($class, 'Tinker') !== false)) {
            $tinkerCommandFound = true;
            echo "✅ Tinker command found: '$command' → " . (is_string($class) ? $class : get_class($class)) . "\n";
        }
    }
    
    if (!$tinkerCommandFound) {
        echo "❌ Tinker command not registered\n";
    }
} catch (\Exception $e) {
    echo "❌ Error checking Tinker commands: " . $e->getMessage() . "\n";
}

// 4. Test a basic model operation
echo "\nTesting basic database operation...\n";
try {
    $userCount = \App\Models\User::count();
    echo "✅ Successfully counted users: $userCount user(s) found\n";
} catch (\Exception $e) {
    echo "❌ Error running test query: " . $e->getMessage() . "\n";
}

// 5. Test expense models (which were problematic)
echo "\nTesting expense models...\n";
try {
    $categoriesCount = \App\Models\CategorieDepense::count();
    $depensesCount = \App\Models\Depense::count();
    echo "✅ Successfully counted expenses: $depensesCount expense(s) found\n";
    echo "✅ Successfully counted expense categories: $categoriesCount category(s) found\n";
} catch (\Exception $e) {
    echo "❌ Error with expense models: " . $e->getMessage() . "\n";
}

// 6. Provide instructions for common fixes
echo "\n=== RECOMMENDATIONS ===\n";
echo "If you're still having issues with Tinker, try these steps:\n";
echo "1. Clear application cache:\n";
echo "   php artisan cache:clear\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "2. Check that Laravel\\Tinker\\TinkerServiceProvider::class is in config/app.php providers array\n";
echo "3. Run composer dump-autoload\n";
echo "4. Make sure the current working directory is the project root when running php artisan tinker\n";
echo "5. Ensure your database connection is working correctly\n";

echo "\n=== USAGE EXAMPLES ===\n";
echo "Once Tinker is working, you can use it to interact with your models:\n";
echo "php artisan tinker\n";
echo "> App\\Models\\User::count();\n";
echo "> App\\Models\\Depense::all();\n";
echo "> \$cat = App\\Models\\CategorieDepense::first();\n";
echo "> \$cat->depenses;\n";

echo "\nExiting...\n"; 
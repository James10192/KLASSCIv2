<?php

echo "=== LARAVEL TINKER FIX TOOL ===\n\n";

// Clear the application cache
echo "Clearing Laravel cache...\n";
passthru('php artisan config:clear');
passthru('php artisan cache:clear');
passthru('php artisan route:clear');
passthru('php artisan view:clear');
echo "✅ Cache cleared\n\n";

// Check if the Tinker service provider is registered
echo "Checking Tinker service provider...\n";
$appConfig = file_get_contents('config/app.php');
if (strpos($appConfig, 'Laravel\Tinker\TinkerServiceProvider') === false) {
    echo "❌ Tinker service provider not found in config/app.php\n";
    echo "Adding Tinker service provider...\n";
    
    // Add the service provider
    $appConfig = preg_replace(
        '/(\'providers\' => \[\s+)/s',
        "$1        /*\n         * Package Service Providers...\n         */\n        Laravel\\Tinker\\TinkerServiceProvider::class,\n\n",
        $appConfig
    );
    
    file_put_contents('config/app.php', $appConfig);
    echo "✅ Tinker service provider added\n";
} else {
    echo "✅ Tinker service provider already registered\n";
}

// Run composer dump-autoload
echo "\nRunning composer dump-autoload...\n";
passthru('composer dump-autoload');
echo "✅ Composer autoload files regenerated\n";

// Create a simple test Tinker script
echo "\nCreating a test Tinker script...\n";
$testScript = <<<'SCRIPT'
<?php
// Initialize the Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Quick test of model operations
echo "Testing basic operations: \n";
echo "- Users count: " . \App\Models\User::count() . "\n";

// Test model relationships
try {
    $categories = \App\Models\CategorieDepense::first();
    if ($categories) {
        echo "- First expense category: " . $categories->nom . "\n";
        $depenses = $categories->depenses;
        echo "- Number of expenses in this category: " . $depenses->count() . "\n";
    } else {
        echo "- No expense categories found\n";
    }
} catch (\Exception $e) {
    echo "- Error accessing expense models: " . $e->getMessage() . "\n";
}

echo "\nTest complete!\n";
SCRIPT;

file_put_contents('test_tinker_script.php', $testScript);
echo "✅ Test script created: test_tinker_script.php\n";

// Verify soft delete migration
echo "\nChecking soft delete migration...\n";
$migrationExists = glob('database/migrations/*add_soft_delete_columns_to_expense_tables.php');

if (empty($migrationExists)) {
    echo "⚠️ Soft delete migration not found. Creating migration...\n";
    
    $migration = <<<'MIGRATION'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteColumnsToExpenseTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('esbtp_categories_depense', 'deleted_at')) {
            Schema::table('esbtp_categories_depense', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('esbtp_depenses', 'deleted_at')) {
            Schema::table('esbtp_depenses', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_categories_depense', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('esbtp_depenses', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
MIGRATION;

    $timestamp = date('Y_m_d_His');
    $filename = "database/migrations/{$timestamp}_add_soft_delete_columns_to_expense_tables.php";
    file_put_contents($filename, $migration);
    echo "✅ Soft delete migration created: $filename\n";
    echo "Running migration...\n";
    passthru('php artisan migrate');
} else {
    echo "✅ Soft delete migration already exists\n";
}

// Create a helpful README file
echo "\nCreating Tinker usage guide...\n";
$readmeContent = <<<'README'
# Laravel Tinker Usage Guide

## Basic Usage

To start an interactive Tinker session:
```bash
php artisan tinker
```

## Running Scripts with Tinker

To run a script with Tinker:
```bash
php artisan tinker --execute="require('test_tinker_script.php');"
```

## Common Tinker Commands

### Working with Models

```php
// Count records
App\Models\User::count();

// Get all records
App\Models\Depense::all();

// Find a record by ID
App\Models\CategorieDepense::find(1);

// Find a record by field
App\Models\User::where('email', 'admin@example.com')->first();
```

### Creating and Modifying Records

```php
// Create a new user
$user = new App\Models\User();
$user->name = 'Test User';
$user->email = 'test@example.com';
$user->password = bcrypt('password');
$user->save();

// Create a new expense category
$cat = new App\Models\CategorieDepense();
$cat->nom = 'New Category';
$cat->description = 'Description';
$cat->save();

// Create a new expense
$expense = new App\Models\Depense();
$expense->montant = 100.50;
$expense->libelle = 'New Expense';
$expense->date_depense = now();
$expense->categorie_id = 1;
$expense->save();
```

### Working with Relationships

```php
// Get a category and its expenses
$cat = App\Models\CategorieDepense::first();
$expenses = $cat->depenses;

// Get an expense and its category
$expense = App\Models\Depense::first();
$category = $expense->categorie;
```

## Troubleshooting Tinker

If Tinker is not working properly:

1. Clear the application caches:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Make sure Tinker is properly installed:
   ```bash
   composer require laravel/tinker
   ```

3. Ensure the service provider is registered in config/app.php:
   ```php
   Laravel\Tinker\TinkerServiceProvider::class,
   ```

4. Run the fix script:
   ```bash
   php fix_tinker.php
   ```

5. Test with the test script:
   ```bash
   php test_tinker_script.php
   ```
README;

file_put_contents('TINKER_USAGE.md', $readmeContent);
echo "✅ Tinker usage guide created: TINKER_USAGE.md\n";

// Instructions for testing
echo "\n=== TINKER SHOULD NOW BE FIXED ===\n";
echo "To test if Tinker is working correctly:\n";
echo "1. Run the test script directly: php test_tinker_script.php\n";
echo "2. Try running with artisan: php artisan tinker --execute=\"require('test_tinker_script.php');\"\n";
echo "3. Start an interactive session: php artisan tinker\n";
echo "4. Read the usage guide: TINKER_USAGE.md\n\n";
echo "If you're still experiencing issues, check that:\n";
echo "- Your database connection is working correctly\n";
echo "- You are in the correct directory when running commands\n";
echo "- You have the necessary permissions to execute the PHP files\n";
echo "- The models referenced in your code actually exist\n"; 
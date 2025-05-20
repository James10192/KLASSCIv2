<?php

// Simple test script for expense models using Tinker
// Run with: php artisan tinker --execute="require('tinker_expense_test.php');"
// Or directly with: php tinker_expense_test.php

echo "=== TINKER EXPENSE MODELS TEST ===\n\n";

// Bootstrap the Laravel application
if (!defined('LARAVEL_START')) {
    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "Laravel application bootstrapped\n\n";
}

try {
    // 1. Count Categories
    $categoriesCount = \App\Models\CategorieDepense::count();
    echo "1. Categories Count: $categoriesCount\n";

    // 2. Count Expenses
    $expensesCount = \App\Models\Depense::count();
    echo "2. Expenses Count: $expensesCount\n";

    // 3. Test relationship: get expenses for first category
    if ($categoriesCount > 0) {
        $category = \App\Models\CategorieDepense::first();
        echo "3. First Category: {$category->nom} (ID: {$category->id})\n";
        
        $relatedExpenses = $category->depenses()->count();
        echo "   Related expenses: $relatedExpenses\n";
    } else {
        echo "3. No categories found to test relationships\n";
    }

    // 4. Test relationship: get category for first expense
    if ($expensesCount > 0) {
        $expense = \App\Models\Depense::first();
        echo "4. First Expense: {$expense->libelle} (ID: {$expense->id})\n";
        
        $relatedCategory = $expense->categorie;
        if ($relatedCategory) {
            echo "   Related category: {$relatedCategory->nom} (ID: {$relatedCategory->id})\n";
        } else {
            echo "   No related category found\n";
        }
    } else {
        echo "4. No expenses found to test relationships\n";
    }

    // 5. Create a new category
    echo "\n5. Creating a new test category...\n";
    $newCategory = new \App\Models\CategorieDepense();
    $newCategory->nom = "Test Category " . date('Y-m-d H:i:s');
    $newCategory->description = "Created via Tinker test script";
    $newCategory->created_by = 1;
    $newCategory->save();
    
    echo "   Category created with ID: {$newCategory->id}\n";

    // 6. Create a new expense
    echo "6. Creating a new test expense...\n";
    $newExpense = new \App\Models\Depense();
    $newExpense->montant = 123.45;
    $newExpense->libelle = "Test Expense " . date('Y-m-d H:i:s');
    $newExpense->date_depense = date('Y-m-d');
    $newExpense->commentaire = "Created via Tinker test script";
    $newExpense->categorie_id = $newCategory->id;
    $newExpense->created_by = 1;
    $newExpense->save();
    
    echo "   Expense created with ID: {$newExpense->id}\n";

    // 7. Test accessor functions
    echo "\n7. Testing accessor functions:\n";
    echo "   Date accessors: {$newExpense->date} (from date_depense: {$newExpense->date_depense})\n";
    echo "   Description accessor: {$newExpense->description} (from libelle: {$newExpense->libelle})\n";
    
    echo "\nAll tests completed successfully!\n";
    
} catch (\Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Error occurred at line " . $e->getLine() . " in file " . $e->getFile() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "If all tests passed, Tinker is working correctly with the expense models.\n";
echo "You can now use Tinker interactively with these models:\n";
echo "php artisan tinker\n";
echo "> \$cats = App\\Models\\CategorieDepense::all();\n";
echo "> \$expenses = App\\Models\\Depense::all();\n"; 
<?php

/**
 * Fix script for adding proper esbtp.attendances.* routes
 * 
 * Run this script to add the missing routes needed for the attendance system
 * php fix_attendance_routes.php
 */

// Load the Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPAttendanceController;

echo "Adding esbtp.attendances.* routes...\n";

// Define our routes within the esbtp prefix
Route::prefix('esbtp')->name('esbtp.')->group(function () {
    // Routes for attendance management
    Route::get('/attendances', [ESBTPAttendanceController::class, 'index'])
        ->name('attendances.index')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:view attendances']);
    
    Route::get('/attendances/create', [ESBTPAttendanceController::class, 'create'])
        ->name('attendances.create')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:create attendance']);
    
    Route::post('/attendances', [ESBTPAttendanceController::class, 'store'])
        ->name('attendances.store')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:create attendance']);
    
    Route::get('/attendances/{attendance}', [ESBTPAttendanceController::class, 'show'])
        ->name('attendances.show')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:view attendances']);
    
    Route::get('/attendances/{attendance}/edit', [ESBTPAttendanceController::class, 'edit'])
        ->name('attendances.edit')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:edit attendances']);
    
    Route::put('/attendances/{attendance}', [ESBTPAttendanceController::class, 'update'])
        ->name('attendances.update')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:edit attendances']);
    
    Route::delete('/attendances/{attendance}', [ESBTPAttendanceController::class, 'destroy'])
        ->name('attendances.destroy')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:delete attendances']);
    
    Route::get('/attendances/rapport', [ESBTPAttendanceController::class, 'rapportForm'])
        ->name('attendances.rapport-form')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:view attendances']);
    
    Route::post('/attendances/rapport', [ESBTPAttendanceController::class, 'rapport'])
        ->name('attendances.rapport')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:view attendances']);
    
    Route::post('/attendances/{absenceId}/process-justification', [ESBTPAttendanceController::class, 'processJustification'])
        ->name('attendances.process-justification')
        ->middleware(['auth', 'role:superAdmin|secretaire', 'permission:edit attendances']);
});

echo "Routes added successfully.\n";
echo "Now modifying the routes file to include these routes permanently...\n";

// Now, we'll create a proper routes file modification that needs to be copied to the routes/web.php file

$routesCode = <<<'EOT'
// Routes pour les présences/absences (ajoutées par fix_attendance_routes.php)
Route::prefix('attendances')->name('attendances.')->group(function() {
    Route::get('/', [ESBTPAttendanceController::class, 'index'])
        ->name('index')
        ->middleware('permission:view attendances');
    
    Route::get('/create', [ESBTPAttendanceController::class, 'create'])
        ->name('create')
        ->middleware('permission:create attendance');
    
    Route::post('/', [ESBTPAttendanceController::class, 'store'])
        ->name('store')
        ->middleware('permission:create attendance');
    
    Route::get('/{attendance}', [ESBTPAttendanceController::class, 'show'])
        ->name('show')
        ->middleware('permission:view attendances');
    
    Route::get('/{attendance}/edit', [ESBTPAttendanceController::class, 'edit'])
        ->name('edit')
        ->middleware('permission:edit attendances');
    
    Route::put('/{attendance}', [ESBTPAttendanceController::class, 'update'])
        ->name('update')
        ->middleware('permission:edit attendances');
    
    Route::delete('/{attendance}', [ESBTPAttendanceController::class, 'destroy'])
        ->name('destroy')
        ->middleware('permission:delete attendances');
    
    Route::get('/rapport-form', [ESBTPAttendanceController::class, 'rapportForm'])
        ->name('rapport-form')
        ->middleware('permission:view attendances');
    
    Route::post('/rapport', [ESBTPAttendanceController::class, 'rapport'])
        ->name('rapport')
        ->middleware('permission:view attendances');
    
    Route::post('/{absenceId}/process-justification', [ESBTPAttendanceController::class, 'processJustification'])
        ->name('process-justification')
        ->middleware('permission:edit attendances');
});
EOT;

echo "Please add the following code to your routes/web.php file inside the esbtp prefix group:\n\n";
echo $routesCode;
echo "\n\nRouteFix complete. Access http://127.0.0.1:8000/esbtp/attendances to verify.\n"; 
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPClasseController;
use App\Http\Controllers\ESBTPEtudiantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes API pour ESBTP
Route::get('/classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieresForApi'])
    ->name('api.classes.matieres');

// Routes pour le calcul des absences
Route::middleware(['auth:sanctum'])->prefix('absences')->group(function () {
    Route::post('/calculer', 'App\Http\Controllers\ESBTPCalculAbsencesController@calculerAbsencesEtudiant');
    Route::post('/resume-par-seance', 'App\Http\Controllers\ESBTPCalculAbsencesController@resumeAbsencesParSeance');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Attendance sync route
    Route::post('/attendance/sync', [App\Http\Controllers\ESBTP\Api\AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});

Route::get('/classes/{id}/available-places', [ESBTPClasseController::class, 'getAvailablePlaces']);

Route::middleware(['auth:sanctum'])->post('/inscriptions/validate', [ESBTPEtudiantController::class, 'validateInscription'])->name('api.inscriptions.validate');

Route::get('/classes', [ESBTPClasseController::class, 'indexApi']);

/*
|--------------------------------------------------------------------------
| API Routes LMS - KLASSCI Integration
|--------------------------------------------------------------------------
|
| Routes pour l'intégration entre le LMS et KLASSCI.
| Ces routes permettent au LMS d'accéder aux données KLASSCI
| et d'envoyer les résultats (notes, présences) vers KLASSCI.
|
*/

// Routes d'authentification LMS (sans middleware auth)
Route::prefix('lms/auth')->group(function () {
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])
        ->name('api.lms.auth.login');
    Route::get('/documentation', [App\Http\Controllers\API\AuthController::class, 'documentation'])
        ->name('api.lms.auth.docs');

    // Découverte multi-tenant (rate-limited, sans auth)
    Route::middleware('throttle:lms-discovery')->group(function () {
        Route::post('/check-user', [App\Http\Controllers\API\AuthController::class, 'checkUser'])
            ->name('api.lms.auth.check-user');
        Route::post('/check-availability', [App\Http\Controllers\API\AuthController::class, 'checkAvailability'])
            ->name('api.lms.auth.check-availability');
    });
});

// Informations publiques du tenant (sans auth, rate-limited)
Route::middleware('throttle:api')->get('lms/tenant-info', [App\Http\Controllers\API\AuthController::class, 'tenantInfo'])
    ->name('api.lms.tenant-info');

// Routes LMS protégées par authentification Sanctum
Route::middleware(['auth:sanctum'])->prefix('lms')->name('api.lms.')->group(function () {

    // ================================
    // AUTHENTIFICATION & PROFIL
    // ================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [App\Http\Controllers\API\AuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
        Route::post('/logout-all', [App\Http\Controllers\API\AuthController::class, 'logoutAll']);
        Route::get('/check', [App\Http\Controllers\API\AuthController::class, 'check']);
    });

    // ================================
    // DONNÉES EN LECTURE SEULE
    // ================================

    // Structure organisationnelle
    Route::get('/structure', [App\Http\Controllers\API\LMSDataController::class, 'structure'])
        ->name('structure');

    // Matières et cours
    Route::get('/matieres', [App\Http\Controllers\API\LMSDataController::class, 'matieres'])
        ->name('matieres');
    Route::get('/matieres/{matiereId}', [App\Http\Controllers\API\LMSDataController::class, 'matiereDetails'])
        ->name('matieres.details');

    // Classes et étudiants
    Route::get('/classes', [App\Http\Controllers\API\LMSDataController::class, 'classes'])
        ->name('classes');
    Route::get('/classes/{classeId}', [App\Http\Controllers\API\LMSDataController::class, 'classeDetails'])
        ->name('classes.details');
    Route::get('/classes/{classeId}/etudiants', [App\Http\Controllers\API\LMSDataController::class, 'etudiantsClasse'])
        ->name('classes.etudiants');

    // Enseignants actifs
    Route::get('/enseignants', [App\Http\Controllers\API\LMSDataController::class, 'enseignants'])
        ->name('enseignants');

    // KPIs pour le dashboard
    Route::get('/filieres', [App\Http\Controllers\API\LMSDataController::class, 'filieres'])
        ->name('filieres');
    Route::get('/niveaux-etudes', [App\Http\Controllers\API\LMSDataController::class, 'niveauxEtudes'])
        ->name('niveaux_etudes');

    // Emploi du temps
    Route::get('/emploi-temps', [App\Http\Controllers\API\LMSDataController::class, 'emploiTemps'])
        ->name('emploi_temps');

    // Évaluations programmées
    Route::get('/evaluations', [App\Http\Controllers\API\LMSDataController::class, 'evaluations'])
        ->name('evaluations');

    // Dashboard étudiant (réservé aux étudiants)
    Route::get('/me/dashboard', [App\Http\Controllers\API\LMSDataController::class, 'studentDashboard'])
        ->name('me.dashboard');

    // Dashboard enseignant (réservé aux enseignants)
    Route::get('/me/teacher-dashboard', [App\Http\Controllers\API\LMSDataController::class, 'teacherDashboard'])
        ->name('me.teacher-dashboard');

    // ================================
    // VISIOCONFÉRENCES (SUPPORT LMS)
    // ================================

    // Séances à venir pour créer les rooms
    Route::get('/seances/upcoming', [App\Http\Controllers\API\LMSDataController::class, 'upcomingSeances'])
        ->name('seances.upcoming');

    // Participants d'une séance
    Route::get('/seances/{seanceId}/participants', [App\Http\Controllers\API\LMSDataController::class, 'seanceParticipants'])
        ->name('seances.participants');

    // Valider un participant
    Route::post('/seances/{seanceId}/validate-participant', [App\Http\Controllers\API\LMSDataController::class, 'validateParticipant'])
        ->name('seances.validate-participant');

    // Sync attendances depuis visio (LMS → KLASSCI)
    Route::post('/attendances/from-video-session', [App\Http\Controllers\API\LMSDataController::class, 'syncVideoAttendances'])
        ->name('attendances.from-video-session');

    // ================================
    // NOTIFICATIONS
    // ================================

    // Envoyer rappels de séance
    Route::post('/notifications/send-session-reminder', [App\Http\Controllers\API\LMSDataController::class, 'sendSessionReminder'])
        ->name('notifications.send-session-reminder');

    // Récupérer préférences de notification
    Route::get('/notifications/preferences/{userId}', [App\Http\Controllers\API\LMSDataController::class, 'notificationPreferences'])
        ->name('notifications.preferences');

    // ================================
    // DONNÉES EN ÉCRITURE (LMS → KLASSCI)
    // ================================

    // Soumettre notes d'évaluations passées en ligne
    Route::post('/evaluations/{evaluationId}/notes', [App\Http\Controllers\API\LMSDataController::class, 'submitEvaluationNotes'])
        ->name('evaluations.notes.submit');

    // Présences cours en ligne
    Route::post('/cours/{coursId}/presences', [App\Http\Controllers\API\LMSWriteController::class, 'saveCourseAttendance'])
        ->name('cours.presences.save');

    // Statut des cours
    Route::put('/cours/{coursId}/statut', [App\Http\Controllers\API\LMSWriteController::class, 'updateCourseStatus'])
        ->name('cours.statut.update');
});

// ================================
// ROUTES DE DOCUMENTATION
// ================================
Route::get('/lms/documentation', function () {
    return response()->json([
        'title' => 'API LMS-KLASSCI Integration',
        'version' => '1.0.0',
        'description' => 'API pour l\'intégration entre le LMS et KLASSCI',
        'base_url' => url('/api/lms'),
        'authentication' => [
            'type' => 'Bearer Token (Laravel Sanctum)',
            'login_endpoint' => '/api/lms/auth/login',
            'header_format' => 'Authorization: Bearer {token}'
        ],
        'endpoints' => [
            'read_only' => [
                'GET /api/lms/structure' => 'Structure organisationnelle (filières, niveaux)',
                'GET /api/lms/matieres' => 'Liste des matières accessibles',
                'GET /api/lms/classes' => 'Classes de l\'année courante',
                'GET /api/lms/classes/{id}/etudiants' => 'Étudiants d\'une classe',
                'GET /api/lms/emploi-temps' => 'Emploi du temps filtré par rôle',
                'GET /api/lms/evaluations' => 'Évaluations programmées'
            ],
            'write_only' => [
                'POST /api/lms/evaluations/{id}/notes' => 'Sauvegarder notes d\'évaluation',
                'POST /api/lms/cours/{id}/presences' => 'Enregistrer présences cours',
                'PUT /api/lms/cours/{id}/statut' => 'Mettre à jour statut cours'
            ]
        ],
        'roles_supported' => ['enseignant', 'coordinateur', 'etudiant'],
        'data_scope' => 'Année universitaire courante uniquement',
        'contact' => [
            'team' => 'KLASSCI Development Team',
            'documentation' => url('/api/lms/auth/documentation')
        ]
    ]);
})->name('api.lms.documentation');

/*
|--------------------------------------------------------------------------
| CLI API Routes — KLASSCI Remote Management
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('cli')->name('api.cli.')->group(function () {
    // Read endpoints — Data & KPIs
    Route::get('/stats', [App\Http\Controllers\API\CLI\CLIDataController::class, 'stats'])->name('stats');
    Route::get('/classes', [App\Http\Controllers\API\CLI\CLIDataController::class, 'classes'])->name('classes');
    Route::get('/payments', [App\Http\Controllers\API\CLI\CLIDataController::class, 'payments'])->name('payments');
    Route::get('/settings', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settings'])->name('settings');

    // Read endpoints — Students & Inscriptions
    Route::get('/students', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'students'])->name('students');
    Route::get('/students/{id}', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'studentShow'])->name('students.show');
    Route::get('/inscriptions', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'inscriptions'])->name('inscriptions');

    // Read endpoints — Academic years
    Route::get('/annee', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'annee'])->name('annee');

    // Read endpoints — Users
    Route::get('/users', [App\Http\Controllers\API\CLI\CLIUserController::class, 'users'])->name('users');

    // Write endpoints
    Route::post('/inscriptions/{id}/validate', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'validateInscription'])->name('inscriptions.validate');
    Route::post('/inscriptions/move', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'moveStudents'])->name('inscriptions.move');
    Route::post('/inscriptions/validate-bulk', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'bulkValidate'])->name('inscriptions.validate-bulk');

    // Admin endpoints (strict throttle)
    Route::middleware('throttle:5,1')->group(function () {
        // Maintenance
        Route::get('/logs', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'logs'])->name('logs');
        Route::post('/cache/clear', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'cacheClear'])->name('cache.clear');
        Route::post('/permissions/fix', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'permissionsFix'])->name('permissions.fix');
        Route::post('/db/fix-duplicates', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'fixDuplicates'])->name('db.fix-duplicates');
        Route::post('/migrate', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'migrate'])->name('migrate');

        // Settings
        Route::put('/settings/{key}', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settingsUpdate'])->name('settings.update');

        // Academic years
        Route::post('/annee/set/{id}', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeSet'])->name('annee.set');
        Route::post('/annee/create', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeCreate'])->name('annee.create');

        // Users
        Route::post('/user/{id}/reset-password-expiry', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userResetPasswordExpiry'])->name('user.reset-password-expiry');
        Route::post('/user/create', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userCreate'])->name('user.create');
        Route::post('/user/{id}/delete', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userDelete'])->name('user.delete');
    });
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPClasseController;
use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\DB;
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

// Route pour vérifier l'existence d'un emploi du temps
Route::get('/check-emploi-temps/{id}', function ($id) {
    try {
        // Vérifier avec DB::table pour éviter les problèmes de modèle
        $emploiTempsDB = DB::table('esbtp_emploi_temps')->where('id', $id)->first();

        // Vérifier avec le modèle Eloquent
        $emploiTemps = ESBTPEmploiTemps::find($id);

        // Vérifier avec withTrashed pour voir si l'emploi du temps a été soft-deleted
        $emploiTempsWithTrashed = ESBTPEmploiTemps::withTrashed()->find($id);

        return response()->json([
            'exists' => $emploiTemps !== null,
            'id' => $id,
            'details' => [
                'db_table_exists' => $emploiTempsDB !== null,
                'eloquent_exists' => $emploiTemps !== null,
                'with_trashed_exists' => $emploiTempsWithTrashed !== null,
                'is_soft_deleted' => $emploiTempsWithTrashed && $emploiTempsWithTrashed->deleted_at !== null,
                'db_table_data' => $emploiTempsDB,
                'eloquent_data' => $emploiTemps ? [
                    'id' => $emploiTemps->id,
                    'classe_id' => $emploiTemps->classe_id,
                    'annee_universitaire_id' => $emploiTemps->annee_universitaire_id,
                    'is_active' => $emploiTemps->is_active,
                    'deleted_at' => $emploiTemps->deleted_at,
                ] : null,
                'with_trashed_data' => $emploiTempsWithTrashed ? [
                    'id' => $emploiTempsWithTrashed->id,
                    'classe_id' => $emploiTempsWithTrashed->classe_id,
                    'annee_universitaire_id' => $emploiTempsWithTrashed->annee_universitaire_id,
                    'is_active' => $emploiTempsWithTrashed->is_active,
                    'deleted_at' => $emploiTempsWithTrashed->deleted_at,
                ] : null,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'exists' => false,
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('api.check-emploi-temps');

// Routes pour le calcul des absences
Route::prefix('absences')->group(function () {
    Route::post('/calculer', 'App\Http\Controllers\ESBTPCalculAbsencesController@calculerAbsencesEtudiant');
    Route::post('/resume-par-seance', 'App\Http\Controllers\ESBTPCalculAbsencesController@resumeAbsencesParSeance');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Attendance sync route
    Route::post('/attendance/sync', [App\Http\Controllers\ESBTP\Api\AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});

Route::get('/classes/{id}/available-places', [ESBTPClasseController::class, 'getAvailablePlaces']);

Route::post('/inscriptions/validate', [ESBTPEtudiantController::class, 'validateInscription'])->name('api.inscriptions.validate');

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
});

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

    // Notes d'évaluations
    Route::post('/evaluations/{evaluationId}/notes', [App\Http\Controllers\API\LMSWriteController::class, 'saveEvaluationNotes'])
        ->name('evaluations.notes.save');
    Route::post('/evaluations/{evaluationId}/notes/preview', [App\Http\Controllers\API\LMSWriteController::class, 'previewEvaluationNotes'])
        ->name('evaluations.notes.preview');

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

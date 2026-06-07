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
    Route::get('/relances', [App\Http\Controllers\API\CLI\CLIDataController::class, 'relances'])->name('relances');
    Route::get('/recouvrement', [App\Http\Controllers\API\CLI\CLIDataController::class, 'recouvrement'])->name('recouvrement');
    Route::get('/journal-caisse', [App\Http\Controllers\API\CLI\CLIDataController::class, 'journalCaisse'])->name('journal-caisse');
    Route::get('/audit-comptable', [App\Http\Controllers\API\CLI\CLIDataController::class, 'auditComptable'])->name('audit-comptable');
    Route::get('/settings', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settings'])->name('settings');

    // Read endpoints — Students & Inscriptions
    Route::get('/students', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'students'])->name('students');
    Route::get('/students/{id}', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'studentShow'])->name('students.show');
    Route::get('/inscriptions', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'inscriptions'])->name('inscriptions');
    Route::get('/resultats/etudiant/{id}/diagnose', [App\Http\Controllers\API\CLI\CLIResultatController::class, 'studentDiagnose'])
        ->name('resultats.student.diagnose');
    Route::get('/resultats/etudiant/{id}/bulletin-consistency-diagnose', [App\Http\Controllers\API\CLI\CLIResultatController::class, 'bulletinConsistencyDiagnose'])
        ->name('resultats.student.bulletin-consistency-diagnose');
    Route::get('/bts-tc/inscriptions/{id}/diagnose', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'diagnoseInscription'])
        ->name('bts-tc.inscriptions.diagnose');
    Route::get('/bts-tc/students/{id}/journey', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'studentJourney'])
        ->name('bts-tc.students.journey');
    Route::get('/bts-tc/students/{id}/results-consistency', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'resultsConsistency'])
        ->name('bts-tc.students.results-consistency');
    Route::get('/bts-tc/classes/{id}/orientation-check', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'classOrientationCheck'])
        ->name('bts-tc.classes.orientation-check');
    Route::get('/bts-tc/legacy-audit', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'legacyAudit'])
        ->name('bts-tc.legacy-audit');
    Route::post('/bts-tc/filieres/{id}/mark-tronc-commun', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'markFiliereTroncCommun'])
        ->name('bts-tc.filieres.mark-tronc-commun');
    Route::post('/bts-tc/classes/{id}/targets', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'addOrientationTarget'])
        ->name('bts-tc.classes.targets.store');
    Route::post('/bts-tc/inscriptions/{id}/orient', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'orientInscription'])
        ->name('bts-tc.inscriptions.orient');
    Route::post('/bts-tc/inscriptions/{id}/seed-academic-sample', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'seedAcademicSample'])
        ->name('bts-tc.inscriptions.seed-academic-sample');
    Route::post('/bts-tc/inscriptions/{id}/sync', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'syncInscription'])
        ->name('bts-tc.inscriptions.sync');
    Route::post('/bts-tc/sync-all', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'syncAll'])
        ->name('bts-tc.sync-all');

    // Diagnose matière liaisons (filière+niveau pivot vs filieres/niveaux relations)
    Route::get('/matieres/diagnose-liaisons', [App\Http\Controllers\API\CLI\CLIMatiereController::class, 'diagnoseLiaisons'])
        ->name('matieres.diagnose-liaisons');

    // Read endpoints — Academic years
    Route::get('/annee', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'annee'])->name('annee');

    // Read endpoints — Users
    Route::get('/users', [App\Http\Controllers\API\CLI\CLIUserController::class, 'users'])->name('users');

    // Write endpoints
    Route::post('/inscriptions/{id}/validate', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'validateInscription'])->name('inscriptions.validate');
    Route::post('/inscriptions/move', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'moveStudents'])->name('inscriptions.move');
    Route::post('/inscriptions/validate-bulk', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'bulkValidate'])->name('inscriptions.validate-bulk');

    // Analytics diagnose (read-only) — couverture échéancier, snapshots, saturation risque
    Route::get('/analytics/diagnose', [App\Http\Controllers\API\CLI\CLIDataController::class, 'analyticsDiagnose'])->name('analytics.diagnose');

    // Comptabilité (read-only) — audit + réconciliation diagnose
    Route::prefix('comptabilite')->name('comptabilite.')->group(function () {
        Route::get('/dashboard-kpis', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'dashboardKpis'])->name('dashboard-kpis');
        Route::get('/cash-balance', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'cashBalance'])->name('cash-balance');
        Route::get('/payments-summary', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'paymentsSummary'])->name('payments-summary');
        Route::get('/period-locks', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'periodLocks'])->name('period-locks');
        Route::get('/reconciliation-candidates', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationCandidates'])->name('reconciliation-candidates');
        Route::get('/orphan-paiements-annee-drift', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'orphanPaiementsAnneeDrift'])->name('orphan-paiements-annee-drift');
        Route::post('/cleanup-orphan-paiements', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'cleanupOrphanPaiements'])->name('cleanup-orphan-paiements');
        // PR1 réconciliation
        Route::get('/reconciliation/sessions', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationSessions'])->name('reconciliation.sessions');
        Route::get('/reconciliation/sessions/{id}', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationSessionShow'])->name('reconciliation.sessions.show');
        // PR3 réconciliation
        Route::get('/reconciliation/health', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationHealth'])->name('reconciliation.health');
        // PR6 réconciliation
        Route::get('/reconciliation/metrics', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationMetrics'])->name('reconciliation.metrics');
    });

    // Permissions supervision (read-only) — registry-driven
    Route::get('/permissions', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'permissions'])->name('permissions.list');
    Route::get('/permissions/audit', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'audit'])->name('permissions.audit');
    Route::get('/roles', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roles'])->name('roles.list');
    Route::get('/roles/{role}', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleShow'])->name('roles.show');
    Route::post('/roles/{role}/grant', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleGrant'])->name('roles.grant');

    // LMD hierarchy (read)
    Route::get('/lmd/tree', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'tree'])->name('lmd.tree');

    // Admin endpoints — throttled at 60/min (matches outer group; auth:sanctum + tokenCan('cli:admin')
    // already gates access. Higher throughput needed for bulk operations like LMD import.)
    Route::middleware('throttle:60,1')->group(function () {
        // Maintenance
        Route::get('/logs', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'logs'])->name('logs');
        Route::post('/cache/clear', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'cacheClear'])->name('cache.clear');
        Route::post('/permissions/fix', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'permissionsFix'])->name('permissions.fix');
        Route::post('/permissions/sync', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'sync'])->name('permissions.sync');
        Route::post('/db/fix-duplicates', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'fixDuplicates'])->name('db.fix-duplicates');
        Route::post('/migrate', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'migrate'])->name('migrate');
        Route::post('/pull', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'pull'])->name('pull');
        Route::post('/seed-demo', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'seedDemo'])->name('seed-demo');
        Route::post('/evaluations/sync-notes', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'evaluationsSyncNotes'])->name('evaluations.sync-notes');
        Route::get('/matieres/{matiere}/coefficient', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'matiereCoefficientLookup'])->name('matieres.coefficient');
        Route::get('/etudiants/{id}/inscriptions-diag', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'etudiantInscriptionsDiag'])->name('etudiants.inscriptions-diag');
        Route::get('/reinscription/eligible-diag', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'reinscriptionEligibleDiag'])->name('reinscription.eligible-diag');
        Route::get('/reinscription/batches', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'reinscriptionBatches'])->name('reinscription.batches');

        // LMD hierarchy (write — bulk setup of Domaine + Mention + Parcours + optional Filiere)
        Route::post('/lmd/setup', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'setup'])->name('lmd.setup');

        // LMD UE linking (idempotent — append by default, sync mode opt-in)
        Route::post('/lmd/parcours/{parcours}/link-ues', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'linkUes'])->name('lmd.link-ues');

        // LMD bulk import (Domaine + Mention + Parcours + Filière + UEs + ECUEs + Planifications)
        Route::post('/lmd/import', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'import'])->name('lmd.import');

        // LMD cleanup — soft-delete UE/ECUE/planifs d'un parcours pour ré-import propre
        // (dry_run par défaut SAFE, garde-fou évaluations). Idempotent.
        Route::post('/lmd/cleanup', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'cleanup'])->name('lmd.cleanup');

        // LMD link-classes — rattache des classes LMD à un parcours (parcours_id +
        // filiere_id dérivé + systeme=LMD). Domaine/Mention via parcours. Dry-run par défaut.
        Route::post('/lmd/link-classes', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'linkClasses'])->name('lmd.link-classes');

        // LMD bulk import — enseignants UEMOA (W1.3) — assigne Users + planifications
        // depuis JSONs `database/seeds-data/lmd-enseignants/*.json`. Throttle hérité
        // 60/min suffit (1 appel par filière ou 1 appel `all` par tenant).
        Route::post('/lmd/import-enseignants', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'importEnseignants'])->name('lmd.import-enseignants');

        // Settings
        Route::put('/settings/{key}', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settingsUpdate'])->name('settings.update');

        // Academic years
        Route::post('/annee/set/{id}', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeSet'])->name('annee.set');
        Route::post('/annee/create', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeCreate'])->name('annee.create');

        // Users
        Route::post('/user/{id}/reset-password-expiry', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userResetPasswordExpiry'])->name('user.reset-password-expiry');
        Route::post('/user/{id}/reset-password', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userResetPassword'])->name('user.reset-password');
        Route::post('/user/create', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userCreate'])->name('user.create');
        Route::post('/user/{id}/delete', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userDelete'])->name('user.delete');
    });
});

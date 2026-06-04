<?php

use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ESBTP\Admin\ESBTPTeacherAttendanceController;
use App\Http\Controllers\ESBTP\TeacherAttendanceController;
use App\Http\Controllers\ESBTP\TeacherAttendanceHistoryController;
use App\Http\Controllers\ESBTPAnneeUniversitaireController;
use App\Http\Controllers\ESBTPAnnonceController;
use App\Http\Controllers\ESBTPAttendanceCodeController;
use App\Http\Controllers\ESBTPAttendanceController;
use App\Http\Controllers\ESBTPBulletinConfigController;
use App\Http\Controllers\ESBTPBulletinController;
use App\Http\Controllers\ESBTPResultatController;
use App\Http\Controllers\ESBTPStudentBulletinController;
use App\Http\Controllers\ESBTPCategoriePaiementController;
use App\Http\Controllers\ESBTPClasseController;
use App\Http\Controllers\ESBTPComptabiliteAnalyticsController;
use App\Http\Controllers\ESBTPComptabiliteController;
use App\Http\Controllers\ESBTPComptabiliteFraisController;
use App\Http\Controllers\ESBTPComptabiliteReportController;
use App\Http\Controllers\ESBTPComptabiliteRelanceController;
use App\Http\Controllers\ESBTPEcheancierController;
use App\Http\Controllers\ESBTPContinuingEducationController;
use App\Http\Controllers\ESBTPCycleController;
use App\Http\Controllers\ESBTPEmploiTempsController;
use App\Http\Controllers\ESBTPEnseignantController;
use App\Http\Controllers\ESBTPEtudiantController;
use App\Http\Controllers\ESBTPEvaluationController;
use App\Http\Controllers\ESBTPFiliereController;
use App\Http\Controllers\ESBTPInscriptionApiController;
use App\Http\Controllers\ESBTPInscriptionController;
use App\Http\Controllers\ESBTPInscriptionPaiementController;
use App\Http\Controllers\ESBTPLogsController;
use App\Http\Controllers\ESBTPMatiereController;
use App\Http\Controllers\ESBTPMatriculeConfigController;
use App\Http\Controllers\ESBTPNiveauEtudeController;
use App\Http\Controllers\ESBTPNoteController;
use App\Http\Controllers\ESBTPNotificationController;
use App\Http\Controllers\ESBTPPaywallConfigController;
use App\Http\Controllers\ESBTPPlanningGeneralController;
use App\Http\Controllers\ESBTPSeanceCoursController;
use App\Http\Controllers\ESBTPSecretaireController;
use App\Http\Controllers\ESBTPSpecialtyController;
use App\Http\Controllers\ESBTPStudentController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\NavbarController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentProgressionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ESBTPAuditController;
// use App\Http\Controllers\ESBTP\ESBTPSecurityController; // COMMENTED - CONTROLLER NOT IMPLEMENTED

/*
|--------------------------------------------------------------------------
| Routes Web ESBTP-yAKRO
|--------------------------------------------------------------------------
|
| Ce fichier contient les routes essentielles pour le fonctionnement
| de l'application ESBTP-yAKRO, centré sur les fonctionnalités spécifiées.
|
*/

// Debug routes — local environment only
if (app()->environment('local')) {
    Route::get('/debug-annees-simple', [ESBTPAnneeUniversitaireController::class, 'debug'])->name('debug-annees-simple');

    Route::get('/test-emploi-temps-show', function () {
        $controller = new ESBTPEmploiTempsController;
        $emploiTemps = \App\Models\ESBTPEmploiTemps::find(1);

        if (! $emploiTemps) {
            return response()->json(['error' => 'Emploi du temps not found'], 404);
        }

        return $controller->show($emploiTemps);
    });
}

// Route d'accueil — la landing publique vit désormais sur klassci.com (Vercel /
// klassci-landing). Sur les sous-domaines tenant et sur l'apex Laravel, on
// redirige vers le login pour ne plus exposer de page marketing.
Route::get('/', fn () => redirect()->route('login'))->name('welcome');

// Les routes /inscriptions/{inscription} ne doivent pas capturer les routes
// statiques comme /inscriptions/create.
Route::pattern('inscription', '[0-9]+');

// Compatibilite anciens liens singuliers: la route canonique est
// esbtp.inscriptions.create (/esbtp/inscriptions/create).
Route::redirect('/inscription/create', '/esbtp/inscriptions/create')
    ->name('inscription.create');
Route::redirect('/esbtp/inscription/create', '/esbtp/inscriptions/create')
    ->name('esbtp.inscription.create');

// Pages publiques (docs, api-reference, changelog) — supprimées de Laravel.
// Elles sont désormais servies en MDX par klassci-landing :
//   https://klassci.com/docs
//   https://klassci.com/docs/api-reference
//   https://klassci.com/docs/changelog

// Routes pour l'installation — verrouillées par install.lock dès que l'app est installée
// (DB OK + superAdmin présent + APP_INSTALLED=true). Sinon 404. Voir BlockInstallIfReady.
Route::prefix('install')->middleware('install.lock')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('install.index');
    Route::get('/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/database', [InstallController::class, 'setupDatabase'])->name('install.setup-database');
    Route::get('/migration', [InstallController::class, 'migration'])->name('install.migration');
    Route::post('/migration', [InstallController::class, 'runMigration'])->name('install.run-migration');
    Route::get('/check-migrations', [InstallController::class, 'checkMigrations'])->name('install.check-migrations');
    Route::get('/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::post('/admin', [InstallController::class, 'setupAdmin'])->name('install.setup-admin');
    Route::get('/complete', [InstallController::class, 'complete'])->name('install.complete');
    Route::post('/complete', [InstallController::class, 'finalize'])->name('install.finalize');
    Route::get('/finalize', [InstallController::class, 'finalize'])->name('install.finalize.get');
});

// CSRF token refresh (keeps login/register forms alive when idle)
Route::get('/csrf-token-refresh', fn () => response()->json(['token' => csrf_token()]))->name('csrf.refresh');

// Routes auth + password reset + SSO → extraites dans routes/auth.php (audit 2026-05-21,
// refactor god-code routes/web.php). require inline préserve le middleware group 'web'.
require __DIR__.'/auth.php';

// /contact-demo supprimé : le formulaire de demande de démo vit désormais sur
// klassci.com (Vercel) et appelle directement contact@klassci.com via Web3Forms
// ou un endpoint Vercel API. ContactController::sendDemo peut être supprimé
// dans une PR de suivi si plus aucun usage interne ne le réclame.

// Routes pour la navbar (recherche, notifications, messages, actions rapides)
Route::middleware(['auth'])->group(function () {
    // Routes de recherche
    Route::get('/search', [SearchController::class, 'globalSearch'])->name('search.global');
    Route::get('/search/results', [SearchController::class, 'searchResults'])->name('search.results');

    // Routes pour les fonctionnalités de la navbar
    Route::prefix('navbar')->name('navbar.')->group(function () {
        Route::get('/notifications', [NavbarController::class, 'getNotifications'])->name('notifications');
        Route::get('/messages', [NavbarController::class, 'getMessages'])->name('messages');
        Route::get('/quick-actions', [NavbarController::class, 'getQuickActions'])->name('quick-actions');
        Route::get('/stats', [NavbarController::class, 'getDashboardStats'])->name('stats');

        // Actions sur les notifications
        Route::post('/notifications/{id}/read', [NavbarController::class, 'markNotificationAsRead'])->name('notifications.read');
        Route::post('/notifications/mark-all-read', [NavbarController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{id}/delete', [NavbarController::class, 'deleteNotification'])->name('notifications.delete');
        Route::delete('/notifications/delete-all', [NavbarController::class, 'deleteAllNotifications'])->name('notifications.delete-all');

        // Actions sur les messages
        Route::post('/messages/mark-all-read', [NavbarController::class, 'markAllMessagesAsRead'])->name('messages.mark-all-read');
        Route::delete('/messages/{id}/delete', [NavbarController::class, 'deleteMessage'])->name('messages.delete');
        Route::delete('/messages/delete-all', [NavbarController::class, 'deleteAllMessages'])->name('messages.delete-all');
    });

    // Route pour la page des notifications
    Route::get('/notifications', [ESBTPNotificationController::class, 'index'])->name('notifications.page');
    Route::post('/notifications/{id}/mark-as-read', [ESBTPNotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('/notifications/mark-all-as-read', [ESBTPNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    Route::get('/notifications/unread-count', [ESBTPNotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');

    // Route pour les paramètres utilisateur
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings.index');
});

// Routes contrat expiration (AJAX — pas de middleware contract.expiry pour éviter récursion)
Route::middleware(['auth'])->prefix('contract-expiry')->name('contract-expiry.')->group(function () {
    Route::get('/status', [\App\Http\Controllers\ContractExpiryController::class, 'status'])->name('status');
    Route::post('/dismiss', [\App\Http\Controllers\ContractExpiryController::class, 'dismiss'])->name('dismiss');
});

// Routes accessibles uniquement après authentification
Route::middleware(['auth', 'installed', 'force.password.change'])->group(function () {
    // Dashboard - Route principale qui redirige vers le tableau de bord approprié selon le rôle
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Lot 9 — Dashboard widget-based (universel, gated par permissions)
    // Premier consommateur : rôles custom (Lot 8). Accessible à tous via /dashboard/widgets.
    Route::prefix('dashboard/widgets')->name('dashboard.widgets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DashboardWidgetController::class, 'index'])->name('index');
        Route::get('/configure', [\App\Http\Controllers\DashboardWidgetController::class, 'configure'])->name('configure');
        Route::post('/update', [\App\Http\Controllers\DashboardWidgetController::class, 'update'])->name('update');
        Route::post('/reset', [\App\Http\Controllers\DashboardWidgetController::class, 'reset'])->name('reset');
    });

    // Route de test debug mode — local environment only (audit 2026-05-21).
    // Avant: gated by config('app.debug') qui peut être true en prod par erreur de config.
    if (app()->environment('local')) {
        Route::get('/test-debug-mode', function () {
            return view('test-debug-mode');
        })->name('test.debug.mode');
    }

    // Routes spécifiques pour chaque type de tableau de bord
    Route::middleware(['role:superAdmin'])->group(function () {
        Route::get('/dashboard/superadmin', [DashboardController::class, 'superadmin'])->name('dashboard.superadmin');
    });

    Route::middleware(['role:secretaire'])->group(function () {
        Route::get('/dashboard/secretaire', [DashboardController::class, 'secretaire'])->name('dashboard.secretaire');
    });

    Route::middleware(['role:etudiant'])->group(function () {
        Route::get('/dashboard/etudiant', [DashboardController::class, 'etudiant'])->name('dashboard.etudiant');
    });

    Route::middleware(['role:enseignant|teacher'])->group(function () {
        Route::get('/dashboard/teacher', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
        Route::get('/dashboard/teacher/timetable', [TeacherDashboardController::class, 'showTimetable'])->name('teacher.timetable');
        Route::get('/dashboard/teacher/grades', [TeacherDashboardController::class, 'showGrades'])->name('teacher.grades');
        Route::get('/dashboard/teacher/grades/{evaluation}/note-modal', [TeacherDashboardController::class, 'getNoteModal'])->name('teacher.grades.note-modal');
        Route::post('/dashboard/teacher/grades/{evaluation}/notes', [TeacherDashboardController::class, 'storeNote'])->name('teacher.grades.note-store');
        Route::get('/dashboard/teacher/grades/{evaluation}/card', [TeacherDashboardController::class, 'refreshEvaluationCard'])->name('teacher.grades.card');
        Route::get('/dashboard/teacher/attendance', [TeacherDashboardController::class, 'showAttendance'])->name('teacher.attendance');
        Route::get('/dashboard/teacher/availability', [TeacherDashboardController::class, 'showAvailability'])->name('teacher.availability');
        Route::post('/dashboard/teacher/availability', [TeacherDashboardController::class, 'updateAvailability'])->name('teacher.availability.update');
        Route::get('/dashboard/teacher/roll-call/{seance}', [TeacherDashboardController::class, 'showRollCall'])->name('teacher.roll-call');
        Route::post('/dashboard/teacher/roll-call/{seance}', [TeacherDashboardController::class, 'storeRollCall'])->name('teacher.roll-call.store');
        Route::post('/dashboard/teacher/close-course/{seance}', [TeacherDashboardController::class, 'closeCourse'])->name('teacher.close-course');
        Route::get('/teacher/profile', [TeacherController::class, 'profile'])->name('teacher.profile');
        Route::put('/teacher/profile', [TeacherController::class, 'updateProfile'])->name('teacher.profile.update');
        Route::put('/teacher/profile/password', [TeacherController::class, 'updatePassword'])->name('teacher.profile.password.update');
        Route::get('/teacher/select-call-type/{seance}', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'selectCallType'])->name('teacher.select-call-type');

        // Routes pour les rapports de séance
        Route::get('/teacher/session-report/create/{seance}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'create'])->name('teacher.session-report.create');
        Route::post('/teacher/session-report/store/{seance}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'store'])->name('teacher.session-report.store');
        Route::get('/teacher/session-reports', [App\Http\Controllers\ESBTP\SessionReportController::class, 'index'])->name('teacher.session-report.index');
        Route::get('/teacher/session-report/{report}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'show'])->name('teacher.session-report.show');
        Route::get('/teacher/session-report/{report}/edit', [App\Http\Controllers\ESBTP\SessionReportController::class, 'edit'])->name('teacher.session-report.edit');
        Route::put('/teacher/session-report/{report}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'update'])->name('teacher.session-report.update');
    });

    // Routes pour la gestion du profil admin, enseignants et coordinateurs
    Route::middleware(['permission:admin.access'])->group(function () {
        Route::get('/admin/profile', [AdminProfileController::class, 'index'])->name('admin.profile');
        Route::put('/admin/profile/update', [AdminProfileController::class, 'update'])->name('admin.profile.update');
        Route::put('/admin/profile/update-professional', [AdminProfileController::class, 'updateProfessionalInfo'])->name('admin.profile.update.professional');
        Route::put('/admin/profile/update-password', [AdminProfileController::class, 'updatePassword'])->name('admin.password.update');
    });

    // Routes pour la gestion du profil coordinateur
    Route::middleware(['role:coordinateur'])->group(function () {
        Route::get('/coordinateur/profile', [AdminProfileController::class, 'index'])->name('coordinateur.profile');
        Route::put('/coordinateur/profile/update', [AdminProfileController::class, 'update'])->name('coordinateur.profile.update');
        Route::put('/coordinateur/profile/update-professional', [AdminProfileController::class, 'updateProfessionalInfo'])->name('coordinateur.profile.update.professional');
        Route::put('/coordinateur/profile/update-password', [AdminProfileController::class, 'updatePassword'])->name('coordinateur.password.update');

        // Dashboard coordinateur — AJAX data refresh
        Route::get('/coordinateur/dashboard-data', [App\Http\Controllers\DashboardController::class, 'coordinateurDashboardData'])->name('coordinateur.dashboard-data');

    });

    // Tableau de bord des présences — accessible coordinateur, secrétaire, superAdmin
    Route::middleware(['auth', 'role:coordinateur|secretaire|superAdmin', 'permission:module.presences.access'])->group(function () {
        Route::get('/coordinateur/attendance-dashboard', [App\Http\Controllers\CoordinateurDashboardController::class, 'attendanceDashboard'])->name('coordinateur.attendance-dashboard');
        Route::get('/coordinateur/recent-activities', [App\Http\Controllers\CoordinateurDashboardController::class, 'getRecentActivities'])->name('coordinateur.recent-activities');
        Route::post('/coordinateur/daily-report', [App\Http\Controllers\CoordinateurDashboardController::class, 'generateDailyReport'])->name('coordinateur.daily-report');
    });

    // Routes pour les fonctionnalités ESBTP
    Route::prefix('esbtp')->name('esbtp.')->group(function () {
        // Routes protégées pour les super-administrateurs, secrétaires, coordinateurs et enseignants
        Route::middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
            // Routes pour les paiements
            Route::resource('payments', \App\Http\Controllers\ESBTP\PaymentController::class);
            Route::get('payments/{payment}/receipt', [\App\Http\Controllers\ESBTP\PaymentController::class, 'generateReceipt'])
                ->name('payments.receipt');

            // Routes pour les frais de scolarité
            Route::resource('fees', \App\Http\Controllers\ESBTP\FeeController::class);

            // Nouveau système de catégories de frais ESBTP
            Route::get('frais', [\App\Http\Controllers\ESBTPFraisController::class, 'index'])->name('frais.index');
            Route::get('frais/configure', [\App\Http\Controllers\ESBTPFraisController::class, 'configure'])->name('frais.configure');
            Route::get('frais/optional-config', [\App\Http\Controllers\ESBTPFraisController::class, 'optionalConfig'])->name('frais.optional-config');
            Route::post('frais/save-assignment', [\App\Http\Controllers\ESBTPFraisController::class, 'saveAssignment'])->name('frais.save-assignment');
            Route::get('frais/get-categories', [\App\Http\Controllers\ESBTPFraisController::class, 'getCategories'])->name('frais.get-categories');
            Route::post('frais/update-configuration', [\App\Http\Controllers\ESBTPFraisController::class, 'updateConfiguration'])->name('frais.update-configuration');
            Route::get('frais/preview-level-targets', [\App\Http\Controllers\ESBTPFraisController::class, 'previewLevelTargets'])->name('frais.preview-level-targets');
            Route::post('frais/apply-level-configuration', [\App\Http\Controllers\ESBTPFraisController::class, 'applyLevelConfiguration'])->name('frais.apply-level-configuration');
            Route::post('frais/{category}/toggle', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleCategory'])->name('frais.toggle');
            Route::post('frais/{fraisCategory}/toggle-active', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleActive'])->name('frais.toggle-active');
            Route::post('frais/reset-defaults', [\App\Http\Controllers\ESBTPFraisController::class, 'resetDefaults'])->name('frais.reset-defaults');
            Route::resource('frais', \App\Http\Controllers\ESBTPFraisController::class)
                ->except(['index'])
                ->whereNumber('frai'); // évite que frais/all-variants etc. soient capturés par show
                // /!\ Laravel auto-singularise 'frais' en 'frai' pour le param ; le controller utilise $frai.

            // Routes API pour les variants
            Route::get('frais/class-details/{filiere}/{niveau}', [\App\Http\Controllers\ESBTPFraisController::class, 'getClassDetails'])->name('frais.class-details');
            Route::get('frais/category-variants/{category}', [\App\Http\Controllers\ESBTPFraisController::class, 'getCategoryVariants'])->name('frais.category-variants');
            Route::get('frais/all-variants', [\App\Http\Controllers\ESBTPFraisController::class, 'getAllVariants'])->name('frais.all-variants');
            Route::post('frais/variants', [\App\Http\Controllers\ESBTPFraisController::class, 'storeVariant'])->name('frais.variants.store');
            Route::put('frais/variants/{variant}', [\App\Http\Controllers\ESBTPFraisController::class, 'updateVariant'])->name('frais.variants.update');
            Route::delete('frais/variants/{variant}', [\App\Http\Controllers\ESBTPFraisController::class, 'destroyVariant'])->name('frais.variants.destroy');

            // Routes pour l'édition inline des configurations
            Route::put('frais/configurations/{configuration}', [\App\Http\Controllers\ESBTPFraisController::class, 'updateConfigurationInline'])->name('frais.configurations.update');
            Route::post('frais/configurations/{configuration}/toggle', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleConfigurationStatus'])->name('frais.configurations.toggle');
            Route::get('frais/configurations/{configuration}/options', [\App\Http\Controllers\ESBTPFraisController::class, 'getConfigurationOptions'])->name('frais.configurations.options');

            // Routes pour l'édition inline des options
            Route::put('frais/options/{option}', [\App\Http\Controllers\ESBTPFraisController::class, 'updateOption'])->name('frais.options.update');
            Route::post('frais/options/{option}/toggle', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleOptionStatus'])->name('frais.options.toggle');
            Route::delete('frais/options/{option}', [\App\Http\Controllers\ESBTPFraisController::class, 'destroyOption'])->name('frais.options.destroy');

            // Routes pour les assignations d'options
            Route::get('frais/options/{option}/assignments', [\App\Http\Controllers\ESBTPFraisController::class, 'getOptionAssignments'])->name('frais.options.assignments');
            Route::post('frais/options/assignments', [\App\Http\Controllers\ESBTPFraisController::class, 'saveOptionAssignments'])->name('frais.options.assignments.save');
            Route::delete('frais/assignments/{assignment}', [\App\Http\Controllers\ESBTPFraisController::class, 'removeAssignment'])->name('frais.assignments.remove');
            Route::delete('frais/options/{option}/assignments', [\App\Http\Controllers\ESBTPFraisController::class, 'clearOptionAssignments'])->name('frais.options.assignments.clear');

            // Routes API pour les relances automatiques
            Route::get('frais/{category}/overdue-students', [\App\Http\Controllers\ESBTPFraisController::class, 'getStudentsWithOverduePayments'])->name('frais.overdue-students');
            Route::post('frais/{category}/schedule-reminders', [\App\Http\Controllers\ESBTPFraisController::class, 'scheduleAutomaticReminders'])->name('frais.schedule-reminders');

            // Routes pour les souscriptions aux frais optionnels — gates inscriptions.edit (touche billing)
            Route::middleware('permission:inscriptions.edit')->group(function () {
                Route::post('inscriptions/{inscription}/subscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionPaiementController::class, 'subscribeToOptionalFee'])->name('inscriptions.subscribe-optional-fee');
                Route::post('inscriptions/{inscription}/unsubscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionPaiementController::class, 'unsubscribeFromOptionalFee'])->name('inscriptions.unsubscribe-optional-fee');
            });

            // Routes pour les certificats de scolarité
            Route::get('/etudiants/{etudiant}/certificat-preview', [ESBTPEtudiantController::class, 'previewCertificat'])
                ->name('etudiants.certificat.preview')
                ->middleware(['permission:students.view']);
            Route::get('/etudiants/{etudiant}/certificat', [ESBTPEtudiantController::class, 'genererCertificat'])
                ->name('etudiants.certificat')
                ->middleware(['permission:students.view']);
            Route::get('/etudiants/{etudiant}/certificat/preview-pdf', [ESBTPEtudiantController::class, 'previewCertificatPdf'])
                ->name('etudiants.certificat.preview-pdf')
                ->middleware(['permission:students.view', 'throttle:60,1']);

            // Routes pour les attestations de fréquentation
            Route::get('/etudiants/{etudiant}/attestation-frequentation-preview', [ESBTPEtudiantController::class, 'previewAttestationFrequentation'])
                ->name('etudiants.attestation-frequentation.preview')
                ->middleware(['permission:students.view']);
            Route::get('/etudiants/{etudiant}/attestation-frequentation', [ESBTPEtudiantController::class, 'genererAttestationFrequentation'])
                ->name('etudiants.attestation-frequentation')
                ->middleware(['permission:students.view']);
            Route::get('/etudiants/{etudiant}/attestation-frequentation/preview-pdf', [ESBTPEtudiantController::class, 'previewAttestationFrequentationPdf'])
                ->name('etudiants.attestation-frequentation.preview-pdf')
                ->middleware(['permission:students.view', 'throttle:60,1']);

            // Routes pour les rôles et permissions
            Route::resource('roles', \App\Http\Controllers\ESBTP\RoleController::class)->middleware(['role:superAdmin']);

            // Routes pour les filières — gates per-méthode (avant: middleware OR'd cassé qui laissait passer view → write)
            // /!\ Les routes statiques (create) DOIVENT être déclarées AVANT les routes paramétrées ({filiere})
            // sinon Laravel matche '/filieres/create' comme '/filieres/{filiere=create}' → 404.
            Route::middleware('permission:filieres.create')->group(function () {
                Route::get('filieres/create', [ESBTPFiliereController::class, 'create'])->name('filieres.create');
                Route::post('filieres', [ESBTPFiliereController::class, 'store'])->name('filieres.store');
            });
            Route::middleware('permission:filieres.view')->group(function () {
                Route::get('filieres', [ESBTPFiliereController::class, 'index'])->name('filieres.index');
                Route::get('filieres/{filiere}', [ESBTPFiliereController::class, 'show'])->name('filieres.show');
            });
            Route::middleware('permission:filieres.edit')->group(function () {
                Route::get('filieres/{filiere}/edit', [ESBTPFiliereController::class, 'edit'])->name('filieres.edit');
                Route::match(['put', 'patch'], 'filieres/{filiere}', [ESBTPFiliereController::class, 'update'])->name('filieres.update');
            });
            Route::delete('filieres/{filiere}', [ESBTPFiliereController::class, 'destroy'])
                ->name('filieres.destroy')
                ->middleware('permission:filieres.delete');

            // Routes pour les niveaux d'études — gates per-méthode
            // /!\ Les routes statiques (create) DOIVENT être déclarées AVANT les routes paramétrées.
            Route::middleware('permission:niveaux.create')->group(function () {
                Route::get('niveaux-etudes/create', [ESBTPNiveauEtudeController::class, 'create'])->name('niveaux-etudes.create');
                Route::post('niveaux-etudes', [ESBTPNiveauEtudeController::class, 'store'])->name('niveaux-etudes.store');
            });
            Route::middleware('permission:niveaux.view')->group(function () {
                Route::get('niveaux-etudes', [ESBTPNiveauEtudeController::class, 'index'])->name('niveaux-etudes.index');
                Route::get('niveaux-etudes/{niveaux_etude}', [ESBTPNiveauEtudeController::class, 'show'])->name('niveaux-etudes.show');
            });
            Route::middleware('permission:niveaux.edit')->group(function () {
                Route::get('niveaux-etudes/{niveaux_etude}/edit', [ESBTPNiveauEtudeController::class, 'edit'])->name('niveaux-etudes.edit');
                Route::match(['put', 'patch'], 'niveaux-etudes/{niveaux_etude}', [ESBTPNiveauEtudeController::class, 'update'])->name('niveaux-etudes.update');
            });
            Route::delete('niveaux-etudes/{niveaux_etude}', [ESBTPNiveauEtudeController::class, 'destroy'])
                ->name('niveaux-etudes.destroy')
                ->middleware('permission:niveaux.delete');

            // Routes pour les années universitaires — set-current dédié + per-méthode (avant: AUCUN gate)
            Route::post('annees-universitaires/{anneesUniversitaire}/set-current', [ESBTPAnneeUniversitaireController::class, 'setCurrent'])
                ->name('annees-universitaires.set-current')
                ->middleware('permission:annees.set_current');
            // /!\ Les routes statiques (create) DOIVENT être déclarées AVANT les routes paramétrées.
            Route::middleware('permission:annees.create')->group(function () {
                Route::get('annees-universitaires/create', [ESBTPAnneeUniversitaireController::class, 'create'])->name('annees-universitaires.create');
                Route::post('annees-universitaires', [ESBTPAnneeUniversitaireController::class, 'store'])->name('annees-universitaires.store');
            });
            Route::middleware('permission:annees.view')->group(function () {
                Route::get('annees-universitaires', [ESBTPAnneeUniversitaireController::class, 'index'])->name('annees-universitaires.index');
                Route::get('annees-universitaires/{anneesUniversitaire}', [ESBTPAnneeUniversitaireController::class, 'show'])->name('annees-universitaires.show');
            });
            Route::middleware('permission:annees.edit')->group(function () {
                Route::get('annees-universitaires/{anneesUniversitaire}/edit', [ESBTPAnneeUniversitaireController::class, 'edit'])->name('annees-universitaires.edit');
                Route::match(['put', 'patch'], 'annees-universitaires/{anneesUniversitaire}', [ESBTPAnneeUniversitaireController::class, 'update'])->name('annees-universitaires.update');
            });
            Route::delete('annees-universitaires/{anneesUniversitaire}', [ESBTPAnneeUniversitaireController::class, 'destroy'])
                ->name('annees-universitaires.destroy')
                ->middleware('permission:annees.delete');

            // Debug route — local environment only (audit 2026-05-21)
            if (app()->environment('local')) {
                Route::get('debug-annees', [ESBTPAnneeUniversitaireController::class, 'debug'])
                    ->name('debug-annees');
            }

            // Routes pour les cycles de formation
            Route::resource('cycles', ESBTPCycleController::class);
            Route::post('cycles/{id}/restore', [ESBTPCycleController::class, 'restore'])->name('cycles.restore');
            Route::delete('cycles/{id}/force-delete', [ESBTPCycleController::class, 'forceDelete'])->name('cycles.force-delete');

            // Routes pour les secrétaires
            Route::resource('secretaires', ESBTPSecretaireController::class);
            Route::post('secretaires/{secretaire}/reset-password', [ESBTPSecretaireController::class, 'resetPassword'])
                ->name('secretaires.reset-password');

            // Dashboard superAdmin
            Route::get('/dashboard', [App\Http\Controllers\ESBTP\SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');

            // Routes de modification des classes — gates per-méthode (avant: middleware OR'd)
            Route::middleware('permission:classes.create')->group(function () {
                Route::get('classes/create', [ESBTPClasseController::class, 'create'])->name('classes.create');
                Route::post('classes', [ESBTPClasseController::class, 'store'])->name('classes.store');
            });
            Route::middleware('permission:classes.edit')->group(function () {
                Route::get('classes/{classe}/edit', [ESBTPClasseController::class, 'edit'])->name('classes.edit');
                Route::match(['put', 'patch'], 'classes/{classe}', [ESBTPClasseController::class, 'update'])->name('classes.update');
            });
            Route::delete('classes/{classe}', [ESBTPClasseController::class, 'destroy'])
                ->name('classes.destroy')
                ->middleware('permission:classes.delete');

            // Routes pour les partenariats
            Route::resource('partnerships', \App\Http\Controllers\ESBTP\PartnershipController::class);

            // Routes du module comptabilité - PROVISOIREMENT SUPPRIMÉ POUR REDÉFINITION

            // Routes pour le système de réinscription
            Route::prefix('reinscription')->name('reinscription.')->group(function () {
                Route::get('/', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'index'])->name('index');

                // Routes statiques AVANT les routes avec paramètres
                Route::get('export/results', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'exportResults'])->name('export');

                // Routes pour la gestion des règles académiques
                Route::prefix('regles')->name('regles.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'regles'])->name('index');
                    Route::post('/', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'storeRegle'])->name('store');
                    Route::put('{id}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'updateRegle'])->name('update');
                    Route::delete('{id}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'destroyRegle'])->name('destroy');
                });

                // Routes pour la gestion des abandons
                Route::post('{etudiant}/abandon', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'marquerAbandon'])->name('marquer-abandon');
                Route::post('{etudiant}/restaurer', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'restaurerAbandon'])->name('restaurer-abandon');
                Route::post('{etudiant}/valider', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'validerReinscription'])->name('valider-reinscription');

                // Route AJAX pour lazy loading des catégories
                Route::get('load-category/{category}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'loadCategory'])->name('load-category');

                // Routes API pour les selects en cascade (Filière → Niveau → Classe)
                Route::get('api/niveaux-by-filiere/{filiere}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'getNiveauxByFiliere'])->name('api.niveaux-by-filiere');
                Route::get('api/classes-by-filiere-niveau', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'getClassesByFiliereNiveau'])->name('api.classes-by-filiere-niveau');

                // Diagnostic en masse — retourne par étudiant {moyenne, decision, frais_soldes, solde_restant}
                // utilisé par le modal de réinscription groupée depuis etudiants.index + reinscription.index
                Route::post('api/bulk-summary', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'bulkSummary'])
                    ->middleware('throttle:30,1')
                    ->name('api.bulk-summary');

                // Bulk reinscription v2 (composant <x-reinscription-bulk-modal>) — preview + execute
                Route::post('api/bulk-preview', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'bulkPreview'])
                    ->middleware('throttle:30,1')
                    ->name('api.bulk-preview');
                Route::post('api/bulk-execute', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'bulkExecute'])
                    ->middleware('throttle:10,1')
                    ->name('api.bulk-execute');
                Route::get('api/classes-list', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'classesList'])
                    ->name('api.classes-list');

                // Route pour la page de finalisation de réinscription
                Route::get('{etudiant}/finaliser', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'create'])->name('create');

                // Mise à jour rapide de la fiche étudiant + parents avant validation de la réinscription
                Route::middleware('permission:students.edit')->group(function () {
                    Route::patch('{etudiant}/quick-update-fiche', [
                        \App\Http\Controllers\ESBTP\ESBTPReinscriptionFicheController::class, 'update'
                    ])->whereNumber('etudiant')->name('quick-update-fiche');
                });

                // Route AJAX pour récupérer les classes selon la décision
                Route::get('{etudiant}/classes-by-decision', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'getClassesByDecision'])->name('classes-by-decision');

                // Routes avec paramètres à la FIN
                Route::get('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'show'])->name('show');
                Route::put('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'update'])->name('update');
            });

            // Routes pour les séances de cours (accessible aux coordinateurs)
            Route::resource('seances-cours', ESBTPSeanceCoursController::class)
                ->parameters(['seances-cours' => 'seancesCour']);

            // Rapports de cours soumis par les enseignants — vue admin agrégée
            Route::middleware('permission:session_reports.view')->group(function () {
                Route::get('rapports-cours', [\App\Http\Controllers\ESBTP\AdminSessionReportController::class, 'index'])
                    ->name('rapports-cours.index');
                Route::get('rapports-cours/{report}', [\App\Http\Controllers\ESBTP\AdminSessionReportController::class, 'show'])
                    ->name('rapports-cours.show');
            });
        });

        // Routes accessibles aux superAdmin, secrétaires, coordinateurs et enseignants
        Route::middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
            // Routes pour les classes ESBTP - index et show avec permission view_classes
            Route::get('classes', [ESBTPClasseController::class, 'index'])
                ->name('classes.index')
                ->middleware(['permission:classes.view']);

            Route::get('classes/{classe}', [ESBTPClasseController::class, 'show'])
                ->name('classes.show')
                ->whereNumber('classe') // évite que classes/overcapacity soit capturé par show
                ->middleware(['permission:classes.view']);

            // Route pour gérer les matières d'une classe - accessible aux superAdmin et secrétaires
            Route::get('classes/{classe}/matieres', [ESBTPClasseController::class, 'matieres'])
                ->name('classes.matieres')
                ->middleware(['permission:classes.view']);

            // Routes de l'API pour récupérer les matières d'une classe - accessible aux superAdmin et secrétaires
            Route::get('classes/{classe}/matieres/api', [ESBTPClasseController::class, 'getMatieres'])
                ->name('classes.matieres.data')
                ->middleware(['permission:classes.view']);

// Route pour vérifier les places disponibles dans une classe (throttle 60/min contre spam Select2)
            Route::get('classes/{id}/available-places', [ESBTPEtudiantController::class, 'getAvailablePlaces'])
                ->name('classes.available-places')
                ->middleware(['permission:classes.view', 'throttle:60,1']);
            
            // Route pour récupérer les classes en surcapacité
            Route::get('classes/overcapacity', [ESBTPClasseController::class, 'getOvercapacityClasses'])
                ->name('classes.overcapacity')
                ->middleware(['permission:classes.view']);
            // Routes pour les matières
            Route::name('matieres.')->prefix('matieres')->group(function () {
                Route::get('/json', [ESBTPMatiereController::class, 'getMatieresJson'])
                    ->name('json')
                    ->middleware(['permission:matieres.view']);
                Route::get('/refresh', [ESBTPMatiereController::class, 'refresh'])
                    ->name('refresh')
                    ->middleware(['permission:matieres.view']);
                Route::delete('/bulk-delete', [ESBTPMatiereController::class, 'bulkDelete'])
                    ->name('bulk-delete')
                    ->middleware(['permission:matieres.delete']);
                // Routes AJAX pour la configuration des liaisons
                Route::get('{matiere}/liaisons', [ESBTPMatiereController::class, 'getLiaisons'])
                    ->name('liaisons')
                    ->middleware(['permission:matieres.view']);
                Route::post('{matiere}/update-liaisons', [ESBTPMatiereController::class, 'updateLiaisons'])
                    ->name('update-liaisons')
                    ->middleware(['permission:matieres.edit']);
                Route::get('{matiere}/statistiques-liaisons', [ESBTPMatiereController::class, 'getStatistiquesLiaisons'])
                    ->name('statistiques-liaisons')
                    ->middleware(['permission:matieres.view']);
                Route::get('{matiere}/refresh-ligne', [ESBTPMatiereController::class, 'refreshLigne'])
                    ->name('refresh-ligne')
                    ->middleware(['permission:matieres.view']);

                // Routes pour l'ajout de matières aux combinaisons vides
                Route::get('available-for-combination', [ESBTPMatiereController::class, 'getAvailableForCombination'])
                    ->name('available-for-combination')
                    ->middleware(['permission:matieres.view']);
                Route::post('add-to-combination', [ESBTPMatiereController::class, 'addToCombination'])
                    ->name('add-to-combination')
                    ->middleware(['permission:matieres.edit']);
            });

            // Routes CRUD pour les matières
            Route::resource('matieres', ESBTPMatiereController::class)
                ->names([
                    'index' => 'matieres.index',
                    'create' => 'matieres.create',
                    'store' => 'matieres.store',
                    'show' => 'matieres.show',
                    'edit' => 'matieres.edit',
                    'update' => 'matieres.update',
                    'destroy' => 'matieres.destroy',
                ])
                ->middleware(['permission:matieres.view|matieres.create|matieres.edit|matieres.delete']);

            // Routes pour l'association/dissociation d'enseignants
            Route::post('matieres/{matiere}/associate-enseignant', [ESBTPMatiereController::class, 'associateEnseignant'])
                ->name('matieres.associate-enseignant');
            Route::post('matieres/{matiere}/dissociate-enseignant', [ESBTPMatiereController::class, 'dissociateEnseignant'])
                ->name('matieres.dissociate-enseignant');

            // Routes pour les emplois du temps ESBTP

            // Route AJAX pour refresh des emplois du temps avec filtres - DOIT ÊTRE AVANT Route::resource
            Route::get('emploi-temps/refresh', [ESBTPEmploiTempsController::class, 'refresh'])
                ->name('emploi-temps.refresh')
                ->middleware(['permission:timetables.view']);

            Route::post('emploi-temps/quick-generate', [ESBTPEmploiTempsController::class, 'quickGenerate'])
                ->name('emploi-temps.quick-generate')
                ->middleware(['permission:timetables.create']);

            Route::post('emploi-temps/quick-generate/preview', [ESBTPEmploiTempsController::class, 'quickGeneratePreview'])
                ->name('emploi-temps.quick-generate.preview')
                ->middleware(['permission:timetables.create']);

            Route::post('emploi-temps/duplicate-week', [ESBTPEmploiTempsController::class, 'duplicateWeek'])
                ->name('emploi-temps.duplicate-week')
                ->middleware(['permission:timetables.create']);

            Route::get('emploi-temps/bulk-edit', [ESBTPEmploiTempsController::class, 'bulkEdit'])
                ->name('emploi-temps.bulk-edit')
                ->middleware(['permission:timetables.edit']);

            Route::get('emploi-temps/{emploi_temp}/sections', [ESBTPEmploiTempsController::class, 'sections'])
                ->name('emploi-temps.sections')
                ->middleware(['permission:timetables.view']);

            // Routes pour les emplois du temps ESBTP (permissions par action)
            Route::get('emploi-temps', [ESBTPEmploiTempsController::class, 'index'])->name('emploi-temps.index')->middleware('permission:timetables.view');
            Route::get('emploi-temps/create', [ESBTPEmploiTempsController::class, 'create'])->name('emploi-temps.create')->middleware('permission:timetables.create');
            Route::post('emploi-temps', [ESBTPEmploiTempsController::class, 'store'])->name('emploi-temps.store')->middleware('permission:timetables.create');
            Route::get('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'show'])->name('emploi-temps.show')->middleware('permission:timetables.view');
            // Endpoint AJAX: retourne uniquement le partial Suivi heures (toggle Semestre/Année sans full reload)
            Route::get('emploi-temps/{emploi_temp}/suivi-heures-partial', [ESBTPEmploiTempsController::class, 'suiviHeuresPartial'])
                ->name('emploi-temps.suivi-heures-partial')
                ->middleware(['permission:timetables.view', 'throttle:60,1']);
            Route::get('emploi-temps/{emploi_temp}/edit', [ESBTPEmploiTempsController::class, 'edit'])->name('emploi-temps.edit')->middleware('permission:timetables.edit');
            Route::put('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'update'])->name('emploi-temps.update')->middleware('permission:timetables.edit');
            Route::delete('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'destroy'])->name('emploi-temps.destroy')->middleware('permission:timetables.delete');

            Route::get('emploi-temps/{emploi_temp}/export-pdf', [ESBTPEmploiTempsController::class, 'generatePdf'])
                ->name('emploi-temps.export-pdf')
                ->middleware(['permission:timetables.view']);

            // Aperçu PDF inline (Phase 9.5 — preview universel)
            Route::get('emploi-temps/{emploi_temp}/preview-pdf', [ESBTPEmploiTempsController::class, 'previewPdf'])
                ->name('emploi-temps.preview-pdf')
                ->middleware(['permission:timetables.view', 'throttle:60,1']);

            // Route pour prévisualiser l'emploi du temps avant génération PDF
            Route::get('emploi-temps/{emploi_temp}/preview', [ESBTPEmploiTempsController::class, 'previewEmploiTemps'])
                ->name('emploi-temps.preview')
                ->middleware(['permission:timetables.view']);

            // Routes pour la gestion des séances d'emploi du temps
            Route::get('emploi-temps/{emploi_temp}/add-session', [ESBTPEmploiTempsController::class, 'addSession'])
                ->name('emploi-temps.add-session')
                ->middleware(['permission:timetables.edit']);
            Route::post('emploi-temps/{emploi_temp}/store-session', [ESBTPEmploiTempsController::class, 'storeSession'])
                ->name('emploi-temps.store-session')
                ->middleware(['permission:timetables.edit']);

            // Routes pour les emplois du temps standards (TimetableController)
            Route::resource('timetables', TimetableController::class)
                ->whereNumber('timetable') // évite que timetables/today soit capturé par show
                ->middleware(['permission:timetables.view|timetables.create|timetables.edit|timetables.delete']);

            // Routes supplémentaires pour les emplois du temps
            Route::get('timetables/class/{classId}', [TimetableController::class, 'showByClass'])
                ->name('timetables.class')
                ->middleware(['permission:timetables.view']);
            Route::get('timetables/teacher/{teacherId}', [TimetableController::class, 'showByTeacher'])
                ->name('timetables.teacher')
                ->middleware(['permission:timetables.view']);
            Route::get('timetables/student/{studentId}', [TimetableController::class, 'showByStudent'])
                ->name('timetables.student')
                ->middleware(['permission:timetables.view_own|timetables.view']);

            // Routes pour les résultats
            Route::get('resultats', [ESBTPResultatController::class, 'resultats'])
                ->name('resultats.index')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            // ═══ Sous-lot C+ : Corbeille multi-entité (étudiants + inscriptions + paiements) ═══
            Route::middleware('permission:trash.view')->prefix('trash')->name('trash.')->group(function () {
                Route::get('/', fn () => view('esbtp.trash.index'))->name('index');
                Route::get('/etudiants', [\App\Http\Controllers\ESBTPEtudiantTrashController::class, 'index'])->name('etudiants');
                Route::get('/inscriptions', [\App\Http\Controllers\ESBTPInscriptionTrashController::class, 'index'])->name('inscriptions');
                Route::get('/paiements', [\App\Http\Controllers\ESBTPPaiementTrashController::class, 'index'])->name('paiements');
                Route::post('/etudiants/{id}/restore', [\App\Http\Controllers\ESBTPEtudiantTrashController::class, 'restore'])->name('etudiants.restore');
                Route::delete('/etudiants/{id}/force', [\App\Http\Controllers\ESBTPEtudiantTrashController::class, 'forceDelete'])->name('etudiants.force');
                Route::get('/etudiants/{id}/dependencies', [\App\Http\Controllers\ESBTPEtudiantTrashController::class, 'dependencies'])->name('etudiants.dependencies');
                Route::post('/inscriptions/{id}/restore', [\App\Http\Controllers\ESBTPInscriptionTrashController::class, 'restore'])->name('inscriptions.restore');
                Route::delete('/inscriptions/{id}/force', [\App\Http\Controllers\ESBTPInscriptionTrashController::class, 'forceDelete'])->name('inscriptions.force');
                Route::get('/inscriptions/{id}/dependencies', [\App\Http\Controllers\ESBTPInscriptionTrashController::class, 'dependencies'])->name('inscriptions.dependencies');
                Route::post('/paiements/{id}/restore', [\App\Http\Controllers\ESBTPPaiementTrashController::class, 'restore'])->name('paiements.restore');
                Route::delete('/paiements/{id}/force', [\App\Http\Controllers\ESBTPPaiementTrashController::class, 'forceDelete'])->name('paiements.force');
                Route::get('/paiements/{id}/dependencies', [\App\Http\Controllers\ESBTPPaiementTrashController::class, 'dependencies'])->name('paiements.dependencies');
            });
            Route::get('resultats/classes', [ESBTPResultatController::class, 'resultatsClasses'])
                ->name('resultats.classes')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);
            Route::get('resultats/classe/{classe}', [ESBTPResultatController::class, 'resultatClasse'])
                ->name('resultats.classe')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);
            Route::get('resultats/etudiant/{etudiant}', [ESBTPResultatController::class, 'resultatEtudiant'])
                ->name('resultats.etudiant')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            Route::get('resultats/etudiant/{etudiant}/preview', [ESBTPResultatController::class, 'previewBulletinEtudiantNew'])
                ->name('resultats.etudiant.preview')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            // Route AJAX pour le lazy loading des étudiants sur la page résultats
            Route::get('resultats/load-etudiants', [ESBTPResultatController::class, 'loadEtudiants'])
                ->name('resultats.load-etudiants')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            // Route principale d'édition groupée par classe
            Route::get('resultats/classe/{classe}/edit', [ESBTPResultatController::class, 'editResultatsClasse'])
                ->name('resultats.classe.edit')
                ->middleware(['permission:bulletins.edit']);

            // Routes AJAX pour édition groupée
            Route::get('resultats/get-moyennes', [ESBTPResultatController::class, 'getMoyennes'])
                ->name('resultats.get-moyennes')
                ->middleware(['permission:bulletins.view|bulletins.view_own']);

            Route::get('resultats/get-absences', [ESBTPResultatController::class, 'getAbsences'])
                ->name('resultats.get-absences')
                ->middleware(['permission:bulletins.view|bulletins.view_own']);
            
            // Force route cache refresh - 2025-01-30

            Route::post('resultats/bulk-update-moyennes', [ESBTPResultatController::class, 'bulkUpdateMoyennes'])
                ->name('resultats.bulk-update-moyennes')
                ->middleware(['permission:bulletins.edit']);

            Route::post('resultats/bulk-update-professeurs', [ESBTPResultatController::class, 'bulkUpdateProfesseurs'])
                ->name('resultats.bulk-update-professeurs')
                ->middleware(['permission:bulletins.edit']);

            Route::post('resultats/bulk-update-absences', [ESBTPResultatController::class, 'bulkUpdateAbsences'])
                ->name('resultats.bulk-update-absences')
                ->middleware(['permission:bulletins.edit']);

            Route::post('resultats/bulk-update-coefficients', [ESBTPResultatController::class, 'bulkUpdateCoefficients'])
                ->name('resultats.bulk-update-coefficients')
                ->middleware(['permission:bulletins.edit']);

            Route::get('resultats/get-matiere-coefficient', [ESBTPResultatController::class, 'getMatiereCoefficient'])
                ->name('resultats.get-matiere-coefficient')
                ->middleware(['permission:bulletins.edit']);

            Route::post('resultats/bulk-update-matieres-config', [ESBTPResultatController::class, 'bulkUpdateMatieresConfig'])
                ->name('resultats.bulk-update-matieres-config')
                ->middleware(['permission:bulletins.edit']);

            Route::get('bulletins/configuration', [ESBTPBulletinController::class, 'configuration'])
                ->name('bulletins.configuration')
                ->middleware(['permission:bulletins.edit']);

            Route::post('bulletins/configuration', [ESBTPBulletinController::class, 'saveConfiguration'])
                ->name('bulletins.save-configuration')
                ->middleware(['permission:bulletins.edit']);
            Route::get('resultats/historique/classes', [ESBTPResultatController::class, 'resultats'])
                ->name('resultats.historique.classes')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            // Routes pour les annonces
            Route::resource('annonces', ESBTPAnnonceController::class)
                ->middleware(['permission:annonces.view|annonces.create|annonces.edit']);

            // Routes pour les présences/absences (esbtp namespace)
            Route::name('attendances.')->group(function () {
                Route::get('/attendances', [ESBTPAttendanceController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:attendances.view');

                Route::get('/attendances/create', [ESBTPAttendanceController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:attendances.create');

                Route::post('/attendances', [ESBTPAttendanceController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:attendances.create');

                // Static segment routes first
                Route::get('/attendances/rapport-form', [ESBTPAttendanceController::class, 'rapportForm'])
                    ->name('rapport-form')
                    ->middleware('permission:attendances.view');

                Route::post('/attendances/rapport', [ESBTPAttendanceController::class, 'rapport'])
                    ->name('rapport')
                    ->middleware('permission:attendances.view');

                Route::post('/attendances/rapport-pdf', [ESBTPAttendanceController::class, 'rapportPdf'])
                    ->name('rapport-pdf')
                    ->middleware('permission:attendances.view');

                // Aperçu PDF inline du rapport de présence (Phase 9.5)
                Route::post('/attendances/rapport-pdf/preview', [ESBTPAttendanceController::class, 'rapportPdfPreview'])
                    ->name('rapport-pdf-preview')
                    ->middleware(['permission:attendances.view', 'throttle:60,1']);

                // AJAX routes for partial refresh
                Route::get('/attendances/load-seances', [ESBTPAttendanceController::class, 'loadSeances'])
                    ->name('load-seances')
                    ->middleware('permission:attendances.create');

                Route::get('/attendances/load-students', [ESBTPAttendanceController::class, 'loadStudents'])
                    ->name('load-students')
                    ->middleware('permission:attendances.create');

                // Saisie manuelle des heures par matière
                Route::get('/attendances/manual/load', [ESBTPAttendanceController::class, 'loadManualTab'])
                    ->name('manual.load')
                    ->middleware('permission:attendances.create');

                Route::post('/attendances/manual', [ESBTPAttendanceController::class, 'storeManualHours'])
                    ->name('manual.store')
                    ->middleware('permission:attendances.create');

                Route::delete('/attendances/manual/{id}', [ESBTPAttendanceController::class, 'deleteManualHours'])
                    ->whereNumber('id')
                    ->name('manual.destroy')
                    ->middleware('permission:attendances.create');

                // Then parameter routes
                Route::get('/attendances/{attendance}', [ESBTPAttendanceController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:attendances.view');

                Route::get('/attendances/{attendance}/edit', [ESBTPAttendanceController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:attendances.edit');

                Route::put('/attendances/{attendance}', [ESBTPAttendanceController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:attendances.edit');

                Route::delete('/attendances/{attendance}', [ESBTPAttendanceController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:attendances.delete');

                Route::post('/attendances/{absenceId}/process-justification', [ESBTPAttendanceController::class, 'processJustification'])
                    ->name('process-justification')
                    ->middleware(['permission:attendances.justify_process', 'throttle:30,1']);

                // Admin: list of pending justifications to process
                // Path: /esbtp/attendances/justifications (under the esbtp prefix group)
                Route::get('/attendances/justifications', [ESBTPAttendanceController::class, 'adminProcessing'])
                    ->name('justifications.admin')
                    ->middleware('permission:attendances.justify_process');
            });

            // Routes pour le planning général
            Route::prefix('planning-general')->name('planning-general.')->group(function () {
                Route::get('/', [ESBTPPlanningGeneralController::class, 'index'])->name('index');
                Route::get('/test', [ESBTPPlanningGeneralController::class, 'indexTest'])->name('test');
                Route::post('/planification', [ESBTPPlanningGeneralController::class, 'storePlanification'])->name('store-planification');
                Route::delete('/planification/{id}', [ESBTPPlanningGeneralController::class, 'destroyPlanification'])->name('destroy-planification');
                Route::post('/planification/{id}/valider', [ESBTPPlanningGeneralController::class, 'validerPlanification'])->name('valider-planification');
                Route::post('/configure-rapide', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'configureRapide'])->name('configure-rapide');
                Route::get('/annuel', [ESBTPPlanningGeneralController::class, 'annuel'])->name('annuel');
                Route::get('/repartition-matieres', [ESBTPPlanningGeneralController::class, 'repartitionMatieres'])->name('repartition-matieres');
                Route::get('/coordinateur', [ESBTPPlanningGeneralController::class, 'coordinateur'])->name('coordinateur')
                    ->middleware('permission:planning.manage|timetables.view_all');
            });

            // Routes pour les événements académiques
            Route::prefix('evenements-academiques')->name('evenements-academiques.')->group(function () {
                Route::get('/', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'create'])->name('create');
                Route::get('/create-quick/{type}/{annee_id}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'createQuick'])->name('create-quick');
                Route::post('/', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'store'])->name('store');
                Route::post('/bulk-action', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'bulkAction'])->name('bulk-action');
                Route::get('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'show'])->name('show');
                Route::get('/{evenementAcademique}/edit', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'edit'])->name('edit');
                Route::put('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'update'])->name('update');
                Route::delete('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'destroy'])->name('destroy');
                Route::post('/{evenementAcademique}/duplicate', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'duplicate'])->name('duplicate');
                Route::post('/{evenementAcademique}/status', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'changeStatus'])->name('change-status');
                Route::get('/api/events', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'getEvents'])->name('api.events');
            });

            // Paiements — gates par permission (caissier voit son propre via view_own dans le controller filter)
            Route::middleware('permission:paiements.view|paiements.view_own')->group(function () {
                Route::get('/paiements', [App\Http\Controllers\ESBTPPaiementController::class, 'index'])->name('paiements.index');
                Route::get('/paiements/refresh', [App\Http\Controllers\ESBTPPaiementController::class, 'refresh'])->name('paiements.refresh');
                Route::get('/paiements/check-updates', [App\Http\Controllers\ESBTPPaiementController::class, 'checkForUpdates'])->name('paiements.check-updates');
            });

            // Lot 15 — Export détaillé des paiements (états financiers avec filtres + garde-fou PDF)
            // Throttling : preview AJAX (count) 60/min, preview/generate PDF/Excel 10/min (rule exports-pdf-excel)
            Route::middleware(['permission:paiements.export'])
                ->prefix('paiements/export-detaille')
                ->name('paiements.export-detaille.')
                ->group(function () {
                    Route::get('/', [App\Http\Controllers\ESBTPPaiementExportController::class, 'index'])->name('index');
                    Route::post('/preview', [App\Http\Controllers\ESBTPPaiementExportController::class, 'preview'])
                        ->middleware('throttle:60,1')
                        ->name('preview');
                    Route::post('/preview-pdf', [App\Http\Controllers\ESBTPPaiementExportController::class, 'previewPdf'])
                        ->middleware('throttle:30,1')
                        ->name('preview-pdf');
                    Route::post('/generate', [App\Http\Controllers\ESBTPPaiementExportController::class, 'generate'])
                        ->middleware('throttle:10,1')
                        ->name('generate');
                });

            // ── VIEW (lecture détail/suivi)
            Route::middleware('permission:paiements.view|paiements.view_own')->group(function () {
                // Route de test des filtres — local environment only (audit 2026-05-21)
                if (app()->environment('local')) {
                    Route::get('/paiements/test-filters', [App\Http\Controllers\ESBTPPaiementController::class, 'testFilters'])->name('paiements.test-filters');
                }
                Route::get('/paiements/{paiement}/refresh-ligne', [App\Http\Controllers\ESBTPPaiementController::class, 'refreshLigne'])
                    ->whereNumber('paiement')
                    ->name('paiements.refresh-ligne');
                Route::get('/paiements/suivi-categories', [App\Http\Controllers\ESBTPPaiementSuiviController::class, 'suiviCategories'])->name('paiements.suivi-categories');
                Route::get('/paiements/suivi-categories/refresh', [App\Http\Controllers\ESBTPPaiementSuiviController::class, 'suiviCategoriesRefresh'])->name('paiements.suivi-categories.refresh');
                Route::get('/paiements/suivi-categories/load/{statut}', [App\Http\Controllers\ESBTPPaiementController::class, 'loadStudentsByStatut'])->name('paiements.suivi-categories.load');
                Route::get('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'show'])
                    ->whereNumber('paiement')
                    ->name('paiements.show');
                Route::get('/paiements/{paiement}/preview', [App\Http\Controllers\ESBTPPaiementController::class, 'previewRecu'])
                    ->whereNumber('paiement')
                    ->name('paiements.preview');
                Route::get('/paiements/{paiement}/recu', [App\Http\Controllers\ESBTPPaiementController::class, 'genererRecu'])
                    ->whereNumber('paiement')
                    ->name('paiements.recu');
                Route::get('/paiements/etudiant/{etudiant}', [App\Http\Controllers\ESBTPPaiementController::class, 'paiementsEtudiant'])
                    ->whereNumber('etudiant')
                    ->name('paiements.etudiant');
            });

            // ── EXPORT (Excel/CSV/PDF) — throttle:10,1 sur downloads, 60,1 sur preview (rule exports-pdf-excel)
            Route::middleware(['permission:paiements.export'])->group(function () {
                Route::get('/paiements/export/excel', [App\Http\Controllers\ESBTPPaiementController::class, 'exportExcel'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.export.excel');
                Route::get('/paiements/export/saari', [App\Http\Controllers\ESBTPPaiementController::class, 'exportSaari'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.export.saari');
                Route::get('/paiements/export/csv', [App\Http\Controllers\ESBTPPaiementController::class, 'exportCsv'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.export.csv');
                Route::get('/paiements/export/pdf', [App\Http\Controllers\ESBTPPaiementController::class, 'exportPdf'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.export.pdf');
                Route::get('/paiements/export/pdf-preview', [App\Http\Controllers\ESBTPPaiementController::class, 'exportPdfPreview'])
                    ->middleware('throttle:60,1')
                    ->name('paiements.export.pdf-preview');
                Route::get('/paiements/suivi-categories/export/{statut}/excel', [App\Http\Controllers\ESBTPPaiementController::class, 'exportStudentsExcel'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.suivi-categories.export.excel');
                Route::get('/paiements/suivi-categories/export/{statut}/pdf', [App\Http\Controllers\ESBTPPaiementController::class, 'exportStudentsPdf'])
                    ->middleware('throttle:10,1')
                    ->name('paiements.suivi-categories.export.pdf');
            });

            // ── CREATE (encaissement)
            Route::middleware('permission:paiements.create')->group(function () {
                Route::get('/paiements/create', [App\Http\Controllers\ESBTPPaiementController::class, 'create'])->name('paiements.create');
                Route::post('/paiements', [App\Http\Controllers\ESBTPPaiementController::class, 'store'])->name('paiements.store');
                Route::post('/reliquats/pay', [App\Http\Controllers\ESBTPPaiementController::class, 'payReliquat'])->name('reliquats.pay');
            });

            // ── EDIT (modification d'un paiement existant)
            Route::middleware('permission:paiements.edit')->group(function () {
                Route::get('/paiements/{paiement}/edit', [App\Http\Controllers\ESBTPPaiementController::class, 'edit'])
                    ->whereNumber('paiement')
                    ->name('paiements.edit');
                Route::put('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'update'])
                    ->whereNumber('paiement')
                    ->name('paiements.update');
            });

            // ── DELETE
            Route::delete('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'destroy'])
                ->whereNumber('paiement')
                ->name('paiements.destroy')
                ->middleware('permission:paiements.delete');

            // ── CANCEL OWN RECENT (S1.5 — fenêtre 5min anti-erreur caissier)
            Route::post('/paiements/{paiement}/cancel-own', [App\Http\Controllers\ESBTPPaiementController::class, 'cancelOwn'])
                ->whereNumber('paiement')
                ->name('paiements.cancel-own')
                ->middleware('throttle:30,1');

            // ── JOURNAL DE CAISSE OHADA (S1.3)
            Route::prefix('comptabilite/journal-caisse')->name('comptabilite.journal-caisse.')->group(function () {
                Route::get('/', [App\Http\Controllers\ESBTPJournalCaisseController::class, 'index'])->name('index');
                Route::get('/export-pdf', [App\Http\Controllers\ESBTPJournalCaisseController::class, 'exportPdf'])
                    ->middleware('throttle:10,1')
                    ->name('export-pdf');
                Route::get('/export-pdf/preview', [App\Http\Controllers\ESBTPJournalCaisseController::class, 'exportPdfPreview'])
                    ->middleware('throttle:30,1')
                    ->name('export-pdf-preview');
                Route::get('/preview-pdf', [App\Http\Controllers\ESBTPJournalCaisseController::class, 'previewPdf'])
                    ->middleware('throttle:30,1')
                    ->name('preview-pdf');
            });

            // PR2 réconciliation paiements ↔ caisse physique
            // Routes orchestrées par ESBTPReconciliationController (orchestration uniquement).
            // Toute la logique métier vit dans Domain/Comptabilite/Reconciliation/.
            Route::prefix('comptabilite/reconciliation')->name('comptabilite.reconciliation.')
                ->middleware('throttle:60,1')->group(function () {
                Route::get('/', [App\Http\Controllers\ESBTPReconciliationController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\ESBTPReconciliationController::class, 'create'])->name('create');
                Route::get('/sessions/{session}', [App\Http\Controllers\ESBTPReconciliationController::class, 'show'])
                    ->name('show')->whereNumber('session');
                Route::get('/sessions/{session}/pv-pdf', [App\Http\Controllers\ESBTPReconciliationController::class, 'exportPv'])
                    ->name('export-pv')->whereNumber('session')->middleware('throttle:10,1');
                Route::post('/sessions', [App\Http\Controllers\ESBTPReconciliationController::class, 'open'])->name('open');
                Route::post('/sessions/{session}/cash-counts', [App\Http\Controllers\ESBTPReconciliationController::class, 'recordCount'])
                    ->name('record-count')->whereNumber('session');
                Route::post('/discrepancies/{discrepancy}/resolve', [App\Http\Controllers\ESBTPReconciliationController::class, 'resolve'])
                    ->name('resolve')->whereNumber('discrepancy');
                Route::post('/sessions/{session}/review', [App\Http\Controllers\ESBTPReconciliationController::class, 'review'])
                    ->name('review')->whereNumber('session');
                Route::post('/sessions/{session}/approve', [App\Http\Controllers\ESBTPReconciliationController::class, 'approve'])
                    ->name('approve')->whereNumber('session');
                Route::post('/sessions/{session}/close', [App\Http\Controllers\ESBTPReconciliationController::class, 'close'])
                    ->name('close')->whereNumber('session');
                Route::post('/sessions/{session}/reopen', [App\Http\Controllers\ESBTPReconciliationController::class, 'reopen'])
                    ->name('reopen')->whereNumber('session');
            });

            // ── VALIDATE/REJECT (workflow comptable — throttled)
            Route::middleware(['permission:paiements.validate', 'throttle:60,1'])->group(function () {
                Route::post('/paiements/{paiement}/valider', [App\Http\Controllers\ESBTPPaiementController::class, 'valider'])
                    ->whereNumber('paiement')
                    ->name('paiements.valider');
                Route::post('/paiements/{paiement}/rejeter', [App\Http\Controllers\ESBTPPaiementController::class, 'rejeter'])
                    ->whereNumber('paiement')
                    ->name('paiements.rejeter');
                Route::post('/paiements/{paiement}/valider-rapide', [App\Http\Controllers\ESBTPPaiementController::class, 'validerRapide'])
                    ->whereNumber('paiement')
                    ->name('paiements.valider-rapide');
            });

            // ── BULK VALIDATE/REJECT (plus strict — opère sur N lignes)
            Route::middleware(['permission:paiements.validate', 'throttle:10,1'])->group(function () {
                Route::post('/paiements/bulk-valider', [App\Http\Controllers\ESBTPPaiementController::class, 'bulkValider'])->name('paiements.bulk-valider');
                Route::post('/paiements/bulk-rejeter', [App\Http\Controllers\ESBTPPaiementController::class, 'bulkRejeter'])->name('paiements.bulk-rejeter');
            });

            // Routes ESBTP Bulletins
            Route::prefix('bulletins')->name('bulletins.')->group(function () {
                Route::get('/', [ESBTPBulletinController::class, 'index'])->name('index');
                Route::get('/create', [ESBTPBulletinController::class, 'create'])->name('create');
                Route::post('/', [ESBTPBulletinController::class, 'store'])->name('store');
                // /!\ Routes statiques avant les paramétrées + whereNumber pour éviter shadowing
                Route::get('/select', [ESBTPBulletinController::class, 'select'])->name('select');
                Route::get('/generate', [ESBTPBulletinController::class, 'generateBulletin'])->name('generate');

                Route::get('/{bulletin}', [ESBTPBulletinController::class, 'show'])->whereNumber('bulletin')->name('show');
                Route::get('/{bulletin}/edit', [ESBTPBulletinController::class, 'edit'])->whereNumber('bulletin')->name('edit');
                Route::put('/{bulletin}', [ESBTPBulletinController::class, 'update'])->whereNumber('bulletin')->name('update');
                Route::delete('/{bulletin}', [ESBTPBulletinController::class, 'destroy'])->whereNumber('bulletin')->name('destroy');

                // Route pour la signature des bulletins
                Route::post('bulletins/{bulletin}/signer/{role}', [ESBTPBulletinController::class, 'signer'])
                    ->name('bulletins.signer')
                    ->middleware(['permission:bulletins.edit']);
                // Route pour basculer la publication d'un bulletin
                Route::put('bulletins/{bulletin}/toggle-publication', [ESBTPBulletinController::class, 'togglePublication'])
                    ->name('bulletins.toggle-publication')
                    ->middleware(['permission:bulletins.edit']);

                // Route pour les bulletins en attente
                Route::get('pending', [ESBTPBulletinController::class, 'pending'])
                    ->name('pending')
                    ->middleware(['permission:bulletins.view']);
            });

            // Route for today's timetable - moved outside bulletins group
            Route::get('timetables/today', [ESBTPEmploiTempsController::class, 'today'])->name('timetables.today');

            // Routes pour la gestion des présences des enseignants
            Route::prefix('admin/attendance')->name('admin.attendance.')->group(function () {
                Route::get('/', [ESBTPTeacherAttendanceController::class, 'index'])->name('index');
                Route::post('/generate-code', [ESBTPTeacherAttendanceController::class, 'generateCode'])->name('generate-code');
                Route::post('/cancel-code/{code}', [ESBTPTeacherAttendanceController::class, 'cancelCode'])->name('cancel-code');
                Route::get('/report', [ESBTPTeacherAttendanceController::class, 'report'])->name('report');
                Route::post('/', [ESBTPTeacherAttendanceController::class, 'store'])->name('store');
                Route::put('/{attendance}', [ESBTPTeacherAttendanceController::class, 'update'])->whereNumber('attendance')->name('update');
            });
        });

        // Routes pré-inscription caissier — gates par permission
        Route::middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
            Route::middleware('permission:inscriptions.create')->group(function () {
                Route::get('/inscriptions/pre-inscription', [ESBTPInscriptionController::class, 'createPreInscription'])->name('inscriptions.pre-inscription');
                Route::post('/inscriptions/pre-inscription', [ESBTPInscriptionController::class, 'storePreInscription'])->name('inscriptions.store-pre-inscription');
            });
            Route::middleware('permission:inscriptions.view')->group(function () {
                Route::get('/inscriptions/search-etudiants', [ESBTPInscriptionController::class, 'searchEtudiants'])->name('inscriptions.search-etudiants');
                Route::get('/inscriptions/analyse-etudiant/{etudiantId}', [ESBTPInscriptionController::class, 'analyseEtudiant'])->name('inscriptions.analyse-etudiant');
            });
        });

        // Routes accessibles pour les secrétaires, super-admins, coordinateurs et caissier (consultation)
        Route::middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
            // Nouvelle route pour la vue fusionnée des étudiants et inscriptions
            Route::get('/etudiants-inscriptions', [ESBTPEtudiantController::class, 'indexFusionne'])
                ->name('etudiants-inscriptions.index')
                ->middleware(['permission:students.view|inscriptions.view']);

            // Routes pour la gestion des comptes utilisateurs étudiants
            Route::post('/etudiants/{etudiant}/create-account', [ESBTPEtudiantController::class, 'createUserAccount'])
                ->name('etudiants.create-account')
                ->middleware(['permission:students.edit']);

            Route::get('/etudiants/{etudiant}/reset-password', [ESBTPEtudiantController::class, 'resetPassword'])
                ->name('etudiants.reset-password')
                ->middleware(['permission:students.edit']);

            // Route pour rechercher des parents existants
            Route::get('/parents/search', [ESBTPEtudiantController::class, 'searchParents'])
                ->name('parents.search')
                ->middleware(['permission:students.edit']);

            // Route pour générer un certificat de scolarité

            // Routes pour les inscriptions ESBTP — gates par permission
            // ── VIEW (lecture)
            Route::middleware('permission:inscriptions.view')->group(function () {
                Route::get('/inscriptions', [ESBTPInscriptionController::class, 'index'])->name('inscriptions.index');
                Route::get('/inscriptions/getClasses', [ESBTPInscriptionApiController::class, 'getClasses'])->name('inscriptions.getClasses');
                Route::get('/inscriptions/check-transfert/{classe}', [ESBTPInscriptionApiController::class, 'checkTransfert'])->name('inscriptions.check-transfert');
                Route::get('/inscriptions/duplicates', [ESBTPInscriptionApiController::class, 'duplicates'])->name('inscriptions.duplicates');
                Route::post('/inscriptions/check-duplicates', [ESBTPInscriptionApiController::class, 'checkDuplicates'])->name('inscriptions.check-duplicates');
                Route::get('/inscriptions/sous-reserve', [ESBTPInscriptionController::class, 'sousReserveIndex'])->name('inscriptions.sous-reserve');
                Route::get('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'show'])->name('inscriptions.show');
                Route::get('/inscriptions/{inscription}/situation-financiere/preview', [ESBTPInscriptionPaiementController::class, 'previewSituationFinanciere'])->name('inscriptions.situation-financiere.preview');
                Route::get('/inscriptions/{inscription}/situation-financiere/pdf', [ESBTPInscriptionPaiementController::class, 'exportSituationFinanciere'])->name('inscriptions.situation-financiere.pdf');
                Route::get('/inscriptions/{inscription}/situation-financiere/pdf/preview', [ESBTPInscriptionPaiementController::class, 'previewSituationFinancierePdf'])
                    ->name('inscriptions.situation-financiere.pdf-preview')
                    ->middleware('throttle:60,1');
                Route::get('/inscriptions/{inscription}/data', [ESBTPInscriptionApiController::class, 'getInscriptionData'])->name('inscriptions.data');
                Route::get('/inscriptions/{inscription}/paiement-en-attente', [ESBTPInscriptionApiController::class, 'getPaiementEnAttente'])->name('inscriptions.paiement-en-attente');
                Route::get('/inscriptions/{inscription}/classes-alternatives', [ESBTPInscriptionApiController::class, 'getClassesAlternatives'])->name('inscriptions.classes-alternatives');
                Route::get('/inscriptions/{inscription}/frais-restants', [ESBTPInscriptionPaiementController::class, 'getFraisRestants'])->name('inscriptions.frais-restants');
                Route::get('/inscriptions/{inscription}/frais/{category}/montant-restant', [ESBTPInscriptionPaiementController::class, 'getMontantRestant'])->name('inscriptions.frais.montant-restant');
                Route::get('/inscriptions/{inscription}/refresh-ligne', [ESBTPInscriptionController::class, 'refreshLigne'])->name('inscriptions.refresh-ligne');
                Route::get('/inscriptions-administration', [ESBTPInscriptionController::class, 'administration'])->name('inscriptions.administration');
                Route::post('/inscriptions/bulk-export', [ESBTPInscriptionController::class, 'bulkExport'])->name('inscriptions.bulk-export');
            });

            // ── CREATE (nouvelles inscriptions)
            Route::middleware('permission:inscriptions.create')->group(function () {
                Route::get('/inscriptions/create', [ESBTPInscriptionController::class, 'create'])->name('inscriptions.create');
                Route::post('/inscriptions', [ESBTPInscriptionController::class, 'store'])->name('inscriptions.store');
            });

            // ── EDIT (modification)
            Route::middleware('permission:inscriptions.edit')->group(function () {
                Route::get('/inscriptions/{inscription}/edit', [ESBTPInscriptionController::class, 'edit'])->name('inscriptions.edit');
                Route::put('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'update'])->name('inscriptions.update');
                Route::post('/inscriptions/{inscription}/changer-classe-rapide', [ESBTPInscriptionController::class, 'changerClasseRapide'])->name('inscriptions.changer-classe-rapide');
                Route::post('/inscriptions/{inscription}/lever-reserve', [ESBTPInscriptionController::class, 'leverReserve'])->name('inscriptions.lever-reserve');
                Route::post('/inscriptions/{inscription}/marquer-sous-reserve', [ESBTPInscriptionController::class, 'marquerSousReserve'])->name('inscriptions.marquer-sous-reserve');
                Route::post('/inscriptions/lever-reserves-bulk', [ESBTPInscriptionController::class, 'leverReservesBulk'])->name('inscriptions.lever-reserves-bulk');
            });

            // ── VALIDATE (workflow validation — la plus sensible)
            Route::middleware('permission:inscriptions.validate')->group(function () {
                Route::put('/inscriptions/{inscription}/valider', [ESBTPInscriptionController::class, 'valider'])->name('inscriptions.valider');
                Route::post('/inscriptions/bulk-valider', [ESBTPInscriptionController::class, 'bulkValider'])->name('inscriptions.bulk-valider');
                Route::post('/inscriptions/{inscription}/valider-definitivement', [ESBTPInscriptionPaiementController::class, 'validerDefinitivement'])->name('inscriptions.valider-definitivement');
            });

            // valider-avec-paiement : crée d'abord un paiement → permission paiements.create suffit.
            // Le contrôleur exige paiements.validate.self_override pour valider le paiement
            // (S1.1 anti-fraude : créateur = validateur), et inscriptions.validate pour
            // auto_validate_inscription. Voir ESBTPInscriptionPaiementController::validerAvecPaiement.
            Route::post('/inscriptions/{inscription}/valider-avec-paiement', [ESBTPInscriptionPaiementController::class, 'validerAvecPaiement'])
                ->name('inscriptions.valider-avec-paiement')
                ->middleware('permission:paiements.create');

            // ── CANCEL (annulation)
            Route::middleware('permission:inscriptions.cancel')->group(function () {
                Route::put('/inscriptions/{inscription}/annuler', [ESBTPInscriptionController::class, 'annuler'])->name('inscriptions.annuler');
                Route::post('/inscriptions/bulk-annuler', [ESBTPInscriptionController::class, 'bulkAnnuler'])->name('inscriptions.bulk-annuler');
            });

            // ── DELETE
            Route::delete('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'destroy'])
                ->name('inscriptions.destroy')
                ->middleware('permission:inscriptions.delete');

            // ── PAYMENT-LINKED (paiement initié depuis fiche inscription)
            Route::post('/inscriptions/{inscription}/payer-frais', [ESBTPInscriptionPaiementController::class, 'payerFraisCategorie'])
                ->name('inscriptions.payer-frais')
                ->middleware('permission:paiements.create');
            Route::post('/inscriptions/{inscription}/transfer-overpayment', [ESBTPInscriptionPaiementController::class, 'transferOverpayment'])
                ->name('inscriptions.transfer-overpayment')
                ->middleware('permission:paiements.edit');
            Route::put('/inscriptions/{inscription}/subscriptions/{subscription}', [ESBTPInscriptionPaiementController::class, 'updateSubscription'])
                ->name('inscriptions.update-subscription')
                ->middleware('role:superAdmin');

            // API pour les parents dans les inscriptions
            Route::get('/api/parents/search', [ESBTPInscriptionApiController::class, 'searchParents'])->name('api.parents.search');

            // Route pour récupérer les frais par classe
            Route::get('/inscriptions/frais-by-classe/{classeId}', [ESBTPInscriptionApiController::class, 'getFraisByClasse'])->name('inscriptions.frais-by-classe');

            // Routes pour la spécialisation (tronc commun → spécialisation)
            Route::get('/inscriptions/{inscription}/specialisation', [\App\Http\Controllers\ESBTP\ESBTPSpecialisationController::class, 'show'])->name('inscriptions.specialisation');
            Route::get('/inscriptions/{inscription}/specialisation/classes', [\App\Http\Controllers\ESBTP\ESBTPSpecialisationController::class, 'getClasses'])->name('inscriptions.specialisation.classes');
            Route::post('/inscriptions/{inscription}/specialisation', [\App\Http\Controllers\ESBTP\ESBTPSpecialisationController::class, 'store'])->name('inscriptions.specialisation.store');

            // Admin BTS Tronc Commun — Configuration des sorties (target classes)
            Route::prefix('admin/orientation-targets')->name('admin.orientation-targets.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\BtsOrientationTargetController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\Admin\BtsOrientationTargetController::class, 'store'])->name('store');
                Route::patch('/{target}', [\App\Http\Controllers\Admin\BtsOrientationTargetController::class, 'update'])->name('update');
                Route::delete('/{target}', [\App\Http\Controllers\Admin\BtsOrientationTargetController::class, 'destroy'])->name('destroy');
            });

            // Resync BTS phases (maintenance données — désynchronisation historique)
            Route::post('/inscriptions/bts-sync-all', [\App\Http\Controllers\ESBTPInscriptionController::class, 'bulkSyncBtsPhases'])
                ->middleware('throttle:5,1')
                ->name('inscriptions.bts-sync-all');
            Route::post('/inscriptions/{inscription}/bts-sync', [\App\Http\Controllers\ESBTPInscriptionController::class, 'syncBtsPhase'])
                ->middleware('throttle:30,1')
                ->name('inscriptions.bts-sync');

            // Routes API utilisées par les formulaires

            // Routes pour les notes — throttle anti-abus (saisie unitaire 30/min, bulk 10/min).
            Route::post('notes/save-ajax', [ESBTPNoteController::class, 'saveNoteAjax'])
                ->middleware('throttle:30,1')
                ->name('notes.save-ajax');
            Route::post('notes/save-ajax-bulk', [ESBTPNoteController::class, 'saveNotesAjaxBulk'])
                ->middleware('throttle:10,1')
                ->name('notes.save-ajax-bulk');

            // PR #7 — Excel import/export bidirectionnel + preview impact bulletin
            Route::get('notes/export-excel', [ESBTPNoteController::class, 'exportExcel'])
                ->middleware('throttle:10,1')
                ->name('notes.export-excel');
            Route::post('notes/import/dry-run', [ESBTPNoteController::class, 'importDryRun'])
                ->middleware('throttle:5,1')
                ->name('notes.import.dry-run');
            Route::post('notes/import/apply', [ESBTPNoteController::class, 'importApply'])
                ->middleware('throttle:3,1')
                ->name('notes.import.apply');
            Route::post('notes/preview-impact', [ESBTPNoteController::class, 'previewImpact'])
                ->middleware('throttle:60,1')
                ->name('notes.preview-impact');
            Route::resource('notes', \App\Http\Controllers\ESBTPNoteController::class)
                ->names([
                    'index' => 'esbtp.notes.index',
                    'create' => 'esbtp.notes.create',
                    'store' => 'esbtp.notes.store',
                    'show' => 'esbtp.notes.show',
                    'edit' => 'esbtp.notes.edit',
                    'update' => 'esbtp.notes.update',
                    'destroy' => 'esbtp.notes.destroy',
                ])
                ->middleware(['permission:notes.view|notes.create|notes.edit|notes.delete']);
            Route::get('evaluations/{evaluation}/saisie-rapide', [ESBTPNoteController::class, 'saisieRapide'])
                ->middleware('throttle:60,1')
                ->name('notes.saisie-rapide');
            Route::get('evaluations/{evaluation}/saisie-rapide/pdf', [ESBTPNoteController::class, 'saisieRapidePDF'])->name('notes.saisie-rapide.pdf');
            Route::get('evaluations/{evaluation}/saisie-rapide/pdf/preview', [ESBTPNoteController::class, 'saisieRapidePDFPreview'])
                ->name('notes.saisie-rapide.pdf-preview')
                ->middleware('throttle:60,1');
            Route::get('classes/{classe}/notes/saisie-rapide/pdf', [ESBTPNoteController::class, 'saisieRapideBlankPDF'])->name('notes.saisie-rapide-blank.pdf');
            Route::get('classes/{classe}/notes/saisie-rapide/pdf/preview', [ESBTPNoteController::class, 'saisieRapideBlankPDFPreview'])
                ->name('notes.saisie-rapide-blank.pdf-preview')
                ->middleware('throttle:60,1');
            Route::post('notes/store-batch', [ESBTPNoteController::class, 'enregistrerSaisieRapide'])
                ->middleware('throttle:10,1')
                ->name('notes.store-batch');
        });

        // Espace étudiant - routes accessibles pour les étudiants
        Route::middleware(['auth', 'role:etudiant'])->group(function () {
            Route::get('/mon-profil', [ESBTPEtudiantController::class, 'profile'])
                ->name('mon-profil.index')
                ->middleware(['permission:profile.view_own|students.view']);
            Route::put('/mon-profil/update', [ESBTPEtudiantController::class, 'updateProfile'])
                ->name('mon-profil.update');
            Route::put('/mon-profil/password', [ESBTPEtudiantController::class, 'updatePassword'])
                ->name('mon-profil.password.update');

            Route::get('/mes-notes', [ESBTPNoteController::class, 'studentGrades'])
                ->name('mes-notes.index')
                ->middleware(['permission:notes.view_own|notes.view']);

            Route::get('/mon-emploi-temps', [ESBTPEmploiTempsController::class, 'studentTimetable'])
                ->name('mon-emploi-temps.index')
                ->middleware(['permission:timetables.view_own|timetables.view']);

            // Routes pour l'affichage des classes (lecture seule) pour les étudiants
            Route::get('/student-classes', [ESBTPClasseController::class, 'index'])
                ->name('student.classes.index')
                ->middleware(['permission:classes.view']);
            Route::get('/student-classes/{classe}', [ESBTPClasseController::class, 'show'])
                ->name('student.classes.show')
                ->middleware(['permission:classes.view']);

            Route::get('/mon-bulletin', [ESBTPStudentBulletinController::class, 'studentBulletins'])
                ->name('mon-bulletin.index')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            Route::get('/mon-bulletin/{bulletinId}', [ESBTPStudentBulletinController::class, 'showStudentBulletin'])
                ->name('mon-bulletin.show')
                ->middleware(['permission:bulletins.view_own|bulletins.view']);

            // Route pour accéder à la page des absences
            Route::get('/esbtp/mes-absences', [ESBTPAttendanceController::class, 'studentAttendance'])
                ->name('mes-absences.index')
                ->middleware(['permission:attendances.view_own|attendances.view']);

            // Route pour justifier une absence (throttle 6/min anti spam upload)
            Route::post('/esbtp/mes-absences/{absenceId}/justify', [ESBTPAttendanceController::class, 'justifyAbsence'])
                ->name('mes-absences.justify')
                ->middleware(['permission:attendances.justify_own|attendances.justify_process', 'throttle:6,1']);

            // Download signed URL — Policy::viewDocument authorize, private disk
            Route::get('/esbtp/justifications/{absence}/document', [ESBTPAttendanceController::class, 'downloadJustificationDocument'])
                ->name('justifications.document')
                ->middleware(['signed', 'throttle:30,1']);

            // Route de debug pour les absences — local environment only (audit 2026-05-21).
            // Avant: visible en prod (la "documentation" ne suffisait pas comme gating).
            if (app()->environment('local')) {
                Route::get('/mes-absences/debug', [ESBTPAttendanceController::class, 'studentAttendance'])
                    ->name('mes-absences.debug')
                    ->middleware(['role:etudiant'])
                    ->defaults('debug', true);
            }

            Route::get('/mes-evaluations', [ESBTPEvaluationController::class, 'studentEvaluations'])
                ->name('mes-evaluations.index')
                ->middleware(['permission:exams.view_own|exams.view']);

            // Route pour accéder à la page des paiements de l'étudiant
            Route::get('/mes-paiements', [\App\Http\Controllers\ESBTP\MesPaiementsController::class, 'index'])
                ->name('mes-paiements.index')
                ->middleware(['permission:profile.view_own|notes.view_own']);

            // Routes pour les notifications des étudiants
            Route::get('/mes-notifications', [ESBTPNotificationController::class, 'index'])->name('mes-notifications.index');
            Route::post('/mes-notifications/{id}/read', [ESBTPNotificationController::class, 'markAsRead'])
                ->name('mes-notifications.read');
            Route::post('/mes-notifications/mark-all-read', [ESBTPNotificationController::class, 'markAllAsRead'])
                ->name('mes-notifications.markAllAsRead');
            Route::get('/notifications/unread-count', [ESBTPNotificationController::class, 'getUnreadCount'])
                ->name('notifications.unreadCount');

            // Routes annonces étudiantes (consultation des ESBTPAnnonce reçues).
            // Sémantique : un étudiant ne reçoit pas de "messages" personnels mais des
            // ANNONCES (broadcast admin → étudiant). D'où le naming `mes-annonces`.
            // Gate `annonces.view` cohérent avec le reste des routes étudiant.
            Route::get('/mes-annonces', [ESBTPAnnonceController::class, 'studentAnnonces'])
                ->name('mes-annonces.index')
                ->middleware('permission:annonces.view');
            Route::post('/mes-annonces/{id}/read', [ESBTPAnnonceController::class, 'markAsRead'])
                ->name('mes-annonces.read')
                ->middleware('permission:annonces.view');
            Route::post('/mes-annonces/mark-all-read', [ESBTPAnnonceController::class, 'markAllAsRead'])
                ->name('mes-annonces.mark-all-read')
                ->middleware('permission:annonces.view');

            // Alias rétrocompat : redirige les anciens bookmarks /mes-messages → /mes-annonces.
            Route::redirect('/mes-messages', '/esbtp/mes-annonces', 301)->name('mes-messages.index');
        });

        // Routes pour la suppression de ressources (protégées par permissions spécifiques)
        Route::middleware(['auth'])->group(function () {
            // Suppression d'étudiants
            Route::delete('/etudiants/{etudiant}', [ESBTPEtudiantController::class, 'destroy'])->name('etudiants.destroy')
                ->middleware(['permission:students.delete']);

            // Suppression de bulletins
            Route::delete('bulletins/{bulletin}', [ESBTPBulletinController::class, 'destroy'])->name('bulletins.destroy')
                ->middleware(['permission:bulletins.edit']);

            // Route de suppression des emplois du temps - Handled by resource route
        });

        // Specialties / Continuing education / Student restore
        Route::middleware(['permission:admin.access'])->group(function () {
            Route::resource('specialties', ESBTPSpecialtyController::class);
            Route::put('specialties/{id}/restore', [ESBTPSpecialtyController::class, 'restore'])->name('specialties.restore');
            Route::resource('continuing-education', ESBTPContinuingEducationController::class);
            Route::put('continuing-education/{id}/restore', [ESBTPContinuingEducationController::class, 'restore'])->name('continuing-education.restore');
            Route::put('students/{id}/restore', [ESBTPStudentController::class, 'restore'])->name('students.restore');
        });
    });

    // Routes pour les paramètres et les rôles
    Route::middleware(['auth', 'permission:system.manage'])->group(function () {
        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');

        Route::get('/roles', function () {
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get();

            return view('admin.roles.index', compact('roles'));
        })->name('roles.index');
    });

    // Notifications routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [ESBTPNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/{id}/read', [ESBTPNotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/mark-all-read', [ESBTPNotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::delete('/{id}/delete', [ESBTPNotificationController::class, 'delete'])->name('notifications.delete');
        Route::get('/unread-count', [ESBTPNotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    });

    // Student Progression Routes
    Route::prefix('esbtp')->middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
        Route::get('/progression', [StudentProgressionController::class, 'index'])->name('esbtp.progression.index');
        Route::get('/api/progression/recommendations/{classe}/{annee}', [StudentProgressionController::class, 'getRecommendations'])->name('esbtp.progression.recommendations');
        Route::post('/api/progression/process', [StudentProgressionController::class, 'processProgression'])->name('esbtp.progression.process');

        // ESBTP Settings Routes (require manage_system)
        Route::middleware(['permission:system.manage'])->group(function () {
            Route::get('/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'index'])->name('esbtp.settings.index');
            Route::put('/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'update'])->name('esbtp.settings.update');
            Route::post('/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'store'])->name('esbtp.settings.store');
            Route::delete('/settings/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'destroy'])->name('esbtp.settings.destroy');

            // ESBTP Settings Backup Routes
            Route::get('/settings/backups', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'backups'])->name('esbtp.settings.backups');
            Route::post('/settings/backup', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'createBackup'])->name('esbtp.settings.backup');
            Route::post('/settings/restore/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'restoreBackup'])->name('esbtp.settings.restore');
            Route::get('/settings/backup/{id}/compare', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'compareBackup'])->name('esbtp.settings.backup.compare');
            Route::post('/settings/backup/{id}/archive', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'archiveBackup'])->name('esbtp.settings.backup.archive');
            Route::delete('/settings/backup/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'destroy'])->name('esbtp.settings.backup.delete');
            Route::post('/settings/backups/cleanup', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'cleanupBackups'])->name('esbtp.settings.backups.cleanup');

            // ESBTP Settings Additional Routes
            Route::post('/settings/{id}/reset', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'resetToDefault'])->name('esbtp.settings.reset');
            Route::get('/settings/export', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'export'])->name('esbtp.settings.export');
            Route::post('/settings/import', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'import'])->name('esbtp.settings.import');
            Route::get('/settings/status', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'checkStatus'])->name('esbtp.settings.status');
            Route::post('/settings/validate', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'checkStatus'])->name('esbtp.settings.validate');
        });

        // Phase 9 — Aperçu PDF avec settings non persistés (nouvelle tab)
        // Accepte POST + PUT : le form principal /esbtp/settings utilise le
        // spoofing _method=PUT (form Laravel @method('PUT')); le bouton aperçu
        // submit via formaction qui préserve ce _method, donc Laravel route en PUT.
        Route::match(['POST', 'PUT'], '/settings/pdf-preview', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'pdfPreview'])
            ->middleware(['permission:settings.pdf.manage', 'throttle:30,1'])
            ->name('esbtp.settings.pdf-preview');

        // ESBTP Parents Search (pour modal de sélection dans edit étudiant)
        Route::get('/parents/search', [ESBTPEtudiantController::class, 'searchParents'])->name('esbtp.parents.search');
    });

    // Configuration des matricules - accès direct sans sidebar
    Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'role:serviceTechnique'])->group(function () {
        Route::get('/matricule-config', [ESBTPMatriculeConfigController::class, 'index'])->name('matricule-config.index');
        Route::post('/matricule-config', [ESBTPMatriculeConfigController::class, 'store'])->name('matricule-config.store');
        Route::delete('/matricule-config/{id}', [ESBTPMatriculeConfigController::class, 'destroy'])->name('matricule-config.destroy');
        Route::post('/matricule-config/preview', [ESBTPMatriculeConfigController::class, 'previewMatricule'])->name('matricule-config.preview');
        Route::post('/matricule-config/change-mode', [ESBTPMatriculeConfigController::class, 'changeMode'])->name('matricule-config.change-mode');
        Route::post('/matricule-config/change-etablissement', [ESBTPMatriculeConfigController::class, 'changeEtablissement'])->name('matricule-config.change-etablissement');
        Route::post('/matricule-config/get-configurations', [ESBTPMatriculeConfigController::class, 'getConfigurations'])->name('matricule-config.get-configurations');

        Route::get('/roles-permissions', [\App\Http\Controllers\ESBTPRolePermissionConfigController::class, 'index'])->name('roles-permissions.index');
        Route::post('/roles-permissions', [\App\Http\Controllers\ESBTPRolePermissionConfigController::class, 'update'])->name('roles-permissions.update');
        Route::post('/roles-permissions/restore-defaults', [\App\Http\Controllers\ESBTPRolePermissionConfigController::class, 'restoreDefaults'])->name('roles-permissions.restore-defaults');
        Route::get('/roles-permissions/audit', [\App\Http\Controllers\ESBTPRolePermissionConfigController::class, 'audit'])->name('roles-permissions.audit');

        Route::get('/bulletin-style', [\App\Http\Controllers\ESBTP\ServiceTechniqueBulletinStyleController::class, 'index'])
            ->name('bulletin-style.index');
        Route::post('/bulletin-style', [\App\Http\Controllers\ESBTP\ServiceTechniqueBulletinStyleController::class, 'update'])
            ->name('bulletin-style.update');

    });

    // Endpoints matricule utilisés par les inscriptions
    Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
        Route::get('/matricule-config/mode-info', [ESBTPMatriculeConfigController::class, 'getModeInfo'])->name('matricule-config.mode-info');
        Route::post('/matricule-config/generate', [ESBTPMatriculeConfigController::class, 'genererMatricule'])->name('matricule-config.generate');
        Route::post('/matricule-config/check', [ESBTPMatriculeConfigController::class, 'checkMatricule'])->name('matricule-config.check');
    });

    // Routes pour la configuration du paywall - Service Technique ADC seulement
    Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'paywall', 'permission:system.manage'])->group(function () {
        Route::get('/paywall-config', [ESBTPPaywallConfigController::class, 'index'])->name('paywall-config.index');
        Route::get('/paywall-config/blocked', [ESBTPPaywallConfigController::class, 'blocked'])->name('paywall-config.blocked');
        Route::get('/paywall-config/upgrade', [ESBTPPaywallConfigController::class, 'upgrade'])->name('paywall-config.upgrade');
        Route::post('/paywall-config', [ESBTPPaywallConfigController::class, 'store'])->name('paywall-config.store');
        Route::post('/paywall-config/extend', [ESBTPPaywallConfigController::class, 'extendSubscription'])->name('paywall-config.extend');
        Route::post('/paywall-config/generate-emergency', [ESBTPPaywallConfigController::class, 'generateEmergencyCode'])->name('paywall-config.generate-emergency');
        Route::get('/paywall-config/status', [ESBTPPaywallConfigController::class, 'checkStatus'])->name('paywall-config.status');
    });

    // Routes pour l'émargement - Administration
    Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'permission:attendances.generate_codes'])->group(function () {
        Route::get('/', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'index'])->name('index');
        Route::post('/generate-code', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'generateCode'])->name('generate-code');
        Route::get('/settings', [App\Http\Controllers\ESBTP\Admin\ESBTPAttendanceSettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [App\Http\Controllers\ESBTP\Admin\ESBTPAttendanceSettingsController::class, 'update'])->name('settings.update');
        Route::get('/report', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'report'])->name('report');
        Route::get('/export', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'export'])->name('export');
        Route::get('/{attendance}/details', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'details'])->name('details');
        Route::post('/cancel-code/{code}', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'cancelCode'])->name('cancel-code');
        Route::post('/validate/{attendance}', [App\Http\Controllers\ESBTP\Admin\AttendanceController::class, 'validateAttendance'])->name('validate');
    });

    // Routes pour l'émargement - Interface Enseignant
    Route::prefix('esbtp/teacher/attendance')->name('esbtp.teacher.attendance.')->middleware(['auth', 'role:enseignant'])->group(function () {
        Route::get('/', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'index'])->name('index')->middleware('permission:attendances.view_own');
        Route::get('/history', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'history'])->name('history')->middleware('permission:attendances.view_own');
        Route::post('/sign', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'sign'])->name('sign')->middleware('permission:attendances.sign');
    });

    // Routes d'émargement pour les enseignants
    Route::middleware(['role:enseignant'])->group(function () {
        Route::get('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'index'])->name('attendance.mark');
        Route::post('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'mark'])->name('attendance.mark.submit');
    });
});

// Routes pour les enseignants
Route::middleware(['auth', 'role:enseignant'])->group(function () {
    // Routes pour l'émargement - Enseignants
    Route::prefix('attendance')->name('teacher.attendance.')->group(function () {
        Route::get('/', 'ESBTP\TeacherAttendanceController@index')->name('index');
        Route::post('/sign', 'ESBTP\TeacherAttendanceController@sign')->name('sign');
    });
});

// Routes pour le tableau de bord étudiant et enseignant
// Ce bloc de routes est commenté car il est redondant avec les routes définies dans le préfixe 'esbtp'
// Route::middleware(['auth', 'verified'])->group(function () {
//     // Routes pour les étudiants
//     Route::middleware(['role:etudiant'])->group(function () {
//         // Mes notes
//         Route::get('/mes-notes', [App\Http\Controllers\ESBTPNoteController::class, 'studentGrades'])
//             ->name('mes-notes.index');
//
//         // Mes examens
//         Route::get('/mes-examens', [App\Http\Controllers\ESBTPExamenController::class, 'studentExams'])
//             ->name('mes-examens.index');
//
//         // Mon bulletin
//         Route::get('/mon-bulletin', [App\Http\Controllers\ESBTPStudentBulletinController::class, 'studentBulletins'])
//             ->name('mon-bulletin.index');
//
//         // Mes absences
//         Route::get('/mes-absences', [App\Http\Controllers\ESBTPAttendanceController::class, 'studentAttendance'])
//             ->name('mes-absences.index');
//
//         // Mon emploi du temps
//         Route::get('/mon-emploi-temps', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'studentTimetable'])
//             ->name('mon-emploi-temps.index');
//
//         // Mon profil
//         Route::get('/mon-profil', [App\Http\Controllers\ESBTPEtudiantController::class, 'profile'])
//             ->name('mon-profil.index');
//     });
// });

// API routes for ESBTP
Route::prefix('api/esbtp')->name('api.esbtp.')->middleware(['auth'])->group(function () {
    Route::get('matieres/list', [ESBTPMatiereController::class, 'apiList'])->name('matieres.list');
});

Route::prefix('esbtp/api')->name('esbtp.api.')->middleware(['auth', 'permission:admin.access'])->group(function () {
    Route::get('classes/{id}', [ESBTPClasseController::class, 'getClasseById'])->name('classes.get');
    Route::get('classes/{id}/niveau-config', [ESBTPClasseController::class, 'getNiveauConfig'])->name('classes.niveau-config');
    Route::get('get-classes', [ESBTPInscriptionApiController::class, 'getClasses'])->name('get-classes');
    Route::get('search-parents', [ESBTPEtudiantController::class, 'searchParents'])->name('search-parents');
    Route::get('etudiants/search', [ESBTPEtudiantController::class, 'searchForApi'])->name('etudiants.search');
    Route::get('etudiants/inscriptions', [ESBTPEtudiantController::class, 'getInscriptionsForApi'])->name('etudiants.inscriptions');
    Route::get('etudiants/soldes', [ESBTPEtudiantController::class, 'getSoldesForApi'])->name('etudiants.soldes');
    Route::get('frais/categories', [\App\Http\Controllers\ESBTPFraisController::class, 'getCategoriesForApi'])->name('frais.categories');
});

// Route for activating all timetables
Route::post('esbtp/activate-all-timetables', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'activateAll'])
    ->name('esbtp.emploi-temps.activate-all')
    ->middleware(['auth', 'permission:timetables.edit']);

// Route for setting a timetable as current
Route::post('esbtp/emploi-temps/{id}/set-current', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'setCurrent'])
    ->name('esbtp.emploi-temps.set-current')
    ->middleware(['auth', 'permission:timetables.edit']);

// Routes pour les évaluations
Route::prefix('esbtp/evaluations')->name('esbtp.evaluations.')->middleware(['auth', 'permission:admin.access'])->group(function () {
    Route::get('/', [ESBTPEvaluationController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPEvaluationController::class, 'create'])->name('create');
    Route::post('/', [ESBTPEvaluationController::class, 'store'])->name('store');

    // AJAX: Charger matières disponibles pour une classe (via combinaisons globales)
    // IMPORTANT: Cette route doit être AVANT les routes /{evaluation} pour éviter les conflits
    Route::get('/load-matieres', [ESBTPEvaluationController::class, 'loadMatieres'])->name('load-matieres');
    Route::get('/coefficients/modal', [ESBTPEvaluationController::class, 'coefficientsModal'])->name('coefficients.modal');
    Route::post('/coefficients/update', [ESBTPEvaluationController::class, 'updateCoefficients'])->name('coefficients.update');
    Route::get('/coefficients/check', [ESBTPEvaluationController::class, 'checkCoefficient'])->name('coefficients.check');
    // Sous-lot δ
    Route::post('/coefficients/copy', [ESBTPEvaluationController::class, 'copyCoefficients'])->name('coefficients.copy');
    Route::get('/coefficients/completion', [ESBTPEvaluationController::class, 'coefficientsCompletion'])->name('coefficients.completion');
    Route::get('/coefficients/read', [ESBTPEvaluationController::class, 'readCoefficients'])->name('coefficients.read');

    Route::get('/{evaluation}/refresh-row', [ESBTPEvaluationController::class, 'refreshRow'])->name('refresh-row');
    // Quick edit (titre + barème + coefficient) — utilisé depuis le modal notes (PR #4)
    Route::patch('/{evaluation}/quick-update', [ESBTPEvaluationController::class, 'quickUpdate'])
        ->middleware('throttle:30,1')
        ->name('quick-update');
    Route::patch('/{evaluation}/cancel', [ESBTPEvaluationController::class, 'cancel'])->name('cancel');
    Route::patch('/{evaluation}/restore', [ESBTPEvaluationController::class, 'restore'])->name('restore');
    // whereNumber évite que evaluations/active-external-links etc. soit capturé par show/update/destroy
    Route::get('/{evaluation}', [ESBTPEvaluationController::class, 'show'])->whereNumber('evaluation')->name('show');
    Route::get('/{evaluation}/edit', [ESBTPEvaluationController::class, 'edit'])->whereNumber('evaluation')->name('edit');
    Route::put('/{evaluation}', [ESBTPEvaluationController::class, 'update'])->whereNumber('evaluation')->name('update');
    Route::delete('/{evaluation}', [ESBTPEvaluationController::class, 'destroy'])->whereNumber('evaluation')->name('destroy');
    Route::patch('/{evaluation}/toggle-published', [ESBTPEvaluationController::class, 'togglePublished'])->name('toggle-published');
    Route::patch('/{evaluation}/toggle-notes-published', [ESBTPEvaluationController::class, 'toggleNotesPublished'])->name('toggle-notes-published');
    Route::patch('/{evaluation}/update-status', [ESBTPEvaluationController::class, 'updateStatus'])->name('update-status');
    Route::get('/{evaluation}/pdf', [ESBTPEvaluationController::class, 'generatePdf'])->name('pdf');
    Route::get('/{evaluation}/pdf/preview', [ESBTPEvaluationController::class, 'previewPdf'])
        ->name('pdf-preview')
        ->middleware('throttle:60,1');
});

// Routes pour les notifications des étudiants
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'role:etudiant'])->group(function () {
    Route::get('/mes-notifications', [ESBTPNotificationController::class, 'index'])->name('mes-notifications.index');
    Route::post('/mes-notifications/{id}/read', [ESBTPNotificationController::class, 'markAsRead'])->name('mes-notifications.read');
    Route::post('/mes-notifications/mark-all-read', [ESBTPNotificationController::class, 'markAllAsRead'])->name('mes-notifications.markAllAsRead');
    Route::get('/notifications/unread-count', [ESBTPNotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
});

// Ajouter la route pour générer le PDF d'une évaluation
Route::get('/evaluations/{evaluation}/pdf', [ESBTPEvaluationController::class, 'generatePdf'])
    ->name('evaluations.pdf')
    ->middleware(['auth']);

// Route pour l'index des bulletins ESBTP
Route::get('/esbtp/bulletins', [ESBTPBulletinController::class, 'index'])->name('esbtp.bulletins.index')->middleware(['auth', 'permission:admin.access']);

// Route spéciale pour la sélection des bulletins
Route::get('/esbtp/bulletins/select', [ESBTPBulletinController::class, 'select'])
    ->name('esbtp.bulletins.select')
    ->middleware(['auth', 'permission:admin.access']);

// Route pour télécharger un bulletin au format PDF
Route::get('/esbtp/bulletins/{bulletin}/download', [ESBTPBulletinController::class, 'genererPDF'])->name('esbtp.bulletins.download')->middleware(['auth', 'permission:admin.access']);
Route::get('/esbtp/bulletins/{bulletin}/preview-pdf', [ESBTPBulletinController::class, 'previewPDF'])
    ->name('esbtp.bulletins.preview-pdf')
    ->middleware(['auth', 'permission:admin.access', 'throttle:60,1']);

// Bulk actions sur la liste /esbtp/bulletins (AJAX)
Route::middleware(['auth'])->group(function () {
    Route::patch('/esbtp/bulletins/bulk-publish', [ESBTPBulletinController::class, 'bulkPublish'])
        ->middleware('permission:bulletins.publish.bulk')
        ->name('esbtp.bulletins.bulk-publish');
    Route::post('/esbtp/bulletins/bulk-regenerate', [ESBTPBulletinController::class, 'bulkRegenerate'])
        ->middleware('permission:bulletins.regenerate.bulk')
        ->name('esbtp.bulletins.bulk-regenerate');
    Route::delete('/esbtp/bulletins/bulk-delete', [ESBTPBulletinController::class, 'bulkDelete'])
        ->middleware('permission:bulletins.delete')
        ->name('esbtp.bulletins.bulk-delete');
});

// Routes pour la gestion des secrétaires
Route::prefix('secretaires')->name('secretaires.')->middleware(['auth', 'permission:system.manage'])->group(function () {
    Route::get('/', [ESBTPSecretaireController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPSecretaireController::class, 'create'])->name('create');
    Route::post('/', [ESBTPSecretaireController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ESBTPSecretaireController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ESBTPSecretaireController::class, 'update'])->name('update');
    Route::delete('/{id}', [ESBTPSecretaireController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [ESBTPSecretaireController::class, 'toggleStatus'])->name('toggle-status');
});

// Routes pour la gestion des enseignants
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'permission:admin.access', 'permission:module.enseignants.access', 'throttle:60,1'])->group(function () {
    Route::get('enseignants/duplicates', [ESBTPEnseignantController::class, 'duplicates'])->name('enseignants.duplicates');
    Route::post('enseignants/quick-create', [ESBTPEnseignantController::class, 'quickStore'])->name('enseignants.quick-create');
    Route::get('enseignants/bulk-availability', [ESBTPEnseignantController::class, 'bulkAvailability'])->name('enseignants.bulk-availability');
    Route::get('enseignants/{enseignant}/availability-section', [ESBTPEnseignantController::class, 'availabilitySection'])->name('enseignants.availability-section');
    Route::get('enseignants/{enseignant}/availability-data', [ESBTPEnseignantController::class, 'availabilityData'])->name('enseignants.availability-data');
    Route::resource('enseignants', ESBTPEnseignantController::class);
    Route::get('enseignants/{teacher}/matieres', [ESBTPEnseignantController::class, 'matieres'])->name('enseignants.matieres');
    Route::post('enseignants/{teacher}/assign-matieres', [ESBTPEnseignantController::class, 'assignMatieres'])->name('enseignants.assign-matieres');
    Route::post('enseignants/{teacher}/toggle-status', [ESBTPEnseignantController::class, 'toggleStatus'])->name('enseignants.toggleStatus');
    Route::post('enseignants/{enseignant}/update-availability', [ESBTPEnseignantController::class, 'updateAvailability'])->name('enseignants.update-availability');
    Route::post('enseignants/{enseignant}/reset-password', [ESBTPEnseignantController::class, 'resetPassword'])->middleware('throttle:5,1')->name('enseignants.reset-password');
    Route::resource('specialties', ESBTPSpecialtyController::class);
    Route::put('specialties/{id}/restore', [ESBTPSpecialtyController::class, 'restore'])->name('specialties.restore');
    Route::resource('continuing-education', ESBTPContinuingEducationController::class);
    Route::put('continuing-education/{id}/restore', [ESBTPContinuingEducationController::class, 'restore'])->name('continuing-education.restore');

});

// Routes pour l'espace enseignant et coordinateur
Route::middleware(['auth', 'role:enseignant|coordinateur'])->group(function () {
    // Notes routes already defined in main group (line 910-924) with role:superAdmin|secretaire|coordinateur|enseignant|teacher

    // Gestion des présences
    Route::prefix('attendance')->name('teacher.attendance.')->group(function () {
        Route::get('/', [ESBTPTeacherAttendanceController::class, 'index'])->name('index');
        Route::post('/sign', [ESBTPTeacherAttendanceController::class, 'sign'])->name('sign');
        Route::get('/history', [ESBTPTeacherAttendanceController::class, 'history'])->name('history');
    });

    // Emploi du temps
    Route::prefix('emploi-temps')->name('emploi-temps.')->group(function () {
        Route::get('/', [ESBTPEmploiTempsController::class, 'index'])->name('index');
        Route::get('/{emploi_temp}', [ESBTPEmploiTempsController::class, 'show'])->name('show');
    });
});

// Groupe de routes pour la comptabilité
// Legacy comptabilité redirects (modules supprimés — voir docs/COMPTABILITE_CLEANUP_PLAN.md)
Route::permanentRedirect('/esbtp/comptabilite', '/esbtp/comptabilite/dashboard');
Route::permanentRedirect('/esbtp/comptabilite/paiements', '/esbtp/paiements');
Route::permanentRedirect('/esbtp/comptabilite/paiements/create', '/esbtp/paiements/create');
Route::permanentRedirect('/esbtp/comptabilite/rapports', '/esbtp/comptabilite/dashboard');
Route::get('/esbtp/comptabilite/paiements/{id}', fn ($id) => redirect()->route('esbtp.paiements.show', $id));
Route::get('/esbtp/comptabilite/paiements/{id}/edit', fn ($id) => redirect()->route('esbtp.paiements.edit', $id));
Route::get('/esbtp/comptabilite/paiements/{id}/recu', fn ($id) => redirect()->route('esbtp.paiements.recu', $id));

Route::middleware(['auth', 'comptabilite.access'])->prefix('esbtp/comptabilite')->name('esbtp.comptabilite.')->group(function () {
    // KPIs temps réel
    Route::get('/kpis-temps-reel', [ESBTPComptabiliteController::class, 'kpisTempsReel'])->name('kpis-temps-reel');

    // Analytics Prédictifs (Phase 3 + Phase 4)
    Route::get('/analytics', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'index'])
        ->name('analytics.index');
    Route::get('/analytics/refresh', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'refresh'])
        ->name('analytics.refresh')
        ->middleware('throttle:30,1');
    Route::post('/analytics/run-now', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'runNow'])
        ->name('analytics.run-now')
        ->middleware(['permission:comptabilite.analytics.run_now', 'throttle:10,1']);
    Route::get('/analytics/settings', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'settings'])
        ->name('analytics.settings')
        ->middleware('permission:comptabilite.analytics.configure');
    Route::post('/analytics/settings', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'updateSettings'])
        ->name('analytics.settings.update')
        ->middleware(['permission:comptabilite.analytics.configure', 'throttle:20,1']);
    // Analytics — exports (PDF preview/download + Excel multi-sheets)
    Route::get('/analytics/preview-pdf', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'previewPdf'])
        ->name('analytics.preview-pdf')
        ->middleware(['permission:comptabilite.analytics.view', 'throttle:30,1']);
    Route::get('/analytics/export-pdf', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'exportPdf'])
        ->name('analytics.export-pdf')
        ->middleware(['permission:comptabilite.analytics.view', 'throttle:10,1']);
    Route::get('/analytics/export-excel', [\App\Http\Controllers\ESBTPAnalyticsController::class, 'exportExcel'])
        ->name('analytics.export-excel')
        ->middleware(['permission:comptabilite.analytics.view', 'throttle:10,1']);

    // Recouvrement quotidien (Sprint 11 — RecouvrementOptimizer + WhatsApp deeplinks)
    Route::get('/recouvrement', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'index'])
        ->name('recouvrement.index');
    Route::post('/recouvrement/log-intent', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'logIntent'])
        ->name('recouvrement.log-intent')
        ->middleware('throttle:120,1');
    Route::post('/recouvrement/confirm-sent', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'confirmSent'])
        ->name('recouvrement.confirm-sent')
        ->middleware('throttle:120,1');
    Route::post('/recouvrement/mark-done', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'markDone'])
        ->name('recouvrement.mark-done')
        ->middleware('throttle:120,1');
    // Recouvrement — exports (PDF preview/download + Excel + email)
    Route::get('/recouvrement/preview-pdf', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'previewPdf'])
        ->name('recouvrement.preview-pdf')
        ->middleware(['permission:comptabilite.recouvrement.access', 'throttle:30,1']);
    Route::get('/recouvrement/export-pdf', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'exportPdf'])
        ->name('recouvrement.export-pdf')
        ->middleware(['permission:comptabilite.recouvrement.access', 'throttle:10,1']);
    Route::get('/recouvrement/export-excel', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'exportExcel'])
        ->name('recouvrement.export-excel')
        ->middleware(['permission:comptabilite.recouvrement.access', 'throttle:10,1']);
    Route::post('/recouvrement/email-pdf', [\App\Http\Controllers\ESBTPRecouvrementController::class, 'emailPdf'])
        ->name('recouvrement.email-pdf')
        ->middleware(['permission:comptabilite.recouvrement.access', 'throttle:5,1']);

    // Gestion des frais de scolarité
    Route::get('/frais-scolarite', [ESBTPComptabiliteReportController::class, 'fraisScolarite'])->name('frais-scolarite');
    Route::get('/frais-scolarite/create', [ESBTPComptabiliteFraisController::class, 'createFraisScolarite'])->name('frais-scolarite.create');
    Route::post('/frais-scolarite', [ESBTPComptabiliteFraisController::class, 'storeFraisScolarite'])->name('frais-scolarite.store');
    Route::get('/frais-scolarite/{id}', [ESBTPComptabiliteFraisController::class, 'showFraisScolarite'])->name('frais-scolarite.show');
    Route::get('/frais-scolarite/{id}/edit', [ESBTPComptabiliteFraisController::class, 'editFraisScolarite'])->name('frais-scolarite.edit');
    Route::put('/frais-scolarite/{id}', [ESBTPComptabiliteFraisController::class, 'updateFraisScolarite'])->name('frais-scolarite.update');
    Route::delete('/frais-scolarite/{id}', [ESBTPComptabiliteFraisController::class, 'destroyFraisScolarite'])->name('frais-scolarite.destroy');

    // Gestion des bourses et aides
    Route::get('/bourses', [ESBTPComptabiliteFraisController::class, 'bourses'])->name('bourses');
    Route::get('/bourses/create', [ESBTPComptabiliteFraisController::class, 'createBourse'])->name('bourses.create');
    Route::post('/bourses', [ESBTPComptabiliteFraisController::class, 'storeBourse'])->name('bourses.store');
    Route::get('/bourses/{id}', [ESBTPComptabiliteFraisController::class, 'showBourse'])->name('bourses.show');
    Route::get('/bourses/{id}/edit', [ESBTPComptabiliteFraisController::class, 'editBourse'])->name('bourses.edit');
    Route::put('/bourses/{id}', [ESBTPComptabiliteFraisController::class, 'updateBourse'])->name('bourses.update');
    Route::delete('/bourses/{id}', [ESBTPComptabiliteFraisController::class, 'destroyBourse'])->name('bourses.destroy');

    // Configuration des échéanciers de paiement
    Route::get('/config/echeanciers', [ESBTPEcheancierController::class, 'index'])
        ->name('echeanciers.index')
        ->middleware(['permission:comptabilite.frais.configure']);
    Route::post('/config/echeanciers', [ESBTPEcheancierController::class, 'upsert'])
        ->name('echeanciers.upsert')
        ->middleware(['permission:comptabilite.frais.configure']);
    Route::post('/config/echeanciers/copy', [ESBTPEcheancierController::class, 'copy'])
        ->name('echeanciers.copy')
        ->middleware(['permission:comptabilite.frais.configure']);
    Route::post('/config/echeanciers/bulk-status', [ESBTPEcheancierController::class, 'bulkStatus'])
        ->name('echeanciers.bulk-status')
        ->middleware(['permission:comptabilite.frais.configure']);
    Route::get('/config/echeanciers/simulate', [ESBTPEcheancierController::class, 'simulate'])
        ->name('echeanciers.simulate')
        ->middleware(['permission:comptabilite.frais.configure']);

    // Rapports — génération + planification + templates (analytics-predictifs supprimés Sprint 9, remplacés par /esbtp/comptabilite/analytics)
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::post('/generer', [ESBTPComptabiliteAnalyticsController::class, 'genererRapportPersonnalise'])->name('generer')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:10,1']);

        Route::post('/schedule', [ESBTPComptabiliteAnalyticsController::class, 'programmerRapport'])->name('schedule')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:5,1']);

        Route::get('/scheduled', [ESBTPComptabiliteAnalyticsController::class, 'listeRapportsProgrammes'])->name('scheduled')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/templates', [ESBTPComptabiliteAnalyticsController::class, 'modelesRapports'])->name('templates')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/templates', [ESBTPComptabiliteAnalyticsController::class, 'sauvegarderModele'])->name('templates.save')
            ->middleware(['permission:comptabilite.config.manage']);
    });

    // Gestion des catégories de paiement
    Route::prefix('categories-paiement')->name('categories-paiement.')->group(function () {
        Route::get('/', [ESBTPCategoriePaiementController::class, 'index'])->name('index');
        Route::get('/create', [ESBTPCategoriePaiementController::class, 'create'])->name('create');
        Route::post('/', [ESBTPCategoriePaiementController::class, 'store'])->name('store');
        Route::get('/{categorie}', [ESBTPCategoriePaiementController::class, 'show'])->name('show');
        Route::get('/{categorie}/edit', [ESBTPCategoriePaiementController::class, 'edit'])->name('edit');
        Route::put('/{categorie}', [ESBTPCategoriePaiementController::class, 'update'])->name('update');
        Route::delete('/{categorie}', [ESBTPCategoriePaiementController::class, 'destroy'])->name('destroy');
        Route::patch('/{categorie}/toggle-status', [ESBTPCategoriePaiementController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Gestion des relances automatisées
    Route::prefix('relances')->name('relances.')->group(function () {
        Route::get('/', [ESBTPComptabiliteRelanceController::class, 'gestionRelances'])->name('index');
        Route::get('/config', [ESBTPComptabiliteRelanceController::class, 'configurationRelances'])->name('config');
        Route::get('/export-excel', [ESBTPComptabiliteRelanceController::class, 'exportRelancesExcel'])->name('export-excel')
            ->middleware(['permission:comptabilite.reports.export']);
        Route::get('/export-pdf', [ESBTPComptabiliteRelanceController::class, 'exportRelancesPdf'])->name('export-pdf')
            ->middleware(['permission:comptabilite.reports.export']);
        Route::get('/preview-pdf', [ESBTPComptabiliteRelanceController::class, 'previewRelancesPdf'])->name('preview-pdf')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:30,1']);
        Route::get('/{id}', [ESBTPComptabiliteRelanceController::class, 'showRelance'])->name('show')->where('id', '[0-9]+');

        // Actions sur les relances
        Route::post('/planifier', [ESBTPComptabiliteRelanceController::class, 'planifierRelances'])->name('planifier')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:10,1']);
        Route::post('/{id}/renvoyer', [ESBTPComptabiliteRelanceController::class, 'renvoyerRelance'])->name('renvoyer')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:30,1']);
        Route::post('/executer', [ESBTPComptabiliteRelanceController::class, 'executerRelances'])->name('executer')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:5,1']);

        // Configuration (superAdmin/comptable uniquement)
        Route::post('/config/templates', [ESBTPComptabiliteRelanceController::class, 'sauvegarderTemplates'])->name('config.templates')
            ->middleware(['permission:comptabilite.relances.send']);
        Route::post('/config/parametres', [ESBTPComptabiliteRelanceController::class, 'sauvegarderParametres'])->name('config.parametres')
            ->middleware(['permission:comptabilite.relances.send']);
        Route::post('/config/preview', [ESBTPComptabiliteRelanceController::class, 'previewTemplate'])->name('config.preview');

        // Aperçus et statistiques
        Route::get('/apercu-etudiants', [ESBTPComptabiliteRelanceController::class, 'apercuRelances'])->name('apercu');

        // NOUVELLES ROUTES ANALYTICS AVANCÉES - Tâche #4
        Route::get('/analytics', [ESBTPComptabiliteRelanceController::class, 'analyticsRelances'])->name('analytics')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/planifier-avancees', [ESBTPComptabiliteRelanceController::class, 'planifierRelancesAvancees'])->name('planifier.avancees')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:5,1']);

        Route::get('/export', [ESBTPComptabiliteRelanceController::class, 'exportAnalyticsRelances'])->name('export')
            ->middleware(['permission:comptabilite.reports.export']);

        Route::post('/preview-segmentation', [ESBTPComptabiliteRelanceController::class, 'previewSegmentation'])->name('preview.segmentation')
            ->middleware(['permission:comptabilite.relances.send']);

        // Fiche relance par étudiant
        Route::get('/etudiant/{inscription}', [ESBTPComptabiliteRelanceController::class, 'relanceEtudiant'])->name('etudiant');
    });

    // Dashboard comptabilité
    Route::get('/dashboard', [ESBTPComptabiliteController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/data', [ESBTPComptabiliteController::class, 'dashboardData'])->name('dashboard.data');
});

// Routes pour le système d'émargement
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth'])->group(function () {
    // Routes pour l'administration des codes (accès restreint aux administrateurs et secrétaires)
    Route::middleware(['permission:attendances.generate_codes', 'paywall'])->group(function () {
        Route::get('/attendance-codes', [ESBTPAttendanceCodeController::class, 'index'])
            ->name('attendance-codes.index');
        Route::post('/attendance-codes/generate', [ESBTPAttendanceCodeController::class, 'generate'])
            ->name('attendance-codes.generate');
        Route::post('/attendance-codes/{code}/invalidate', [ESBTPAttendanceCodeController::class, 'invalidate'])
            ->name('attendance-codes.invalidate');
        Route::post('/attendance-codes/cleanup-duplicates', [ESBTPAttendanceCodeController::class, 'cleanupDuplicates'])
            ->name('attendance-codes.cleanup-duplicates');
        Route::get('/attendance-codes/report', [ESBTPAttendanceCodeController::class, 'report'])
            ->name('attendance-codes.report');
    });

    // Routes spécifiques aux administrateurs
    Route::middleware(['permission:system.manage'])->group(function () {
        Route::get('/attendance-codes/settings', [ESBTPAttendanceCodeController::class, 'settings'])
            ->name('attendance-codes.settings');
        Route::post('/attendance-codes/settings', [ESBTPAttendanceCodeController::class, 'updateSettings'])
            ->name('attendance-codes.settings.update');
    });

    // Routes pour l'émargement des enseignants
    Route::prefix('teacher-attendance')->name('teacher-attendance.')->middleware(['auth', 'role:enseignant'])->group(function () {
        Route::get('/', [TeacherAttendanceController::class, 'index'])->name('index');
        Route::get('/history', [TeacherAttendanceController::class, 'history'])->name('history');
        Route::post('/sign', [TeacherAttendanceController::class, 'sign'])->name('sign');
    });

    // Route rapport accessible aux enseignants et superadmins
    Route::get('teacher-attendance/report', [TeacherAttendanceController::class, 'report'])
        ->name('teacher-attendance.report')
        ->middleware(['auth', 'permission:attendances.view']);

    Route::get('teacher-attendance/teacher/{teacher}', [TeacherAttendanceController::class, 'teacherReport'])
        ->name('teacher-attendance.teacher-report')
        ->middleware(['auth', 'permission:attendances.view']);

    // Routes AJAX pour update statut et refresh ligne (coordinateur/admin)
    Route::post('teacher-attendance/seance/{seance}/update-status', [ESBTPTeacherAttendanceController::class, 'updateStatus'])
        ->name('esbtp.teacher-attendance.update-status')
        ->middleware(['auth', 'permission:attendances.edit']);
    Route::get('teacher-attendance/seance/{seance}/refresh-ligne', [ESBTPTeacherAttendanceController::class, 'refreshSeanceLigne'])
        ->name('esbtp.teacher-attendance.refresh-ligne')
        ->middleware(['auth', 'permission:attendances.edit']);
    Route::post('teacher-attendance/bulk-update-status', [ESBTPTeacherAttendanceController::class, 'bulkUpdateStatus'])
        ->name('esbtp.teacher-attendance.bulk-update-status')
        ->middleware(['auth', 'permission:attendances.edit']);

    // ... autres routes ...
    Route::resource('payment-categories', \App\Http\Controllers\ESBTP\PaymentCategoryController::class);
});

Route::prefix('esbtp')->middleware(['auth', 'validate.device', 'attendance.rate_limit'])->group(function () {
    Route::get('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'index'])->name('esbtp.attendance.mark.index');
    Route::post('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'store'])->name('esbtp.attendance.mark');
    Route::get('/teacher/attendance/history', [TeacherAttendanceHistoryController::class, 'index'])->name('esbtp.teacher.attendance.history');
    // ... existing routes ...
});

// Forgotten Codes Routes
Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'permission:attendances.generate_codes'])->group(function () {
    Route::get('/forgotten-codes', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'index'])
        ->name('forgotten-codes');
    Route::post('/generate-manual-code', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'generateManualCode'])
        ->name('generate-manual-code');
    Route::post('/mark-manual', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'markManualAttendance'])
        ->name('mark-manual');
});

// Manual Attendance Routes
Route::prefix('esbtp/admin/attendance/manual')->name('esbtp.admin.attendance.manual.')->middleware(['auth', 'permission:admin.access'])->group(function () {
    Route::get('/', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'index'])
        ->name('index');
    Route::post('/store', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'store'])
        ->name('store');
    Route::post('/bulk', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'bulkStore'])
        ->name('bulk');
});

// Routes pour les paramètres système ESBTP (manage_system)
Route::middleware(['auth', 'permission:system.manage'])->group(function () {
    // ESBTP Settings Routes
    Route::get('/esbtp/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'index'])->name('esbtp.settings.index');
    Route::put('/esbtp/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'update'])->name('esbtp.settings.update');
    Route::post('/esbtp/settings', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'store'])->name('esbtp.settings.store');
    Route::delete('/esbtp/settings/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'destroy'])->name('esbtp.settings.destroy');

    // ESBTP Settings Backup Routes
    Route::get('/esbtp/settings/backups', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'backups'])->name('esbtp.settings.backups');
    Route::post('/esbtp/settings/backup', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'createBackup'])->name('esbtp.settings.backup');
    Route::post('/esbtp/settings/restore/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'restoreBackup'])->name('esbtp.settings.restore');
    Route::get('/esbtp/settings/backup/{id}/compare', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'compareBackup'])->name('esbtp.settings.backup.compare');
    Route::post('/esbtp/settings/backup/{id}/archive', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'archiveBackup'])->name('esbtp.settings.backup.archive');
    Route::delete('/esbtp/settings/backup/{id}', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'destroy'])->name('esbtp.settings.backup.delete');
    Route::post('/esbtp/settings/backups/cleanup', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'cleanupBackups'])->name('esbtp.settings.backups.cleanup');

    // ESBTP Settings Additional Routes
    Route::post('/esbtp/settings/{id}/reset', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'resetToDefault'])->name('esbtp.settings.reset');
    Route::get('/esbtp/settings/export', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'export'])->name('esbtp.settings.export');
    Route::post('/esbtp/settings/import', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'import'])->name('esbtp.settings.import');
    Route::get('/esbtp/settings/status', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'checkStatus'])->name('esbtp.settings.status');
    Route::post('/esbtp/settings/validate', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'checkStatus'])->name('esbtp.settings.validate');
    Route::post('/esbtp/settings/test-reminders', [App\Http\Controllers\ESBTP\ESBTPSettingsController::class, 'testReminders'])->name('esbtp.settings.test-reminders');

    // ESBTP Logs Routes
    Route::get('/esbtp/logs', [ESBTPLogsController::class, 'index'])->name('esbtp.logs.index');
    Route::get('/esbtp/logs/{filename}', [ESBTPLogsController::class, 'show'])->name('esbtp.logs.show');
    Route::get('/esbtp/logs/{filename}/download', [ESBTPLogsController::class, 'download'])->name('esbtp.logs.download');
    Route::post('/esbtp/logs/{filename}/clear', [ESBTPLogsController::class, 'clear'])->name('esbtp.logs.clear');
    Route::delete('/esbtp/logs/{filename}', [ESBTPLogsController::class, 'destroy'])->name('esbtp.logs.destroy');
});

// Routes pour la gestion des étudiants
Route::middleware(['auth', 'permission:admin.access', 'paywall'])->group(function () {
    // AJAX pour charger toutes les inscriptions d'un étudiant
    Route::get('esbtp/etudiants/{etudiant}/all-inscriptions', [ESBTPStudentController::class, 'getAllInscriptions'])
        ->name('esbtp.etudiants.all-inscriptions')
        ->middleware('permission:students.view');

    // Export étudiants
    Route::get('esbtp/etudiants-export/excel', [ESBTPStudentController::class, 'exportExcel'])
        ->name('esbtp.etudiants.export.excel')
        ->middleware('permission:students.view');
    Route::get('esbtp/etudiants-export/pdf', [ESBTPStudentController::class, 'exportPdf'])
        ->name('esbtp.etudiants.export.pdf')
        ->middleware('permission:students.view');

    // Resource CRUD
    Route::resource('esbtp/etudiants', ESBTPStudentController::class, ['as' => 'esbtp'])
        ->parameters(['etudiants' => 'etudiant'])
        ->middleware('permission:students.view');
    Route::post('esbtp/etudiants/{id}/restore', [ESBTPStudentController::class, 'restore'])
        ->name('esbtp.etudiants.restore')
        ->middleware('permission:students.edit');
    Route::post('esbtp/etudiants/{etudiant}/update-photo', [ESBTPEtudiantController::class, 'updatePhoto'])
        ->name('esbtp.etudiants.update-photo')
        ->middleware('permission:students.edit');
    Route::post('esbtp/etudiants/{etudiant}/documents', [ESBTPEtudiantController::class, 'storeDocument'])
        ->name('esbtp.etudiants.documents.store')
        ->middleware('permission:students.edit');
    Route::get('esbtp/etudiants/{etudiant}/documents/{document}/download', [ESBTPEtudiantController::class, 'downloadDocument'])
        ->name('esbtp.etudiants.documents.download')
        ->middleware('permission:students.view');
    Route::delete('esbtp/etudiants/{etudiant}/documents/{document}', [ESBTPEtudiantController::class, 'destroyDocument'])
        ->name('esbtp.etudiants.documents.destroy')
        ->middleware('permission:students.edit');

    // ===== Accessibilité (handicap, aménagements pédagogiques) =====
    Route::get('esbtp/accessibility', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'index'])
        ->name('esbtp.accessibility.index')
        ->middleware('permission:students.accessibility.view');

    Route::get('esbtp/accessibility/preview-pdf', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'previewPdf'])
        ->name('esbtp.accessibility.preview-pdf')
        ->middleware(['permission:students.accessibility.export', 'throttle:60,1']);

    Route::get('esbtp/accessibility/export-pdf', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'exportPdf'])
        ->name('esbtp.accessibility.export-pdf')
        ->middleware(['permission:students.accessibility.export', 'throttle:10,1']);

    Route::get('esbtp/accessibility/export-excel', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'exportExcel'])
        ->name('esbtp.accessibility.export-excel')
        ->middleware(['permission:students.accessibility.export', 'throttle:10,1']);

    Route::get('esbtp/etudiants/{etudiant}/accessibility', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'show'])
        ->name('esbtp.etudiants.accessibility.show')
        ->middleware('permission:students.accessibility.view');

    Route::post('esbtp/etudiants/{etudiant}/accessibility', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'store'])
        ->name('esbtp.etudiants.accessibility.store')
        ->middleware('permission:students.accessibility.edit');

    Route::delete('esbtp/etudiants/{etudiant}/accessibility', [\App\Http\Controllers\ESBTPStudentAccessibilityController::class, 'destroy'])
        ->name('esbtp.etudiants.accessibility.destroy')
        ->middleware('permission:students.accessibility.edit');
});

// ... existing code ...

// --- SUPPRESSION dans le groupe esbtp ---
// (Supprimer la section suivante du groupe)
// Route::middleware(['auth', 'role:superAdmin|secretaire'])->group(function () {
//     Route::resource('esbtp/annees-universitaires', ESBTPAnneeUniversitaireController::class)->names([
//         'index' => 'esbtp.annees-universitaires.index',
//         'create' => 'esbtp.annees-universitaires.create',
//         'store' => 'esbtp.annees-universitaires.store',
//         'show' => 'esbtp.annees-universitaires.show',
//         'edit' => 'esbtp.annees-universitaires.edit',
//         'update' => 'esbtp.annees-universitaires.update',
//         'destroy' => 'esbtp.annees-universitaires.destroy',
//     ]);
// });
// ... existing code ...
// ... existing code ...

// Route de diagnostic temporaire — local environment only (audit 2026-05-21).
// Avant: accessible en prod, exposait la matrice complète rôles/permissions de l'user
// connecté → leak d'info sensible.
if (app()->environment('local')) {
    Route::get('/debug-permissions', function () {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'Aucun utilisateur connecté']);
        }

        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->getRoleNames()->toArray(),
            'has_superAdmin_role' => $user->hasRole('superAdmin'),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];

        // Test des permissions pour les matières
        $matiere = \App\Models\ESBTPMatiere::first();
        if ($matiere) {
            $data['matiere_permissions'] = [
                'matiere_id' => $matiere->id,
                'matiere_nom' => $matiere->nom,
                'can_view' => $user->can('view', $matiere),
                'can_update' => $user->can('update', $matiere),
                'can_delete' => $user->can('delete', $matiere),
            ];
        }

        return response()->json($data, 200, [], JSON_PRETTY_PRINT);
    })->middleware('auth');
}

// Routes spéciales pour le workflow des bulletins — PROTÉGÉES.
// Lot 4 fix: ouvert à 'bulletins.configure' OU 'admin.access' (rule customizable-roles :
// le secretaire/comptable/rôle custom peut gérer les bulletins via bulletins.configure
// sans avoir admin.access global). Les controllers font leurs propres checks fins.
Route::middleware(['auth', 'permission:admin.access|bulletins.configure'])->group(function () {
    Route::get('/esbtp-special/bulletins-pdf', [ESBTPBulletinController::class, 'genererPDFParParamsUnified'])->name('esbtp.bulletins.pdf-params');
    Route::get('/esbtp-special/bulletins-pdf/preview', [ESBTPBulletinController::class, 'previewPDFParParamsUnified'])
        ->name('esbtp.bulletins.pdf-params-preview')
        ->middleware('throttle:60,1');
    Route::get('/esbtp-special/bulletins-check', [ESBTPBulletinController::class, 'checkBulletinPrerequisites'])->name('esbtp.bulletins.check-prerequisites');
    Route::get('/esbtp-special/bulletins-check-consistency', [ESBTPBulletinController::class, 'checkBulletinConsistency'])->name('esbtp.bulletins.check-consistency');
    Route::post('/esbtp-special/bulletins-regenerate', [ESBTPBulletinController::class, 'regenerateOfficialBulletin'])
        ->middleware('permission:bulletins.edit')
        ->name('esbtp.bulletins.regenerate');
    Route::get('/esbtp/bulletins/preview', [ESBTPBulletinController::class, 'previewBulletin'])->name('esbtp.bulletins.preview');
    Route::post('/esbtp/bulletins/generer-classe', [ESBTPBulletinController::class, 'genererClasseBulletins'])->name('esbtp.bulletins.generer-classe');

    // Routes spéciales moyennes
    Route::get('/esbtp-special/bulletins/moyennes-preview', [ESBTPResultatController::class, 'previewMoyennes'])->name('esbtp.bulletins.moyennes-preview');
    Route::post('/esbtp-special/bulletins/moyennes-update', [ESBTPResultatController::class, 'updateMoyennes'])->name('esbtp.bulletins.moyennes-update');
    Route::delete('/esbtp-special/bulletins/moyennes-delete', [ESBTPResultatController::class, 'deleteMoyenne'])->name('esbtp.bulletins.moyennes-delete');

    // Configuration matières, professeurs, absences bulletins
    Route::get('/esbtp-special/bulletins/config-matieres', [ESBTPBulletinConfigController::class, 'configMatieresTypeFormation'])->name('esbtp.bulletins.config-matieres');
    Route::post('/esbtp-special/bulletins/save-config-matieres', [ESBTPBulletinConfigController::class, 'saveConfigMatieresTypeFormation'])->name('esbtp.bulletins.save-config-matieres');
    // Sous-lot δ — copy config-matieres entre semestres
    Route::post('/esbtp-special/bulletins/config-matieres/copy', [ESBTPBulletinConfigController::class, 'copyConfigMatieres'])->name('esbtp.bulletins.config-matieres.copy');
    // Sous-lot β AJAX — switch S1/S2/Annuel sans reload
    Route::get('/esbtp-special/bulletins/config-matieres/data', [ESBTPBulletinConfigController::class, 'configMatieresData'])->name('esbtp.bulletins.config-matieres.data');
    Route::get('/esbtp-special/bulletins/edit-professeurs', [ESBTPBulletinConfigController::class, 'editProfesseurs'])->name('esbtp.bulletins.edit-professeurs');
    Route::post('/esbtp-special/bulletins/copy-professeurs-from-other-semestre', [ESBTPBulletinConfigController::class, 'copyProfesseursFromOtherSemestre'])
        ->middleware('throttle:30,1')
        ->name('esbtp.bulletins.copy-professeurs-from-other-semestre');
    Route::post('/esbtp-special/bulletins/save-professeurs', [ESBTPBulletinConfigController::class, 'saveProfesseurs'])->name('esbtp.bulletins.save-professeurs');
    Route::get('/esbtp-special/bulletins/edit-absences', [ESBTPBulletinConfigController::class, 'editAbsences'])->name('esbtp.bulletins.edit-absences');
    Route::post('/esbtp-special/bulletins/save-absences', [ESBTPBulletinConfigController::class, 'saveAbsences'])->name('esbtp.bulletins.save-absences');
    Route::get('/esbtp-special/bulletins/generate', [ESBTPBulletinController::class, 'generate'])->name('esbtp.bulletins.generate-special');

    // Bulletins configurables
    Route::get('/bulletins/configurable', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])->name('esbtp.bulletins.configurable');
    Route::post('/bulletins/configurable', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])->name('esbtp.bulletins.configurable.generate');
});

// Routes classes — PROTÉGÉES
Route::middleware(['auth'])->group(function () {
    Route::post('/esbtp/classes/sync-systeme-academique', [ESBTPClasseController::class, 'syncSystemeAcademique'])->name('esbtp.classes.sync-systeme-academique');
    Route::get('/esbtp/classes/{classe}/etudiants', [ESBTPClasseController::class, 'getEtudiants'])->name('esbtp.classes.etudiants');
    Route::get('/esbtp/classes/{classe}/semestres-lmd', function (\App\Models\ESBTPClasse $classe) {
        return response()->json(['semestres' => $classe->getSemestresLMD()]);
    })->name('esbtp.classes.semestres-lmd');
    Route::middleware('permission:classes.view')->group(function () {
        Route::get('/esbtp/classes/{classe}/liste-appel', [ESBTPClasseController::class, 'listeAppel'])->name('esbtp.classes.liste-appel');
        Route::get('/esbtp/classes/{classe}/liste-appel/pdf', [ESBTPClasseController::class, 'listeAppelPDF'])->name('esbtp.classes.liste-appel.pdf');
        Route::get('/esbtp/classes/{classe}/liste-complete', [ESBTPClasseController::class, 'listeComplete'])->name('esbtp.classes.liste-complete');
        Route::get('/esbtp/classes/{classe}/liste-complete/pdf', [ESBTPClasseController::class, 'listeCompletePDF'])->name('esbtp.classes.liste-complete.pdf');
        Route::get('/esbtp/classes/{classe}/liste-complete/excel', [ESBTPClasseController::class, 'listeCompleteExcel'])->name('esbtp.classes.liste-complete.excel');
    });
    Route::get('/esbtp/classes-export/excel', [ESBTPClasseController::class, 'exportExcel'])->name('esbtp.classes.export.excel');
    Route::get('/esbtp/classes-export/csv', [ESBTPClasseController::class, 'exportCsv'])->name('esbtp.classes.export.csv');
    Route::get('/esbtp/classes-export/pdf', [ESBTPClasseController::class, 'exportPdf'])->name('esbtp.classes.export.pdf');
    Route::get('/esbtp/classes/{classe}/refresh-ligne', [ESBTPClasseController::class, 'refreshLigne'])->name('esbtp.classes.refresh-ligne');
    Route::post('/esbtp/classes/{classe}/update-matieres', [ESBTPClasseController::class, 'updateMatieres'])->name('esbtp.classes.update-matieres');
    Route::get('/esbtp/classes/{classe}/search-available-students', [ESBTPClasseController::class, 'searchAvailableStudents'])->name('esbtp.classes.search-available-students');
    Route::post('/esbtp/classes/{classe}/add-students', [ESBTPClasseController::class, 'addStudents'])->name('esbtp.classes.add-students');
    Route::post('/esbtp/classes/{classe}/remove-students', [ESBTPClasseController::class, 'removeStudents'])->name('esbtp.classes.remove-students');
    Route::post('/esbtp/classes/{classe}/check-student-data', [ESBTPClasseController::class, 'checkStudentData'])->name('esbtp.classes.check-student-data');
    Route::get('/esbtp/classes/{classe}/student-table-html', [ESBTPClasseController::class, 'studentTableHtml'])->name('esbtp.classes.student-table-html');
});

// ... existing code ...

// Routes pour le système de bulletin configurable
Route::middleware(['auth'])->group(function () {
    // Test des paramètres de bulletin — local environment only (audit 2026-05-21)
    if (app()->environment('local')) {
        Route::get('/test-bulletin-parameters', [ESBTPBulletinController::class, 'testBulletinParameters'])
            ->name('test.bulletin.parameters');
    }

    // Génération de bulletin configurable
    Route::post('/bulletin/configurable/generate', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])
        ->name('bulletin.configurable.generate');

    // Prévisualisation de bulletin configurable
    Route::get('/bulletin/configurable/preview', [ESBTPBulletinController::class, 'previewConfigurableBulletin'])
        ->name('bulletin.configurable.preview');

    // Interface de test pour bulletin configurable — local environment only (audit 2026-05-21)
    if (app()->environment('local')) {
        Route::get('/bulletin/configurable/test', function () {
            return view('esbtp.bulletins.test-configurable');
        })->name('bulletin.configurable.test');
    }
});

// ... existing code ...

// Routes ESBTP Audit et Sécurité (Task #10)
Route::middleware(['auth', 'throttle:audit'])->prefix('esbtp/audit')->name('esbtp.audit.')->group(function () {
    // Page principale d'audit
    Route::get('/', [ESBTPAuditController::class, 'index'])->name('index');

    // Données d'audit via AJAX (avec rate limiting strict)
    Route::get('/data', [ESBTPAuditController::class, 'getAuditData'])
        ->middleware('throttle:30,1')
        ->name('data');

    // Audits spécifiques à la comptabilité (avant /{id} pour éviter capture wildcard)
    Route::get('/comptabilite', [ESBTPAuditController::class, 'comptabiliteAudits'])
        ->middleware('permission:comptabilite.audit.view')
        ->name('comptabilite');

    // Surveillance de l'activité des utilisateurs
    Route::get('/user-activity', [ESBTPAuditController::class, 'userActivity'])
        ->middleware('permission:security.users.monitor')
        ->name('user-activity');

    // Export des audits (avec rate limiting très strict)
    Route::middleware(['throttle:5,1', 'permission:security.audit.export'])->group(function () {
        Route::get('/export/excel', [ESBTPAuditController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ESBTPAuditController::class, 'exportPdf'])->name('export.pdf');
    });

    // Liens entités liées d'un audit (AJAX, pour modal "Aperçu rapide")
    Route::get('/{id}/related-links', [ESBTPAuditController::class, 'relatedLinks'])
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1')
        ->name('related-links');

    // Détails d'un audit spécifique (en dernier pour éviter conflit avec routes nommées ci-dessus)
    Route::get('/{id}', [ESBTPAuditController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('show');
});

// Routes de sécurité avancées (Task #10) - COMMENTED : ESBTPSecurityController NOT IMPLEMENTED YET
/*
Route::middleware(['auth', 'throttle:security'])->prefix('esbtp/security')->name('esbtp.security.')->group(function () {
    // Tableau de bord sécurité (superAdmin uniquement)
    Route::get('/dashboard', [ESBTPSecurityController::class, 'dashboard'])
        ->middleware('permission:admin.system.security')
        ->name('dashboard');

    // Gestion des événements de sécurité
    Route::get('/events', [ESBTPSecurityController::class, 'securityEvents'])
        ->middleware('permission:security.events.view')
        ->name('events');

    // Monitoring des connexions suspectes
    Route::get('/suspicious-logins', [ESBTPSecurityController::class, 'suspiciousLogins'])
        ->middleware('permission:security.users.monitor')
        ->name('suspicious-logins');

    // Gestion des backups sécurisés
    Route::middleware('permission:security.backup.view')->group(function () {
        Route::get('/backups', [ESBTPSecurityController::class, 'backups'])->name('backups');
        Route::post('/backups/create', [ESBTPSecurityController::class, 'createBackup'])
            ->middleware(['permission:security.backup.create', 'throttle:1,60'])
            ->name('backups.create');
        Route::post('/backups/{id}/restore', [ESBTPSecurityController::class, 'restoreBackup'])
            ->middleware(['permission:security.backup.restore', 'throttle:1,300'])
            ->name('backups.restore');
    });
});
*/

// ... existing code ...
// Route supprimée: duplicate de esbtp.comptabilite.paiements.recu (définie ligne 1372)
// ... existing code ...

// Routes pour la gestion des comptables
Route::middleware(['auth', 'permission:system.manage', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::get('/comptables', [\App\Http\Controllers\ESBTPComptableController::class, 'index'])->name('comptables.index');
    Route::get('/comptables/create', [\App\Http\Controllers\ESBTPComptableController::class, 'create'])->name('comptables.create');
    Route::post('/comptables', [\App\Http\Controllers\ESBTPComptableController::class, 'store'])->name('comptables.store');
    Route::get('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'show'])->name('comptables.show');
    Route::put('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'update'])->name('comptables.update');
    Route::post('/comptables/{user}/toggle-status', [\App\Http\Controllers\ESBTPComptableController::class, 'toggleStatus'])->name('comptables.toggle-status');
    Route::delete('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'destroy'])->name('comptables.destroy');
    // Lot 18d — bouton reset-password universel sur fiche comptable
    Route::post('/comptables/{user}/reset-password', [\App\Http\Controllers\ESBTPComptableController::class, 'resetPassword'])->name('comptables.reset-password');

    // Caissier (create/store mutualisés avec ESBTPComptableController, le reste sur ESBTPCaissierController)
    Route::get('/caissiers/create', [\App\Http\Controllers\ESBTPComptableController::class, 'createCaissier'])->name('caissiers.create');
    Route::post('/caissiers', [\App\Http\Controllers\ESBTPComptableController::class, 'storeCaissier'])->name('caissiers.store');

    // Lot 18a — caissiers : show, edit, update, destroy + toggle-status + reset-password
    Route::get('/caissiers/{caissier}', [\App\Http\Controllers\ESBTPCaissierController::class, 'show'])->name('caissiers.show');
    Route::get('/caissiers/{caissier}/edit', [\App\Http\Controllers\ESBTPCaissierController::class, 'edit'])->name('caissiers.edit');
    Route::put('/caissiers/{caissier}', [\App\Http\Controllers\ESBTPCaissierController::class, 'update'])->name('caissiers.update');
    Route::patch('/caissiers/{caissier}', [\App\Http\Controllers\ESBTPCaissierController::class, 'update']);
    Route::delete('/caissiers/{caissier}', [\App\Http\Controllers\ESBTPCaissierController::class, 'destroy'])->name('caissiers.destroy');
    Route::patch('/caissiers/{caissier}/toggle-status', [\App\Http\Controllers\ESBTPCaissierController::class, 'toggleStatus'])->name('caissiers.toggle-status');
    Route::post('/caissiers/{caissier}/reset-password', [\App\Http\Controllers\ESBTPCaissierController::class, 'resetPassword'])->name('caissiers.reset-password');
});

// Page unifiee personnel : entree avec personnel.view, tabs/actions filtres par permission metier.
Route::middleware(['auth', 'permission:personnel.view', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::get('/personnel/unified', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'index'])->name('personnel.unified.index');
    Route::get('/personnel/unified/data', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'getData'])->name('personnel.unified.data');
    Route::get('/personnel/unified/stats', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'getStats'])->name('personnel.unified.stats');
    Route::post('/personnel/unified', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'store'])->name('personnel.unified.store');
    Route::put('/personnel/unified/{type}/{id}', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'update'])->name('personnel.unified.update');
    Route::delete('/personnel/unified/{type}/{id}', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'destroy'])->name('personnel.unified.destroy');
    Route::patch('/personnel/unified/{type}/{id}/toggle-status', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'toggleStatus'])->name('personnel.unified.toggle-status');
});

Route::middleware(['auth', 'permission:personnel.manage', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::get('/custom-roles', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'index'])->name('custom-roles.index');
    Route::get('/custom-roles/create', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'create'])->name('custom-roles.create');
    Route::post('/custom-roles', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'store'])->name('custom-roles.store');
    Route::get('/custom-roles/{role}/edit', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'edit'])->name('custom-roles.edit');
    Route::put('/custom-roles/{role}', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'update'])->name('custom-roles.update');
    Route::delete('/custom-roles/{role}', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'destroy'])->name('custom-roles.destroy');
    Route::get('/custom-roles/{role}/assign-users', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'assignUsersForm'])->name('custom-roles.assign-users.form');
    Route::post('/custom-roles/{role}/assign-users', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'assignUsers'])->name('custom-roles.assign-users');
    Route::delete('/custom-roles/{role}/detach-user/{user}', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'detachUser'])->name('custom-roles.detach-user');
    Route::get('/custom-roles/standard/{role}/edit', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'editStandard'])->name('custom-roles.standard.edit');
    Route::put('/custom-roles/standard/{role}', [\App\Http\Controllers\ESBTPCustomRoleController::class, 'updateStandard'])->name('custom-roles.standard.update');
});

// Routes pour la gestion du personnel avec sliders
Route::middleware(['auth', 'permission:admin.access', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
    // Vue combinée du personnel avec sliders
    Route::get('/personnel', [\App\Http\Controllers\ESBTPPersonnelController::class, 'index'])->name('personnel.index');
    Route::get('/personnel/data', [\App\Http\Controllers\ESBTPPersonnelController::class, 'getData'])->name('personnel.data');
    Route::get('/personnel/stats', [\App\Http\Controllers\ESBTPPersonnelController::class, 'getStats'])->name('personnel.stats');
    Route::post('/personnel', [\App\Http\Controllers\ESBTPPersonnelController::class, 'store'])->name('personnel.store');
    Route::put('/personnel/{personnel}', [\App\Http\Controllers\ESBTPPersonnelController::class, 'update'])->name('personnel.update');
    Route::delete('/personnel/{personnel}', [\App\Http\Controllers\ESBTPPersonnelController::class, 'destroy'])->name('personnel.destroy');
    Route::patch('/personnel/{personnel}/toggle-status', [\App\Http\Controllers\ESBTPPersonnelController::class, 'toggleStatus'])->name('personnel.toggle-status');
    Route::post('/personnel/bulk-action', [\App\Http\Controllers\ESBTPPersonnelController::class, 'bulkAction'])->name('personnel.bulk-action');
    Route::get('/personnel/export', [\App\Http\Controllers\ESBTPPersonnelController::class, 'export'])->name('personnel.export');
    // Routes pour les coordinateurs (maintien de la compatibilité)
    Route::resource('coordinateurs', \App\Http\Controllers\ESBTPCoordinateurController::class);
    Route::patch('coordinateurs/{coordinateur}/toggle-status', [\App\Http\Controllers\ESBTPCoordinateurController::class, 'toggleStatus'])->name('coordinateurs.toggle-status');
    Route::post('coordinateurs/{coordinateur}/reset-password', [\App\Http\Controllers\ESBTPCoordinateurController::class, 'resetPassword'])->name('coordinateurs.reset-password');
});

// Routes pour les coordinateurs et rôles admin avec permissions spécifiques
Route::middleware(['auth', 'permission:admin.access'])->prefix('esbtp')->name('esbtp.')->group(function () {
    // Routes pour les notes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPNoteController::class, 'index'])->name('index')
            ->middleware('permission:notes.view');
        Route::get('/create', [\App\Http\Controllers\ESBTPNoteController::class, 'create'])->name('create')
            ->middleware('permission:notes.create');
        Route::post('/', [\App\Http\Controllers\ESBTPNoteController::class, 'store'])->name('store')
            ->middleware('permission:notes.create');
        Route::get('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'show'])->name('show')
            ->middleware('permission:notes.view');
        Route::get('/{note}/edit', [\App\Http\Controllers\ESBTPNoteController::class, 'edit'])->name('edit')
            ->middleware('permission:notes.edit');
        Route::put('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'update'])->name('update')
            ->middleware('permission:notes.edit');
        Route::delete('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'destroy'])->name('destroy')
            ->middleware('permission:notes.delete');
        // saisie-rapide already defined in enseignant|coordinateur group (line 1347)
        
        // API routes for new notes system — throttle généreux 60/min (lecture seule).
        Route::get('/api/evaluations/by-class-matiere/{classId}/{matiereId}', [\App\Http\Controllers\ESBTPEvaluationController::class, 'byClassMatiere'])
            ->middleware('throttle:60,1')
            ->name('evaluations.by-class-matiere');
        Route::get('/api/classes/{classe}/students', [\App\Http\Controllers\ESBTPClasseController::class, 'students'])
            ->middleware('throttle:60,1')
            ->name('classes.students');
    });

    // Routes pour les annonces - REMOVED (déjà définies ligne 617 dans le groupe esbtp)

    // Routes pour l'emploi du temps (déjà accessible via permissions existantes)
    Route::get('/emploi-temps', [\App\Http\Controllers\ESBTPEmploiTempsController::class, 'index'])->name('emploi-temps.index')
        ->middleware('permission:timetables.view');
    Route::get('/emploi-temps/{emploi_temp}', [\App\Http\Controllers\ESBTPEmploiTempsController::class, 'show'])->name('emploi-temps.show')
        ->middleware('permission:timetables.view');

    // Routes pour les présences (attendances)
    Route::get('/attendances', [\App\Http\Controllers\ESBTPAttendanceController::class, 'index'])->name('attendances.index')
        ->middleware('permission:attendances.view');
    Route::get('/attendances/{attendance}', [\App\Http\Controllers\ESBTPAttendanceController::class, 'show'])->name('attendances.show')
        ->middleware('permission:attendances.view');

    // Routes pour le planning général coordinateur
    Route::get('/planning-general', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'index'])->name('planning-general.index')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/coordinateur', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'coordinateur'])->name('planning-general.coordinateur')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/repartition-matieres', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'repartitionMatieres'])->name('planning-general.repartition-matieres')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/annuel', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'annuel'])->name('planning-general.annuel')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/impact-emargements', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'impactEmargements'])->name('planning-general.impact-emargements')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/emargement', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'emargement'])->name('planning-general.emargement')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::post('/planning-general/emargement/generer-code', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'genererCodeEmargement'])->name('planning-general.generer-code-emargement')
        ->middleware('permission:planning.manage|timetables.view_all');

    // Routes AJAX pour la configuration des volumes horaires
    Route::get('/planning-general/get-matieres-configuration', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'getMatieresPourConfiguration'])
        ->name('planning-general.get-matieres-configuration')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::post('/planning-general/save-volume-configuration', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'saveVolumeConfiguration'])
        ->name('planning-general.save-volume-configuration')
        ->middleware('permission:planning.manage|timetables.view_all');
    Route::get('/planning-general/planifications/{planification}/teachers', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'getTeachersForManagement'])
        ->name('planning-general.get-teachers');
    Route::post('/planning-general/planifications/{planification}/manage-teachers', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'manageTeachers'])
        ->name('planning-general.manage-teachers');
});

// Routes pour les événements académiques
Route::middleware(['auth', 'permission:planning.manage'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::prefix('evenements-academiques')->name('evenements-academiques.')->group(function () {
        Route::get('/', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'store'])->name('store');
        Route::get('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'show'])->name('show');
        Route::get('/{evenementAcademique}/edit', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'edit'])->name('edit');
        Route::put('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'update'])->name('update');
        Route::delete('/{evenementAcademique}', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'destroy'])->name('destroy');
        Route::post('/{evenementAcademique}/duplicate', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'duplicate'])->name('duplicate');
        Route::post('/{evenementAcademique}/status', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'changeStatus'])->name('change-status');
        Route::get('/api/events', [App\Http\Controllers\ESBTPEvenementAcademiqueController::class, 'getEvents'])->name('api.events');
    });
});

// Routes pour la gestion des liens externes
Route::middleware(['auth', 'permission:admin.access'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::post('/evaluations/{evaluation}/generate-external-link', [ESBTPEvaluationController::class, 'generateExternalLink'])->name('evaluations.generate-external-link');
    Route::delete('/evaluations/{evaluation}/revoke-external-link', [ESBTPEvaluationController::class, 'revokeExternalLink'])->name('evaluations.revoke-external-link');
    Route::get('/evaluations/active-external-links', [ESBTPEvaluationController::class, 'getActiveExternalLinks'])->name('evaluations.active-external-links');
});

// Routes pour la saisie externe de notes (sans authentification)
Route::prefix('external-grading')->name('external-grading.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\ExternalGradingController::class, 'show'])->name('show');
    Route::post('/{token}', [App\Http\Controllers\ExternalGradingController::class, 'store'])->name('store');
});

// Routes Chatbot IA - Accessible à tous les utilisateurs authentifiés
Route::middleware(['auth'])->prefix('chatbot')->name('chatbot.')->group(function () {
    Route::post('/message', [App\Http\Controllers\ChatbotController::class, 'sendMessage'])->name('message');
    Route::post('/message/stream', [App\Http\Controllers\ChatbotController::class, 'sendMessageStream'])->name('message.stream');
    Route::get('/conversations', [App\Http\Controllers\ChatbotController::class, 'listConversations'])->name('conversations');
    Route::get('/conversations/{conversationId}/history', [App\Http\Controllers\ChatbotController::class, 'getHistory'])->name('history');
    Route::delete('/conversations/{conversationId}', [App\Http\Controllers\ChatbotController::class, 'deleteConversation'])->name('delete');
    Route::put('/conversations/{conversationId}/title', [App\Http\Controllers\ChatbotController::class, 'updateConversationTitle'])->name('conversations.title');
    Route::get('/preferences', [App\Http\Controllers\ChatbotController::class, 'getPreferences'])->name('preferences');
    Route::put('/preferences', [App\Http\Controllers\ChatbotController::class, 'updatePreferences'])->name('preferences.update');
    Route::post('/preferences/memory', [App\Http\Controllers\ChatbotController::class, 'saveMemory'])->name('preferences.memory');
    Route::get('/forms/frais-category', [App\Http\Controllers\ChatbotController::class, 'getMandatoryFraisCategoryForm'])->name('forms.frais-category');
    Route::post('/forms/frais-category', [App\Http\Controllers\ChatbotController::class, 'storeMandatoryFraisCategory'])->name('forms.frais-category.store');
    Route::get('/forms/frais-config', [App\Http\Controllers\ChatbotController::class, 'getFraisConfigForm'])->name('forms.frais-config');
    Route::post('/forms/frais-config', [App\Http\Controllers\ChatbotController::class, 'storeFraisConfig'])->name('forms.frais-config.store');
    Route::get('/forms/inscriptions-filter', [App\Http\Controllers\ChatbotController::class, 'getInscriptionsFilterForm'])->name('forms.inscriptions-filter');
    Route::post('/forms/inscriptions-filter', [App\Http\Controllers\ChatbotController::class, 'storeInscriptionsFilter'])->name('forms.inscriptions-filter.store');
});

// ============================================================
// Routes LMD (Licence-Master-Doctorat)
// ============================================================
Route::prefix('esbtp/lmd')->name('esbtp.lmd.')->middleware(['auth', 'permission:admin.access', 'permission:module.lmd.access', 'paywall'])->group(function () {

    // --- Domaines / Mentions / Parcours ---
    Route::get('parcours-domain', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'index'])->name('parcours-domain.index');
    Route::post('domaines', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'storeDomaine'])->name('domaines.store');
    Route::put('domaines/{domaine}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'updateDomaine'])->name('domaines.update');
    Route::delete('domaines/{domaine}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'destroyDomaine'])->name('domaines.destroy');
    Route::post('mentions', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'storeMention'])->name('mentions.store');
    Route::put('mentions/{mention}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'updateMention'])->name('mentions.update');
    Route::delete('mentions/{mention}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'destroyMention'])->name('mentions.destroy');
    Route::post('parcours', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'storeParcours'])->name('parcours.store');
    Route::put('parcours/{parcours}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'updateParcours'])->name('parcours.update');
    Route::delete('parcours/{parcours}', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'destroyParcours'])->name('parcours.destroy');

    // --- Classes liées au parcours ---
    Route::get('parcours/{parcours}/classes-disponibles', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'getClassesDisponibles'])->name('parcours.classes-disponibles');
    Route::post('parcours/{parcours}/sync-classes', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'syncClasses'])->name('parcours.sync-classes');
    Route::post('parcours/{parcours}/classe-rapide', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'storeClasseRapide'])->name('parcours.classe-rapide');
    Route::get('parcours/{parcours}/ues-disponibles', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'getUesDisponibles'])->name('parcours.ues-disponibles');
    Route::post('parcours/{parcours}/sync-ues', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'syncUes'])->middleware('throttle:10,1')->name('parcours.sync-ues');

    // --- Unites d'Enseignement ---
    Route::resource('ue', \App\Http\Controllers\ESBTPLMDUEController::class)->parameters(['ue' => 'ue']);
    Route::get('ue/{ue}/json', [\App\Http\Controllers\ESBTPLMDUEController::class, 'getJson'])->name('ue.json');
    Route::post('ue/{ue}/ecue', [\App\Http\Controllers\ESBTPLMDUEController::class, 'storeECUE'])->name('ue.ecue.store');
    Route::put('ue/{ue}/ecue/{ecue}', [\App\Http\Controllers\ESBTPLMDUEController::class, 'updateECUE'])->name('ue.ecue.update');
    Route::delete('ue/{ue}/ecue/{ecue}', [\App\Http\Controllers\ESBTPLMDUEController::class, 'destroyECUE'])->name('ue.ecue.destroy');
    Route::get('ue/{ue}/matieres-disponibles', [\App\Http\Controllers\ESBTPLMDUEController::class, 'matieresDisponibles'])->name('ue.matieres-disponibles');
    Route::get('ue/{ue}/parcours-disponibles', [\App\Http\Controllers\ESBTPLMDUEController::class, 'parcoursDisponibles'])->name('ue.parcours-disponibles');
    Route::post('ue/{ue}/sync-parcours', [\App\Http\Controllers\ESBTPLMDUEController::class, 'syncParcours'])->name('ue.sync-parcours');

    // --- Notes LMD ---
    Route::get('notes', [\App\Http\Controllers\ESBTPLMDNoteController::class, 'index'])->name('notes.index');
    Route::get('notes/saisie/{evaluation}', [\App\Http\Controllers\ESBTPLMDNoteController::class, 'saisieRapide'])->name('notes.saisie');
    Route::post('notes/save-bulk', [\App\Http\Controllers\ESBTPLMDNoteController::class, 'saveBulk'])->name('notes.save-bulk');
    Route::get('notes/classe/{classe}/data', [\App\Http\Controllers\ESBTPLMDNoteController::class, 'classeData'])->name('notes.classe-data');

    // --- Resultats LMD ---
    Route::get('resultats', [\App\Http\Controllers\ESBTPLMDResultatController::class, 'index'])->name('resultats.index');
    Route::get('resultats/classe/{classe}', [\App\Http\Controllers\ESBTPLMDResultatController::class, 'classe'])->name('resultats.classe');
    Route::get('resultats/etudiant/{etudiant}', [\App\Http\Controllers\ESBTPLMDResultatController::class, 'etudiant'])->name('resultats.etudiant');

    // --- Bulletins LMD ---
    Route::get('bulletins', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'index'])->name('bulletins.index');
    Route::get('bulletins/select', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'select'])->name('bulletins.select');
    Route::post('bulletins/generer', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'generer'])->name('bulletins.generer');
    Route::post('bulletins/generer-classe', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'genererClasse'])->name('bulletins.generer-classe');
    Route::get('bulletins/{bulletin}', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'show'])->name('bulletins.show');
    Route::get('bulletins/{bulletin}/pdf', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'pdf'])->name('bulletins.pdf');
    Route::get('bulletins/{bulletin}/pdf/preview', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'pdfPreview'])
        ->name('bulletins.pdf-preview')
        ->middleware('throttle:60,1');
    Route::put('bulletins/{bulletin}/toggle-publication', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'togglePublication'])->name('bulletins.toggle-publication');
    Route::delete('bulletins/{bulletin}', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'destroy'])->name('bulletins.destroy');

    // --- Planning LMD (UEMOA) ---
    Route::get('planning', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'index'])
        ->middleware('permission:lmd.planning.view')
        ->name('planning.index');
    Route::get('planning/partial', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'partial'])
        ->middleware('permission:lmd.planning.view')
        ->name('planning.partial');
    Route::get('planning/enseignants', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'enseignants'])
        ->middleware(['permission:lmd.planning.edit', 'throttle:60,1'])
        ->name('planning.enseignants');
    Route::get('planning/volumes', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'volumes'])
        ->middleware(['permission:lmd.planning.view', 'throttle:60,1'])
        ->name('planning.volumes');
    Route::patch('planifications/{ecueId}', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'updatePlanification'])
        ->middleware(['permission:lmd.planning.edit', 'throttle:60,1'])
        ->whereNumber('ecueId')
        ->name('planifications.update');
    Route::post('planifications/bulk-update', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'bulkUpdatePlanification'])
        ->middleware(['permission:lmd.planning.edit', 'throttle:10,1'])
        ->name('planifications.bulk-update');

    // W1.2 — Responsable d'UE (directive UEMOA 03/2007/CM : 1 responsable par UE)
    Route::patch('ues/{ueId}/responsable', [\App\Http\Controllers\ESBTPLMDPlanningController::class, 'updateUeResponsable'])
        ->middleware(['permission:lmd.planning.edit', 'throttle:60,1'])
        ->whereNumber('ueId')
        ->name('ues.update-responsable');
});

// ============================================================
// Routes Jury de délibération LMD (PR12 — UI premium juy-*)
// ============================================================
Route::prefix('esbtp/lmd/jurys')->name('esbtp.lmd.jurys.')
    ->middleware(['auth', 'permission:admin.access', 'permission:module.lmd.access', 'paywall'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'index'])
            ->middleware('permission:lmd.jury.view')
            ->name('index');
        Route::post('/', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'store'])
            ->middleware(['permission:lmd.jury.preside', 'throttle:30,1'])
            ->name('store');
        Route::get('/{jury}', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'show'])
            ->middleware('permission:lmd.jury.view')
            ->name('show');
        Route::delete('/{jury}', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'destroy'])
            ->middleware('permission:lmd.jury.preside')
            ->name('destroy');

        // Membres
        Route::post('/{jury}/membres', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'addMembre'])
            ->middleware(['permission:lmd.jury.preside', 'throttle:60,1'])
            ->name('membres.store');
        Route::delete('/{jury}/membres/{membre}', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'removeMembre'])
            ->middleware('permission:lmd.jury.preside')
            ->name('membres.destroy');
        Route::post('/{jury}/membres/{membre}/signer', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'signerMembre'])
            ->middleware(['permission:lmd.jury.deliberate', 'throttle:30,1'])
            ->name('membres.signer');

        // Délibération
        Route::post('/{jury}/decisions/auto', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'appliquerAuto'])
            ->middleware(['permission:lmd.jury.deliberate', 'throttle:10,1'])
            ->name('decisions.auto');
        Route::patch('/{jury}/decisions/{etudiant}', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'overrideDecision'])
            ->middleware(['permission:lmd.jury.deliberate', 'throttle:60,1'])
            ->name('decisions.override');
        Route::get('/{jury}/kpis', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'kpis'])
            ->middleware(['permission:lmd.jury.view', 'throttle:120,1'])
            ->name('kpis');

        // PV
        Route::post('/{jury}/pv/generer', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'genererPv'])
            ->middleware(['permission:lmd.jury.publish', 'throttle:10,1'])
            ->name('pv.generer');
        Route::get('/{jury}/pv/preview', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'pvPreview'])
            ->middleware(['permission:lmd.jury.view', 'throttle:60,1'])
            ->name('pv-preview');
        Route::get('/{jury}/pv/download', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'pvDownload'])
            ->middleware(['permission:lmd.jury.view', 'throttle:30,1'])
            ->name('pv-download');
        Route::post('/{jury}/publier', [\App\Http\Controllers\ESBTPLMDJuryController::class, 'publier'])
            ->middleware(['permission:lmd.jury.publish', 'throttle:10,1'])
            ->name('publier');
    });

// ============================================================
// Routes Rattrapage LMD (PR10 — sessions 2e session UEMOA)
// ============================================================
Route::prefix('esbtp/lmd/rattrapage')->name('esbtp.lmd.rattrapage.')
    ->middleware(['auth', 'permission:admin.access', 'permission:module.lmd.access', 'paywall'])
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'index'])
            ->middleware('permission:lmd.rattrapage.view')
            ->name('index');
        Route::get('/{session}', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'show'])
            ->middleware('permission:lmd.rattrapage.view')
            ->name('show');
        Route::post('/sessions', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'store'])
            ->middleware(['permission:lmd.rattrapage.manage', 'throttle:30,1'])
            ->name('store');
        Route::post('/sessions/{session}/lancer', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'lancerRattrapage'])
            ->middleware(['permission:lmd.rattrapage.manage', 'throttle:10,1'])
            ->name('lancer');
        Route::post('/sessions/{session}/recalculer', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'recalculerNotes'])
            ->middleware(['permission:lmd.rattrapage.manage', 'throttle:30,1'])
            ->name('recalculer');
        Route::post('/sessions/{session}/inscrire', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'inscrireEligibles'])
            ->middleware(['permission:lmd.rattrapage.manage', 'throttle:30,1'])
            ->name('inscrire');
        Route::post('/sessions/{session}/publier', [\App\Http\Controllers\ESBTPLMDSessionController::class, 'publier'])
            ->middleware(['permission:lmd.rattrapage.manage', 'throttle:30,1'])
            ->name('publier');
    });

// ============================================================
// Routes Examens planifiés (PR9 — workflow UEMOA scolarité)
// ============================================================
Route::prefix('esbtp/examens')->name('esbtp.examens.')
    ->middleware(['auth', 'permission:admin.access', 'paywall'])
    ->group(function () {
        // Aperçus / KPIs (lecture)
        Route::get('/kpis', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'kpis'])
            ->middleware(['permission:lmd.examens.view', 'throttle:120,1'])
            ->name('kpis');
        // Options pour modal AJAX (classes + matières + parcours + sessions)
        Route::get('/options', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'options'])
            ->middleware(['permission:lmd.examens.manage', 'throttle:60,1'])
            ->name('options');
        // UEMOA cascade : UE + ECUE pour un parcours
        Route::get('/ecues-by-parcours', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'ecuesByParcours'])
            ->middleware(['permission:lmd.examens.manage', 'throttle:60,1'])
            ->name('ecues-by-parcours');
        // Feed JSON FullCalendar pour la vue calendrier
        Route::get('/feed', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'calendarFeed'])
            ->middleware(['permission:lmd.examens.view', 'throttle:120,1'])
            ->name('feed');
        // UEMOA scope : preview des classes ciblées (scope_type/scope_id) + parcours partagés
        Route::post('/resolve-scope-classes', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'resolveScopeClasses'])
            ->middleware(['permission:lmd.examens.manage', 'throttle:60,1'])
            ->name('resolve-scope-classes');
        Route::get('/convocations/preview', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'convocationsPreview'])
            ->middleware(['permission:lmd.examens.view', 'throttle:60,1'])
            ->name('convocations.preview');
        Route::get('/convocations/download', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'convocationsDownload'])
            ->middleware(['permission:lmd.examens.view', 'throttle:10,1'])
            ->name('convocations.download');

        // Bulk generate + actions custom
        Route::post('/bulk-generate', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'bulkGenerate'])
            ->middleware(['permission:lmd.examens.manage', 'throttle:10,1'])
            ->name('bulk-generate');
        Route::post('/{examen}/surveillants', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'assignSurveillants'])
            ->middleware(['permission:lmd.examens.manage', 'throttle:30,1'])
            ->name('surveillants.assign');
        Route::post('/{examen}/lock-notes', [\App\Http\Controllers\ESBTPExamenPlanifieController::class, 'lockNotes'])
            ->middleware(['permission:lmd.examens.notes_lock', 'throttle:30,1'])
            ->name('lock-notes');

        // Resource CRUD
        Route::resource('/', \App\Http\Controllers\ESBTPExamenPlanifieController::class)
            ->parameters(['' => 'examen'])
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'show' => 'show',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ]);
    });

/*
|--------------------------------------------------------------------------
| Chat interactif + Notifs workflow (issue #298)
|--------------------------------------------------------------------------
| Page /messages : conversations DM + groupe + workflow (action cards inline).
| Notifs in-app pour étapes workflow inscription→paiement→validation.
|
| Isolation étudiants (issue #315) : le chat user-to-user est réservé aux
| utilisateurs qui ont la permission `messages.send`. Les étudiants n'ont
| que `messages.receive` + `annonces.view` et consultent leurs annonces via
| la page séparée /esbtp/mes-annonces. Un étudiant qui tape /messages
| directement dans la barre d'adresse est bloqué en 403.
*/
Route::middleware(['auth', 'paywall', 'permission:messages.send'])->prefix('messages')->name('chat.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ChatController::class, 'index'])->name('index');
    Route::get('/conversations/{conversation}', [\App\Http\Controllers\ChatController::class, 'show'])
        ->whereNumber('conversation')
        ->name('show');
    Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'send'])
        ->whereNumber('conversation')
        ->name('send')
        ->middleware('throttle:60,1');
    Route::post('/dm/start', [\App\Http\Controllers\ChatController::class, 'startDm'])->name('dm.start');
    Route::get('/users/search', [\App\Http\Controllers\ChatController::class, 'searchUsers'])->name('users.search');
    Route::get('/notifications', [\App\Http\Controllers\ChatController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\ChatController::class, 'markNotificationRead'])->name('notifications.read');
    Route::get('/conversations-list', [\App\Http\Controllers\ChatController::class, 'conversationsList'])->name('conversations.list');

    // Action cards — partager une inscription/paiement comme card riche
    Route::get('/picker/inscriptions', [\App\Http\Controllers\ChatController::class, 'pickerInscriptions'])
        ->middleware('throttle:60,1')
        ->name('picker.inscriptions');
    Route::get('/picker/paiements', [\App\Http\Controllers\ChatController::class, 'pickerPaiements'])
        ->middleware('throttle:60,1')
        ->name('picker.paiements');
    // permission:messages.send déjà appliqué au groupe parent ci-dessus (issue #315).
    Route::post('/share/inscription/{inscription}', [\App\Http\Controllers\ChatController::class, 'shareInscription'])
        ->whereNumber('inscription')
        ->middleware('throttle:30,1')
        ->name('share.inscription');
    Route::post('/share/paiement/{paiement}', [\App\Http\Controllers\ChatController::class, 'sharePaiement'])
        ->whereNumber('paiement')
        ->middleware('throttle:30,1')
        ->name('share.paiement');
});

// ============================================================
// Routes TPE — Travail Personnel Étudiant (LMD UEMOA)
// Options 2 (journal étudiant) + 3 (workflow validation prof opt-in)
// ============================================================
Route::middleware(['auth', 'permission:module.tpe.access'])->group(function () {

    // --- Journal étudiant (Option 2 — toujours actif si module enabled) ---
    Route::middleware(['permission:tpe.declare', 'throttle:60,1'])->group(function () {
        Route::get('esbtp/tpe-journal', [\App\Http\Controllers\ESBTPTpeDeclarationController::class, 'index'])
            ->name('esbtp.tpe-journal.index');
        Route::post('esbtp/tpe-journal', [\App\Http\Controllers\ESBTPTpeDeclarationController::class, 'store'])
            ->name('esbtp.tpe-journal.store');
        Route::put('esbtp/tpe-journal/{declaration}', [\App\Http\Controllers\ESBTPTpeDeclarationController::class, 'update'])
            ->whereNumber('declaration')
            ->name('esbtp.tpe-journal.update');
        Route::delete('esbtp/tpe-journal/{declaration}', [\App\Http\Controllers\ESBTPTpeDeclarationController::class, 'destroy'])
            ->whereNumber('declaration')
            ->name('esbtp.tpe-journal.destroy');
    });

    // --- Validation enseignant (Option 3 — dormant tant que Setting tpe.validation.enabled = false) ---
    Route::middleware(['permission:tpe.validate', 'throttle:30,1'])->group(function () {
        Route::get('esbtp/tpe-validation', [\App\Http\Controllers\ESBTPTpeValidationController::class, 'index'])
            ->name('esbtp.tpe-validation.index');
        Route::patch('esbtp/tpe-validation/{declaration}/validate', [\App\Http\Controllers\ESBTPTpeValidationController::class, 'approve'])
            ->whereNumber('declaration')
            ->name('esbtp.tpe-validation.validate');
        Route::patch('esbtp/tpe-validation/{declaration}/reject', [\App\Http\Controllers\ESBTPTpeValidationController::class, 'reject'])
            ->whereNumber('declaration')
            ->name('esbtp.tpe-validation.reject');
    });
});

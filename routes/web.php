<?php

use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ESBTP\Admin\ESBTPTeacherAttendanceController;
use App\Http\Controllers\ESBTP\Admin\TeacherAdminController;
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
use App\Http\Controllers\ESBTPComptabilitePaiementController;
use App\Http\Controllers\ESBTPComptabiliteReportController;
use App\Http\Controllers\ESBTPComptabiliteRelanceController;
use App\Http\Controllers\ESBTPContinuingEducationController;
use App\Http\Controllers\ESBTPCycleController;
use App\Http\Controllers\ESBTPDepartmentController;
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
use App\Http\Controllers\ParentMessageController;
use App\Http\Controllers\ParentNotificationController;
use App\Http\Controllers\ParentPaymentController;
use App\Http\Controllers\ParentSettingsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentProgressionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\ESBTP\ESBTPAuditController; // COMMENTED - CONTROLLER NOT IMPLEMENTED
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

// Route d'accueil
Route::get('/', function () {
    return view('welcome')->withHeaders([
        'Cache-Control' => 'public, max-age=3600',
        'Expires' => gmdate('D, d M Y H:i:s', time() + 3600).' GMT',
    ]);
})->name('welcome');

// Route pour l'ancienne version de l'école (si nécessaire pour référence)
Route::get('/school', function () {
    return view('welcome-redesign')->withHeaders([
        'Cache-Control' => 'public, max-age=3600', // Cache 1 heure
        'Expires' => gmdate('D, d M Y H:i:s', time() + 3600).' GMT',
    ]);
})->name('welcome.school');

// Routes pour l'installation
Route::prefix('install')->group(function () {
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

// Routes d'authentification simplifiées
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Routes d'enregistrement
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register']);

// Routes de réinitialisation de mot de passe
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Routes de changement de mot de passe forcé
Route::middleware(['auth'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'showChangeForm'])->name('password.change.form');
    Route::post('/password/change', [PasswordChangeController::class, 'updatePassword'])->name('password.change.update');
});

// Route pour les demandes de démonstration (accessible publiquement)
Route::post('/contact-demo', [App\Http\Controllers\ContactController::class, 'sendDemo'])->name('contact.demo');

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

    // Route de test debug mode (uniquement en développement)
    if (config('app.debug')) {
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
    Route::middleware(['permission:access_admin'])->group(function () {
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
        Route::middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
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
            Route::post('frais/{category}/toggle', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleCategory'])->name('frais.toggle');
            Route::post('frais/{fraisCategory}/toggle-active', [\App\Http\Controllers\ESBTPFraisController::class, 'toggleActive'])->name('frais.toggle-active');
            Route::post('frais/reset-defaults', [\App\Http\Controllers\ESBTPFraisController::class, 'resetDefaults'])->name('frais.reset-defaults');
            Route::resource('frais', \App\Http\Controllers\ESBTPFraisController::class)->except(['index']);

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

            // Routes pour les souscriptions aux frais optionnels
            Route::post('inscriptions/{inscription}/subscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionPaiementController::class, 'subscribeToOptionalFee'])->name('inscriptions.subscribe-optional-fee');
            Route::post('inscriptions/{inscription}/unsubscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionPaiementController::class, 'unsubscribeFromOptionalFee'])->name('inscriptions.unsubscribe-optional-fee');

            // Routes pour les certificats de scolarité
            Route::get('/etudiants/{etudiant}/certificat-preview', [ESBTPEtudiantController::class, 'previewCertificat'])
                ->name('etudiants.certificat.preview')
                ->middleware(['permission:view_students']);
            Route::get('/etudiants/{etudiant}/certificat', [ESBTPEtudiantController::class, 'genererCertificat'])
                ->name('etudiants.certificat')
                ->middleware(['permission:view_students']);

            // Routes pour les attestations de fréquentation
            Route::get('/etudiants/{etudiant}/attestation-frequentation-preview', [ESBTPEtudiantController::class, 'previewAttestationFrequentation'])
                ->name('etudiants.attestation-frequentation.preview')
                ->middleware(['permission:view_students']);
            Route::get('/etudiants/{etudiant}/attestation-frequentation', [ESBTPEtudiantController::class, 'genererAttestationFrequentation'])
                ->name('etudiants.attestation-frequentation')
                ->middleware(['permission:view_students']);

            // Routes pour les rôles et permissions
            Route::resource('roles', \App\Http\Controllers\ESBTP\RoleController::class)->middleware(['role:superAdmin']);

            // Routes pour les départements
            Route::resource('departments', ESBTPDepartmentController::class);
            Route::put('departments/{id}/restore', [ESBTPDepartmentController::class, 'restore'])->name('departments.restore');
            Route::delete('departments/{id}/force-delete', [ESBTPDepartmentController::class, 'forceDelete'])->name('departments.force-delete');

            // Routes pour les filières
            Route::resource('filieres', ESBTPFiliereController::class)
                ->middleware(['permission:view_filieres|create_filieres|edit_filieres|delete_filieres']);

            // Routes pour les niveaux d'études
            Route::resource('niveaux-etudes', ESBTPNiveauEtudeController::class)
                ->middleware(['permission:view_niveaux_etudes|create_niveaux_etudes|edit_niveaux_etudes|delete_niveaux_etudes']);

            // Routes pour les années universitaires
            Route::post('annees-universitaires/{anneesUniversitaire}/set-current', [ESBTPAnneeUniversitaireController::class, 'setCurrent'])
                ->name('annees-universitaires.set-current');
            Route::resource('annees-universitaires', ESBTPAnneeUniversitaireController::class);

            // Debug route
            Route::get('debug-annees', [ESBTPAnneeUniversitaireController::class, 'debug'])
                ->name('debug-annees');

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

            // Routes de modification des classes - réservées aux superAdmin
            Route::resource('classes', ESBTPClasseController::class)
                ->parameters(['classes' => 'classe'])
                ->except(['index', 'show'])
                ->names([
                    'create' => 'classes.create',
                    'store' => 'classes.store',
                    'edit' => 'classes.edit',
                    'update' => 'classes.update',
                    'destroy' => 'classes.destroy',
                ])
                ->middleware(['permission:create_classe|create classes|edit_classes|edit classes|delete_classes|delete classes']);

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

                // Route pour la page de finalisation de réinscription
                Route::get('{etudiant}/finaliser', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'create'])->name('create');

                // Route AJAX pour récupérer les classes selon la décision
                Route::get('{etudiant}/classes-by-decision', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'getClassesByDecision'])->name('classes-by-decision');

                // Routes avec paramètres à la FIN
                Route::get('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'show'])->name('show');
                Route::put('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'update'])->name('update');
            });

            // Routes pour les séances de cours (accessible aux coordinateurs)
            Route::resource('seances-cours', ESBTPSeanceCoursController::class)
                ->parameters(['seances-cours' => 'seancesCour']);
        });

        // Routes accessibles aux superAdmin, secrétaires, coordinateurs et enseignants
        Route::middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
            // Routes pour les classes ESBTP - index et show avec permission view_classes
            Route::get('classes', [ESBTPClasseController::class, 'index'])
                ->name('classes.index')
                ->middleware(['permission:view_classes|view classes']);

            Route::get('classes/{classe}', [ESBTPClasseController::class, 'show'])
                ->name('classes.show')
                ->middleware(['permission:view_classes|view classes']);

            // Route pour gérer les matières d'une classe - accessible aux superAdmin et secrétaires
            Route::get('classes/{classe}/matieres', [ESBTPClasseController::class, 'matieres'])
                ->name('classes.matieres')
                ->middleware(['permission:view_classes|view classes']);

            // Routes de l'API pour récupérer les matières d'une classe - accessible aux superAdmin et secrétaires
            Route::get('classes/{classe}/matieres/api', [ESBTPClasseController::class, 'getMatieres'])
                ->name('classes.matieres.data')
                ->middleware(['permission:view_classes|view classes']);

// Route pour vérifier les places disponibles dans une classe
            Route::get('classes/{id}/available-places', [ESBTPEtudiantController::class, 'getAvailablePlaces'])
                ->name('classes.available-places')
                ->middleware(['permission:view_classes|view classes']);
            
            // Route pour récupérer les classes en surcapacité
            Route::get('classes/overcapacity', [ESBTPClasseController::class, 'getOvercapacityClasses'])
                ->name('classes.overcapacity')
                ->middleware(['permission:view_classes|view classes']);
            // Routes pour les matières
            Route::name('matieres.')->prefix('matieres')->group(function () {
                Route::get('/json', [ESBTPMatiereController::class, 'getMatieresJson'])
                    ->name('json')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::get('/refresh', [ESBTPMatiereController::class, 'refresh'])
                    ->name('refresh')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::delete('/bulk-delete', [ESBTPMatiereController::class, 'bulkDelete'])
                    ->name('bulk-delete')
                    ->middleware(['permission:delete_matieres|delete matieres']);
                Route::get('attach-to-classes', [ESBTPMatiereController::class, 'attachToClasses'])
                    ->name('attach-to-classes')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::post('process-attach-to-classes', [ESBTPMatiereController::class, 'processAttachToClasses'])
                    ->name('process-attach-to-classes')
                    ->middleware(['permission:edit_matieres|edit matieres']);

                // Routes AJAX pour la configuration des liaisons
                Route::get('{matiere}/liaisons', [ESBTPMatiereController::class, 'getLiaisons'])
                    ->name('liaisons')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::post('{matiere}/update-liaisons', [ESBTPMatiereController::class, 'updateLiaisons'])
                    ->name('update-liaisons')
                    ->middleware(['permission:edit_matieres|edit matieres']);
                Route::get('{matiere}/statistiques-liaisons', [ESBTPMatiereController::class, 'getStatistiquesLiaisons'])
                    ->name('statistiques-liaisons')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::get('{matiere}/refresh-ligne', [ESBTPMatiereController::class, 'refreshLigne'])
                    ->name('refresh-ligne')
                    ->middleware(['permission:view_matieres|view matieres']);

                // Routes pour l'ajout de matières aux combinaisons vides
                Route::get('available-for-combination', [ESBTPMatiereController::class, 'getAvailableForCombination'])
                    ->name('available-for-combination')
                    ->middleware(['permission:view_matieres|view matieres']);
                Route::post('add-to-combination', [ESBTPMatiereController::class, 'addToCombination'])
                    ->name('add-to-combination')
                    ->middleware(['permission:edit_matieres|edit matieres']);
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
                ->middleware(['permission:view_matieres|create_matieres|edit_matieres|delete_matieres']);

            // Routes pour l'association/dissociation d'enseignants
            Route::post('matieres/{matiere}/associate-enseignant', [ESBTPMatiereController::class, 'associateEnseignant'])
                ->name('matieres.associate-enseignant');
            Route::post('matieres/{matiere}/dissociate-enseignant', [ESBTPMatiereController::class, 'dissociateEnseignant'])
                ->name('matieres.dissociate-enseignant');

            // Routes pour les emplois du temps ESBTP

            // Route AJAX pour refresh des emplois du temps avec filtres - DOIT ÊTRE AVANT Route::resource
            Route::get('emploi-temps/refresh', [ESBTPEmploiTempsController::class, 'refresh'])
                ->name('emploi-temps.refresh')
                ->middleware(['permission:view_timetables']);

            Route::post('emploi-temps/quick-generate', [ESBTPEmploiTempsController::class, 'quickGenerate'])
                ->name('emploi-temps.quick-generate')
                ->middleware(['permission:create_timetable']);

            Route::post('emploi-temps/quick-generate/preview', [ESBTPEmploiTempsController::class, 'quickGeneratePreview'])
                ->name('emploi-temps.quick-generate.preview')
                ->middleware(['permission:create_timetable']);

            Route::get('emploi-temps/bulk-edit', [ESBTPEmploiTempsController::class, 'bulkEdit'])
                ->name('emploi-temps.bulk-edit')
                ->middleware(['permission:edit_timetables']);

            Route::get('emploi-temps/{emploi_temp}/sections', [ESBTPEmploiTempsController::class, 'sections'])
                ->name('emploi-temps.sections')
                ->middleware(['permission:view_timetables']);

            // Routes pour les emplois du temps ESBTP (permissions par action)
            Route::get('emploi-temps', [ESBTPEmploiTempsController::class, 'index'])->name('emploi-temps.index')->middleware('permission:view_timetables');
            Route::get('emploi-temps/create', [ESBTPEmploiTempsController::class, 'create'])->name('emploi-temps.create')->middleware('permission:create_timetable');
            Route::post('emploi-temps', [ESBTPEmploiTempsController::class, 'store'])->name('emploi-temps.store')->middleware('permission:create_timetable');
            Route::get('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'show'])->name('emploi-temps.show')->middleware('permission:view_timetables');
            Route::get('emploi-temps/{emploi_temp}/edit', [ESBTPEmploiTempsController::class, 'edit'])->name('emploi-temps.edit')->middleware('permission:edit_timetables');
            Route::put('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'update'])->name('emploi-temps.update')->middleware('permission:edit_timetables');
            Route::delete('emploi-temps/{emploi_temp}', [ESBTPEmploiTempsController::class, 'destroy'])->name('emploi-temps.destroy')->middleware('permission:delete_timetables');

            Route::get('emploi-temps/{emploi_temp}/export-pdf', [ESBTPEmploiTempsController::class, 'generatePdf'])
                ->name('emploi-temps.export-pdf')
                ->middleware(['permission:view_timetables']);

            // Route pour prévisualiser l'emploi du temps avant génération PDF
            Route::get('emploi-temps/{emploi_temp}/preview', [ESBTPEmploiTempsController::class, 'previewEmploiTemps'])
                ->name('emploi-temps.preview')
                ->middleware(['permission:view_timetables']);

            // Routes pour la gestion des séances d'emploi du temps
            Route::get('emploi-temps/{emploi_temp}/add-session', [ESBTPEmploiTempsController::class, 'addSession'])
                ->name('emploi-temps.add-session')
                ->middleware(['permission:edit_timetables']);
            Route::post('emploi-temps/{emploi_temp}/store-session', [ESBTPEmploiTempsController::class, 'storeSession'])
                ->name('emploi-temps.store-session')
                ->middleware(['permission:edit_timetables']);

            // Routes pour les emplois du temps standards (TimetableController)
            Route::resource('timetables', TimetableController::class)
                ->middleware(['permission:view_timetables|create_timetable|edit_timetables|delete_timetables']);

            // Routes supplémentaires pour les emplois du temps
            Route::get('timetables/class/{classId}', [TimetableController::class, 'showByClass'])
                ->name('timetables.class')
                ->middleware(['permission:view_timetables']);
            Route::get('timetables/teacher/{teacherId}', [TimetableController::class, 'showByTeacher'])
                ->name('timetables.teacher')
                ->middleware(['permission:view_timetables']);
            Route::get('timetables/student/{studentId}', [TimetableController::class, 'showByStudent'])
                ->name('timetables.student')
                ->middleware(['permission:view_own_timetable|view_timetables']);

            // Routes pour les résultats
            Route::get('resultats', [ESBTPResultatController::class, 'resultats'])
                ->name('resultats.index')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            Route::get('resultats/classes', [ESBTPResultatController::class, 'resultatsClasses'])
                ->name('resultats.classes')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            Route::get('resultats/classe/{classe}', [ESBTPResultatController::class, 'resultatClasse'])
                ->name('resultats.classe')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            Route::get('resultats/etudiant/{etudiant}', [ESBTPResultatController::class, 'resultatEtudiant'])
                ->name('resultats.etudiant')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            Route::get('resultats/etudiant/{etudiant}/preview', [ESBTPResultatController::class, 'previewBulletinEtudiantNew'])
                ->name('resultats.etudiant.preview')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            // Route AJAX pour le lazy loading des étudiants sur la page résultats
            Route::get('resultats/load-etudiants', [ESBTPResultatController::class, 'loadEtudiants'])
                ->name('resultats.load-etudiants')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            // Route principale d'édition groupée par classe
            Route::get('resultats/classe/{classe}/edit', [ESBTPResultatController::class, 'editResultatsClasse'])
                ->name('resultats.classe.edit')
                ->middleware(['permission:edit_bulletins']);

            // Routes AJAX pour édition groupée
            Route::get('resultats/get-moyennes', [ESBTPResultatController::class, 'getMoyennes'])
                ->name('resultats.get-moyennes')
                ->middleware(['permission:view_bulletins|view_own_bulletin']);

            Route::get('resultats/get-absences', [ESBTPResultatController::class, 'getAbsences'])
                ->name('resultats.get-absences')
                ->middleware(['permission:view_bulletins|view_own_bulletin']);
            
            // Force route cache refresh - 2025-01-30

            Route::post('resultats/bulk-update-moyennes', [ESBTPResultatController::class, 'bulkUpdateMoyennes'])
                ->name('resultats.bulk-update-moyennes')
                ->middleware(['permission:edit_bulletins']);

            Route::post('resultats/bulk-update-professeurs', [ESBTPResultatController::class, 'bulkUpdateProfesseurs'])
                ->name('resultats.bulk-update-professeurs')
                ->middleware(['permission:edit_bulletins']);

            Route::post('resultats/bulk-update-absences', [ESBTPResultatController::class, 'bulkUpdateAbsences'])
                ->name('resultats.bulk-update-absences')
                ->middleware(['permission:edit_bulletins']);

            Route::post('resultats/bulk-update-coefficients', [ESBTPResultatController::class, 'bulkUpdateCoefficients'])
                ->name('resultats.bulk-update-coefficients')
                ->middleware(['permission:edit_bulletins']);

            Route::get('resultats/get-matiere-coefficient', [ESBTPResultatController::class, 'getMatiereCoefficient'])
                ->name('resultats.get-matiere-coefficient')
                ->middleware(['permission:edit_bulletins']);

            Route::post('resultats/bulk-update-matieres-config', [ESBTPResultatController::class, 'bulkUpdateMatieresConfig'])
                ->name('resultats.bulk-update-matieres-config')
                ->middleware(['permission:edit_bulletins']);

            Route::get('bulletins/configuration', [ESBTPBulletinController::class, 'configuration'])
                ->name('bulletins.configuration')
                ->middleware(['permission:edit_bulletins']);

            Route::post('bulletins/configuration', [ESBTPBulletinController::class, 'saveConfiguration'])
                ->name('bulletins.save-configuration')
                ->middleware(['permission:edit_bulletins']);
            Route::get('resultats/historique/classes', [ESBTPResultatController::class, 'resultats'])
                ->name('resultats.historique.classes')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            // Routes pour les annonces
            Route::resource('annonces', ESBTPAnnonceController::class)
                ->middleware(['permission:view_annonces|create_annonces|edit_annonces']);

            // Routes pour les présences/absences (esbtp namespace)
            Route::name('attendances.')->group(function () {
                Route::get('/attendances', [ESBTPAttendanceController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:view_attendances');

                Route::get('/attendances/create', [ESBTPAttendanceController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:create_attendance');

                Route::post('/attendances', [ESBTPAttendanceController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:create_attendance');

                // Static segment routes first
                Route::get('/attendances/rapport-form', [ESBTPAttendanceController::class, 'rapportForm'])
                    ->name('rapport-form')
                    ->middleware('permission:view_attendances');

                Route::post('/attendances/rapport', [ESBTPAttendanceController::class, 'rapport'])
                    ->name('rapport')
                    ->middleware('permission:view_attendances');

                Route::post('/attendances/rapport-pdf', [ESBTPAttendanceController::class, 'rapportPdf'])
                    ->name('rapport-pdf')
                    ->middleware('permission:view_attendances');

                // AJAX routes for partial refresh
                Route::get('/attendances/load-seances', [ESBTPAttendanceController::class, 'loadSeances'])
                    ->name('load-seances')
                    ->middleware('permission:create_attendance');

                Route::get('/attendances/load-students', [ESBTPAttendanceController::class, 'loadStudents'])
                    ->name('load-students')
                    ->middleware('permission:create_attendance');

                // Then parameter routes
                Route::get('/attendances/{attendance}', [ESBTPAttendanceController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:view_attendances');

                Route::get('/attendances/{attendance}/edit', [ESBTPAttendanceController::class, 'edit'])
                    ->name('edit')
                    ->middleware('permission:edit_attendances');

                Route::put('/attendances/{attendance}', [ESBTPAttendanceController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:edit_attendances');

                Route::delete('/attendances/{attendance}', [ESBTPAttendanceController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:delete_attendances');

                Route::post('/attendances/{absenceId}/process-justification', [ESBTPAttendanceController::class, 'processJustification'])
                    ->name('process-justification')
                    ->middleware('permission:edit_attendances');
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
                    ->middleware('permission:manage-planning|view-all-timetables');
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

            // Routes pour les enseignants (ancien système - profile controller)
            Route::prefix('enseignants-profiles')->name('enseignants.profiles.')->group(function () {
                Route::get('/', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'index'])->name('index');
                Route::get('/dashboard', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'dashboard'])->name('dashboard');
                Route::get('/create', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'store'])->name('store');
                Route::get('/{id}', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'edit'])->name('edit');
                Route::put('/{id}', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'update'])->name('update');
                Route::post('/{id}/valider', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'valider'])->name('valider');
                Route::post('/{id}/affecter', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'affecter'])->name('affecter');
                Route::get('/{id}/disponibilites', [App\Http\Controllers\ESBTPEnseignantProfileController::class, 'disponibilites'])->name('disponibilites');
            });

            // Paiements
            Route::get('/paiements', [App\Http\Controllers\ESBTPPaiementController::class, 'index'])->name('paiements.index');
            Route::get('/paiements/refresh', [App\Http\Controllers\ESBTPPaiementController::class, 'refresh'])->name('paiements.refresh');
            Route::get('/paiements/check-updates', [App\Http\Controllers\ESBTPPaiementController::class, 'checkForUpdates'])->name('paiements.check-updates');

            // Routes pour export des paiements
            Route::get('/paiements/test-filters', [App\Http\Controllers\ESBTPPaiementController::class, 'testFilters'])->name('paiements.test-filters');
            Route::get('/paiements/export/excel', [App\Http\Controllers\ESBTPPaiementController::class, 'exportExcel'])->name('paiements.export.excel');
            Route::get('/paiements/export/csv', [App\Http\Controllers\ESBTPPaiementController::class, 'exportCsv'])->name('paiements.export.csv');
            Route::get('/paiements/export/pdf', [App\Http\Controllers\ESBTPPaiementController::class, 'exportPdf'])->name('paiements.export.pdf');
            Route::get('/paiements/{paiement}/refresh-ligne', [App\Http\Controllers\ESBTPPaiementController::class, 'refreshLigne'])->name('paiements.refresh-ligne');
            Route::get('/paiements/suivi-categories', [App\Http\Controllers\ESBTPPaiementSuiviController::class, 'suiviCategories'])->name('paiements.suivi-categories');
            Route::get('/paiements/suivi-categories/refresh', [App\Http\Controllers\ESBTPPaiementSuiviController::class, 'suiviCategoriesRefresh'])->name('paiements.suivi-categories.refresh');
            Route::get('/paiements/suivi-categories/load/{statut}', [App\Http\Controllers\ESBTPPaiementController::class, 'loadStudentsByStatut'])->name('paiements.suivi-categories.load');
            Route::get('/paiements/suivi-categories/export/{statut}/excel', [App\Http\Controllers\ESBTPPaiementController::class, 'exportStudentsExcel'])->name('paiements.suivi-categories.export.excel');
            Route::get('/paiements/suivi-categories/export/{statut}/pdf', [App\Http\Controllers\ESBTPPaiementController::class, 'exportStudentsPdf'])->name('paiements.suivi-categories.export.pdf');
            Route::get('/paiements/create', [App\Http\Controllers\ESBTPPaiementController::class, 'create'])->name('paiements.create');
            Route::post('/paiements', [App\Http\Controllers\ESBTPPaiementController::class, 'store'])->name('paiements.store');
            Route::get('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'show'])->name('paiements.show');
            Route::get('/paiements/{paiement}/edit', [App\Http\Controllers\ESBTPPaiementController::class, 'edit'])->name('paiements.edit');
            Route::put('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'update'])->name('paiements.update');
            Route::delete('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'destroy'])->name('paiements.destroy');
            Route::get('/paiements/{paiement}/preview', [App\Http\Controllers\ESBTPPaiementController::class, 'previewRecu'])->name('paiements.preview');
            Route::get('/paiements/{paiement}/recu', [App\Http\Controllers\ESBTPPaiementController::class, 'genererRecu'])->name('paiements.recu');
            Route::get('/paiements/etudiant/{etudiant}', [App\Http\Controllers\ESBTPPaiementController::class, 'paiementsEtudiant'])->name('paiements.etudiant');

            // Routes pour les reliquats
            Route::post('/reliquats/pay', [App\Http\Controllers\ESBTPPaiementController::class, 'payReliquat'])->name('reliquats.pay');

            // Routes pour validation des paiements
            Route::post('/paiements/{paiement}/valider', [App\Http\Controllers\ESBTPPaiementController::class, 'valider'])->name('paiements.valider');
            Route::post('/paiements/{paiement}/rejeter', [App\Http\Controllers\ESBTPPaiementController::class, 'rejeter'])->name('paiements.rejeter');

            // Route pour validation rapide depuis modal (inscriptions.index)
            Route::post('/paiements/{paiement}/valider-rapide', [App\Http\Controllers\ESBTPPaiementController::class, 'validerRapide'])->name('paiements.valider-rapide');

            // Routes pour validation/rejet groupés
            Route::post('/paiements/bulk-valider', [App\Http\Controllers\ESBTPPaiementController::class, 'bulkValider'])->name('paiements.bulk-valider');
            Route::post('/paiements/bulk-rejeter', [App\Http\Controllers\ESBTPPaiementController::class, 'bulkRejeter'])->name('paiements.bulk-rejeter');

            // Routes ESBTP Bulletins
            Route::prefix('bulletins')->name('bulletins.')->group(function () {
                Route::get('/', [ESBTPBulletinController::class, 'index'])->name('index');
                Route::get('/create', [ESBTPBulletinController::class, 'create'])->name('create');
                Route::post('/', [ESBTPBulletinController::class, 'store'])->name('store');
                Route::get('/{bulletin}', [ESBTPBulletinController::class, 'show'])->name('show');
                Route::get('/{bulletin}/edit', [ESBTPBulletinController::class, 'edit'])->name('edit');
                Route::put('/{bulletin}', [ESBTPBulletinController::class, 'update'])->name('update');
                Route::delete('/{bulletin}', [ESBTPBulletinController::class, 'destroy'])->name('destroy');
                Route::get('/select', [ESBTPBulletinController::class, 'select'])->name('select');
                Route::get('/generate', [ESBTPBulletinController::class, 'generateBulletin'])->name('generate');

                // Route pour la signature des bulletins
                Route::post('bulletins/{bulletin}/signer/{role}', [ESBTPBulletinController::class, 'signer'])
                    ->name('bulletins.signer')
                    ->middleware(['permission:edit_bulletins']);
                // Route pour basculer la publication d'un bulletin
                Route::put('bulletins/{bulletin}/toggle-publication', [ESBTPBulletinController::class, 'togglePublication'])
                    ->name('bulletins.toggle-publication')
                    ->middleware(['permission:edit_bulletins']);

                // Route pour les bulletins en attente
                Route::get('pending', [ESBTPBulletinController::class, 'pending'])
                    ->name('pending')
                    ->middleware(['permission:view_bulletins']);
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
                Route::put('/{attendance}', [ESBTPTeacherAttendanceController::class, 'update'])->name('update');
            });
        });

        // Routes pré-inscription caissier
        Route::middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
            Route::get('/inscriptions/pre-inscription', [ESBTPInscriptionController::class, 'createPreInscription'])->name('inscriptions.pre-inscription');
            Route::post('/inscriptions/pre-inscription', [ESBTPInscriptionController::class, 'storePreInscription'])->name('inscriptions.store-pre-inscription');
            Route::get('/inscriptions/search-etudiants', [ESBTPInscriptionController::class, 'searchEtudiants'])->name('inscriptions.search-etudiants');
            Route::get('/inscriptions/analyse-etudiant/{etudiantId}', [ESBTPInscriptionController::class, 'analyseEtudiant'])->name('inscriptions.analyse-etudiant');
        });

        // Routes accessibles pour les secrétaires, super-admins, coordinateurs et caissier (consultation)
        Route::middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
            // Nouvelle route pour la vue fusionnée des étudiants et inscriptions
            Route::get('/etudiants-inscriptions', [ESBTPEtudiantController::class, 'indexFusionne'])
                ->name('etudiants-inscriptions.index')
                ->middleware(['permission:view_students|view_inscriptions']);

            // Routes pour la gestion des comptes utilisateurs étudiants
            Route::post('/etudiants/{etudiant}/create-account', [ESBTPEtudiantController::class, 'createUserAccount'])
                ->name('etudiants.create-account')
                ->middleware(['permission:edit_students']);

            Route::get('/etudiants/{etudiant}/reset-password', [ESBTPEtudiantController::class, 'resetPassword'])
                ->name('etudiants.reset-password')
                ->middleware(['permission:edit_students']);

            // Route pour rechercher des parents existants
            Route::get('/parents/search', [ESBTPEtudiantController::class, 'searchParents'])
                ->name('parents.search')
                ->middleware(['permission:edit_students']);

            // Route pour générer un certificat de scolarité

            // Routes pour les inscriptions ESBTP
            Route::get('/inscriptions', [ESBTPInscriptionController::class, 'index'])->name('inscriptions.index');
            Route::get('/inscriptions/create', [ESBTPInscriptionController::class, 'create'])->name('inscriptions.create');
            Route::get('/inscriptions/getClasses', [ESBTPInscriptionApiController::class, 'getClasses'])->name('inscriptions.getClasses');
            Route::get('/inscriptions/check-transfert/{classe}', [ESBTPInscriptionApiController::class, 'checkTransfert'])->name('inscriptions.check-transfert');
            Route::get('/inscriptions/duplicates', [ESBTPInscriptionApiController::class, 'duplicates'])->name('inscriptions.duplicates');
            Route::post('/inscriptions/check-duplicates', [ESBTPInscriptionApiController::class, 'checkDuplicates'])->name('inscriptions.check-duplicates');
            Route::post('/inscriptions', [ESBTPInscriptionController::class, 'store'])->name('inscriptions.store');
            Route::get('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'show'])->name('inscriptions.show');
            Route::get('/inscriptions/{inscription}/situation-financiere/preview', [ESBTPInscriptionPaiementController::class, 'previewSituationFinanciere'])->name('inscriptions.situation-financiere.preview');
            Route::get('/inscriptions/{inscription}/situation-financiere/pdf', [ESBTPInscriptionPaiementController::class, 'exportSituationFinanciere'])->name('inscriptions.situation-financiere.pdf');
            Route::get('/inscriptions/{inscription}/edit', [ESBTPInscriptionController::class, 'edit'])->name('inscriptions.edit');
            Route::put('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'update'])->name('inscriptions.update');
            Route::delete('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'destroy'])->name('inscriptions.destroy');
            Route::put('/inscriptions/{inscription}/valider', [ESBTPInscriptionController::class, 'valider'])->name('inscriptions.valider');
            Route::put('/inscriptions/{inscription}/annuler', [ESBTPInscriptionController::class, 'annuler'])->name('inscriptions.annuler');

            // Routes pour validation groupée des inscriptions
            Route::post('/inscriptions/bulk-valider', [ESBTPInscriptionController::class, 'bulkValider'])->name('inscriptions.bulk-valider');

            // Routes pour actions rapides sur inscriptions (modals AJAX)
            Route::get('/inscriptions/{inscription}/data', [ESBTPInscriptionApiController::class, 'getInscriptionData'])->name('inscriptions.data');
            Route::get('/inscriptions/{inscription}/paiement-en-attente', [ESBTPInscriptionApiController::class, 'getPaiementEnAttente'])->name('inscriptions.paiement-en-attente');
            Route::get('/inscriptions/{inscription}/classes-alternatives', [ESBTPInscriptionApiController::class, 'getClassesAlternatives'])->name('inscriptions.classes-alternatives');
            Route::post('/inscriptions/{inscription}/changer-classe-rapide', [ESBTPInscriptionController::class, 'changerClasseRapide'])->name('inscriptions.changer-classe-rapide');

            // Route API pour validation montant paiement (AJAX)
            Route::get('/inscriptions/{inscription}/frais-restants', [ESBTPInscriptionPaiementController::class, 'getFraisRestants'])->name('inscriptions.frais-restants');
            Route::get('/inscriptions/{inscription}/frais/{category}/montant-restant', [ESBTPInscriptionPaiementController::class, 'getMontantRestant'])->name('inscriptions.frais.montant-restant');
            Route::get('/inscriptions/{inscription}/refresh-ligne', [ESBTPInscriptionController::class, 'refreshLigne'])->name('inscriptions.refresh-ligne');

            // Routes pour l'administration des inscriptions
            Route::get('/inscriptions-administration', [ESBTPInscriptionController::class, 'administration'])->name('inscriptions.administration');
            Route::post('/inscriptions/{inscription}/valider-avec-paiement', [ESBTPInscriptionPaiementController::class, 'validerAvecPaiement'])->name('inscriptions.valider-avec-paiement');
            Route::post('/inscriptions/{inscription}/valider-definitivement', [ESBTPInscriptionPaiementController::class, 'validerDefinitivement'])->name('inscriptions.valider-definitivement');
            Route::post('/inscriptions/{inscription}/payer-frais', [ESBTPInscriptionPaiementController::class, 'payerFraisCategorie'])->name('inscriptions.payer-frais');
            Route::post('/inscriptions/{inscription}/transfer-overpayment', [ESBTPInscriptionPaiementController::class, 'transferOverpayment'])->name('inscriptions.transfer-overpayment');
            Route::put('/inscriptions/{inscription}/subscriptions/{subscription}', [ESBTPInscriptionPaiementController::class, 'updateSubscription'])->name('inscriptions.update-subscription')->middleware('role:superAdmin');

            // API pour les parents dans les inscriptions
            Route::get('/api/parents/search', [ESBTPInscriptionApiController::class, 'searchParents'])->name('api.parents.search');

            // Route pour récupérer les frais par classe
            Route::get('/inscriptions/frais-by-classe/{classeId}', [ESBTPInscriptionApiController::class, 'getFraisByClasse'])->name('inscriptions.frais-by-classe');

            // Routes API utilisées par les formulaires

            // Routes pour les notes
            Route::post('notes/save-ajax', [ESBTPNoteController::class, 'saveNoteAjax'])->name('notes.save-ajax');
            Route::post('notes/save-ajax-bulk', [ESBTPNoteController::class, 'saveNotesAjaxBulk'])->name('notes.save-ajax-bulk');
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
                ->middleware(['permission:view_notes|view_grades|create_grade|edit_grades|delete_grades']);
            Route::get('evaluations/{evaluation}/saisie-rapide', [ESBTPNoteController::class, 'saisieRapide'])->name('notes.saisie-rapide');
            Route::get('evaluations/{evaluation}/saisie-rapide/pdf', [ESBTPNoteController::class, 'saisieRapidePDF'])->name('notes.saisie-rapide.pdf');
            Route::get('classes/{classe}/notes/saisie-rapide/pdf', [ESBTPNoteController::class, 'saisieRapideBlankPDF'])->name('notes.saisie-rapide-blank.pdf');
            Route::post('notes/store-batch', [ESBTPNoteController::class, 'enregistrerSaisieRapide'])->name('notes.store-batch');
        });

        // Espace étudiant - routes accessibles pour les étudiants
        Route::middleware(['auth', 'role:etudiant'])->group(function () {
            Route::get('/mon-profil', [ESBTPEtudiantController::class, 'profile'])
                ->name('mon-profil.index')
                ->middleware(['permission:view_own_profile|view_students']);
            Route::put('/mon-profil/update', [ESBTPEtudiantController::class, 'updateProfile'])
                ->name('mon-profil.update');
            Route::put('/mon-profil/password', [ESBTPEtudiantController::class, 'updatePassword'])
                ->name('mon-profil.password.update');

            Route::get('/mes-notes', [ESBTPNoteController::class, 'studentGrades'])
                ->name('mes-notes.index')
                ->middleware(['permission:view_own_grades|view_grades']);

            Route::get('/mon-emploi-temps', [ESBTPEmploiTempsController::class, 'studentTimetable'])
                ->name('mon-emploi-temps.index')
                ->middleware(['permission:view_own_timetable|view_timetables']);

            // Routes pour l'affichage des classes (lecture seule) pour les étudiants
            Route::get('/student-classes', [ESBTPClasseController::class, 'index'])
                ->name('student.classes.index')
                ->middleware(['permission:view_classes|view classes']);
            Route::get('/student-classes/{classe}', [ESBTPClasseController::class, 'show'])
                ->name('student.classes.show')
                ->middleware(['permission:view_classes|view classes']);

            Route::get('/mon-bulletin', [ESBTPStudentBulletinController::class, 'studentBulletins'])
                ->name('mon-bulletin.index')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            Route::get('/mon-bulletin/{bulletinId}', [ESBTPStudentBulletinController::class, 'showStudentBulletin'])
                ->name('mon-bulletin.show')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            // Route pour accéder à la page des absences
            Route::get('/esbtp/mes-absences', [ESBTPAttendanceController::class, 'studentAttendance'])
                ->name('mes-absences.index')
                ->middleware(['permission:view_own_attendances|view_attendances']);

            // Route pour justifier une absence
            Route::post('/esbtp/mes-absences/{absenceId}/justify', [ESBTPAttendanceController::class, 'justifyAbsence'])
                ->name('mes-absences.justify')
                ->middleware(['permission:view_own_attendances|view_attendances']);

            // Route de debug pour les absences (accessible uniquement en développement)
            Route::get('/mes-absences/debug', [ESBTPAttendanceController::class, 'studentAttendance'])
                ->name('mes-absences.debug')
                ->middleware(['role:etudiant'])
                ->defaults('debug', true);

            Route::get('/mes-evaluations', [ESBTPEvaluationController::class, 'studentEvaluations'])
                ->name('mes-evaluations.index')
                ->middleware(['permission:view_own_exams|view_exams']);

            // Route pour accéder à la page des paiements de l'étudiant
            Route::get('/mes-paiements', [\App\Http\Controllers\ESBTP\MesPaiementsController::class, 'index'])
                ->name('mes-paiements.index')
                ->middleware(['permission:view_own_profile|view_own_notes']);

            // Routes pour les notifications des étudiants
            Route::get('/mes-notifications', [ESBTPNotificationController::class, 'index'])->name('mes-notifications.index');
            Route::post('/mes-notifications/{id}/read', [ESBTPNotificationController::class, 'markAsRead'])
                ->name('mes-notifications.read');
            Route::post('/mes-notifications/mark-all-read', [ESBTPNotificationController::class, 'markAllAsRead'])
                ->name('mes-notifications.markAllAsRead');
            Route::get('/notifications/unread-count', [ESBTPNotificationController::class, 'getUnreadCount'])
                ->name('notifications.unreadCount');

            // Routes pour les messages des étudiants
            Route::get('/mes-messages', [ESBTPAnnonceController::class, 'studentMessages'])
                ->name('mes-messages.index');
            Route::post('/mes-messages/{id}/read', [ESBTPAnnonceController::class, 'markAsRead'])
                ->name('mes-messages.read');
            Route::post('/mes-messages/mark-all-read', [ESBTPAnnonceController::class, 'markAllAsRead'])
                ->name('mes-messages.mark-all-read');
        });

        // Routes pour la suppression de ressources (protégées par permissions spécifiques)
        Route::middleware(['auth'])->group(function () {
            // Suppression d'étudiants
            Route::delete('/etudiants/{etudiant}', [ESBTPEtudiantController::class, 'destroy'])->name('etudiants.destroy')
                ->middleware(['permission:delete_students']);

            // Suppression de bulletins
            Route::delete('bulletins/{bulletin}', [ESBTPBulletinController::class, 'destroy'])->name('bulletins.destroy')
                ->middleware(['permission:edit_bulletins']);

            // Route de suppression des emplois du temps - Handled by resource route
        });

        // Teachers routes
        Route::resource('teachers', TeacherAdminController::class);
        Route::put('teachers/{id}/restore', [TeacherAdminController::class, 'restore'])->name('teachers.restore');
        Route::resource('specialties', ESBTPSpecialtyController::class);
        Route::put('specialties/{id}/restore', [ESBTPSpecialtyController::class, 'restore'])->name('specialties.restore');
        Route::resource('continuing-education', ESBTPContinuingEducationController::class);
        Route::put('continuing-education/{id}/restore', [ESBTPContinuingEducationController::class, 'restore'])->name('continuing-education.restore');
        Route::put('students/{id}/restore', [ESBTPStudentController::class, 'restore'])->name('students.restore');
    });

    // Routes pour les paramètres et les rôles
    Route::middleware(['auth', 'permission:manage_system'])->group(function () {
        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');

        Route::get('/roles', function () {
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get();

            return view('admin.roles.index', compact('roles'));
        })->name('roles.index');
    });

    // Routes pour le rôle parent
    Route::middleware(['auth', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ESBTP\ParentController::class, 'dashboard'])->name('dashboard');
        Route::get('/etudiant/{id}', [App\Http\Controllers\ESBTP\ParentController::class, 'showStudent'])->name('student.show');

        // Notifications
        Route::get('/notifications', [ParentNotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/{id}', [ParentNotificationController::class, 'show'])->name('notifications.show');
        Route::get('/notifications/{id}/read', [ParentNotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::get('/notifications/mark-all-as-read', [ParentNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        // Messages
        Route::get('/messages', [ParentMessageController::class, 'index'])->name('messages');
        Route::get('/messages/create', [ParentMessageController::class, 'create'])->name('messages.create');
        Route::post('/messages', [ParentMessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/{id}', [ParentMessageController::class, 'show'])->name('messages.show');
        Route::get('/messages/{id}/reply', [ParentMessageController::class, 'reply'])->name('messages.reply');
        Route::post('/messages/{id}/reply', [ParentMessageController::class, 'storeReply'])->name('messages.store-reply');
        Route::get('/messages/{id}/read', [ParentMessageController::class, 'markAsRead'])->name('messages.read');
        Route::get('/messages/mark-all-as-read', [ParentMessageController::class, 'markAllAsRead'])->name('messages.mark-all-read');

        // Paiements
        Route::get('/paiements', [ParentPaymentController::class, 'index'])->name('payments');
        Route::get('/paiements/etudiant/{id}', [ParentPaymentController::class, 'studentHistory'])->name('payments.student');
        Route::get('/paiements/{id}', [ParentPaymentController::class, 'show'])->name('payments.show');
        Route::get('/paiements/{id}/recu', [ParentPaymentController::class, 'downloadReceipt'])->name('payments.download-receipt');
        Route::get('/paiements/nouveau', [ParentPaymentController::class, 'create'])->name('payments.create');
        Route::post('/paiements', [ParentPaymentController::class, 'store'])->name('payments.store');

        // Absences
        Route::get('/absences/resume', [App\Http\Controllers\ESBTP\ParentAbsenceController::class, 'summary'])->name('absences.summary');
        Route::get('/absences/etudiant/{etudiant_id}', [App\Http\Controllers\ESBTP\ParentAbsenceController::class, 'index'])->name('absences.index');
        Route::get('/absences/etudiant/{etudiant_id}/absence/{absence_id}', [App\Http\Controllers\ESBTP\ParentAbsenceController::class, 'show'])->name('absences.show');
        Route::get('/absences/etudiant/{etudiant_id}/absence/{absence_id}/justifier', [App\Http\Controllers\ESBTP\ParentAbsenceController::class, 'edit'])->name('absences.edit');
        Route::post('/absences/etudiant/{etudiant_id}/absence/{absence_id}/justifier', [App\Http\Controllers\ESBTP\ParentAbsenceController::class, 'update'])->name('absences.update');

        // Bulletins - nouvelles routes pour parents
        Route::get('/bulletins', [App\Http\Controllers\ESBTP\ParentController::class, 'bulletins'])->name('bulletins.index')
            ->middleware(['permission:view children bulletins']);
        Route::get('/bulletins/etudiant/{id}', [App\Http\Controllers\ESBTP\ParentController::class, 'showStudentBulletins'])->name('bulletins.student');
        Route::get('/bulletins/{id}', [App\Http\Controllers\ESBTP\ParentController::class, 'show'])->name('bulletins.show')
            ->middleware(['permission:view children bulletins']);
        Route::get('/bulletins/{id}/pdf', [App\Http\Controllers\ESBTP\ParentController::class, 'downloadPdf'])->name('bulletins.pdf');

        // Paramètres du compte
        Route::get('/settings', [ParentSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/profile', [ParentSettingsController::class, 'updateProfile'])->name('settings.update');
        Route::put('/settings/password', [ParentSettingsController::class, 'updatePassword'])->name('settings.password.update');
        Route::put('/settings/notifications', [ParentSettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        Route::put('/settings/photo', [ParentSettingsController::class, 'updatePhoto'])->name('settings.photo.update');
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
    Route::prefix('esbtp')->middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
        Route::get('/progression', [StudentProgressionController::class, 'index'])->name('esbtp.progression.index');
        Route::get('/api/progression/recommendations/{classe}/{annee}', [StudentProgressionController::class, 'getRecommendations'])->name('esbtp.progression.recommendations');
        Route::post('/api/progression/process', [StudentProgressionController::class, 'processProgression'])->name('esbtp.progression.process');

        // ESBTP Settings Routes (require manage_system)
        Route::middleware(['permission:manage_system'])->group(function () {
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

        // ESBTP Parents Search (pour modal de sélection dans edit étudiant)
        Route::get('/parents/search', [App\Http\Controllers\ESBTP\ParentController::class, 'search'])->name('esbtp.parents.search');
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

        Route::get('/bulletin-style', [\App\Http\Controllers\ESBTP\ServiceTechniqueBulletinStyleController::class, 'index'])
            ->name('bulletin-style.index');
        Route::post('/bulletin-style', [\App\Http\Controllers\ESBTP\ServiceTechniqueBulletinStyleController::class, 'update'])
            ->name('bulletin-style.update');

    });

    // Endpoints matricule utilisés par les inscriptions
    Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
        Route::get('/matricule-config/mode-info', [ESBTPMatriculeConfigController::class, 'getModeInfo'])->name('matricule-config.mode-info');
        Route::post('/matricule-config/generate', [ESBTPMatriculeConfigController::class, 'genererMatricule'])->name('matricule-config.generate');
        Route::post('/matricule-config/check', [ESBTPMatriculeConfigController::class, 'checkMatricule'])->name('matricule-config.check');
    });

    // Routes pour la configuration du paywall - Service Technique ADC seulement
    Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'paywall'])->group(function () {
        Route::get('/paywall-config', [ESBTPPaywallConfigController::class, 'index'])->name('paywall-config.index');
        Route::get('/paywall-config/blocked', [ESBTPPaywallConfigController::class, 'blocked'])->name('paywall-config.blocked');
        Route::get('/paywall-config/upgrade', [ESBTPPaywallConfigController::class, 'upgrade'])->name('paywall-config.upgrade');
        Route::post('/paywall-config', [ESBTPPaywallConfigController::class, 'store'])->name('paywall-config.store');
        Route::post('/paywall-config/extend', [ESBTPPaywallConfigController::class, 'extendSubscription'])->name('paywall-config.extend');
        Route::post('/paywall-config/generate-emergency', [ESBTPPaywallConfigController::class, 'generateEmergencyCode'])->name('paywall-config.generate-emergency');
        Route::get('/paywall-config/status', [ESBTPPaywallConfigController::class, 'checkStatus'])->name('paywall-config.status');
    });

    // Routes pour l'émargement - Administration
    Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'permission:generate-attendance-codes'])->group(function () {
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
        Route::get('/', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'index'])->name('index')->middleware('permission:view_own_attendance');
        Route::get('/history', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'history'])->name('history')->middleware('permission:view_own_attendance');
        Route::post('/sign', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'sign'])->name('sign')->middleware('permission:sign_attendance');
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

Route::prefix('esbtp/api')->name('esbtp.api.')->middleware(['auth'])->group(function () {
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
    ->middleware(['auth', 'permission:edit_timetables']);

// Route for setting a timetable as current
Route::post('esbtp/emploi-temps/{id}/set-current', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'setCurrent'])
    ->name('esbtp.emploi-temps.set-current')
    ->middleware(['auth', 'permission:edit_timetables']);

// Routes pour les évaluations
Route::prefix('esbtp/evaluations')->name('esbtp.evaluations.')->middleware(['auth'])->group(function () {
    Route::get('/', [ESBTPEvaluationController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPEvaluationController::class, 'create'])->name('create');
    Route::post('/', [ESBTPEvaluationController::class, 'store'])->name('store');

    // AJAX: Charger matières disponibles pour une classe (via combinaisons globales)
    // IMPORTANT: Cette route doit être AVANT les routes /{evaluation} pour éviter les conflits
    Route::get('/load-matieres', [ESBTPEvaluationController::class, 'loadMatieres'])->name('load-matieres');
    Route::get('/coefficients/modal', [ESBTPEvaluationController::class, 'coefficientsModal'])->name('coefficients.modal');
    Route::post('/coefficients/update', [ESBTPEvaluationController::class, 'updateCoefficients'])->name('coefficients.update');
    Route::get('/coefficients/check', [ESBTPEvaluationController::class, 'checkCoefficient'])->name('coefficients.check');

    Route::get('/{evaluation}/refresh-row', [ESBTPEvaluationController::class, 'refreshRow'])->name('refresh-row');
    Route::patch('/{evaluation}/cancel', [ESBTPEvaluationController::class, 'cancel'])->name('cancel');
    Route::patch('/{evaluation}/restore', [ESBTPEvaluationController::class, 'restore'])->name('restore');
    Route::get('/{evaluation}', [ESBTPEvaluationController::class, 'show'])->name('show');
    Route::get('/{evaluation}/edit', [ESBTPEvaluationController::class, 'edit'])->name('edit');
    Route::put('/{evaluation}', [ESBTPEvaluationController::class, 'update'])->name('update');
    Route::delete('/{evaluation}', [ESBTPEvaluationController::class, 'destroy'])->name('destroy');
    Route::patch('/{evaluation}/toggle-published', [ESBTPEvaluationController::class, 'togglePublished'])->name('toggle-published');
    Route::patch('/{evaluation}/toggle-notes-published', [ESBTPEvaluationController::class, 'toggleNotesPublished'])->name('toggle-notes-published');
    Route::patch('/{evaluation}/update-status', [ESBTPEvaluationController::class, 'updateStatus'])->name('update-status');
    Route::get('/{evaluation}/pdf', [ESBTPEvaluationController::class, 'generatePdf'])->name('pdf');
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
Route::get('/esbtp/bulletins', [ESBTPBulletinController::class, 'index'])->name('esbtp.bulletins.index')->middleware(['auth']);

// Route spéciale pour la sélection des bulletins
Route::get('/esbtp/bulletins/select', [ESBTPBulletinController::class, 'select'])
    ->name('esbtp.bulletins.select')
    ->middleware(['auth']);

// Route pour télécharger un bulletin au format PDF
Route::get('/esbtp/bulletins/{bulletin}/download', [ESBTPBulletinController::class, 'genererPDF'])->name('esbtp.bulletins.download')->middleware(['auth']);

// Routes pour la gestion des secrétaires
Route::prefix('secretaires')->name('secretaires.')->middleware(['auth', 'permission:manage_system'])->group(function () {
    Route::get('/', [ESBTPSecretaireController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPSecretaireController::class, 'create'])->name('create');
    Route::post('/', [ESBTPSecretaireController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ESBTPSecretaireController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ESBTPSecretaireController::class, 'update'])->name('update');
    Route::delete('/{id}', [ESBTPSecretaireController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/toggle-status', [ESBTPSecretaireController::class, 'toggleStatus'])->name('toggle-status');
});

// Routes pour la gestion des enseignants
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'permission:access_admin', 'permission:module.enseignants.access'])->group(function () {
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
    Route::get('enseignants/{enseignant}/debug-result', [ESBTPEnseignantController::class, 'debugResult'])->name('enseignants.debug-result');
    Route::post('enseignants/{enseignant}/reset-password', [ESBTPEnseignantController::class, 'resetPassword'])->name('enseignants.reset-password');
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
Route::middleware(['auth', 'comptabilite.access'])->prefix('esbtp/comptabilite')->name('esbtp.comptabilite.')->group(function () {
    // Dashboard comptabilité
    Route::get('/', [ESBTPComptabiliteController::class, 'index'])->name('index');

    // KPIs temps réel
    Route::get('/kpis-temps-reel', [ESBTPComptabiliteController::class, 'kpisTempsReel'])->name('kpis-temps-reel');

    // Paiements
    // Gestion des paiements
    Route::get('/paiements', [ESBTPComptabilitePaiementController::class, 'paiements'])->name('paiements');
    Route::get('/paiements/create', [ESBTPComptabilitePaiementController::class, 'createPaiement'])->name('paiements.create');
    Route::post('/paiements', [ESBTPComptabilitePaiementController::class, 'storePaiement'])->name('paiements.store');
    Route::get('/paiements/{id}', [ESBTPComptabilitePaiementController::class, 'showPaiement'])->name('paiements.show');
    Route::get('/paiements/{id}/edit', [ESBTPComptabilitePaiementController::class, 'editPaiement'])->name('paiements.edit');
    Route::put('/paiements/{id}', [ESBTPComptabilitePaiementController::class, 'updatePaiement'])->name('paiements.update');
    Route::post('/paiements/{id}/valider', [ESBTPComptabilitePaiementController::class, 'validerPaiement'])->name('paiements.valider');
    Route::post('/paiements/{id}/rejeter', [ESBTPComptabilitePaiementController::class, 'rejeterPaiement'])->name('paiements.rejeter');
    Route::get('/paiements/{id}/recu', [ESBTPComptabilitePaiementController::class, 'genererRecu'])->name('paiements.recu');

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

    // Tableau de bord et rapports financiers
    Route::get('/rapports', [ESBTPComptabiliteReportController::class, 'rapports'])->name('rapports');
    Route::get('/rapports/generate', [ESBTPComptabiliteReportController::class, 'generateReport'])->name('rapports.generate');
    Route::post('/rapports/export', [ESBTPComptabiliteReportController::class, 'exportReport'])->name('rapports.export');

    // Routes avancées pour le générateur de rapports - Task #6
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/builder', [ESBTPComptabiliteController::class, 'rapportsAvances'])->name('builder')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/generer', [ESBTPComptabiliteAnalyticsController::class, 'genererRapportPersonnalise'])->name('generer')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:10,1']);

        Route::post('/schedule', [ESBTPComptabiliteAnalyticsController::class, 'programmerRapport'])->name('schedule')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:5,1']);

        Route::get('/scheduled', [ESBTPComptabiliteAnalyticsController::class, 'listeRapportsProgrammes'])->name('scheduled')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/analytics/predictive', [ESBTPComptabiliteAnalyticsController::class, 'analysesPredictives'])->name('analytics.predictive')
            ->middleware(['permission:comptabilite.dashboard.view', 'throttle:20,1']);

        Route::get('/analytics/cashflow', [ESBTPComptabiliteAnalyticsController::class, 'projectionCashFlow'])->name('analytics.cashflow')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/analytics/anomalies', [ESBTPComptabiliteAnalyticsController::class, 'detectionAnomalies'])->name('analytics.anomalies')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/templates', [ESBTPComptabiliteAnalyticsController::class, 'modelesRapports'])->name('templates')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/templates', [ESBTPComptabiliteAnalyticsController::class, 'sauvegarderModele'])->name('templates.save')
            ->middleware(['permission:comptabilite.config.manage']);
    });

    // NOUVELLES ROUTES ANALYTICS PRÉDICTIFS - Tâche #11
    Route::prefix('analytics-predictifs')->name('analytics-predictifs.')->group(function () {
        Route::get('/', [ESBTPComptabiliteAnalyticsController::class, 'analyticsPredictifs'])->name('index')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/recommandations', [ESBTPComptabiliteAnalyticsController::class, 'recommandationsIntelligentes'])->name('recommandations')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/benchmarking', [ESBTPComptabiliteAnalyticsController::class, 'benchmarkingAvance'])->name('benchmarking')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/visualisations', [ESBTPComptabiliteAnalyticsController::class, 'visualisationsAvancees'])->name('visualisations')
            ->middleware(['permission:comptabilite.dashboard.view']);

        // API pour les données en temps réel
        Route::get('/api/data', [ESBTPComptabiliteAnalyticsController::class, 'apiAnalyticsPredictifs'])->name('api.data')
            ->middleware(['permission:comptabilite.dashboard.view', 'throttle:60,1']);
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
        Route::get('/{id}', [ESBTPComptabiliteRelanceController::class, 'showRelance'])->name('show')->where('id', '[0-9]+');

        // Actions sur les relances
        Route::post('/planifier', [ESBTPComptabiliteRelanceController::class, 'planifierRelances'])->name('planifier')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:10,1']);
        Route::post('/{id}/renvoyer', [ESBTPComptabiliteRelanceController::class, 'renvoyerRelance'])->name('renvoyer')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:30,1']);
        Route::post('/executer', [ESBTPComptabiliteRelanceController::class, 'executerRelances'])->name('executer')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:5,1']);

        // Configuration
        Route::post('/config/templates', [ESBTPComptabiliteRelanceController::class, 'sauvegarderTemplates'])->name('config.templates');
        Route::post('/config/parametres', [ESBTPComptabiliteRelanceController::class, 'sauvegarderParametres'])->name('config.parametres');
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
    Route::middleware(['permission:generate-attendance-codes', 'paywall'])->group(function () {
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
    Route::middleware(['permission:manage_system'])->group(function () {
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
        ->middleware(['auth', 'permission:view_attendances']);

    Route::get('teacher-attendance/teacher/{teacher}', [TeacherAttendanceController::class, 'teacherReport'])
        ->name('teacher-attendance.teacher-report')
        ->middleware(['auth', 'permission:view_attendances']);

    // Routes AJAX pour update statut et refresh ligne (coordinateur/admin)
    Route::post('teacher-attendance/seance/{seance}/update-status', [ESBTPTeacherAttendanceController::class, 'updateStatus'])
        ->name('esbtp.teacher-attendance.update-status')
        ->middleware(['auth', 'permission:edit_attendances']);
    Route::get('teacher-attendance/seance/{seance}/refresh-ligne', [ESBTPTeacherAttendanceController::class, 'refreshSeanceLigne'])
        ->name('esbtp.teacher-attendance.refresh-ligne')
        ->middleware(['auth', 'permission:edit_attendances']);
    Route::post('teacher-attendance/bulk-update-status', [ESBTPTeacherAttendanceController::class, 'bulkUpdateStatus'])
        ->name('esbtp.teacher-attendance.bulk-update-status')
        ->middleware(['auth', 'permission:edit_attendances']);

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
Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'permission:generate-attendance-codes'])->group(function () {
    Route::get('/forgotten-codes', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'index'])
        ->name('forgotten-codes');
    Route::post('/generate-manual-code', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'generateManualCode'])
        ->name('generate-manual-code');
    Route::post('/mark-manual', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'markManualAttendance'])
        ->name('mark-manual');
});

// Manual Attendance Routes
Route::prefix('esbtp/admin/attendance/manual')->name('esbtp.admin.attendance.manual.')->middleware(['auth', 'permission:access_admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'index'])
        ->name('index');
    Route::post('/store', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'store'])
        ->name('store');
    Route::post('/bulk', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'bulkStore'])
        ->name('bulk');
});

// Routes pour les paramètres système ESBTP (manage_system)
Route::middleware(['auth', 'permission:manage_system'])->group(function () {
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
Route::middleware(['auth', 'permission:access_admin', 'paywall'])->group(function () {
    // AJAX pour charger toutes les inscriptions d'un étudiant
    Route::get('esbtp/etudiants/{etudiant}/all-inscriptions', [ESBTPStudentController::class, 'getAllInscriptions'])
        ->name('esbtp.etudiants.all-inscriptions')
        ->middleware('permission:view_students');

    // Export étudiants
    Route::get('esbtp/etudiants-export/excel', [ESBTPStudentController::class, 'exportExcel'])
        ->name('esbtp.etudiants.export.excel')
        ->middleware('permission:view_students');
    Route::get('esbtp/etudiants-export/pdf', [ESBTPStudentController::class, 'exportPdf'])
        ->name('esbtp.etudiants.export.pdf')
        ->middleware('permission:view_students');

    // Resource CRUD
    Route::resource('esbtp/etudiants', ESBTPStudentController::class, ['as' => 'esbtp'])
        ->parameters(['etudiants' => 'etudiant'])
        ->middleware('permission:view_students');
    Route::post('esbtp/etudiants/{id}/restore', [ESBTPStudentController::class, 'restore'])
        ->name('esbtp.etudiants.restore')
        ->middleware('permission:edit_students');
    Route::post('esbtp/etudiants/{etudiant}/update-photo', [ESBTPEtudiantController::class, 'updatePhoto'])
        ->name('esbtp.etudiants.update-photo')
        ->middleware('permission:edit_students');
    Route::post('esbtp/etudiants/{etudiant}/documents', [ESBTPEtudiantController::class, 'storeDocument'])
        ->name('esbtp.etudiants.documents.store')
        ->middleware('permission:edit_students');
    Route::get('esbtp/etudiants/{etudiant}/documents/{document}/download', [ESBTPEtudiantController::class, 'downloadDocument'])
        ->name('esbtp.etudiants.documents.download')
        ->middleware('permission:view_students');
    Route::delete('esbtp/etudiants/{etudiant}/documents/{document}', [ESBTPEtudiantController::class, 'destroyDocument'])
        ->name('esbtp.etudiants.documents.destroy')
        ->middleware('permission:edit_students');
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

// Route de diagnostic temporaire (à supprimer après résolution)
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

// Routes spéciales pour le workflow des bulletins — PROTÉGÉES
Route::middleware(['auth', 'permission:access_admin'])->group(function () {
    Route::get('/esbtp-special/bulletins-pdf', [ESBTPBulletinController::class, 'genererPDFParParamsUnified'])->name('esbtp.bulletins.pdf-params');
    Route::get('/esbtp-special/bulletins-check', [ESBTPBulletinController::class, 'checkBulletinPrerequisites'])->name('esbtp.bulletins.check-prerequisites');
    Route::get('/esbtp/bulletins/preview', [ESBTPBulletinController::class, 'previewBulletin'])->name('esbtp.bulletins.preview');
    Route::post('/esbtp/bulletins/generer-classe', [ESBTPBulletinController::class, 'genererClasseBulletins'])->name('esbtp.bulletins.generer-classe');

    // Routes spéciales moyennes
    Route::get('/esbtp-special/bulletins/moyennes-preview', [ESBTPResultatController::class, 'previewMoyennes'])->name('esbtp.bulletins.moyennes-preview');
    Route::post('/esbtp-special/bulletins/moyennes-update', [ESBTPResultatController::class, 'updateMoyennes'])->name('esbtp.bulletins.moyennes-update');
    Route::delete('/esbtp-special/bulletins/moyennes-delete', [ESBTPResultatController::class, 'deleteMoyenne'])->name('esbtp.bulletins.moyennes-delete');

    // Configuration matières, professeurs, absences bulletins
    Route::get('/esbtp-special/bulletins/config-matieres', [ESBTPBulletinConfigController::class, 'configMatieresTypeFormation'])->name('esbtp.bulletins.config-matieres');
    Route::post('/esbtp-special/bulletins/save-config-matieres', [ESBTPBulletinConfigController::class, 'saveConfigMatieresTypeFormation'])->name('esbtp.bulletins.save-config-matieres');
    Route::get('/esbtp-special/bulletins/edit-professeurs', [ESBTPBulletinConfigController::class, 'editProfesseurs'])->name('esbtp.bulletins.edit-professeurs');
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
    Route::get('/esbtp/classes/{classe}/liste-appel', [ESBTPClasseController::class, 'listeAppel'])->name('esbtp.classes.liste-appel');
    Route::get('/esbtp/classes/{classe}/liste-appel/pdf', [ESBTPClasseController::class, 'listeAppelPDF'])->name('esbtp.classes.liste-appel.pdf');
    Route::get('/esbtp/classes/{classe}/liste-complete', [ESBTPClasseController::class, 'listeComplete'])->name('esbtp.classes.liste-complete');
    Route::get('/esbtp/classes/{classe}/liste-complete/pdf', [ESBTPClasseController::class, 'listeCompletePDF'])->name('esbtp.classes.liste-complete.pdf');
    Route::get('/esbtp/classes/{classe}/liste-complete/excel', [ESBTPClasseController::class, 'listeCompleteExcel'])->name('esbtp.classes.liste-complete.excel');
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
    // Test des paramètres de bulletin
    Route::get('/test-bulletin-parameters', [ESBTPBulletinController::class, 'testBulletinParameters'])
        ->name('test.bulletin.parameters');

    // Génération de bulletin configurable
    Route::post('/bulletin/configurable/generate', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])
        ->name('bulletin.configurable.generate');

    // Prévisualisation de bulletin configurable
    Route::get('/bulletin/configurable/preview', [ESBTPBulletinController::class, 'previewConfigurableBulletin'])
        ->name('bulletin.configurable.preview');

    // Interface de test pour bulletin configurable
    Route::get('/bulletin/configurable/test', function () {
        return view('esbtp.bulletins.test-configurable');
    })->name('bulletin.configurable.test');
});

// ... existing code ...

// Routes ESBTP Audit et Sécurité (Task #10) - COMMENTED OUT TEMPORARILY - CONTROLLERS NOT IMPLEMENTED YET
/*
Route::middleware(['auth', 'throttle:audit'])->prefix('esbtp/audit')->name('esbtp.audit.')->group(function () {
    // Page principale d'audit
    Route::get('/', [ESBTPAuditController::class, 'index'])->name('index');

    // Données d'audit via AJAX (avec rate limiting strict)
    Route::get('/data', [ESBTPAuditController::class, 'getAuditData'])
        ->middleware('throttle:30,1')
        ->name('data');

    // Détails d'un audit spécifique
    Route::get('/{id}', [ESBTPAuditController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('show');

    // Audits spécifiques à la comptabilité
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
});

// Routes de sécurité avancées (Task #10)
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
Route::middleware(['auth', 'permission:manage_system', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::get('/comptables', [\App\Http\Controllers\ESBTPComptableController::class, 'index'])->name('comptables.index');
    Route::get('/comptables/create', [\App\Http\Controllers\ESBTPComptableController::class, 'create'])->name('comptables.create');
    Route::post('/comptables', [\App\Http\Controllers\ESBTPComptableController::class, 'store'])->name('comptables.store');
    Route::get('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'show'])->name('comptables.show');
    Route::put('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'update'])->name('comptables.update');
    Route::post('/comptables/{user}/toggle-status', [\App\Http\Controllers\ESBTPComptableController::class, 'toggleStatus'])->name('comptables.toggle-status');
    Route::delete('/comptables/{user}', [\App\Http\Controllers\ESBTPComptableController::class, 'destroy'])->name('comptables.destroy');

    // Caissier
    Route::get('/caissiers/create', [\App\Http\Controllers\ESBTPComptableController::class, 'createCaissier'])->name('caissiers.create');
    Route::post('/caissiers', [\App\Http\Controllers\ESBTPComptableController::class, 'storeCaissier'])->name('caissiers.store');
});

// Routes pour la gestion du personnel avec sliders
Route::middleware(['auth', 'permission:access_admin', 'paywall'])->prefix('esbtp')->name('esbtp.')->group(function () {
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

    // Page unifiée pour la gestion du personnel
    Route::get('/personnel/unified', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'index'])->name('personnel.unified.index');
    Route::get('/personnel/unified/data', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'getData'])->name('personnel.unified.data');
    Route::get('/personnel/unified/stats', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'getStats'])->name('personnel.unified.stats');
    Route::post('/personnel/unified', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'store'])->name('personnel.unified.store');
    Route::put('/personnel/unified/{type}/{id}', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'update'])->name('personnel.unified.update');
    Route::delete('/personnel/unified/{type}/{id}', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'destroy'])->name('personnel.unified.destroy');
    Route::patch('/personnel/unified/{type}/{id}/toggle-status', [\App\Http\Controllers\ESBTPPersonnelUnifiedController::class, 'toggleStatus'])->name('personnel.unified.toggle-status');

    // Routes pour les coordinateurs (maintien de la compatibilité)
    Route::resource('coordinateurs', \App\Http\Controllers\ESBTPCoordinateurController::class);
    Route::patch('coordinateurs/{coordinateur}/toggle-status', [\App\Http\Controllers\ESBTPCoordinateurController::class, 'toggleStatus'])->name('coordinateurs.toggle-status');
    Route::post('coordinateurs/{coordinateur}/reset-password', [\App\Http\Controllers\ESBTPCoordinateurController::class, 'resetPassword'])->name('coordinateurs.reset-password');
});

// Routes pour les coordinateurs et rôles admin avec permissions spécifiques
Route::middleware(['auth', 'permission:access_admin'])->prefix('esbtp')->name('esbtp.')->group(function () {
    // Routes pour les notes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPNoteController::class, 'index'])->name('index')
            ->middleware('permission:view_notes');
        Route::get('/create', [\App\Http\Controllers\ESBTPNoteController::class, 'create'])->name('create')
            ->middleware('permission:create_grade');
        Route::post('/', [\App\Http\Controllers\ESBTPNoteController::class, 'store'])->name('store')
            ->middleware('permission:create_grade');
        Route::get('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'show'])->name('show')
            ->middleware('permission:view_notes');
        Route::get('/{note}/edit', [\App\Http\Controllers\ESBTPNoteController::class, 'edit'])->name('edit')
            ->middleware('permission:edit_grades');
        Route::put('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'update'])->name('update')
            ->middleware('permission:edit_grades');
        Route::delete('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'destroy'])->name('destroy')
            ->middleware('permission:delete_grades');
        // saisie-rapide already defined in enseignant|coordinateur group (line 1347)
        
        // API routes for new notes system
        Route::get('/api/evaluations/by-class-matiere/{classId}/{matiereId}', [\App\Http\Controllers\ESBTPEvaluationController::class, 'byClassMatiere'])
            ->name('evaluations.by-class-matiere');
        Route::get('/api/classes/{classe}/students', [\App\Http\Controllers\ESBTPClasseController::class, 'students'])
            ->name('classes.students');
    });

    // Routes pour les annonces - REMOVED (déjà définies ligne 617 dans le groupe esbtp)

    // Routes pour l'emploi du temps (déjà accessible via permissions existantes)
    Route::get('/emploi-temps', [\App\Http\Controllers\ESBTPEmploiTempsController::class, 'index'])->name('emploi-temps.index')
        ->middleware('permission:view_timetables');
    Route::get('/emploi-temps/{emploi_temp}', [\App\Http\Controllers\ESBTPEmploiTempsController::class, 'show'])->name('emploi-temps.show')
        ->middleware('permission:view_timetables');

    // Routes pour les présences (attendances)
    Route::get('/attendances', [\App\Http\Controllers\ESBTPAttendanceController::class, 'index'])->name('attendances.index')
        ->middleware('permission:view_attendances');
    Route::get('/attendances/{attendance}', [\App\Http\Controllers\ESBTPAttendanceController::class, 'show'])->name('attendances.show')
        ->middleware('permission:view_attendances');

    // Routes pour le planning général coordinateur
    Route::get('/planning-general', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'index'])->name('planning-general.index')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/coordinateur', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'coordinateur'])->name('planning-general.coordinateur')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/repartition-matieres', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'repartitionMatieres'])->name('planning-general.repartition-matieres')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/annuel', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'annuel'])->name('planning-general.annuel')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/impact-emargements', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'impactEmargements'])->name('planning-general.impact-emargements')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/emargement', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'emargement'])->name('planning-general.emargement')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::post('/planning-general/emargement/generer-code', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'genererCodeEmargement'])->name('planning-general.generer-code-emargement')
        ->middleware('permission:manage-planning|view-all-timetables');

    // Routes AJAX pour la configuration des volumes horaires
    Route::get('/planning-general/get-matieres-configuration', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'getMatieresPourConfiguration'])
        ->name('planning-general.get-matieres-configuration')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::post('/planning-general/save-volume-configuration', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'saveVolumeConfiguration'])
        ->name('planning-general.save-volume-configuration')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::get('/planning-general/planifications/{planification}/teachers', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'getTeachersForManagement'])
        ->name('planning-general.get-teachers');
    Route::post('/planning-general/planifications/{planification}/manage-teachers', [\App\Http\Controllers\ESBTPPlanningConfigController::class, 'manageTeachers'])
        ->name('planning-general.manage-teachers');
});

// Routes pour les événements académiques
Route::middleware(['auth', 'permission:manage-planning'])->prefix('esbtp')->name('esbtp.')->group(function () {
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
Route::middleware(['auth', 'permission:access_admin'])->prefix('esbtp')->name('esbtp.')->group(function () {
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
Route::prefix('esbtp/lmd')->name('esbtp.lmd.')->middleware(['auth', 'permission:access_admin', 'permission:module.lmd.access', 'paywall'])->group(function () {

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
    Route::post('parcours/{parcours}/sync-ues', [\App\Http\Controllers\ESBTPLMDParcoursDomainController::class, 'syncUes'])->name('parcours.sync-ues');

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
    Route::put('bulletins/{bulletin}/toggle-publication', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'togglePublication'])->name('bulletins.toggle-publication');
    Route::delete('bulletins/{bulletin}', [\App\Http\Controllers\ESBTPLMDBulletinController::class, 'destroy'])->name('bulletins.destroy');
});

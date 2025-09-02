<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ESBTPFiliereController;
use App\Http\Controllers\ESBTPNiveauEtudeController;
use App\Http\Controllers\ESBTPClasseController;
use App\Http\Controllers\ESBTPEtudiantController;
use App\Http\Controllers\ESBTPInscriptionController;
use App\Http\Controllers\ESBTPMatiereController;
use App\Http\Controllers\ESBTPAnneeUniversitaireController;
use App\Http\Controllers\ESBTPEmploiTempsController;
use App\Http\Controllers\ESBTPEvaluationController;
use App\Http\Controllers\ESBTPNoteController;
use App\Http\Controllers\ESBTPBulletinController;
use App\Http\Controllers\ESBTPAnnonceController;
use App\Http\Controllers\ESBTPSeanceCoursController;
use App\Http\Controllers\ESBTPAttendanceController;
use App\Http\Controllers\ESBTPExamenController;
use App\Http\Controllers\ESBTP\TeacherAttendanceController;
use App\Http\Controllers\ESBTP\ESBTPReinscriptionController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\ParentNotificationController;
use App\Http\Controllers\ParentMessageController;
use App\Http\Controllers\ParentPaymentController;
use App\Http\Controllers\ParentSettingsController;
use App\Http\Controllers\ESBTPPaiementController;
use App\Http\Controllers\ESBTPNotificationController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\StudentProgressionController;
use App\Http\Controllers\ESBTPComptabiliteController;
use App\Http\Controllers\ESBTPSecretaireController;
use App\Http\Controllers\ESBTPEnseignantController;
use App\Http\Controllers\DepensesController;
use App\Http\Controllers\ESBTPCategoriePaiementController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\ESBTP\Admin\ESBTPTeacherAttendanceController;
use App\Http\Controllers\ESBTPEnseignantPresenceController;
use App\Http\Controllers\ESBTPAttendanceCodeController;
use App\Http\Controllers\ESBTP\TeacherAttendanceHistoryController;
use App\Http\Controllers\ESBTPCycleController;
use App\Http\Controllers\ESBTPSpecialtyController;
use App\Http\Controllers\ESBTPContinuingEducationController;
use App\Http\Controllers\ESBTPStudentController;
use App\Http\Controllers\ESBTPDepartmentController;
use App\Http\Controllers\ESBTPPlanningGeneralController;
use App\Http\Controllers\ESBTP\Admin\TeacherAdminController;
use App\Http\Controllers\ESBTP\ESBTPSettingsController;
use App\Http\Controllers\ESBTPLogsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NavbarController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\Auth\PasswordChangeController;
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

// Route de debug temporaire pour identifier l'erreur is_current
Route::get('/debug-annees-simple', [ESBTPAnneeUniversitaireController::class, 'debug'])->name('debug-annees-simple');

// Test route for debugging
Route::get('/test-emploi-temps-show', function () {
    $controller = new ESBTPEmploiTempsController();
    $emploiTemps = \App\Models\ESBTPEmploiTemps::find(1);

    if (!$emploiTemps) {
        return response()->json(['error' => 'Emploi du temps not found'], 404);
    }

    return $controller->show($emploiTemps);
});

// Route d'accueil - Modifiée pour utiliser la vue de solution logicielle par défaut
Route::get('/', function () {
    // Charger la vue de solution logicielle comme page d'accueil principale
    return view('welcome-software')->withHeaders([
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
    ]);
})->name('welcome');

// Route pour l'ancienne version de l'école (si nécessaire pour référence)
Route::get('/school', function () {
    return view('welcome-redesign')->withHeaders([
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
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
    Route::get('/settings', function() {
        return view('settings.index');
    })->name('settings.index');
});

// Routes accessibles uniquement après authentification
Route::middleware(['auth', 'installed', 'force.password.change'])->group(function () {
    // Dashboard - Route principale qui redirige vers le tableau de bord approprié selon le rôle
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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

    Route::middleware(['role:teacher'])->group(function () {
        Route::get('/dashboard/teacher', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
        Route::get('/dashboard/teacher/timetable', [TeacherDashboardController::class, 'showTimetable'])->name('teacher.timetable');
        Route::get('/dashboard/teacher/grades', [TeacherDashboardController::class, 'showGrades'])->name('teacher.grades');
        Route::get('/dashboard/teacher/attendance', [TeacherDashboardController::class, 'showAttendance'])->name('teacher.attendance');
        Route::get('/dashboard/teacher/availability', [TeacherDashboardController::class, 'showAvailability'])->name('teacher.availability');
        Route::post('/dashboard/teacher/availability', [TeacherDashboardController::class, 'updateAvailability'])->name('teacher.availability.update');
        Route::get('/dashboard/teacher/roll-call/{seance}', [TeacherDashboardController::class, 'showRollCall'])->name('teacher.roll-call');
        Route::post('/dashboard/teacher/roll-call/{seance}', [TeacherDashboardController::class, 'storeRollCall'])->name('teacher.roll-call.store');
        Route::post('/dashboard/teacher/close-course/{seance}', [TeacherDashboardController::class, 'closeCourse'])->name('teacher.close-course');
        Route::get('/teacher/profile', [TeacherController::class, 'profile'])->name('teacher.profile');
        Route::get('/teacher/select-call-type/{seance}', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'selectCallType'])->name('teacher.select-call-type');
        
        // Routes pour les rapports de séance
        Route::get('/teacher/session-report/create/{seance}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'create'])->name('teacher.session-report.create');
        Route::post('/teacher/session-report/store/{seance}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'store'])->name('teacher.session-report.store');
        Route::get('/teacher/session-reports', [App\Http\Controllers\ESBTP\SessionReportController::class, 'index'])->name('teacher.session-report.index');
        Route::get('/teacher/session-report/{report}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'show'])->name('teacher.session-report.show');
        Route::get('/teacher/session-report/{report}/edit', [App\Http\Controllers\ESBTP\SessionReportController::class, 'edit'])->name('teacher.session-report.edit');
        Route::put('/teacher/session-report/{report}', [App\Http\Controllers\ESBTP\SessionReportController::class, 'update'])->name('teacher.session-report.update');
    });

    // Routes pour la gestion du profil admin
    Route::middleware(['role:superAdmin|secretaire'])->group(function () {
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
        
        // Tableau de bord des présences pour coordinateurs
        Route::get('/coordinateur/attendance-dashboard', [App\Http\Controllers\CoordinateurDashboardController::class, 'attendanceDashboard'])->name('coordinateur.attendance-dashboard');
        Route::get('/coordinateur/recent-activities', [App\Http\Controllers\CoordinateurDashboardController::class, 'getRecentActivities'])->name('coordinateur.recent-activities');
        Route::post('/coordinateur/daily-report', [App\Http\Controllers\CoordinateurDashboardController::class, 'generateDailyReport'])->name('coordinateur.daily-report');
    });

    // Routes pour les fonctionnalités ESBTP
    Route::prefix('esbtp')->name('esbtp.')->group(function () {
        // Routes protégées pour les super-administrateurs, secrétaires et coordinateurs
        Route::middleware(['auth', 'role:superAdmin|secretaire|coordinateur'])->group(function () {
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
            Route::post('inscriptions/{inscription}/subscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionController::class, 'subscribeToOptionalFee'])->name('inscriptions.subscribe-optional-fee');
            Route::post('inscriptions/{inscription}/unsubscribe-optional-fee', [\App\Http\Controllers\ESBTPInscriptionController::class, 'unsubscribeFromOptionalFee'])->name('inscriptions.unsubscribe-optional-fee');

            // Routes pour les certificats de scolarité
            Route::get('/etudiants/{etudiant}/certificat-preview', [ESBTPEtudiantController::class, 'previewCertificat'])
                ->name('etudiants.certificat.preview')
                ->middleware(['permission:view_students']);
            Route::get('/etudiants/{etudiant}/certificat', [ESBTPEtudiantController::class, 'genererCertificat'])
                ->name('etudiants.certificat')
                ->middleware(['permission:view_students']);

            // Routes pour les rôles et permissions
            Route::resource('roles', \App\Http\Controllers\ESBTP\RoleController::class);

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
                    'destroy' => 'classes.destroy'
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
                
                // Routes avec paramètres à la FIN
                Route::get('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'show'])->name('show');
                Route::put('{etudiant}', [\App\Http\Controllers\ESBTP\ESBTPReinscriptionController::class, 'update'])->name('update');
            });

            // Routes pour les séances de cours (accessible aux coordinateurs)
            Route::resource('seances-cours', ESBTPSeanceCoursController::class)
                ->parameters(['seances-cours' => 'seancesCour']);
        });

        // Routes accessibles aux superAdmin et secrétaires
        Route::middleware(['auth', 'role:superAdmin|secretaire'])->group(function () {
            // Routes pour les classes ESBTP - index et show avec permission view_classes
            Route::get('classes', [ESBTPClasseController::class, 'index'])
                ->name('classes.index')
                ->middleware(['permission:view_classes|view classes']);

            Route::get('classes/{classe}', [ESBTPClasseController::class, 'show'])
                ->name('classes.show')
                ->middleware(['permission:view_classes|view classes']);

            // Routes de l'API pour récupérer les matières d'une classe - accessible aux superAdmin et secrétaires
            Route::get('classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieres'])
                ->name('classes.matieres')
                ->middleware(['permission:view_classes|view classes']);

            
            // Route pour vérifier les places disponibles dans une classe
            Route::get('classes/{id}/available-places', [ESBTPEtudiantController::class, 'getAvailablePlaces'])
                ->name('classes.available-places')
                ->middleware(['permission:view_classes|view classes']);
            // Routes pour les matières
            Route::name('matieres.')->prefix('matieres')->group(function () {
                Route::get('/json', [ESBTPMatiereController::class, 'getMatieresJson'])
                    ->name('json')
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
                    'destroy' => 'matieres.destroy'
                ])
                ->middleware(['role:superAdmin|secretaire|teacher']);

            // Routes pour les emplois du temps ESBTP

            // Routes pour les emplois du temps ESBTP
            Route::resource('emploi-temps', ESBTPEmploiTempsController::class)
                ->parameters(['emploi-temps' => 'emploi_temp'])
                ->names([
                    'index' => 'emploi-temps.index',
                    'create' => 'emploi-temps.create',
                    'store' => 'emploi-temps.store',
                    'show' => 'emploi-temps.show',
                    'edit' => 'emploi-temps.edit',
                    'update' => 'emploi-temps.update',
                    'destroy' => 'emploi-temps.destroy'
                ])
                ->middleware([
                    'index' => 'permission:view_timetables',
                    'create' => 'permission:create_timetable',
                    'store' => 'permission:create_timetable',
                    'show' => 'permission:view_timetables',
                    'edit' => 'permission:edit_timetables',
                    'update' => 'permission:edit_timetables',
                    'destroy' => 'permission:delete_timetables'
                ]);

            Route::get('emploi-temps/{emploi_temp}/export-pdf', [ESBTPEmploiTempsController::class, 'generatePdf'])
                ->name('emploi-temps.export-pdf')
                ->middleware(['permission:view_timetables']);

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
            Route::get('resultats', [ESBTPBulletinController::class, 'resultats'])
                ->name('resultats.index')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            Route::get('resultats/classe/{classe}', [ESBTPBulletinController::class, 'resultatClasse'])
                ->name('resultats.classe')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            Route::get('resultats/etudiant/{etudiant}', [ESBTPBulletinController::class, 'resultatEtudiant'])
                ->name('resultats.etudiant')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
            
            Route::get('resultats/etudiant/{etudiant}/preview', [ESBTPBulletinController::class, 'previewBulletinEtudiant'])
                ->name('resultats.etudiant.preview')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);
                
            Route::get('bulletins/configuration', [ESBTPBulletinController::class, 'configuration'])
                ->name('bulletins.configuration')
                ->middleware(['permission:edit_bulletins']);
                
            Route::post('bulletins/configuration', [ESBTPBulletinController::class, 'saveConfiguration'])
                ->name('bulletins.save-configuration')
                ->middleware(['permission:edit_bulletins']);
            Route::get('resultats/historique/classes', [ESBTPBulletinController::class, 'resultats'])
                ->name('resultats.historique.classes')
                ->middleware(['permission:view_own_bulletin|view_bulletins']);

            // Routes pour les annonces
            Route::resource('annonces', ESBTPAnnonceController::class)
                ->middleware(['permission:send_messages']);


            // Routes pour les présences/absences (esbtp namespace)
            Route::name('attendances.')->group(function() {
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
                Route::post('/configure-rapide', [ESBTPPlanningGeneralController::class, 'configureRapide'])->name('configure-rapide');
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
            Route::get('/paiements/suivi-categories', [App\Http\Controllers\ESBTPPaiementController::class, 'suiviCategories'])->name('paiements.suivi-categories');
            Route::get('/paiements/create', [App\Http\Controllers\ESBTPPaiementController::class, 'create'])->name('paiements.create');
            Route::post('/paiements', [App\Http\Controllers\ESBTPPaiementController::class, 'store'])->name('paiements.store');
            Route::get('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'show'])->name('paiements.show');
            Route::get('/paiements/{paiement}/edit', [App\Http\Controllers\ESBTPPaiementController::class, 'edit'])->name('paiements.edit');
            Route::put('/paiements/{paiement}', [App\Http\Controllers\ESBTPPaiementController::class, 'update'])->name('paiements.update');
            Route::get('/paiements/{paiement}/valider', [App\Http\Controllers\ESBTPPaiementController::class, 'valider'])->name('paiements.valider');
            Route::post('/paiements/{paiement}/rejeter', [App\Http\Controllers\ESBTPPaiementController::class, 'rejeter'])->name('paiements.rejeter');
            Route::get('/paiements/{paiement}/preview', [App\Http\Controllers\ESBTPPaiementController::class, 'previewRecu'])->name('paiements.preview');
            Route::get('/paiements/{paiement}/recu', [App\Http\Controllers\ESBTPPaiementController::class, 'genererRecu'])->name('paiements.recu');
            Route::get('/paiements/etudiant/{etudiant}', [App\Http\Controllers\ESBTPPaiementController::class, 'paiementsEtudiant'])->name('paiements.etudiant');

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

        // Routes accessibles pour les secrétaires et super-admins
        Route::middleware(['auth', 'role:secretaire|superAdmin'])->group(function () {
            // Nouvelle route pour la vue fusionnée des étudiants et inscriptions
            Route::get('/etudiants-inscriptions', [ESBTPEtudiantController::class, 'indexFusionne'])
                ->name('etudiants-inscriptions.index')
                ->middleware(['permission:view_students|view_inscriptions']);



            // Routes pour réinitialiser le mot de passe d'un étudiant
            Route::get('/etudiants/{etudiant}/reset-password', [ESBTPEtudiantController::class, 'resetPassword'])
                ->name('etudiants.reset-password')
                ->middleware(['permission:edit_students']);

            // Route pour générer un certificat de scolarité


            // Routes pour les inscriptions ESBTP
            Route::get('/inscriptions', [ESBTPInscriptionController::class, 'index'])->name('inscriptions.index');
            Route::get('/inscriptions/create', [ESBTPInscriptionController::class, 'create'])->name('inscriptions.create');
            Route::get('/inscriptions/getClasses', [ESBTPInscriptionController::class, 'getClasses'])->name('inscriptions.getClasses');
            Route::post('/inscriptions', [ESBTPInscriptionController::class, 'store'])->name('inscriptions.store');
            Route::get('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'show'])->name('inscriptions.show');
            Route::get('/inscriptions/{inscription}/edit', [ESBTPInscriptionController::class, 'edit'])->name('inscriptions.edit');
            Route::put('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'update'])->name('inscriptions.update');
            Route::delete('/inscriptions/{inscription}', [ESBTPInscriptionController::class, 'destroy'])->name('inscriptions.destroy');
            Route::put('/inscriptions/{inscription}/valider', [ESBTPInscriptionController::class, 'valider'])->name('inscriptions.valider');
            Route::put('/inscriptions/{inscription}/annuler', [ESBTPInscriptionController::class, 'annuler'])->name('inscriptions.annuler');
            
            // Routes pour l'administration des inscriptions
            Route::get('/inscriptions-administration', [ESBTPInscriptionController::class, 'administration'])->name('inscriptions.administration');
            Route::post('/inscriptions/{inscription}/valider-avec-paiement', [ESBTPInscriptionController::class, 'validerAvecPaiement'])->name('inscriptions.valider-avec-paiement');
            Route::post('/inscriptions/{inscription}/valider-definitivement', [ESBTPInscriptionController::class, 'validerDefinitivement'])->name('inscriptions.valider-definitivement');
            Route::post('/inscriptions/{inscription}/payer-frais', [ESBTPInscriptionController::class, 'payerFraisCategorie'])->name('inscriptions.payer-frais');
            Route::post('/inscriptions/{inscription}/transfer-overpayment', [ESBTPInscriptionController::class, 'transferOverpayment'])->name('inscriptions.transfer-overpayment');
            
            // API pour les parents dans les inscriptions
            Route::get('/api/parents/search', [ESBTPInscriptionController::class, 'searchParents'])->name('api.parents.search');
            
            // Route pour récupérer les frais par classe
            Route::get('/inscriptions/frais-by-classe/{classeId}', [ESBTPInscriptionController::class, 'getFraisByClasse'])->name('inscriptions.frais-by-classe');

            // Routes API utilisées par les formulaires
            Route::get('classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieres'])
                ->name('classes.matieres')
                ->middleware(['permission:view_classes|view classes']);

            // Routes pour les notes
            Route::resource('notes', ESBTPNoteController::class)
                ->names([
                    'index' => 'esbtp.notes.index',
                    'create' => 'esbtp.notes.create',
                    'store' => 'esbtp.notes.store',
                    'show' => 'esbtp.notes.show',
                    'edit' => 'esbtp.notes.edit',
                    'update' => 'esbtp.notes.update',
                    'destroy' => 'esbtp.notes.destroy'
                ])
                ->middleware(['permission:view_grades|create_grade|edit_grades|delete_grades']);
            Route::get('evaluations/{evaluation}/saisie-rapide', [ESBTPNoteController::class, 'saisieRapide'])->name('esbtp.notes.saisie-rapide');
            Route::post('notes/store-batch', [ESBTPNoteController::class, 'enregistrerSaisieRapide'])->name('esbtp.notes.store-batch');
        });

        // Espace étudiant - routes accessibles pour les étudiants
        Route::middleware(['auth', 'role:etudiant'])->group(function () {
            Route::get('/mon-profil', [ESBTPEtudiantController::class, 'profile'])
                ->name('mon-profil.index')
                ->middleware(['permission:view_own_profile|view_students']);

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

            Route::get('/mon-bulletin', [ESBTPBulletinController::class, 'studentBulletins'])
                ->name('mon-bulletin.index')
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

        // Routes exclusives pour le superAdmin (suppression de ressources)
        Route::middleware(['auth', 'role:superAdmin'])->group(function () {
            // Suppression d'étudiants
            Route::delete('/etudiants/{etudiant}', [ESBTPEtudiantController::class, 'destroy'])->name('etudiants.destroy')
                ->middleware(['permission:delete_students']);

            // Suppression de bulletins
            Route::delete('bulletins/{bulletin}', [ESBTPBulletinController::class, 'destroy'])->name('bulletins.destroy');



            // Route de suppression des emplois du temps - Handled by resource route
        });

        // Emploi du temps routes
        Route::get('/emploi-temps/{emploi_temp}/add-session', [ESBTPEmploiTempsController::class, 'addSession'])
            ->name('esbtp.emploi-temps.add-session');
        Route::post('/emploi-temps/{emploi_temp}/store-session', [ESBTPEmploiTempsController::class, 'storeSession'])
            ->name('esbtp.emploi-temps.store-session');

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
    Route::middleware(['auth', 'role:superAdmin'])->group(function () {
        Route::get('/settings', function() {
            return view('admin.settings.index');
        })->name('settings.index');

        Route::get('/roles', function() {
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
    Route::prefix('esbtp')->middleware(['auth', 'role:superAdmin|secretaire|coordinateur'])->group(function () {
        Route::get('/progression', [StudentProgressionController::class, 'index'])->name('esbtp.progression.index');
        Route::get('/api/progression/recommendations/{classe}/{annee}', [StudentProgressionController::class, 'getRecommendations'])->name('esbtp.progression.recommendations');
        Route::post('/api/progression/process', [StudentProgressionController::class, 'processProgression'])->name('esbtp.progression.process');

        // ESBTP Settings Routes
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

    // Routes pour l'émargement - Administration
    Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'role:superAdmin,secretaire'])->group(function () {
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
    Route::prefix('esbtp/teacher/attendance')->name('esbtp.teacher.attendance.')->middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'index'])->name('index')->middleware('permission:view_own_attendance');
        Route::get('/history', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'history'])->name('history')->middleware('permission:view_own_attendance');
        Route::post('/sign', [App\Http\Controllers\ESBTP\TeacherAttendanceController::class, 'sign'])->name('sign')->middleware('permission:sign_attendance');
    });

    // Routes d'émargement pour les enseignants
    Route::middleware(['role:teacher'])->group(function () {
        Route::get('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'index'])->name('attendance.mark');
        Route::post('/attendance/mark', [ESBTPTeacherAttendanceController::class, 'mark'])->name('attendance.mark.submit');
    });
});

// Routes pour les enseignants
Route::middleware(['auth', 'role:teacher'])->group(function () {
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
//         Route::get('/mon-bulletin', [App\Http\Controllers\ESBTPBulletinController::class, 'studentBulletins'])
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
    Route::get('classes/{id}/matieres', [ESBTPClasseController::class, 'getMatieresForApi'])->name('classes.matieres.api');
    Route::get('classes/{id}', [ESBTPClasseController::class, 'getClasseById'])->name('classes.get');
    Route::get('get-classes', [ESBTPInscriptionController::class, 'getClasses'])->name('get-classes');
    Route::get('search-parents', [ESBTPEtudiantController::class, 'searchParents'])->name('search-parents');
    Route::get('etudiants/search', [ESBTPEtudiantController::class, 'searchForApi'])->name('etudiants.search');
    Route::get('etudiants/inscriptions', [ESBTPEtudiantController::class, 'getInscriptionsForApi'])->name('etudiants.inscriptions');
    Route::get('etudiants/soldes', [ESBTPEtudiantController::class, 'getSoldesForApi'])->name('etudiants.soldes');
    Route::get('frais/categories', [\App\Http\Controllers\ESBTPFraisController::class, 'getCategoriesForApi'])->name('frais.categories');
});

// Route for activating all timetables
Route::post('esbtp/activate-all-timetables', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'activateAll'])
    ->name('esbtp.emploi-temps.activate-all')
    ->middleware(['auth', 'role:superAdmin']);

// Route for setting a timetable as current
Route::post('esbtp/emploi-temps/{id}/set-current', [App\Http\Controllers\ESBTPEmploiTempsController::class, 'setCurrent'])
    ->name('esbtp.emploi-temps.set-current')
    ->middleware(['auth', 'role:superAdmin|secretaire']);

// Routes pour les évaluations
Route::prefix('esbtp/evaluations')->name('esbtp.evaluations.')->middleware(['auth'])->group(function () {
    Route::get('/', [ESBTPEvaluationController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPEvaluationController::class, 'create'])->name('create');
    Route::post('/', [ESBTPEvaluationController::class, 'store'])->name('store');
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
    ->name('evaluations.pdf');

// Route pour l'index des bulletins ESBTP
Route::get('/esbtp/bulletins', [ESBTPBulletinController::class, 'index'])->name('esbtp.bulletins.index');

// Route spéciale pour la sélection des bulletins
Route::get('/esbtp/bulletins/select', [ESBTPBulletinController::class, 'select'])
    ->name('esbtp.bulletins.select')
    ->middleware(['auth']);

// Route pour télécharger un bulletin au format PDF
Route::get('/esbtp/bulletins/{bulletin}/download', [ESBTPBulletinController::class, 'genererPDF'])->name('esbtp.bulletins.download');

// Routes pour la gestion des secrétaires
Route::prefix('secretaires')->name('secretaires.')->group(function () {
    Route::get('/', [ESBTPSecretaireController::class, 'index'])->name('index');
    Route::get('/create', [ESBTPSecretaireController::class, 'create'])->name('create');
    Route::post('/', [ESBTPSecretaireController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ESBTPSecretaireController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ESBTPSecretaireController::class, 'update'])->name('update');
    Route::delete('/{id}', [ESBTPSecretaireController::class, 'destroy'])->name('destroy');
});

// Routes pour la gestion des enseignants
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'role:superAdmin'])->group(function () {
    Route::resource('enseignants', ESBTPEnseignantController::class);
    Route::get('enseignants/{teacher}/matieres', [ESBTPEnseignantController::class, 'matieres'])->name('enseignants.matieres');
    Route::post('enseignants/{teacher}/assign-matieres', [ESBTPEnseignantController::class, 'assignMatieres'])->name('enseignants.assign-matieres');
    Route::post('enseignants/{teacher}/toggle-status', [ESBTPEnseignantController::class, 'toggleStatus'])->name('enseignants.toggleStatus');
    Route::post('enseignants/{enseignant}/update-availability', [ESBTPEnseignantController::class, 'updateAvailability'])->name('enseignants.update-availability');
    Route::get('enseignants/{enseignant}/debug-result', [ESBTPEnseignantController::class, 'debugResult'])->name('enseignants.debug-result');
    Route::resource('specialties', ESBTPSpecialtyController::class);
    Route::put('specialties/{id}/restore', [ESBTPSpecialtyController::class, 'restore'])->name('specialties.restore');
    Route::resource('continuing-education', ESBTPContinuingEducationController::class);
    Route::put('continuing-education/{id}/restore', [ESBTPContinuingEducationController::class, 'restore'])->name('continuing-education.restore');
    Route::resource('etudiants', ESBTPStudentController::class)->parameters(['etudiants' => 'etudiant']);
    Route::put('students/{id}/restore', [ESBTPStudentController::class, 'restore'])->name('students.restore');
});

// Routes pour l'espace enseignant
Route::middleware(['auth', 'role:teacher'])->group(function () {
    // Gestion des notes
    Route::prefix('esbtp/notes')->name('esbtp.notes.')->group(function () {
        Route::get('/', [ESBTPNoteController::class, 'index'])->name('index');
        Route::get('/create', [ESBTPNoteController::class, 'create'])->name('create');
        Route::post('/', [ESBTPNoteController::class, 'store'])->name('store');
        Route::get('/{note}', [ESBTPNoteController::class, 'show'])->name('show');
        Route::get('/{note}/edit', [ESBTPNoteController::class, 'edit'])->name('edit');
        Route::put('/{note}', [ESBTPNoteController::class, 'update'])->name('update');
        Route::delete('/{note}', [ESBTPNoteController::class, 'destroy'])->name('destroy');
        Route::get('/evaluations/{evaluation}/saisie-rapide', [ESBTPNoteController::class, 'saisieRapide'])->name('saisie-rapide');
        Route::post('/store-batch', [ESBTPNoteController::class, 'enregistrerSaisieRapide'])->name('store-batch');
    });

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
Route::middleware(['auth', 'permission:access_comptabilite_module'])->prefix('esbtp/comptabilite')->name('esbtp.comptabilite.')->group(function () {
    // Dashboard comptabilité
    Route::get('/', [ESBTPComptabiliteController::class, 'index'])->name('index');

    // Dashboard avancé avec KPIs temps réel
    Route::get('/dashboard-avance', [ESBTPComptabiliteController::class, 'dashboardAvance'])->name('dashboard-avance');
    Route::get('/kpis-temps-reel', [ESBTPComptabiliteController::class, 'kpisTempsReel'])->name('kpis-temps-reel');

    // Paiements
    // Gestion des paiements
    Route::get('/paiements', [ESBTPComptabiliteController::class, 'paiements'])->name('paiements');
    Route::get('/paiements/create', [ESBTPComptabiliteController::class, 'createPaiement'])->name('paiements.create');
    Route::post('/paiements', [ESBTPComptabiliteController::class, 'storePaiement'])->name('paiements.store');
    Route::get('/paiements/{id}', [ESBTPComptabiliteController::class, 'showPaiement'])->name('paiements.show');
    Route::get('/paiements/{id}/edit', [ESBTPComptabiliteController::class, 'editPaiement'])->name('paiements.edit');
    Route::put('/paiements/{id}', [ESBTPComptabiliteController::class, 'updatePaiement'])->name('paiements.update');
    Route::post('/paiements/{id}/valider', [ESBTPComptabiliteController::class, 'validerPaiement'])->name('paiements.valider');
    Route::post('/paiements/{id}/rejeter', [ESBTPComptabiliteController::class, 'rejeterPaiement'])->name('paiements.rejeter');
    Route::get('/paiements/{id}/recu', [ESBTPComptabiliteController::class, 'genererRecu'])->name('paiements.recu');

    // Gestion des frais de scolarité
    Route::get('/frais-scolarite', [ESBTPComptabiliteController::class, 'fraisScolarite'])->name('frais-scolarite');
    Route::get('/frais-scolarite/create', [ESBTPComptabiliteController::class, 'createFraisScolarite'])->name('frais-scolarite.create');
    Route::post('/frais-scolarite', [ESBTPComptabiliteController::class, 'storeFraisScolarite'])->name('frais-scolarite.store');
    Route::get('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'showFraisScolarite'])->name('frais-scolarite.show');
    Route::get('/frais-scolarite/{id}/edit', [ESBTPComptabiliteController::class, 'editFraisScolarite'])->name('frais-scolarite.edit');
    Route::put('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'updateFraisScolarite'])->name('frais-scolarite.update');
    Route::delete('/frais-scolarite/{id}', [ESBTPComptabiliteController::class, 'destroyFraisScolarite'])->name('frais-scolarite.destroy');

    // Gestion des dépenses
    Route::get('/depenses', [ESBTPComptabiliteController::class, 'depenses'])->name('depenses');
    Route::get('/depenses/create', [ESBTPComptabiliteController::class, 'createDepense'])->name('depenses.create');
    Route::post('/depenses', [ESBTPComptabiliteController::class, 'storeDepense'])->name('depenses.store');
    Route::get('/depenses/{id}', [ESBTPComptabiliteController::class, 'showDepense'])->name('depenses.show');
    Route::get('/depenses/{id}/edit', [ESBTPComptabiliteController::class, 'editDepense'])->name('depenses.edit');
    Route::put('/depenses/{id}', [ESBTPComptabiliteController::class, 'updateDepense'])->name('depenses.update');
    Route::delete('/depenses/{id}', [ESBTPComptabiliteController::class, 'destroyDepense'])->name('depenses.destroy');

    // Gestion des catégories de dépenses
    Route::get('/depenses/categories', [ESBTPComptabiliteController::class, 'categoriesDepenses'])->name('depenses.categories');
    Route::post('/depenses/categories', [ESBTPComptabiliteController::class, 'storeCategorieDepense'])->name('depenses.categories.store');
    Route::put('/depenses/categories/{id}', [ESBTPComptabiliteController::class, 'updateCategorieDepense'])->name('depenses.categories.update');
    Route::delete('/depenses/categories/{id}', [ESBTPComptabiliteController::class, 'destroyCategorieDepense'])->name('depenses.categories.destroy');

    // Route AJAX pour créer un fournisseur
    Route::post('/fournisseurs/ajax', [ESBTPComptabiliteController::class, 'storeFournisseurAjax'])->name('fournisseurs.ajax.store');

    // Gestion des bourses et aides
    Route::get('/bourses', [ESBTPComptabiliteController::class, 'bourses'])->name('bourses');
    Route::get('/bourses/create', [ESBTPComptabiliteController::class, 'createBourse'])->name('bourses.create');
    Route::post('/bourses', [ESBTPComptabiliteController::class, 'storeBourse'])->name('bourses.store');
    Route::get('/bourses/{id}', [ESBTPComptabiliteController::class, 'showBourse'])->name('bourses.show');
    Route::get('/bourses/{id}/edit', [ESBTPComptabiliteController::class, 'editBourse'])->name('bourses.edit');
    Route::put('/bourses/{id}', [ESBTPComptabiliteController::class, 'updateBourse'])->name('bourses.update');
    Route::delete('/bourses/{id}', [ESBTPComptabiliteController::class, 'destroyBourse'])->name('bourses.destroy');

    // Gestion des salaires
    Route::get('/salaires', [ESBTPComptabiliteController::class, 'salaires'])->name('salaires');
    Route::get('/salaires/create', [ESBTPComptabiliteController::class, 'createSalaire'])->name('salaires.create');
    Route::post('/salaires', [ESBTPComptabiliteController::class, 'storeSalaire'])->name('salaires.store');
    Route::get('/salaires/{id}', [ESBTPComptabiliteController::class, 'showSalaire'])->name('salaires.show');
    Route::get('/salaires/{id}/edit', [ESBTPComptabiliteController::class, 'editSalaire'])->name('salaires.edit');
    Route::put('/salaires/{id}', [ESBTPComptabiliteController::class, 'updateSalaire'])->name('salaires.update');
    Route::delete('/salaires/{id}', [ESBTPComptabiliteController::class, 'destroySalaire'])->name('salaires.destroy');
    Route::get('/salaires/{id}/bulletin', [ESBTPComptabiliteController::class, 'bulletinSalaire'])->name('salaires.bulletin');

    // Gestion des fournisseurs
    Route::get('/fournisseurs', [ESBTPComptabiliteController::class, 'fournisseurs'])->name('fournisseurs');
    Route::get('/fournisseurs/create', [ESBTPComptabiliteController::class, 'createFournisseur'])->name('fournisseurs.create');
    Route::post('/fournisseurs', [ESBTPComptabiliteController::class, 'storeFournisseur'])->name('fournisseurs.store');
    Route::get('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'showFournisseur'])->name('fournisseurs.show');
    Route::get('/fournisseurs/{id}/edit', [ESBTPComptabiliteController::class, 'editFournisseur'])->name('fournisseurs.edit');
    Route::put('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'updateFournisseur'])->name('fournisseurs.update');
    Route::delete('/fournisseurs/{id}', [ESBTPComptabiliteController::class, 'destroyFournisseur'])->name('fournisseurs.destroy');

    // Gestion des factures
    Route::get('/factures', [ESBTPComptabiliteController::class, 'factures'])->name('factures');
    Route::get('/factures/create', [ESBTPComptabiliteController::class, 'createFacture'])->name('factures.create');
    Route::post('/factures', [ESBTPComptabiliteController::class, 'storeFacture'])->name('factures.store');
    Route::get('/factures/{id}', [ESBTPComptabiliteController::class, 'showFacture'])->name('factures.show');
    Route::get('/factures/{id}/edit', [ESBTPComptabiliteController::class, 'editFacture'])->name('factures.edit');
    Route::put('/factures/{id}', [ESBTPComptabiliteController::class, 'updateFacture'])->name('factures.update');
    Route::delete('/factures/{id}', [ESBTPComptabiliteController::class, 'destroyFacture'])->name('factures.destroy');
    Route::get('/factures/{id}/pdf', [ESBTPComptabiliteController::class, 'pdfFacture'])->name('factures.pdf');

    // Tableau de bord et rapports financiers
    Route::get('/rapports', [ESBTPComptabiliteController::class, 'rapports'])->name('rapports');
    Route::get('/rapports/generate', [ESBTPComptabiliteController::class, 'generateReport'])->name('rapports.generate');
    Route::post('/rapports/export', [ESBTPComptabiliteController::class, 'exportReport'])->name('rapports.export');

    // Routes avancées pour le générateur de rapports - Task #6
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/builder', [ESBTPComptabiliteController::class, 'rapportsAvances'])->name('builder')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/generer', [ESBTPComptabiliteController::class, 'genererRapportPersonnalise'])->name('generer')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:10,1']);

        Route::post('/schedule', [ESBTPComptabiliteController::class, 'programmerRapport'])->name('schedule')
            ->middleware(['permission:comptabilite.reports.export', 'throttle:5,1']);

        Route::get('/scheduled', [ESBTPComptabiliteController::class, 'listeRapportsProgrammes'])->name('scheduled')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/analytics/predictive', [ESBTPComptabiliteController::class, 'analysesPredictives'])->name('analytics.predictive')
            ->middleware(['permission:comptabilite.dashboard.view', 'throttle:20,1']);

        Route::get('/analytics/cashflow', [ESBTPComptabiliteController::class, 'projectionCashFlow'])->name('analytics.cashflow')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/analytics/anomalies', [ESBTPComptabiliteController::class, 'detectionAnomalies'])->name('analytics.anomalies')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/templates', [ESBTPComptabiliteController::class, 'modelesRapports'])->name('templates')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/templates', [ESBTPComptabiliteController::class, 'sauvegarderModele'])->name('templates.save')
            ->middleware(['permission:comptabilite.config.manage']);
    });

    // NOUVELLES ROUTES ANALYTICS PRÉDICTIFS - Tâche #11
    Route::prefix('analytics-predictifs')->name('analytics-predictifs.')->group(function () {
        Route::get('/', [ESBTPComptabiliteController::class, 'analyticsPredictifs'])->name('index')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/recommandations', [ESBTPComptabiliteController::class, 'recommandationsIntelligentes'])->name('recommandations')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/benchmarking', [ESBTPComptabiliteController::class, 'benchmarkingAvance'])->name('benchmarking')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::get('/visualisations', [ESBTPComptabiliteController::class, 'visualisationsAvancees'])->name('visualisations')
            ->middleware(['permission:comptabilite.dashboard.view']);

        // API pour les données en temps réel
        Route::get('/api/data', [ESBTPComptabiliteController::class, 'apiAnalyticsPredictifs'])->name('api.data')
            ->middleware(['permission:comptabilite.dashboard.view', 'throttle:60,1']);
    });

    // Configuration du module comptabilité
    Route::get('/configuration', [ESBTPComptabiliteController::class, 'configuration'])->name('configuration');
    Route::post('/configuration', [ESBTPComptabiliteController::class, 'updateConfiguration'])->name('configuration.update');

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

    // Gestion des bons de sortie avec workflow
    Route::prefix('bons-sortie')->name('bons-sortie.')->group(function () {
        Route::get('/', [ESBTPComptabiliteController::class, 'bonsSortie'])->name('index');
        Route::get('/create', [ESBTPComptabiliteController::class, 'createBonSortie'])->name('create');
        Route::post('/', [ESBTPComptabiliteController::class, 'storeBonSortie'])->name('store');
        Route::get('/{id}', [ESBTPComptabiliteController::class, 'showBonSortie'])->name('show')->where('id', '[0-9]+');
        Route::get('/{id}/edit', [ESBTPComptabiliteController::class, 'editBonSortie'])->name('edit')->where('id', '[0-9]+');
        Route::put('/{id}', [ESBTPComptabiliteController::class, 'updateBonSortie'])->name('update')->where('id', '[0-9]+');

        // Actions workflow
        Route::post('/{id}/approuver', [ESBTPComptabiliteController::class, 'approuverBon'])->name('approuver')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.bons.approve', 'throttle:30,1']);
        Route::post('/{id}/rejeter', [ESBTPComptabiliteController::class, 'rejeterBon'])->name('rejeter')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.bons.approve', 'throttle:30,1']);
        Route::post('/{id}/soumettre', [ESBTPComptabiliteController::class, 'soumettreApprobation'])->name('soumettre')->where('id', '[0-9]+')
            ->middleware(['throttle:20,1']);
        Route::post('/{id}/payer', [ESBTPComptabiliteController::class, 'marquerCommePaye'])->name('payer')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.bons.pay', 'throttle:20,1']);

        // Génération PDF
        Route::get('/{id}/pdf', [ESBTPComptabiliteController::class, 'genererPDFBon'])->name('pdf')->where('id', '[0-9]+')
            ->middleware(['throttle:5,1']);
    });

    // Gestion des relances automatisées
    Route::prefix('relances')->name('relances.')->group(function () {
        Route::get('/', [ESBTPComptabiliteController::class, 'gestionRelances'])->name('index');
        Route::get('/config', [ESBTPComptabiliteController::class, 'configurationRelances'])->name('config');
        Route::get('/{id}', [ESBTPComptabiliteController::class, 'showRelance'])->name('show')->where('id', '[0-9]+');

        // Actions sur les relances
        Route::post('/planifier', [ESBTPComptabiliteController::class, 'planifierRelances'])->name('planifier')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:10,1']);
        Route::post('/{id}/renvoyer', [ESBTPComptabiliteController::class, 'renvoyerRelance'])->name('renvoyer')->where('id', '[0-9]+')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:30,1']);
        Route::post('/executer', [ESBTPComptabiliteController::class, 'executerRelances'])->name('executer')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:5,1']);

        // Configuration
        Route::post('/config/templates', [ESBTPComptabiliteController::class, 'sauvegarderTemplates'])->name('config.templates');
        Route::post('/config/parametres', [ESBTPComptabiliteController::class, 'sauvegarderParametres'])->name('config.parametres');
        Route::post('/config/preview', [ESBTPComptabiliteController::class, 'previewTemplate'])->name('config.preview');

        // Aperçus et statistiques
        Route::get('/apercu-etudiants', [ESBTPComptabiliteController::class, 'apercuRelances'])->name('apercu');

        // NOUVELLES ROUTES ANALYTICS AVANCÉES - Tâche #4
        Route::get('/analytics', [ESBTPComptabiliteController::class, 'analyticsRelances'])->name('analytics')
            ->middleware(['permission:comptabilite.dashboard.view']);

        Route::post('/planifier-avancees', [ESBTPComptabiliteController::class, 'planifierRelancesAvancees'])->name('planifier.avancees')
            ->middleware(['permission:comptabilite.relances.send', 'throttle:5,1']);

        Route::get('/export', [ESBTPComptabiliteController::class, 'exportAnalyticsRelances'])->name('export')
            ->middleware(['permission:comptabilite.reports.export']);

        Route::post('/preview-segmentation', [ESBTPComptabiliteController::class, 'previewSegmentation'])->name('preview.segmentation')
            ->middleware(['permission:comptabilite.relances.send']);
    });

    // Dashboard comptabilité
    Route::get('/dashboard', [ESBTPComptabiliteController::class, 'dashboard'])->name('dashboard');
});

// Routes pour le système d'émargement
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth'])->group(function () {
    // Routes pour l'administration des codes (accès restreint aux administrateurs et secrétaires)
    Route::middleware(['role:superAdmin|secretaire'])->group(function () {
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
    Route::middleware(['role:superAdmin'])->group(function () {
        Route::get('/attendance-codes/settings', [ESBTPAttendanceCodeController::class, 'settings'])
            ->name('attendance-codes.settings');
        Route::post('/attendance-codes/settings', [ESBTPAttendanceCodeController::class, 'updateSettings'])
            ->name('attendance-codes.settings.update');
    });

    // Routes pour l'émargement des enseignants
    Route::prefix('teacher-attendance')->name('teacher-attendance.')->middleware(['auth', 'role:teacher'])->group(function () {
        Route::get('/', [TeacherAttendanceController::class, 'index'])->name('index');
        Route::get('/history', [TeacherAttendanceController::class, 'history'])->name('history');
        Route::post('/sign', [TeacherAttendanceController::class, 'sign'])->name('sign');
    });
    
    // Route rapport accessible aux enseignants et superadmins
    Route::get('teacher-attendance/report', [TeacherAttendanceController::class, 'report'])
        ->name('teacher-attendance.report')
        ->middleware(['auth', 'role:teacher|superAdmin']);

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
Route::prefix('esbtp/admin/attendance')->name('esbtp.admin.attendance.')->middleware(['auth', 'role:secretary,superAdmin'])->group(function () {
    Route::get('/forgotten-codes', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'index'])
        ->name('forgotten-codes');
    Route::post('/generate-manual-code', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'generateManualCode'])
        ->name('generate-manual-code');
    Route::post('/mark-manual', [App\Http\Controllers\ESBTP\Admin\ESBTPForgottenCodeController::class, 'markManualAttendance'])
        ->name('mark-manual');
});

// Manual Attendance Routes
Route::prefix('esbtp/admin/attendance/manual')->name('esbtp.admin.attendance.manual.')->middleware(['auth', 'role:superAdmin'])->group(function () {
    Route::get('/', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'index'])
        ->name('index');
    Route::post('/store', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'store'])
        ->name('store');
    Route::post('/bulk', [App\Http\Controllers\ESBTP\Admin\ESBTPManualAttendanceController::class, 'bulkStore'])
        ->name('bulk');
});

Route::middleware(['auth'])->group(function () {
    // ... existing code ...

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

    // ... existing code ...

    // ESBTP Logs Routes
    Route::middleware(['role:superAdmin'])->group(function () {
        Route::get('/esbtp/logs', [ESBTPLogsController::class, 'index'])->name('esbtp.logs.index');
        Route::get('/esbtp/logs/{filename}', [ESBTPLogsController::class, 'show'])->name('esbtp.logs.show');
        Route::get('/esbtp/logs/{filename}/download', [ESBTPLogsController::class, 'download'])->name('esbtp.logs.download');
        Route::post('/esbtp/logs/{filename}/clear', [ESBTPLogsController::class, 'clear'])->name('esbtp.logs.clear');
        Route::delete('/esbtp/logs/{filename}', [ESBTPLogsController::class, 'destroy'])->name('esbtp.logs.destroy');
    });

    // ... existing code ...

    // ESBTP Student Routes
    Route::resource('esbtp/etudiants', ESBTPStudentController::class, ['as' => 'esbtp'])->parameters(['etudiants' => 'etudiant']);
    Route::post('esbtp/etudiants/{id}/restore', [ESBTPStudentController::class, 'restore'])->name('esbtp.etudiants.restore');
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

    if (!$user) {
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

// Routes spéciales pour le workflow des bulletins (copiées exactement du GitHub)
// Route spéciale pour la génération de PDF de bulletins - placée ici pour éviter les conflits
Route::get('/esbtp-special/bulletins-pdf', [ESBTPBulletinController::class, 'genererPDFParParams'])->name('esbtp.bulletins.pdf-params');

// Route pour prévisualiser le bulletin avant génération PDF
Route::get('/esbtp/bulletins/preview', [ESBTPBulletinController::class, 'previewBulletin'])->name('esbtp.bulletins.preview');

// Route pour générer PDF directement (utilisée par le bouton download de la preview)
Route::get('/esbtp/bulletins/generer-pdf', [ESBTPBulletinController::class, 'genererPDFParParams'])->name('esbtp.bulletins.generer-pdf');

// Route AJAX pour récupérer les étudiants d'une classe
Route::get('/esbtp/classes/{classe}/etudiants', [ESBTPClasseController::class, 'getEtudiants'])->name('esbtp.classes.etudiants');

// Routes spéciales pour la prévisualisation et modification des moyennes
Route::get('/esbtp-special/bulletins/moyennes-preview', [ESBTPBulletinController::class, 'previewMoyennes'])->name('esbtp.bulletins.moyennes-preview');
Route::post('/esbtp-special/bulletins/moyennes-update', [ESBTPBulletinController::class, 'updateMoyennes'])->name('esbtp.bulletins.moyennes-update');
Route::delete('/esbtp-special/bulletins/moyennes-delete', [ESBTPBulletinController::class, 'deleteMoyenne'])->name('esbtp.bulletins.moyennes-delete');

// Routes spéciales pour la configuration des matières et l'édition des professeurs
Route::get('/esbtp-special/bulletins/config-matieres', [ESBTPBulletinController::class, 'configMatieresTypeFormation'])->name('esbtp.bulletins.config-matieres');
Route::post('/esbtp-special/bulletins/save-config-matieres', [ESBTPBulletinController::class, 'saveConfigMatieresTypeFormation'])->name('esbtp.bulletins.save-config-matieres');
Route::get('/esbtp-special/bulletins/edit-professeurs', [ESBTPBulletinController::class, 'editProfesseurs'])->name('esbtp.bulletins.edit-professeurs');
Route::post('/esbtp-special/bulletins/save-professeurs', [ESBTPBulletinController::class, 'saveProfesseurs'])->name('esbtp.bulletins.save-professeurs');
Route::get('/esbtp-special/bulletins/generate', [ESBTPBulletinController::class, 'generate'])->name('esbtp.bulletins.generate-special');

    // Routes pour les bulletins configurables
    Route::get('/bulletins/configurable', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])->name('esbtp.bulletins.configurable');
    Route::post('/bulletins/configurable', [ESBTPBulletinController::class, 'generateConfigurableBulletin'])->name('esbtp.bulletins.configurable.generate');

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
Route::get('/comptabilite/paiements/{id}/recu', [ESBTPComptabiliteController::class, 'genererRecuPaiement'])->name('esbtp.comptabilite.paiements.recu');
// ... existing code ...

// Routes pour la gestion du personnel avec sliders
Route::middleware(['auth'])->prefix('esbtp')->name('esbtp.')->group(function () {
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
});

// Routes pour les coordinateurs avec permissions spécifiques
Route::middleware(['auth', 'role:coordinateur'])->prefix('esbtp')->name('esbtp.')->group(function () {
    // Routes pour les inscriptions (lecture seule pour coordinateurs)
    Route::get('/inscriptions', [\App\Http\Controllers\ESBTPInscriptionController::class, 'index'])->name('inscriptions.index')
        ->middleware('permission:view_inscriptions');
    Route::get('/inscriptions/{inscription}', [\App\Http\Controllers\ESBTPInscriptionController::class, 'show'])->name('inscriptions.show')
        ->middleware('permission:view_inscriptions');
    Route::get('/etudiants-inscriptions', [\App\Http\Controllers\ESBTPEtudiantController::class, 'indexFusionne'])->name('etudiants-inscriptions.index')
        ->middleware('permission:view_inscriptions');
    
    // Routes pour les notes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPNoteController::class, 'index'])->name('index')
            ->middleware('permission:view_notes');
        Route::get('/{note}', [\App\Http\Controllers\ESBTPNoteController::class, 'show'])->name('show')
            ->middleware('permission:view_notes');
        Route::get('/evaluations/{evaluation}/saisie-rapide', [\App\Http\Controllers\ESBTPNoteController::class, 'saisieRapide'])->name('saisie-rapide')
            ->middleware('permission:view_notes');
    });
    
    // Routes pour les annonces
    Route::prefix('annonces')->name('annonces.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ESBTPAnnonceController::class, 'index'])->name('index')
            ->middleware('permission:view_annonces');
        Route::get('/create', [\App\Http\Controllers\ESBTPAnnonceController::class, 'create'])->name('create')
            ->middleware('permission:create_annonces');
        Route::post('/', [\App\Http\Controllers\ESBTPAnnonceController::class, 'store'])->name('store')
            ->middleware('permission:create_annonces');
        Route::get('/{annonce}', [\App\Http\Controllers\ESBTPAnnonceController::class, 'show'])->name('show')
            ->middleware('permission:view_annonces');
        Route::get('/{annonce}/edit', [\App\Http\Controllers\ESBTPAnnonceController::class, 'edit'])->name('edit')
            ->middleware('permission:edit_annonces');
        Route::put('/{annonce}', [\App\Http\Controllers\ESBTPAnnonceController::class, 'update'])->name('update')
            ->middleware('permission:edit_annonces');
        Route::delete('/{annonce}', [\App\Http\Controllers\ESBTPAnnonceController::class, 'destroy'])->name('destroy')
            ->middleware('permission:edit_annonces');
    });
    
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
    Route::get('/planning-general/get-matieres-configuration', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'getMatieresPourConfiguration'])
        ->name('planning-general.get-matieres-configuration')
        ->middleware('permission:manage-planning|view-all-timetables');
    Route::post('/planning-general/save-volume-configuration', [\App\Http\Controllers\ESBTPPlanningGeneralController::class, 'saveVolumeConfiguration'])
        ->name('planning-general.save-volume-configuration')
        ->middleware('permission:manage-planning|view-all-timetables');
});

// Routes spécifiques pour les coordinateurs pour événements académiques
Route::middleware(['auth', 'role:coordinateur'])->prefix('esbtp')->name('esbtp.')->group(function () {
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

// Routes pour la gestion des liens externes (pour admins/secrétaires)
Route::middleware(['auth', 'role:superAdmin,secretary,coordinateur'])->prefix('esbtp')->name('esbtp.')->group(function () {
    Route::post('/evaluations/{evaluation}/generate-external-link', [ESBTPEvaluationController::class, 'generateExternalLink'])->name('evaluations.generate-external-link');
    Route::delete('/evaluations/{evaluation}/revoke-external-link', [ESBTPEvaluationController::class, 'revokeExternalLink'])->name('evaluations.revoke-external-link');
    Route::get('/evaluations/active-external-links', [ESBTPEvaluationController::class, 'getActiveExternalLinks'])->name('evaluations.active-external-links');
});

// Routes pour la saisie externe de notes (sans authentification)
Route::prefix('external-grading')->name('external-grading.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\ExternalGradingController::class, 'show'])->name('show');
    Route::post('/{token}', [App\Http\Controllers\ExternalGradingController::class, 'store'])->name('store');
});

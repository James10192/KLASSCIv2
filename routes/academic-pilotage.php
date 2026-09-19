<?php

use App\Http\Controllers\AcademicPilotage\AcademicAssignmentController;
use App\Http\Controllers\AcademicPilotage\AcademicAlertController;
use App\Http\Controllers\AcademicPilotage\AcademicPilotageController;
use App\Http\Controllers\AcademicPilotage\GradeSheetController;
use App\Http\Controllers\AcademicPilotage\GradeSheetDocumentController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('esbtp')->name('esbtp.')
    ->middleware([
        'auth',
        'installed',
        'force.password.change',
        'paywall',
    ])
    ->group(function () {
        Route::get('/pilotage-academique', [AcademicPilotageController::class, 'index'])
            ->middleware([
                'permission:module.academic_pilotage.access',
                'permission:academic_pilotage.view',
            ])
            ->name('pilotage-academique.index');
        Route::get('/pilotage-academique/data', [AcademicPilotageController::class, 'data'])
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                'permission:academic_pilotage.view',
            ])
            ->name('pilotage-academique.data');
        Route::get('/pilotage-academique/tendances', [AcademicPilotageController::class, 'trends'])
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                'permission:academic_pilotage.view',
            ])
            ->name('pilotage-academique.tendances');
        Route::post('/pilotage-academique/synchronize', [AcademicPilotageController::class, 'synchronize'])
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                'permission:academic_health.recalculate',
                'throttle:10,1',
            ])
            ->name('pilotage-academique.synchronize');
        Route::get('/pilotage-academique/classes/{classe}', [AcademicPilotageController::class, 'classHealth'])
            ->whereNumber('classe')
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                'permission:academic_health.view',
            ])
            ->name('pilotage-academique.classes.show');
        // La couverture des notes d'une classe, a la demande. Separee du
        // tableau de bord, qui recalcule tout a chaque appel.
        Route::get('/pilotage-academique/classes/{classe}/couverture', [\App\Http\Controllers\AcademicPilotage\AcademicCoverageController::class, 'show'])
            ->whereNumber('classe')
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                // `view_own` suffit ICI, et nulle part ailleurs sur ce groupe :
                // l'enseignant qui saisit les notes est le premier a devoir
                // savoir ce qui manque, et c'est lui qui n'avait pas le droit
                // de le lire. Le perimetre n'est pas relache pour autant —
                // `AcademicCoverageController::show()` refuse en 403 une classe
                // hors du perimetre rendu par `dashboardScope()`, qui pour un
                // enseignant se limite a ce qu'il enseigne, corrige ou a saisi.
                'permission:academic_health.view|academic_health.view_own',
                'throttle:60,1',
            ])
            ->name('pilotage-academique.classes.couverture');
        Route::get('/pilotage-academique/etudiants/{etudiant}', [AcademicPilotageController::class, 'studentHealth'])
            ->whereNumber('etudiant')
            ->middleware([
                ForceJsonResponse::class,
                'permission:module.academic_pilotage.access',
                'permission:academic_health.view',
            ])
            ->name('pilotage-academique.etudiants.show');

        Route::middleware([
            ForceJsonResponse::class,
            'permission:module.academic_pilotage.access',
        ])->group(function () {
            Route::prefix('academic-sheets')->name('academic-sheets.')->group(function () {
                Route::get('/{sheet}', [GradeSheetController::class, 'show'])
                    ->whereNumber('sheet')
                    ->middleware('throttle:60,1')
                    ->name('show');
                Route::post('/', [GradeSheetController::class, 'store'])
                    ->middleware('throttle:30,1')
                    ->name('store');
                Route::post('/{sheet}/transition', [GradeSheetController::class, 'transition'])
                    ->whereNumber('sheet')
                    ->middleware('throttle:60,1')
                    ->name('transition');
                Route::post('/{sheet}/sync-entries', [GradeSheetController::class, 'syncEntries'])
                    ->whereNumber('sheet')
                    ->middleware('throttle:30,1')
                    ->name('sync-entries');
                Route::post('/{sheet}/documents', [GradeSheetDocumentController::class, 'upload'])
                    ->whereNumber('sheet')
                    ->middleware('throttle:20,1')
                    ->name('documents.upload');
                Route::get('/documents/{document}/download', [GradeSheetDocumentController::class, 'download'])
                    ->whereNumber('document')
                    ->middleware(['signed', 'throttle:30,1'])
                    ->name('documents.download');
            });

            Route::post('/academic-alerts/{alert}/transition', [AcademicAlertController::class, 'transition'])
                ->whereNumber('alert')
                ->middleware('throttle:30,1')
                ->name('academic-alerts.transition');

            Route::prefix('academic-assignments')->name('academic-assignments.')
                ->middleware('permission:academic_sheets.assign')
                ->group(function () {
                    Route::get('/', [AcademicAssignmentController::class, 'index'])
                        ->middleware('throttle:60,1')
                        ->name('index');
                    Route::post('/', [AcademicAssignmentController::class, 'store'])
                        ->middleware('throttle:30,1')
                        ->name('store');
                    Route::delete('/{assignment}', [AcademicAssignmentController::class, 'deactivate'])
                        ->whereNumber('assignment')
                        ->middleware('throttle:30,1')
                        ->name('deactivate');
                });
        });
    });

<?php

use App\Http\Controllers\AcademicPilotage\AcademicAssignmentController;
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

            Route::prefix('academic-assignments')->name('academic-assignments.')
                ->group(function () {
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

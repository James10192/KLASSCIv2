<?php

use App\Http\Controllers\AcademicPilotage\AcademicAssignmentController;
use App\Http\Controllers\AcademicPilotage\GradeSheetController;
use App\Http\Controllers\AcademicPilotage\GradeSheetDocumentController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('esbtp')->name('esbtp.')
    ->middleware([
        ForceJsonResponse::class,
        'auth',
        'installed',
        'force.password.change',
        'paywall',
        'permission:module.academic_pilotage.access',
    ])
    ->group(function () {
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

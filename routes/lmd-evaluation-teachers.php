<?php

use App\Http\Controllers\ESBTPLMDEvaluationTeacherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:module.lmd.access'])
    ->prefix('esbtp/lmd')
    ->name('esbtp.lmd.')
    ->group(function () {
        Route::get('evaluation-teacher/context', [ESBTPLMDEvaluationTeacherController::class, 'context'])
            ->name('evaluation-teacher.context');
        Route::post('evaluation-teacher/assign', [ESBTPLMDEvaluationTeacherController::class, 'assign'])
            ->middleware('permission:lmd.planning.edit')
            ->name('evaluation-teacher.assign');

        Route::get('bulletins/professeurs', [ESBTPLMDEvaluationTeacherController::class, 'professeurs'])
            ->name('bulletins.professeurs');
        Route::post('bulletins/professeurs', [ESBTPLMDEvaluationTeacherController::class, 'saveProfesseurs'])
            ->middleware('permission:lmd.planning.edit')
            ->name('bulletins.professeurs.save');
    });

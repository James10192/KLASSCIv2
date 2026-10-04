<?php

use App\Http\Controllers\ESBTPLMDEvaluationTeacherController;
use App\Http\Controllers\ESBTPLMDSessionTeacherController;
use App\Http\Controllers\ESBTPLMDTeacherPoolController;
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
        Route::post('evaluation-teacher/pool', [ESBTPLMDEvaluationTeacherController::class, 'savePool'])
            ->middleware('permission:lmd.planning.edit')
            ->name('evaluation-teacher.pool');

        Route::get('session-teacher/context', [ESBTPLMDSessionTeacherController::class, 'context'])
            ->name('session-teacher.context');

        Route::get('planning/teacher-pool', [ESBTPLMDTeacherPoolController::class, 'show'])
            ->name('planning.teacher-pool');

        Route::get('bulletins/professeurs', [ESBTPLMDEvaluationTeacherController::class, 'professeurs'])
            ->name('bulletins.professeurs');
        Route::post('bulletins/professeurs', [ESBTPLMDEvaluationTeacherController::class, 'saveProfesseurs'])
            ->middleware('permission:lmd.planning.edit')
            ->name('bulletins.professeurs.save');
    });
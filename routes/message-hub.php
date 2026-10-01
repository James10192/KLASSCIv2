<?php

use App\Http\Controllers\MessageHubActionController;
use App\Http\Controllers\MessageHubController;
use App\Http\Controllers\MessageHubEntityLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'paywall', 'permission:messages.send'])
    ->prefix('message-hub')
    ->name('message-hub.')
    ->group(function () {
        Route::get('/bootstrap', [MessageHubController::class, 'bootstrap'])
            ->middleware('throttle:120,1')
            ->name('bootstrap');

        Route::get('/conversations/{conversation}', [MessageHubController::class, 'conversation'])
            ->whereNumber('conversation')
            ->middleware('throttle:120,1')
            ->name('conversations.show');

        Route::patch('/conversations/{conversation}/state', [MessageHubController::class, 'updateConversationState'])
            ->whereNumber('conversation')
            ->middleware('throttle:60,1')
            ->name('conversations.state');

        Route::post('/legacy-actions/{notification}/read', [MessageHubController::class, 'markLegacyActionRead'])
            ->middleware('throttle:60,1')
            ->name('legacy-actions.read');

        Route::get('/actions/assignees', [MessageHubActionController::class, 'assignees'])
            ->middleware('throttle:60,1')
            ->name('actions.assignees');
        Route::post('/actions', [MessageHubActionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('actions.store');
        Route::get('/actions/{action}', [MessageHubActionController::class, 'show'])
            ->whereNumber('action')
            ->middleware('throttle:120,1')
            ->name('actions.show');
        Route::patch('/actions/{action}', [MessageHubActionController::class, 'update'])
            ->whereNumber('action')
            ->middleware('throttle:60,1')
            ->name('actions.update');

        Route::patch('/entity-links/{link}', [MessageHubEntityLinkController::class, 'update'])
            ->whereNumber('link')
            ->middleware('throttle:30,1')
            ->name('entity-links.update');
    });

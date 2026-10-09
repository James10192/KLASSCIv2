<?php

use App\Http\Controllers\ESBTP\AccesFamilialController;
use App\Http\Controllers\ESBTP\InvitationFamilialeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->group(function (): void {
    Route::get('/esbtp/famille/mes-enfants', [AccesFamilialController::class, 'espace'])
        ->name('esbtp.famille.espace');

    Route::middleware('permission:students.edit')->group(function (): void {
        Route::get('/esbtp/famille/habilitations', [AccesFamilialController::class, 'habilitations'])
            ->name('esbtp.famille.habilitations');
        Route::post('/esbtp/famille/habilitations', [AccesFamilialController::class, 'approuver'])
            ->middleware('throttle:10,1')->name('esbtp.famille.habilitations.approuver');
        Route::post('/esbtp/famille/habilitations/{grant}/inviter', [InvitationFamilialeController::class, 'inviter'])
            ->middleware('throttle:5,1')->name('esbtp.famille.invitation.envoyer');
        Route::post('/esbtp/famille/habilitations/{grant}/revoquer', [AccesFamilialController::class, 'revoquer'])
            ->middleware('throttle:10,1')->name('esbtp.famille.habilitations.revoquer');
    });
});

Route::middleware(['guest', 'throttle:10,1'])->group(function (): void {
    Route::get('/esbtp/famille/invitation/{token}', [InvitationFamilialeController::class, 'form'])
        ->name('esbtp.famille.invitation.form');
    Route::post('/esbtp/famille/invitation/{token}', [InvitationFamilialeController::class, 'activer'])
        ->middleware('throttle:5,1')->name('esbtp.famille.invitation.activer');
});

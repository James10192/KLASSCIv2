<?php

use App\Http\Controllers\API\CLI\CLICorrectionFautesController;
use App\Http\Controllers\API\CLI\CLIEmailsController;
use App\Http\Controllers\API\CLI\CLIRattrapageConvocationsController;
use App\Http\Controllers\API\CLI\CLISuiviConvocationsController;
use Illuminate\Support\Facades\Route;

/*
 * Inclus DANS le groupe /api/cli (auth:sanctum, noms `api.cli.*`) de routes/api.php.
 * Joignabilite des adresses et suivi reel des convocations. Chaque action
 * verifie son droit (`cli:read` en lecture, `cli:admin` en ecriture).
 */
Route::get('/emails/diagnostic', [CLIEmailsController::class, 'diagnostic'])->name('emails.diagnostic');
Route::post('/emails/nettoyer-factices', [CLIEmailsController::class, 'nettoyerFactices'])
    ->middleware('throttle:10,1')->name('emails.nettoyer-factices');
// Fautes de frappe (gmai.com...) : propositions, puis correction cle par cle validee par l'ecole.
Route::post('/emails/corriger-fautes', CLICorrectionFautesController::class)
    ->middleware('throttle:10,1')->name('emails.corriger-fautes');
Route::post('/rendez-vous/synchroniser-convocations', [CLISuiviConvocationsController::class, 'synchroniser'])
    ->middleware('throttle:10,1')->name('rendez-vous.synchroniser-convocations');
Route::get('/rendez-vous/familles', [CLISuiviConvocationsController::class, 'familles'])->name('rendez-vous.familles');
// Convocations d'avant le suivi : rattacher l'identifiant MailPulse (simulation par defaut).
Route::post('/rendez-vous/rattrapage-convocations', CLIRattrapageConvocationsController::class)
    ->middleware('throttle:5,1')->name('rendez-vous.rattrapage-convocations');

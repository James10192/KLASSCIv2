<?php

use Illuminate\Support\Facades\Route;

/*
 * Inclus DANS le groupe /api/cli (auth:sanctum, noms `api.cli.*`) de routes/api.php.
 * Joignabilite des adresses et remise reelle des convocations : lecture seule, `cli:read`.
 */
Route::get('/emails/diagnostic', [App\Http\Controllers\API\CLI\CLIEmailsController::class, 'diagnostic'])->name('emails.diagnostic');

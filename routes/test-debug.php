<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTP\ESBTPComptabiliteController;

// Route de test temporaire pour diagnostiquer les problèmes d'interaction
Route::get('/test-interaction-debug', function () {
    return view('test-interaction-debug');
})->name('test.interaction.debug');

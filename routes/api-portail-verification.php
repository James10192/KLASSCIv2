<?php

use Illuminate\Support\Facades\Route;

/*
 * Verification du contact (e-mail ou WhatsApp) d'une demande deposee sur le
 * portail. Memes routes pour les deux canaux, champ `canal` dans le corps.
 * Signees par le site vitrine comme toutes les routes publiques ; seau de
 * debit a part (`verification`). Le renvoi a en plus son propre debit par
 * demande (RenvoiVerification). Le corps (code, jeton) n'est jamais journalise.
 */
Route::prefix('portail/email')
    ->withoutMiddleware(['throttle:api'])
    ->middleware('portail.public:verification')
    ->group(function () {
        Route::post('/verifier', [\App\Http\Controllers\API\Public\VerificationContactPortalController::class, 'verifier'])
            ->name('api.portail.email.verifier');
        Route::post('/renvoyer', [\App\Http\Controllers\API\Public\VerificationContactPortalController::class, 'renvoyer'])
            ->name('api.portail.email.renvoyer');
    });

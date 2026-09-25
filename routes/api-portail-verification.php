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

/*
 * Suivi d'un dossier deja depose : situation, adresse e-mail a verifier ou
 * corriger, convocation a recevoir, reference oubliee (envoyee par e-mail).
 * Canal `verification`, toujours ouvert : une saison qui se ferme ne doit pas
 * laisser une famille sans acces a son rendez-vous. Le plancher de duree rend
 * « trouve » et « introuvable » indiscernables.
 */
Route::prefix('public/suivi')
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['portail.public:verification', 'reinscription.plancher'])
    ->group(function () {
        $c = \App\Http\Controllers\API\Public\SuiviDossierPortalController::class;
        Route::post('/consulter', [$c, 'consulter'])->name('api.public.suivi.consulter');
        Route::post('/email', [$c, 'email'])->name('api.public.suivi.email');
        Route::post('/verifier', [$c, 'verifier'])->name('api.public.suivi.verifier');
        Route::post('/convocation', [$c, 'convocation'])->name('api.public.suivi.convocation');
        Route::post('/reference-oubliee', [$c, 'referenceOubliee'])->name('api.public.suivi.reference-oubliee');
    });

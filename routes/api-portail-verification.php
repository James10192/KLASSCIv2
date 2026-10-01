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
 * Renvoi d'une convocation existante. Cette route reste separee de la creation
 * ou du deplacement du rendez-vous : elle ne peut donc jamais reserver un
 * second creneau. Le couple reference + date de naissance est reverifie avant
 * de remettre la convocation dans la file multicanale.
 */
Route::post('public/rendez-vous/renvoyer', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'renvoyer'])
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
    ->name('api.public.rendez-vous.renvoyer');

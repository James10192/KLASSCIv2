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
 * Suivi d'une verification WhatsApp inversee : le site l'appelle toutes les
 * cinq secondes pendant que la famille envoie le code depuis WhatsApp. Seau
 * `catalogue` (plus large) et non `identite` : dix appels par minute et par
 * adresse, partages avec la saisie et le renvoi, couperaient le suivi au bout
 * d'une minute. Ce point d'entree ne lit qu'un identifiant aleatoire de
 * demande, et n'ecrit qu'apres le verdict de MailPulse.
 */
Route::post('portail/email/statut', [\App\Http\Controllers\API\Public\VerificationContactPortalController::class, 'statut'])
    ->withoutMiddleware(['throttle:api'])
    ->middleware('portail.public:verification,catalogue')
    ->name('api.portail.email.statut');

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

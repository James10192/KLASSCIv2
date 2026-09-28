<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPClasseController;
use App\Http\Controllers\ESBTPEtudiantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * Export securise de reinscription — SEULE surface non authentifiee de
 * l'application. Consomme par le site klassci.com, qui signe chaque appel avec
 * le secret partage de l'etablissement.
 *
 * La limitation de debit ne figure PAS ici, volontairement : Laravel trie la
 * pile d'intergiciels par `middlewarePriority`, ou `ThrottleRequests` figure,
 * si bien que l'ordre ecrit dans ce fichier n'est pas l'ordre d'execution — un
 * `throttle:` pose ici passait AVANT le garde, et comptait donc sur des champs
 * non authentifies. Elle vit desormais dans le garde, apres verification de la
 * signature. Voir PortailPublicGuard.
 *
 * `throttle:api` du groupe API est retire pour la meme famille de raisons : il
 * compte sur `$request->ip()`, qui vaut ici l'adresse de sortie du site
 * vitrine — partagee par toute l'ecole, et renouvelee a chaque demarrage a
 * froid chez l'hebergeur. Le laisser plafonnerait le portail a 60 requetes par
 * minute pour l'etablissement entier, et permettrait a un voisin d'hebergement
 * de fermer le canal.
 *
 * Le plancher de temps de reponse n'enveloppe que le traitement reel, pas les
 * refus : un refus rapide ne revele rien, et le faire attendre offrirait un
 * amplificateur de deni de service.
 */
Route::prefix('public/reinscription')
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['portail.public', 'reinscription.plancher'])
    ->group(function () {
        Route::post('/lookup', [\App\Http\Controllers\API\Public\ReinscriptionPortalController::class, 'lookup'])
            ->name('api.public.reinscription.lookup');
        Route::post('/submit', [\App\Http\Controllers\API\Public\ReinscriptionPortalController::class, 'submit'])
            ->name('api.public.reinscription.submit');
    });

/*
 * Candidatures des NOUVEAUX etudiants. Meme garde, meme signature, meme fenetre
 * de dates — seul l'interrupteur differe, une ecole pouvant vouloir reinscrire
 * les siens sans ouvrir aux exterieurs, ou l'inverse.
 *
 * Pas de plancher de temps de reponse ici, et c'est deliberé : le plancher
 * existe pour rendre indistinguables « ce dossier existe » et « il n'existe
 * pas ». Une candidature ne consulte aucun dossier — il n'y a rien a
 * enumerer — donc rien a masquer, et faire attendre chaque envoi ne
 * protegerait personne tout en immobilisant un processus PHP.
 */
Route::prefix('public/inscription')
    ->withoutMiddleware(['throttle:api'])
    ->group(function () {
        // Le garde est declare POINT PAR POINT, et non sur le groupe : les
        // deux entrees ne pesent pas pareil, et un garde de groupe s'ajouterait
        // a celui de la route au lieu de le remplacer — chaque appel serait
        // alors compte deux fois, dans deux seaux differents.
        //
        // `catalogue` : /choix ne sert que des noms de filieres, de niveaux et
        // de nationalites — rien d'un etudiant, rien a enumerer. Il compte donc
        // dans un seau a part et large, sans quoi OUVRIR le formulaire couterait
        // autant que le deposer, et la file d'attente devant le formulaire
        // fermerait le canal des envois.
        Route::post('/choix', [\App\Http\Controllers\API\Public\CandidaturePortalController::class, 'choix'])
            ->middleware('portail.public:candidatures,catalogue')
            ->name('api.public.inscription.choix');
        Route::post('/submit', [\App\Http\Controllers\API\Public\CandidaturePortalController::class, 'submit'])
            ->middleware('portail.public:candidatures')
            ->name('api.public.inscription.submit');
    });

Route::prefix('public/rendez-vous')
    ->withoutMiddleware(['throttle:api'])
    ->group(function () {
        Route::post('/creneaux', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'creneaux'])
            ->middleware('portail.public:rendezvous,catalogue')
            ->name('api.public.rendez-vous.creneaux');
        Route::post('/reserver', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'reserver'])
            ->middleware('portail.public:rendezvous')
            ->name('api.public.rendez-vous.reserver');
        Route::post('/consulter', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'consulter'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.consulter');
        Route::post('/renvoyer', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'renvoyer'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.renvoyer');
        Route::post('/deplacer', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'deplacer'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.deplacer');
        Route::post('/annuler', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'annuler'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.annuler');
        Route::post('/retrouver', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'retrouver'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.retrouver');
    });

require __DIR__.'/api-portail-verification.php';

/*
 * Identite publique de l'etablissement, lue par le site klassci.com.
 *
 * Le site affiche les logos des ecoles qu'il sert, et habille le formulaire
 * d'inscription aux couleurs de celle qu'on a choisie. Les deux viennent des
 * reglages que l'ecole a deja remplis pour ses documents PDF : elle ne
 * configure son identite qu'une fois, et elle vaut partout.
 *
 * Pas de signature, contrairement aux deux autres surfaces publiques : celles-
 * la parlent d'un etudiant, celle-ci ne parle que de l'etablissement, et ne
 * sert rien qu'il n'imprime deja en tete de chaque bulletin. `throttle:api` du
 * groupe suffit : il ne protege ici aucun secret, il empeche seulement qu'on
 * se serve du point d'entree comme hebergeur d'images.
 */
Route::prefix('public/etablissement')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\Public\EtablissementPublicController::class, 'show'])
        ->name('api.public.etablissement');
    Route::get('/logo', [\App\Http\Controllers\API\Public\EtablissementPublicController::class, 'logo'])
        ->name('api.public.etablissement.logo');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// MailPulse is the transport only. KLASSCI validates and answers parent requests itself.
Route::post('/v1/integrations/mailpulse/parent-chatbot/inbound', App\Http\Controllers\API\ParentChatbotInboundController::class)
    ->middleware('throttle:30,1')
    ->name('api.mailpulse.parent-chatbot.inbound');

// Throttled because the link is unauthenticated by design and each hit renders
// a full PDF: a link forwarded into a group chat must not become a CPU sink.
Route::get('/v1/parent-chatbot/report-cards/{bulletin}', App\Http\Controllers\ParentChatbotReportCardController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('parent-chatbot.report-card');

// Routes API pour ESBTP
//
// Ces trois adresses rendaient la structure academique complete de l ecole —
// classes, capacites, effectifs, filieres, niveaux — a qui la demandait, sans
// authentification, sur toutes les instances. Leurs seuls appelants etaient des
// pages de test laissees dans public/, supprimees en meme temps ; l application,
// elle, passe par les routes web equivalentes, qui sont protegees.
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieresForApi'])
        ->name('api.classes.matieres');
});

// Routes pour le calcul des absences
Route::middleware(['auth:sanctum'])->prefix('absences')->group(function () {
    Route::post('/calculer', 'App\Http\Controllers\ESBTPCalculAbsencesController@calculerAbsencesEtudiant');
    Route::post('/resume-par-seance', 'App\Http\Controllers\ESBTPCalculAbsencesController@resumeAbsencesParSeance');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Attendance sync route
    Route::post('/attendance/sync', [App\Http\Controllers\ESBTP\Api\AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});

Route::middleware(['auth:sanctum'])->get('/classes/{id}/available-places', [ESBTPClasseController::class, 'getAvailablePlaces']);

Route::middleware(['auth:sanctum'])->post('/inscriptions/validate', [ESBTPEtudiantController::class, 'validateInscription'])->name('api.inscriptions.validate');

Route::middleware(['auth:sanctum'])->get('/classes', [ESBTPClasseController::class, 'indexApi']);

/*
|--------------------------------------------------------------------------
| API Routes LMS - KLASSCI Integration
|--------------------------------------------------------------------------
|
| Routes pour l'intégration entre le LMS et KLASSCI.
| Ces routes permettent au LMS d'accéder aux données KLASSCI
| et d'envoyer les résultats (notes, présences) vers KLASSCI.
|
*/

// Routes d'authentification LMS (sans middleware auth)
Route::prefix('lms/auth')->group(function () {
    // Meme limiteur que la connexion web : 5 essais par minute et par identifiant,
    // 10 par minute et par IP. Sans lui, cette porte n heritait que du plafond
    // general de 60 par minute — soit douze fois plus d essais de mot de passe
    // que par le formulaire, sur les memes comptes.
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.lms.auth.login');
    Route::get('/documentation', [App\Http\Controllers\API\AuthController::class, 'documentation'])
        ->name('api.lms.auth.docs');

    // Découverte multi-tenant (rate-limited, sans auth)
    Route::middleware('throttle:lms-discovery')->group(function () {
        Route::post('/check-user', [App\Http\Controllers\API\AuthController::class, 'checkUser'])
            ->name('api.lms.auth.check-user');
        Route::post('/check-availability', [App\Http\Controllers\API\AuthController::class, 'checkAvailability'])
            ->name('api.lms.auth.check-availability');
    });
});

// Informations publiques du tenant (sans auth, rate-limited)
Route::middleware('throttle:api')->get('lms/tenant-info', [App\Http\Controllers\API\AuthController::class, 'tenantInfo'])
    ->name('api.lms.tenant-info');

// Routes LMS protégées par authentification Sanctum
Route::middleware(['auth:sanctum'])->prefix('lms')->name('api.lms.')->group(function () {

    // ================================
    // AUTHENTIFICATION & PROFIL
    // ================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [App\Http\Controllers\API\AuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
        Route::post('/logout-all', [App\Http\Controllers\API\AuthController::class, 'logoutAll']);
    });

    // Suite du fichier inchangée dans le dépôt : cette mise à jour volontairement
    // localisée est complétée ci-dessous par les routes existantes.
});

<?php

use App\Http\Controllers\ESBTP\ManagedActivationController;
use App\Http\Controllers\ESBTP\ManagedCashierEntryController;
use App\Http\Controllers\ESBTP\ManagedInscriptionCompletionController;
use App\Http\Controllers\ESBTP\ManagedInscriptionQueueController;
use App\Http\Controllers\ESBTP\ManagedInscriptionStepController;
use App\Http\Controllers\ESBTP\ManagedInscriptionWorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parcours d'inscription configurable
|--------------------------------------------------------------------------
|
| Ce fichier est charge APRES routes/web.php. Les deux routes historiques de
| preinscription caisse sont donc volontairement redeclarees ici : quand le
| workflow gere est actif elles ouvrent la file des candidatures acceptees ;
| quand il est OFF le controleur delegue au flux historique sans le modifier.
|
*/

Route::middleware([
    'auth',
    'permission:admin.access|identity.direct_studies|identity.registrar|identity.registrar_clerk|identity.enrollment_officer',
    'paywall',
    'permission:inscriptions.create',
])->group(function () {
    Route::get('/esbtp/inscriptions/pre-inscription', [ManagedCashierEntryController::class, 'show'])
        ->name('esbtp.inscriptions.pre-inscription');
    Route::post('/esbtp/inscriptions/pre-inscription', [ManagedCashierEntryController::class, 'store'])
        ->name('esbtp.inscriptions.store-pre-inscription');
});

Route::prefix('esbtp/admissions/workflow')
    ->name('esbtp.admissions.workflow.')
    ->group(function () {
        // E-mail : jeton aléatoire à usage unique, hashé en base.
        Route::get('/activation/{token}', [ManagedInscriptionWorkflowController::class, 'activationForm'])
            ->middleware('throttle:20,1')
            ->name('activation.form');
        Route::post('/activation/{token}', [ManagedInscriptionWorkflowController::class, 'activate'])
            ->middleware('throttle:10,1')
            ->name('activation.submit');

        // WhatsApp : URL Laravel signée et expirante. Le GET ouvre le formulaire
        // puis génère un POST signé court ; aucune donnée secrète n'est placée
        // dans le message WhatsApp ou conservée en clair côté serveur.
        Route::get('/activation-whatsapp/{workflow}', [ManagedActivationController::class, 'signedForm'])
            ->middleware(['signed', 'throttle:20,1'])
            ->name('activation.signed.form');
        Route::post('/activation-whatsapp/{workflow}', [ManagedActivationController::class, 'signedActivate'])
            ->middleware(['signed', 'throttle:10,1'])
            ->name('activation.signed.submit');

        Route::middleware(['auth', 'paywall'])->group(function () {
            Route::get('/mon-dossier', [ManagedInscriptionWorkflowController::class, 'student'])
                ->name('student');
            Route::post('/mon-dossier/profil', [ManagedInscriptionWorkflowController::class, 'updateStudentProfile'])
                ->middleware('throttle:20,1')
                ->name('student.profile');
            Route::post('/mon-dossier/classe', [ManagedInscriptionCompletionController::class, 'chooseClass'])
                ->middleware('throttle:20,1')
                ->name('student.choose-class');

            // Lecture : la caisse, l'inscription et la scolarité ouvrent le
            // même dossier, chacun n'agit que sur son étape.
            Route::get('/', [ManagedInscriptionQueueController::class, 'index'])
                ->middleware('permission:paiements.create|inscriptions.create|pieces_dossier.suivre')
                ->name('index');
            Route::get('/candidatures/{candidature}', [ManagedInscriptionWorkflowController::class, 'show'])
                ->middleware('permission:paiements.create|inscriptions.create|pieces_dossier.suivre')
                ->name('show');

            // Caisse : encaisser et valider un paiement. Rien d'académique.
            // L'ordre choisi par le tenant est imposé dans le contrôleur
            // d'étape : l'URL directe ne contourne rien.
            Route::post('/candidatures/{candidature}/paiement', [ManagedInscriptionStepController::class, 'pay'])
                ->middleware([
                    'permission:paiements.create',
                    'permission:paiements.validate',
                    'throttle:20,1',
                ])
                ->name('pay');

            Route::post('/{workflow}/activation/renvoyer', [ManagedActivationController::class, 'resend'])
                ->middleware(['permission:inscriptions.validate|pieces_dossier.suivre', 'throttle:10,1'])
                ->name('activation.resend');

            // Décisions académiques : affecter une classe, finaliser. Le droit
            // existant « valider une inscription » — que la caisse n'a pas.
            Route::middleware('permission:inscriptions.validate')->group(function () {
                Route::post('/{workflow}/classe', [ManagedInscriptionWorkflowController::class, 'chooseClassAsAdmin'])
                    ->middleware('throttle:20,1')
                    ->name('class.choose-admin');
                Route::post('/{workflow}/finaliser', [ManagedInscriptionCompletionController::class, 'finalize'])
                    ->middleware('throttle:20,1')
                    ->name('finalize');
            });

            Route::middleware('permission:pieces_dossier.suivre')->group(function () {
                Route::post('/{workflow}/pieces', [ManagedInscriptionStepController::class, 'receivePiece'])
                    ->middleware('throttle:60,1')
                    ->name('pieces.receive');
                Route::post('/{workflow}/pieces/{depot}/decision', [ManagedInscriptionStepController::class, 'decidePiece'])
                    ->middleware('throttle:60,1')
                    ->name('pieces.decide');
                Route::post('/{workflow}/pieces/valider-dossier', [ManagedInscriptionStepController::class, 'validateDocuments'])
                    ->middleware('throttle:20,1')
                    ->name('pieces.validate');
            });

            // Le droit existe déjà dans le registre et signifie précisément
            // « corriger une inscription validée ». On le réutilise plutôt que
            // d'introduire un droit orphelin uniquement pour ce pilote.
            Route::post('/{workflow}/classe/override', [ManagedInscriptionWorkflowController::class, 'overrideClass'])
                ->middleware(['permission:inscriptions.edit_validated', 'throttle:20,1'])
                ->name('class.override');
        });
    });

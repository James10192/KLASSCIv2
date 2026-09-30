<?php

use App\Http\Controllers\ESBTP\ManagedCashierEntryController;
use App\Http\Controllers\ESBTP\ManagedInscriptionCompletionController;
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
        // Le lien d'activation doit fonctionner avant la premiere connexion ; il
        // ne peut donc pas dependre du middleware auth. Le service verifie le
        // tenant, l'empreinte du jeton, son expiration et son usage unique.
        Route::get('/activation/{token}', [ManagedInscriptionWorkflowController::class, 'activationForm'])
            ->middleware('throttle:20,1')
            ->name('activation.form');
        Route::post('/activation/{token}', [ManagedInscriptionWorkflowController::class, 'activate'])
            ->middleware('throttle:10,1')
            ->name('activation.submit');

        Route::middleware(['auth', 'paywall'])->group(function () {
            Route::get('/mon-dossier', [ManagedInscriptionWorkflowController::class, 'student'])
                ->name('student');
            Route::post('/mon-dossier/profil', [ManagedInscriptionWorkflowController::class, 'updateStudentProfile'])
                ->middleware('throttle:20,1')
                ->name('student.profile');
            Route::post('/mon-dossier/classe', [ManagedInscriptionCompletionController::class, 'chooseClass'])
                ->middleware('throttle:20,1')
                ->name('student.choose-class');

            // La caisse et la scolarite doivent pouvoir ouvrir le meme dossier
            // sans qu'on leur donne les droits de l'autre metier.
            Route::get('/candidatures/{candidature}', [ManagedInscriptionWorkflowController::class, 'show'])
                ->middleware('permission:inscriptions.create|pieces_dossier.suivre')
                ->name('show');

            Route::middleware('permission:inscriptions.create')->group(function () {
                Route::get('/', [ManagedInscriptionWorkflowController::class, 'index'])->name('index');

                // Cette action cree ET valide le versement. Un etablissement qui
                // retire la validation au caissier conserve donc sa separation des
                // taches : ce flux ne la contourne pas.
                Route::post('/candidatures/{candidature}/paiement', [ManagedInscriptionWorkflowController::class, 'pay'])
                    ->middleware([
                        'permission:paiements.create',
                        'permission:paiements.validate',
                        'throttle:20,1',
                    ])
                    ->name('pay');

                Route::post('/{workflow}/activation/renvoyer', [ManagedInscriptionWorkflowController::class, 'resendActivation'])
                    ->middleware('throttle:10,1')
                    ->name('activation.resend');
                Route::post('/{workflow}/classe', [ManagedInscriptionWorkflowController::class, 'chooseClassAsAdmin'])
                    ->middleware('throttle:20,1')
                    ->name('class.choose-admin');
                Route::post('/{workflow}/finaliser', [ManagedInscriptionCompletionController::class, 'finalize'])
                    ->middleware('throttle:20,1')
                    ->name('finalize');
            });

            Route::middleware('permission:pieces_dossier.suivre')->group(function () {
                Route::post('/{workflow}/pieces', [ManagedInscriptionWorkflowController::class, 'receivePiece'])
                    ->middleware('throttle:60,1')
                    ->name('pieces.receive');
                Route::post('/{workflow}/pieces/{depot}/decision', [ManagedInscriptionWorkflowController::class, 'decidePiece'])
                    ->middleware('throttle:60,1')
                    ->name('pieces.decide');
                Route::post('/{workflow}/pieces/valider-dossier', [ManagedInscriptionWorkflowController::class, 'validateDocuments'])
                    ->middleware('throttle:20,1')
                    ->name('pieces.validate');
            });

            // Le droit existe deja dans le registre et signifie precisement
            // « corriger une inscription validee ». On le reutilise plutot que
            // d'introduire un droit orphelin uniquement pour ce pilote.
            Route::post('/{workflow}/classe/override', [ManagedInscriptionWorkflowController::class, 'overrideClass'])
                ->middleware(['permission:inscriptions.edit_validated', 'throttle:20,1'])
                ->name('class.override');
        });
    });

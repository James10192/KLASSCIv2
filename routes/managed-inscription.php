<?php

use App\Http\Controllers\ESBTP\ManagedInscriptionWorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parcours d'inscription configurable
|--------------------------------------------------------------------------
|
| Ce fichier est séparé de routes/web.php pour que le pilote reste lisible et
| retirable. L'activation par jeton est publique ; tout le reste conserve les
| permissions déjà employées par la caisse, le suivi des pièces et les
| inscriptions. Le comportement reste inerte quand le setting du tenant est OFF.
|
*/

Route::prefix('esbtp/admissions/workflow')
    ->name('esbtp.admissions.workflow.')
    ->group(function () {
        // Le lien d'activation doit fonctionner avant la première connexion ; il
        // ne peut donc pas dépendre du middleware auth. Le service vérifie le
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
            Route::post('/mon-dossier/classe', [ManagedInscriptionWorkflowController::class, 'chooseClass'])
                ->middleware('throttle:20,1')
                ->name('student.choose-class');

            // La caisse et la scolarité doivent pouvoir ouvrir le même dossier
            // sans qu'on leur donne les droits de l'autre métier.
            Route::get('/candidatures/{candidature}', [ManagedInscriptionWorkflowController::class, 'show'])
                ->middleware('permission:inscriptions.create|pieces_dossier.suivre')
                ->name('show');

            Route::middleware('permission:inscriptions.create')->group(function () {
                Route::get('/', [ManagedInscriptionWorkflowController::class, 'index'])->name('index');

                // Cette action crée ET valide le versement. Un établissement qui
                // retire la validation au caissier conserve donc sa séparation des
                // tâches : ce flux ne la contourne pas.
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
                Route::post('/{workflow}/finaliser', [ManagedInscriptionWorkflowController::class, 'finalize'])
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

            // Le droit existe déjà dans le registre et signifie précisément
            // « corriger une inscription validée ». On le réutilise plutôt que
            // d'introduire un droit orphelin uniquement pour ce pilote.
            Route::post('/{workflow}/classe/override', [ManagedInscriptionWorkflowController::class, 'overrideClass'])
                ->middleware(['permission:inscriptions.edit_validated', 'throttle:20,1'])
                ->name('class.override');
        });
    });

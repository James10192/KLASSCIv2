<?php

namespace Tests\Feature\Admissions;

use App\Http\Controllers\ESBTP\ManagedActivationController;
use App\Http\Controllers\ESBTP\ManagedCashierEntryController;
use App\Http\Controllers\ESBTP\ManagedInscriptionCompletionController;
use App\Http\Controllers\ESBTP\ManagedInscriptionQueueController;
use App\Http\Controllers\ESBTP\ManagedInscriptionStepController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ManagedInscriptionWorkflowRoutesTest extends TestCase
{
    /** @test */
    public function les_routes_du_parcours_gere_sont_enregistrees(): void
    {
        foreach ([
            'esbtp.admissions.workflow.index',
            'esbtp.admissions.workflow.show',
            'esbtp.admissions.workflow.pay',
            'esbtp.admissions.workflow.pieces.receive',
            'esbtp.admissions.workflow.pieces.decide',
            'esbtp.admissions.workflow.pieces.validate',
            'esbtp.admissions.workflow.activation.form',
            'esbtp.admissions.workflow.activation.submit',
            'esbtp.admissions.workflow.activation.signed.form',
            'esbtp.admissions.workflow.activation.signed.submit',
            'esbtp.admissions.workflow.activation.resend',
            'esbtp.admissions.workflow.student',
            'esbtp.admissions.workflow.student.profile',
            'esbtp.admissions.workflow.student.choose-class',
            'esbtp.admissions.workflow.class.choose-admin',
            'esbtp.admissions.workflow.class.override',
            'esbtp.admissions.workflow.finalize',
        ] as $route) {
            $this->assertTrue(Route::has($route), "Route absente : {$route}");
        }
    }

    /** @test */
    public function la_preinscription_historique_passe_par_le_point_entree_configurable(): void
    {
        $get = Route::getRoutes()->getByName('esbtp.inscriptions.pre-inscription');
        $post = Route::getRoutes()->getByName('esbtp.inscriptions.store-pre-inscription');

        $this->assertNotNull($get);
        $this->assertNotNull($post);
        $this->assertSame(ManagedCashierEntryController::class.'@show', $get->getActionName());
        $this->assertSame(ManagedCashierEntryController::class.'@store', $post->getActionName());

        $matchedGet = Route::getRoutes()->match(Request::create('/esbtp/inscriptions/pre-inscription', 'GET'));
        $matchedPost = Route::getRoutes()->match(Request::create('/esbtp/inscriptions/pre-inscription', 'POST'));
        $this->assertSame(ManagedCashierEntryController::class.'@show', $matchedGet->getActionName());
        $this->assertSame(ManagedCashierEntryController::class.'@store', $matchedPost->getActionName());
    }

    /** @test */
    public function la_file_et_les_ecritures_physiques_passent_par_la_garde_de_sequence(): void
    {
        $index = Route::getRoutes()->getByName('esbtp.admissions.workflow.index');
        $pay = Route::getRoutes()->getByName('esbtp.admissions.workflow.pay');
        $receive = Route::getRoutes()->getByName('esbtp.admissions.workflow.pieces.receive');
        $validate = Route::getRoutes()->getByName('esbtp.admissions.workflow.pieces.validate');

        $this->assertSame(ManagedInscriptionQueueController::class.'@index', $index->getActionName());
        $this->assertSame(ManagedInscriptionStepController::class.'@pay', $pay->getActionName());
        $this->assertSame(ManagedInscriptionStepController::class.'@receivePiece', $receive->getActionName());
        $this->assertSame(ManagedInscriptionStepController::class.'@validateDocuments', $validate->getActionName());
    }

    /** @test */
    public function les_liens_whatsapp_sont_signes_et_le_renvoi_utilise_le_controleur_d_activation(): void
    {
        $form = Route::getRoutes()->getByName('esbtp.admissions.workflow.activation.signed.form');
        $submit = Route::getRoutes()->getByName('esbtp.admissions.workflow.activation.signed.submit');
        $resend = Route::getRoutes()->getByName('esbtp.admissions.workflow.activation.resend');

        $this->assertSame(ManagedActivationController::class.'@signedForm', $form->getActionName());
        $this->assertSame(ManagedActivationController::class.'@signedActivate', $submit->getActionName());
        $this->assertSame(ManagedActivationController::class.'@resend', $resend->getActionName());
        $this->assertContains('signed', $form->gatherMiddleware());
        $this->assertContains('signed', $submit->gatherMiddleware());
    }

    /** @test */
    public function la_finalisation_utilise_le_controleur_canonique_du_dossier_provisoire(): void
    {
        $studentChoice = Route::getRoutes()->getByName('esbtp.admissions.workflow.student.choose-class');
        $adminFinalize = Route::getRoutes()->getByName('esbtp.admissions.workflow.finalize');

        $this->assertNotNull($studentChoice);
        $this->assertNotNull($adminFinalize);
        $this->assertSame(ManagedInscriptionCompletionController::class.'@chooseClass', $studentChoice->getActionName());
        $this->assertSame(ManagedInscriptionCompletionController::class.'@finalize', $adminFinalize->getActionName());
    }
}

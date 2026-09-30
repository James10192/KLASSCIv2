<?php

namespace Tests\Feature\Admissions;

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
    public function le_flux_legacy_reste_enregistre_en_parallele(): void
    {
        $this->assertTrue(Route::has('esbtp.inscriptions.pre-inscription'));
        $this->assertTrue(Route::has('esbtp.inscriptions.store-pre-inscription'));
    }
}

<?php

namespace Tests\Unit\Exports;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Mockery;
use Tests\TestCase;

class EnseignantsExportRedirectRegressionTest extends TestCase
{
    private function autoriserSansBase(): void
    {
        $this->withoutMiddleware();

        // Le callback global de Spatie tente une lecture MySQL. Le contrat de
        // Gate est remplace uniquement pour ce test du rendu HTTP de l'export.
        $gate = Mockery::mock(Gate::class);
        $gate->shouldReceive('authorize')->with('teachers.view', [])
            ->once()->andReturn(Response::allow());
        $this->app->instance(Gate::class, $gate);
    }

    public function test_apercu_pdf_invalide_affiche_422_sans_redirection(): void
    {
        $this->autoriserSansBase();

        $reponse = $this->get(route('esbtp.enseignants.preview-pdf', ['status' => 'inconnu']));

        $reponse->assertStatus(422);
        $reponse->assertSee("Le document n'a pas pu être préparé", false);
        $reponse->assertSee('gestion des enseignants');
        $this->assertFalse($reponse->isRedirection());
    }

    public function test_telechargement_pdf_invalide_ne_renvoie_pas_302(): void
    {
        $this->autoriserSansBase();

        $reponse = $this->get(route('esbtp.enseignants.export-pdf', ['semestre' => '3']));

        $reponse->assertStatus(422);
        $this->assertFalse($reponse->isRedirection());
        $reponse->assertHeader('Cache-Control', 'no-store');
    }
}

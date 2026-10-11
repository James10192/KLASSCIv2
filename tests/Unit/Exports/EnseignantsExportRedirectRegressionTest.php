<?php

namespace Tests\Unit\Exports;

use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Access\Response;
use Mockery;
use Tests\TestCase;

class EnseignantsExportRedirectRegressionTest extends TestCase
{
    public function test_aperçu_pdf_invalide_affiche_un_message_422_au_lieu_de_revenir_aux_enseignants(): void
    {
        $this->withoutMiddleware();

        $user = new User();
        $user->id = 1;
        $this->be($user);
        // Spatie enregistre son propre Gate::before avant les tests : celui-ci
        // interroge MySQL. Injecter une porte factice pour tester la reponse
        // HTTP de l'export, sans base et sans changer les regles de production.
        $gate = Mockery::mock(Gate::class);
        $gate->shouldReceive('authorize')->with('teachers.view')
            ->once()->andReturn(Response::allow());
        $this->app->instance(Gate::class, $gate);

        $reponse = $this->get(route('esbtp.enseignants.preview-pdf', ['status' => 'inconnu']));

        $reponse->assertStatus(422);
        $reponse->assertSee("Le document n'a pas pu être préparé", false);
        $reponse->assertSee('gestion des enseignants');
        $this->assertFalse($reponse->isRedirection());
    }

    public function test_telechargement_pdf_invalide_ne_renvoie_pas_302(): void
    {
        $this->withoutMiddleware();

        $user = new User();
        $user->id = 1;
        $this->be($user);
        Gate::before(fn ($user, $ability) => $ability === 'teachers.view' ? true : null);

        $reponse = $this->get(route('esbtp.enseignants.export-pdf', ['semestre' => '3']));

        $reponse->assertStatus(422);
        $this->assertFalse($reponse->isRedirection());
        $reponse->assertHeader('Cache-Control', 'no-store');
    }
}

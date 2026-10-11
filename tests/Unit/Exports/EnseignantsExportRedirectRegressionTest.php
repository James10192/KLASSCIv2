<?php

namespace Tests\Unit\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EnseignantsExportRedirectRegressionTest extends TestCase
{
    public function test_aperçu_pdf_invalide_affiche_un_message_422_au_lieu_de_revenir_aux_enseignants(): void
    {
        $this->withoutMiddleware();

        $user = new User();
        $user->id = 1;
        $this->be($user);
        Gate::before(fn ($user, $ability) => $ability === 'teachers.view' ? true : null);

        $reponse = $this->get(route('esbtp.enseignants.preview-pdf', ['status' => 'inconnu']));

        $reponse->assertStatus(422);
        $reponse->assertSee('Le document n&#039;a pas pu être préparé', false);
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

<?php

namespace Tests\Feature\Resultat;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudentDocumentRouteAvailabilityTest extends TestCase
{
    public function test_bts_releve_routes_sont_enregistrees(): void
    {
        $this->assertTrue(Route::has('esbtp.bts.releves.preview'));
        $this->assertTrue(Route::has('esbtp.bts.releves.download'));
    }

    public function test_les_vues_bts_protegent_leurs_liens_de_releves(): void
    {
        $fiche = file_get_contents(resource_path('views/esbtp/partials/fiche-notes-contenu.blade.php'));
        $notes = file_get_contents(resource_path('views/esbtp/notes/index.blade.php'));

        $this->assertStringContainsString("Route::has('esbtp.bts.releves.preview')", $fiche);
        $this->assertStringContainsString("Route::has('esbtp.bts.releves.download')", $fiche);
        $this->assertStringContainsString("Route::has('esbtp.bts.releves.preview')", $notes);
        $this->assertStringContainsString('&& btsDocumentUrl', $notes);
    }

    public function test_le_controleur_transmet_l_inscription_selectionnee_a_la_vue(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ESBTPResultatController.php'));

        $this->assertMatchesRegularExpression(
            "/return view\\('esbtp\\.resultats\\.etudiant', compact\\(\\s*'etudiant',\\s*'inscription',/",
            $controller
        );
    }
}

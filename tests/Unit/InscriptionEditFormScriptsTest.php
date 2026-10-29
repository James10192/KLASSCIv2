<?php

namespace Tests\Unit;

use Tests\TestCase;

class InscriptionEditFormScriptsTest extends TestCase
{
    public function test_lmd_class_options_are_filtered_on_initial_load(): void
    {
        $script = file_get_contents(resource_path('views/esbtp/inscriptions/partials/edit-form-scripts.blade.php'));

        $initialStatePosition = strpos($script, '// État initial');
        $applyModePosition = strpos($script, 'applyModeFromNiveau();', $initialStatePosition);
        $filterPosition = strpos($script, 'filterClasses();', $applyModePosition);
        $placesPosition = strpos($script, "updatePlacesInfo($('#classe_id').val());", $filterPosition);

        $this->assertIsInt($initialStatePosition);
        $this->assertIsInt($applyModePosition);
        $this->assertIsInt($filterPosition);
        $this->assertIsInt($placesPosition);
    }

    public function test_le_formulaire_de_modification_transmet_l_annee_de_l_inscription(): void
    {
        $form = file_get_contents(resource_path('views/esbtp/inscriptions/partials/edit-form.blade.php'));
        $script = file_get_contents(resource_path('views/esbtp/inscriptions/partials/edit-form-scripts.blade.php'));

        $this->assertStringContainsString('data-annee-universitaire-id="{{ $inscription->annee_universitaire_id }}"', $form);
        $this->assertStringContainsString("form.attr('data-annee-universitaire-id')", $script);
        $this->assertStringContainsString('annee_universitaire_id=${encodeURIComponent(anneeUniversitaireId)}', $script);
        $this->assertStringContainsString('requestId !== placesRequestId', $script);
        $this->assertStringContainsString("String(data.annee_universitaire_id ?? '') !== anneeUniversitaireId", $script);
    }

    public function test_reinscription_inter_annees_interroge_la_bonne_annee_pour_chaque_classe(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/reinscription/create.blade.php'));
        $placesPartial = file_get_contents(resource_path('views/esbtp/reinscription/partials/places-availability-autre-js.blade.php'));
        $this->assertStringContainsString("@include('esbtp.reinscription.partials.places-availability-autre-js')", $view);
        $view .= $placesPartial;

        $this->assertStringContainsString("document.getElementById('annee_universitaire_id')?.value", $view);
        $this->assertStringContainsString('annee_universitaire_id=${encodeURIComponent(anneeId)}', $view);
        $this->assertSame(2, substr_count($view, 'fetch(placesUrl)'));
        $this->assertStringContainsString("document.getElementById('annee_universitaire_id')?.addEventListener('change'", $view);
        $this->assertStringContainsString('requestId !== autrePlacesRequestId', $view);
        $this->assertStringContainsString('afficherAutrePlacesDisponibles(placesInfo, data)', $view);
        $this->assertStringContainsString('requestId !== nouvellePlacesRequestId', $view);
        $this->assertSame(2, substr_count($view, '!reponsePlacesPourAnneeValide(data)'));
        $this->assertStringContainsString("String(data.annee_universitaire_id ?? '') === String(anneeId)", $view);
    }
}

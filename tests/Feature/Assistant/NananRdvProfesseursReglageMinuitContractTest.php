<?php

namespace Tests\Feature\Assistant;

use Tests\TestCase;

class NananRdvProfesseursReglageMinuitContractTest extends TestCase
{
    public function test_registre_expose_les_nouvelles_actions_nanan(): void
    {
        $source = file_get_contents(app_path('Domain/Assistant/Actions/RegistreDesActions.php'));

        $this->assertStringContainsString('ModifierEnseignant::class', $source);
        $this->assertStringContainsString('AnnulerRendezVous::class', $source);
        $this->assertStringContainsString('BasculerCreneauRdv::class', $source);
        $this->assertStringContainsString('ReglerFermetureJourMinuit::class', $source);
    }

    public function test_reglage_minuit_est_une_bascule_ecole_et_filtre_le_catalogue(): void
    {
        $reglages = file_get_contents(app_path('Services/RendezVous/RendezVousReglages.php'));
        $catalogue = file_get_contents(app_path('Services/RendezVous/CatalogueCreneaux.php'));
        $vue = file_get_contents(resource_path('views/esbtp/rendez-vous/partials/_reglages.blade.php'));

        $this->assertStringContainsString("inscriptions.rdv.fermer_jour_a_minuit", $reglages);
        $this->assertStringContainsString('self::FERMER_JOUR_A_MINUIT', $reglages);
        $this->assertStringContainsString('fermerJourAMinuit()', $catalogue);
        $this->assertStringContainsString('$debut->addDay()', $catalogue);
        $this->assertStringContainsString('Fermer automatiquement les rendez-vous du jour à 00:00', $vue);
    }

    public function test_actions_restent_bornees_aux_permissions_metier(): void
    {
        $enseignant = file_get_contents(app_path('Domain/Assistant/Actions/Enseignants/ModifierEnseignant.php'));
        $annulation = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/AnnulerRendezVous.php'));
        $creneau = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/BasculerCreneauRdv.php'));
        $minuit = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/ReglerFermetureJourMinuit.php'));

        $this->assertStringContainsString("can('teachers.edit')", $enseignant);
        $this->assertStringContainsString("can('inscriptions.rdv.manage')", $annulation);
        $this->assertStringContainsString("can('inscriptions.rdv.manage')", $creneau);
        $this->assertStringContainsString("can('inscriptions.rdv.configure')", $minuit);
        $this->assertStringContainsString("can('comptabilite.salaires.set_rate')", $enseignant);
    }
}

<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\Enseignants\ModifierProfilEnseignant;
use App\Domain\Assistant\Actions\Lmd\ConfirmerProfesseurClasseLmd;
use App\Domain\Assistant\Actions\RendezVous\GererRendezVousCible;
use App\Domain\Assistant\Outils\LireRendezVousCible;
use App\Services\RendezVous\RendezVousReglages;
use Tests\TestCase;

class NananRdvProfesseurAutonomieContractTest extends TestCase
{
    public function test_new_nanan_capabilities_are_registered_with_permissions(): void
    {
        $classes = config('assistant.actions.classes', []);
        $this->assertContains(GererRendezVousCible::class, $classes);
        $this->assertContains(ConfirmerProfesseurClasseLmd::class, $classes);

        $registre = file_get_contents(app_path('Domain/Assistant/Actions/RegistreDesActions.php'));
        $this->assertStringContainsString('ModifierProfilEnseignant::class', $registre);
        $profil = file_get_contents(app_path('Domain/Assistant/Actions/Enseignants/ModifierProfilEnseignant.php'));
        $this->assertStringContainsString("\$user->can('teachers.edit')", $profil);
        $this->assertStringContainsString("\$user->can('comptabilite.salaires.set_rate')", $profil);
        $this->assertStringContainsString('proposer_professeur_classe_lmd', $profil);

        $this->assertTrue(config('chatbot.tools.lire_rendez_vous_cible.enabled'));
        $this->assertContains('inscriptions.rdv.manage', config('chatbot.tools.lire_rendez_vous_cible.any_permissions'));
        $this->assertTrue(config('chatbot.tools.proposer_gestion_rendez_vous_cible.enabled'));
        $this->assertContains('inscriptions.rdv.accueil', config('chatbot.tools.proposer_gestion_rendez_vous_cible.any_permissions'));
        $this->assertSame(['lmd.planning.edit'], config('chatbot.tools.proposer_professeur_classe_lmd.all_permissions'));
    }

    public function test_targeted_rdv_reader_is_in_catalogue(): void
    {
        $source = file_get_contents(app_path('Domain/Assistant/Outils/CatalogueOutils.php'));
        $this->assertStringContainsString('new LireRendezVousCible()', $source);
        $this->assertStringContainsString('lire_rendez_vous_cible', (new LireRendezVousCible())->name());
    }

    public function test_midnight_setting_is_a_school_rdv_toggle_and_is_visible(): void
    {
        $this->assertContains(RendezVousReglages::FERMER_JOUR_A_MINUIT, RendezVousReglages::clesBascules());

        $view = file_get_contents(resource_path('views/esbtp/rendez-vous/partials/_reglages.blade.php'));
        $this->assertStringContainsString('FERMER_JOUR_A_MINUIT', $view);
        $this->assertStringContainsString('Fermer automatiquement la journée à minuit', $view);
    }

    public function test_midnight_rule_removes_today_from_catalogue_has_catchup_and_hard_write_guard(): void
    {
        $catalogue = file_get_contents(app_path('Services/RendezVous/CatalogueCreneaux.php'));
        $this->assertStringContainsString('fermerJourAMinuit()', $catalogue);
        $this->assertStringContainsString('Carbon::today()->addDay()', $catalogue);

        $reservation = file_get_contents(app_path('Models/ESBTPRdvReservation.php'));
        $this->assertStringContainsString("static::saving(function (self \$reservation)", $reservation);
        $this->assertStringContainsString("\$reservation->isDirty('creneau_id')", $reservation);
        $this->assertStringContainsString('La journée de ce créneau est fermée depuis minuit', $reservation);

        $console = file_get_contents(base_path('routes/console.php'));
        $this->assertStringContainsString('inscriptions:fermer-creneaux-rdv-du-jour', $console);
        $this->assertStringContainsString('->everyMinute()', $console);
    }

    public function test_nanan_rdv_action_covers_targeted_workflow_and_setting_toggle(): void
    {
        $source = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/GererRendezVousCible.php'));
        foreach (['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau', 'fermeture_jour_minuit'] as $mode) {
            $this->assertStringContainsString("'{$mode}'", $source);
        }
        $this->assertStringContainsString('proposer_convocations_rdv', $source);
        $this->assertStringContainsString('FermetureAutomatiqueCreneaux::class', $source);
    }

    public function test_lmd_professor_action_uses_canonical_class_teacher_service(): void
    {
        $source = file_get_contents(app_path('Domain/Assistant/Actions/Lmd/ConfirmerProfesseurClasseLmd.php'));
        $this->assertStringContainsString('EnseignantDeClasseLmd', $source);
        $this->assertStringContainsString('$this->enseignants->resoudre', $source);
        $this->assertStringContainsString('$this->enseignants->confirmer', $source);
        $this->assertStringContainsString('bulletin(s) déjà publié(s) restent figés', $source);
    }
}

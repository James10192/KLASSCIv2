<?php

namespace Tests\Feature\Assistant;

use Tests\TestCase;

class NananRendezVousEnseignantsContractTest extends TestCase
{
    public function test_midnight_closure_is_a_setting_scheduler_and_hard_booking_guard(): void
    {
        $settings = file_get_contents(app_path('Services/RendezVous/RendezVousReglages.php'));
        $catalogue = file_get_contents(app_path('Services/RendezVous/CatalogueCreneaux.php'));
        $reservateur = file_get_contents(app_path('Services/RendezVous/ReservateurRdv.php'));
        $kernel = file_get_contents(app_path('Console/Kernel.php'));
        $view = file_get_contents(resource_path('views/esbtp/rendez-vous/partials/_reglages.blade.php'));

        $this->assertStringContainsString("FERMER_JOUR_A_MINUIT = 'inscriptions.rdv.fermer_jour_a_minuit'", $settings);
        $this->assertStringContainsString('self::FERMER_JOUR_A_MINUIT', $settings);
        $this->assertStringContainsString('Carbon::tomorrow()->startOfDay()', $catalogue);
        $this->assertStringContainsString('$this->reglages->fermerJourAMinuit()', $reservateur);
        $this->assertStringContainsString("->dailyAt('00:00')", $kernel);
        $this->assertStringContainsString('inscriptions:fermer-creneaux-rdv-du-jour', $kernel);
        $this->assertStringContainsString('Fermer les créneaux du jour à minuit', $view);
    }

    public function test_nanan_has_targeted_rdv_search_management_and_configuration(): void
    {
        $catalogue = file_get_contents(app_path('Domain/Assistant/Outils/CatalogueOutils.php'));
        $registry = file_get_contents(config_path('assistant_tools_nanan.php'));
        $action = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/GererRendezVous.php'));
        $config = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/ConfigurerRendezVous.php'));

        $this->assertStringContainsString('app(RechercherRendezVous::class)', $catalogue);
        $this->assertStringContainsString("'rechercher_rendez_vous'", $registry);
        $this->assertStringContainsString("'inscriptions.rdv.manage'", $registry);
        $this->assertStringContainsString("'inscriptions.rdv.configure'", $registry);
        foreach (['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau'] as $mode) {
            $this->assertStringContainsString("'{$mode}'", $action);
        }
        $this->assertStringContainsString('confirmation_annulation', $action);
        $this->assertStringContainsString('FERMER_JOUR_A_MINUIT', $config);
    }

    public function test_nanan_can_modify_teacher_profile_and_confirm_lmd_professor(): void
    {
        $registry = file_get_contents(config_path('assistant_tools_nanan.php'));
        $teacher = file_get_contents(app_path('Domain/Assistant/Actions/Enseignants/ModifierEnseignant.php'));
        $lmd = file_get_contents(app_path('Domain/Assistant/Actions/Lmd/DefinirProfesseurClasseLmd.php'));

        $this->assertStringContainsString("'teachers.edit'", $registry);
        $this->assertStringContainsString("'lmd.planning.edit'", $registry);
        $this->assertStringContainsString("'titre_academique'", $teacher);
        $this->assertStringContainsString('UserManagementService', $teacher);
        $this->assertStringContainsString('EnseignantDeClasseLmd', $lmd);
        $this->assertStringContainsString('is_published', $lmd);
        $this->assertStringContainsString('->each->save()', $lmd);
    }

    public function test_new_tools_remain_opt_in_when_absent_from_both_registries(): void
    {
        $base = file_get_contents(app_path('Services/Chatbot/Tools/ChatbotTool.php'));
        $this->assertStringContainsString("config('chatbot.tools.' . \$this->name())", $base);
        $this->assertStringContainsString("config('assistant_tools_nanan.tools.' . \$this->name(), [])", $base);
        $this->assertStringContainsString('return is_array($extension) ? $extension : [];', $base);
    }
}

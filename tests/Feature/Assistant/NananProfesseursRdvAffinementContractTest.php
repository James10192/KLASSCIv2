<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\Enseignants\ModifierProfilEnseignant;
use App\Domain\Assistant\Actions\Lmd\ConfirmerProfesseurClasseLmd;
use App\Domain\Assistant\Actions\RendezVous\GererRendezVousCible;
use App\Domain\Assistant\Outils\LireRendezVousCible;
use App\Services\Chatbot\Tools\SearchTeachersTool;
use App\Services\RendezVous\RendezVousReglages;
use Tests\TestCase;

class NananProfesseursRdvAffinementContractTest extends TestCase
{
    public function test_recherche_enseignant_expose_le_teacher_id_et_enseigne_les_deux_gestes(): void
    {
        $outil = new SearchTeachersTool();
        $description = $outil->description();
        $source = file_get_contents(app_path('Services/Chatbot/Tools/SearchTeachersTool.php'));

        $this->assertStringContainsString('teacher_id', $description);
        $this->assertStringContainsString('proposer_profil_enseignant', $description);
        $this->assertStringContainsString('proposer_professeur_classe_lmd', $description);
        $this->assertStringContainsString("'teacher_id' => (int) \$teacher->id", $source);
        $this->assertStringContainsString("'titre_academique'", $source);
        $this->assertStringContainsString("'regime'", $source);
    }

    public function test_les_deux_actions_professeur_restent_distinctes_et_disponibles(): void
    {
        $registre = app(\App\Domain\Assistant\Actions\RegistreDesActions::class);

        $this->assertInstanceOf(ModifierProfilEnseignant::class, $registre->action('profil_enseignant'));
        $this->assertInstanceOf(ConfirmerProfesseurClasseLmd::class, $registre->action('professeur_classe_lmd'));

        $profil = app(ModifierProfilEnseignant::class)->description();
        $lmd = app(ConfirmerProfesseurClasseLmd::class)->description();
        $this->assertStringContainsString('proposer_professeur_classe_lmd', $profil);
        $this->assertStringContainsString('bulletins déjà publiés restent figés', $lmd);
    }

    public function test_lecture_rdv_cible_expose_la_reservabilite_reelle_et_le_workflow(): void
    {
        $outil = new LireRendezVousCible();
        $description = $outil->description();
        $parametres = $outil->parameters();
        $source = file_get_contents(app_path('Domain/Assistant/Outils/LireRendezVousCible.php'));

        $this->assertArrayHasKey('disponibles_seulement', $parametres['properties']);
        $this->assertStringContainsString('proposer_gestion_rendez_vous_cible', $description);
        $this->assertStringContainsString('proposer_convocations_rdv', $description);
        $this->assertStringContainsString('reservable=true', $description);
        $this->assertStringContainsString("'reservable' => \$reservable", $source);
        $this->assertStringContainsString("'raison_indisponible' => \$raison", $source);
        $this->assertStringContainsString('fermeture du jour à minuit', $source);
    }

    public function test_nanan_peut_toujours_programmer_reprogrammer_annuler_et_piloter_minuit(): void
    {
        $action = file_get_contents(app_path('Domain/Assistant/Actions/RendezVous/GererRendezVousCible.php'));

        foreach (['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau', 'fermeture_jour_minuit'] as $mode) {
            $this->assertStringContainsString("'{$mode}'", $action);
        }
        $this->assertStringContainsString('proposer_convocations_rdv', $action);
        $this->assertStringContainsString("\$user->can('inscriptions.rdv.manage')", $action);
        $this->assertContains(RendezVousReglages::FERMER_JOUR_A_MINUIT, RendezVousReglages::clesBascules());
    }
}

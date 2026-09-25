<?php

namespace Tests\Feature\Dashboard;

use App\Domain\EmploiTemps\ActiviteDEmargement;
use App\Domain\EmploiTemps\CoursDuJour;
use App\Domain\EmploiTemps\JourneeDeLEnseignant;
use App\Helpers\InstallationHelper;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tableau de bord enseignant et écran d'émargement : les cours du jour se
 * lisent par le jour de la semaine (trame hebdomadaire), leur état suit les
 * délais de l'école, et les deux écrans partagent ce calcul.
 *
 * Mercredi 4 février 2026, cours de 10:00 à 12:00 (délais livrés : présent
 * jusqu'à 10:20, retard jusqu'à 10:45, fin émargeable de 11:40 à 12:30).
 */
class JourneeDeLEnseignantTest extends TestCase
{
    use DatabaseTransactions;

    private User $enseignant;

    private ESBTPSeanceCours $seance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PaywallMiddleware::class);

        foreach (['admin.access', 'dashboard.view', 'identity.teach', 'attendances.create', 'attendances.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('enseignant', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();

        Carbon::setTestNow('2026-02-04 09:55:00');

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'start_date' => '2025-09-01', 'end_date' => '2026-07-31']);
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Résistance des matériaux']);

        $this->enseignant = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $this->enseignant->assignRole('enseignant');
        $this->enseignant->givePermissionTo(['admin.access', 'dashboard.view', 'identity.teach', 'attendances.create', 'attendances.view']);
        $teacher = ESBTPTeacher::create(['user_id' => $this->enseignant->id, 'matricule' => 'ENS-JOUR', 'status' => 'active']);

        $emploi = ESBTPEmploiTemps::create([
            'titre' => 'Trame', 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1', 'date_debut' => $annee->start_date, 'date_fin' => $annee->end_date,
            'is_active' => true, 'is_current' => true,
        ]);
        // Trame hebdomadaire : aucune date_seance, seulement le jour.
        $this->seance = ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploi->id, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id, 'jour' => 3, 'heure_debut' => '10:00:00', 'heure_fin' => '12:00:00',
            'salle' => 'B12', 'annee_universitaire_id' => $annee->id, 'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'CM', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_l_etat_du_cours_suit_l_heure_et_les_delais_de_l_ecole(): void
    {
        $etats = [];
        foreach (['09:55', '10:10', '10:30', '11:00'] as $heure) {
            $etats[$heure] = $this->etatA($heure);
        }

        $this->assertSame([
            '09:55' => CoursDuJour::A_VENIR,
            '10:10' => CoursDuJour::OUVERT,
            '10:30' => CoursDuJour::RETARD,
            '11:00' => CoursDuJour::DEPASSE,
        ], $etats);
    }

    public function test_un_cours_emarge_propose_la_fin_et_l_appel_dans_la_file_de_travail(): void
    {
        $this->emarger('2026-02-04', 'present', '10:05');
        Carbon::setTestNow('2026-02-04 11:50:00');

        $journee = app(JourneeDeLEnseignant::class);
        $cours = $journee->coursDuJour($this->enseignant->fresh());
        $file = $journee->fileDeTravail($cours, collect());

        $this->assertSame(CoursDuJour::FIN_OUVERTE, $cours->first()->etat);
        $this->assertSame(['Émarger la fin', 'Faire l’appel'], array_column($file, 'action'));
    }

    public function test_le_bilan_du_mois_compare_au_mois_precedent_sans_inventer_de_taux(): void
    {
        $this->emarger('2026-02-02', 'present', '10:05');
        $this->emarger('2026-02-03', 'late', '10:30');
        $this->emarger('2026-01-20', 'absent', '10:50');

        $bilan = app(ActiviteDEmargement::class)->bilanDuMois($this->enseignant, Carbon::now());

        $this->assertSame([2, 1, 1, 0], [$bilan['total'], $bilan['present'], $bilan['retard'], $bilan['absent']]);
        $this->assertSame(50, $bilan['ponctualite']);
        $this->assertSame(4.0, $bilan['heures']);
        $this->assertSame(0, $bilan['precedent']['ponctualite']);
        $this->assertSame(0.0, $bilan['precedent']['heures']);

        $vide = app(ActiviteDEmargement::class)->bilanDuMois($this->enseignant, Carbon::parse('2025-11-10'));
        $this->assertNull($vide['ponctualite']);
    }

    public function test_le_tableau_de_bord_montre_le_cours_de_la_trame_et_ce_qu_il_faut_faire(): void
    {
        Carbon::setTestNow('2026-02-04 10:10:00');

        $this->actingAs($this->enseignant)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('À faire maintenant')
            ->assertSee('Résistance des matériaux')
            ->assertSee('Émarger — Résistance des matériaux')
            ->assertSee(route('esbtp.teacher-attendance.index').'#cours-'.$this->seance->id, false)
            ->assertDontSee('Aucun cours au programme aujourd’hui');
    }

    public function test_l_ecran_d_emargement_garde_ses_deux_formulaires(): void
    {
        Carbon::setTestNow('2026-02-04 10:10:00');

        $this->actingAs($this->enseignant)->get(route('esbtp.teacher-attendance.index'))
            ->assertOk()
            ->assertSee('id="cours-'.$this->seance->id.'"', false)
            ->assertSee('action="'.route('esbtp.teacher.attendance.sign').'"', false)
            ->assertSee('name="code"', false)
            ->assertSee('action="'.route('esbtp.teacher-attendance.demander-code').'"', false)
            ->assertSee('Présent si vous émargez avant');
    }

    private function etatA(string $heure): string
    {
        Carbon::setTestNow('2026-02-04 '.$heure.':00');

        return app(JourneeDeLEnseignant::class)->coursDuJour($this->enseignant->fresh())->first()->etat;
    }

    private function emarger(string $date, string $statut, string $heure): void
    {
        ESBTPTeacherAttendance::create([
            'teacher_id' => $this->enseignant->id, 'course_id' => $this->seance->id, 'date' => $date,
            'status' => $statut, 'type' => 'start', 'validated_at' => $date.' '.$heure.':00',
        ]);
    }
}

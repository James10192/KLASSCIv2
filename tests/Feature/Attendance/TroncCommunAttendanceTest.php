<?php

namespace Tests\Feature\Attendance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Helpers\InstallationHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TroncCommunAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin.access', 'attendances.create'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        InstallationHelper::flushCachedStatus();
    }

    public function test_s1_tronc_commun_session_roster_includes_oriented_student_with_historical_label(): void
    {
        $this->actingAsAttendanceManager();
        $context = $this->makeOrientedStudentContext('semestre1');

        $response = $this->getJson(route('esbtp.attendances.load-students', [
            'classe_id' => $context['tcClasse']->id,
            'seance_id' => $context['seance']->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('nbEtudiants', 1)
            // Le nom est rendu en majuscules dans la liste (mb_strtoupper du nom).
            ->assertSee(mb_strtoupper($context['student']->nom, 'UTF-8'))
            ->assertSee($context['student']->prenoms)
            ->assertSee('Tronc commun');
    }

    public function test_s2_tronc_commun_session_excludes_student_oriented_to_specialty(): void
    {
        $this->actingAsAttendanceManager();
        $context = $this->makeOrientedStudentContext('semestre2');

        $response = $this->getJson(route('esbtp.attendances.load-students', [
            'classe_id' => $context['tcClasse']->id,
            'seance_id' => $context['seance']->id,
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Aucun étudiant inscrit dans cette classe.')
            ->assertDontSee($context['student']->nom_complet);
    }

    public function test_forged_s2_tronc_commun_attendance_post_is_rejected_without_writing(): void
    {
        $this->actingAsAttendanceManager();
        $context = $this->makeOrientedStudentContext('semestre2');

        $response = $this->postJson(route('esbtp.attendances.store'), [
            'seance_cours_id' => $context['seance']->id,
            'date' => $context['seance']->date_seance,
            'statuts' => [$context['student']->id => 'present'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('statuts.'.$context['student']->id);

        $this->assertDatabaseMissing('esbtp_attendances', [
            'seance_cours_id' => $context['seance']->id,
            'etudiant_id' => $context['student']->id,
        ]);
    }

    public function test_s1_tronc_commun_attendance_post_persists_session_context_after_orientation(): void
    {
        $this->actingAsAttendanceManager();
        $context = $this->makeOrientedStudentContext('semestre1');

        $response = $this->post(route('esbtp.attendances.store'), [
            'seance_cours_id' => $context['seance']->id,
            'date' => $context['seance']->date_seance,
            'statuts' => [$context['student']->id => 'absent'],
        ]);

        $response->assertRedirect(route('esbtp.attendances.index'));

        $this->assertDatabaseHas('esbtp_attendances', [
            'seance_cours_id' => $context['seance']->id,
            'etudiant_id' => $context['student']->id,
            'classe_id' => $context['tcClasse']->id,
            'matiere_id' => $context['matiere']->id,
            'teacher_id' => $context['teacher']->id,
            'annee_universitaire_id' => $context['annee']->id,
            'statut' => 'absent',
        ]);
    }

    public function test_attendance_index_keeps_s1_tronc_commun_absence_visible_after_orientation(): void
    {
        $this->actingAsAttendanceManager();
        $context = $this->makeOrientedStudentContext('semestre1');

        ESBTPAttendance::create([
            'seance_cours_id' => $context['seance']->id,
            'etudiant_id' => $context['student']->id,
            'classe_id' => $context['tcClasse']->id,
            'matiere_id' => $context['matiere']->id,
            'teacher_id' => $context['teacher']->id,
            'annee_universitaire_id' => $context['annee']->id,
            'date' => $context['seance']->date_seance,
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => 'absent',
            'call_type' => 'merged',
        ]);

        $this->get(route('esbtp.attendances.index'))
            ->assertOk()
            ->assertSee($context['student']->nom_complet)
            ->assertSee($context['tcClasse']->name);
    }

    public function test_annual_absence_aggregate_includes_s1_tronc_commun_sessions_after_orientation(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-31',
        ]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specialtyFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specialtyClasse = ESBTPClasse::factory()->create(['filiere_id' => $specialtyFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $student = ESBTPEtudiant::factory()->create();
        $matiereS1 = ESBTPMatiere::factory()->create();
        $matiereS2 = ESBTPMatiere::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $student->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specialtyClasse->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $specialtyClasse->id,
            'filiere_id' => $specialtyFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        $teacher = ESBTPTeacher::create([
            'user_id' => User::factory()->create()->id,
            'matricule' => 'ENS-ANNUAL',
            'status' => 'active',
        ]);
        $seanceS1 = $this->makeSeance($tcClasse, $matiereS1, $teacher, $annee, 'semestre1', '2026-02-10');
        $seanceS2 = $this->makeSeance($specialtyClasse, $matiereS2, $teacher, $annee, 'semestre2', '2026-05-12');

        // Absence S1 saisie sous la classe tronc commun (2h non justifiées).
        ESBTPAttendance::create([
            'seance_cours_id' => $seanceS1->id,
            'etudiant_id' => $student->id,
            'classe_id' => $tcClasse->id,
            'matiere_id' => $matiereS1->id,
            'annee_universitaire_id' => $annee->id,
            'date' => '2026-02-10',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'statut' => 'absent',
            'call_type' => 'merged',
        ]);
        // Absence S2 saisie sous la classe de spécialité (3h non justifiées).
        ESBTPAttendance::create([
            'seance_cours_id' => $seanceS2->id,
            'etudiant_id' => $student->id,
            'classe_id' => $specialtyClasse->id,
            'matiere_id' => $matiereS2->id,
            'annee_universitaire_id' => $annee->id,
            'date' => '2026-05-12',
            'heure_debut' => '08:00:00',
            'heure_fin' => '11:00:00',
            'statut' => 'absent',
            'call_type' => 'merged',
        ]);

        $result = app(\App\Services\ESBTP\ESBTPAbsenceService::class)->calculerDetailAbsences(
            $student->id,
            $specialtyClasse->id, // Bulletin généré sur la classe de spécialité
            '2026-01-01',
            '2026-08-31',
            $annee->id,
            'annuel'
        );

        // Le total annuel doit inclure les 2h S1 (classe TC) + 3h S2 (spécialité).
        $this->assertSame(5.0, (float) $result['non_justifiees']);
    }

    private function makeSeance(
        ESBTPClasse $classe,
        ESBTPMatiere $matiere,
        ESBTPTeacher $teacher,
        ESBTPAnneeUniversitaire $annee,
        string $semestre,
        string $dateSeance
    ): ESBTPSeanceCours {
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning '.$semestre,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => $semestre,
            'date_debut' => '2026-01-05',
            'date_fin' => '2026-06-30',
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'jour' => 'lundi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $annee->id,
            'date_seance' => $dateSeance,
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }

    private function actingAsAttendanceManager(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['admin.access', 'attendances.create']);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{student: ESBTPEtudiant, tcClasse: ESBTPClasse, seance: ESBTPSeanceCours, annee: ESBTPAnneeUniversitaire, matiere: ESBTPMatiere, teacher: ESBTPTeacher}
     */
    private function makeOrientedStudentContext(string $semestre): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => true,
            'semestres_tronc_commun' => 1,
        ]);
        $specialtyFiliere = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $tcFiliere->id,
        ]);
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specialtyClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specialtyFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $student = ESBTPEtudiant::factory()->create([
            'nom' => 'Historique',
            'prenoms' => 'Awa',
        ]);
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $student->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specialtyClasse->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $specialtyClasse->id,
            'filiere_id' => $specialtyFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning TC '.$semestre,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => $semestre,
            'date_debut' => '2026-01-05',
            'date_fin' => '2026-06-30',
            'is_active' => true,
            'is_current' => true,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $teacher = ESBTPTeacher::create([
            'user_id' => User::factory()->create()->id,
            'matricule' => 'ENS-TC-'.$semestre,
            'status' => 'active',
        ]);
        $seance = ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $tcClasse->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'jour' => 'lundi',
            'heure_debut' => '08:00:00',
            'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $annee->id,
            'date_seance' => $semestre === 'semestre1' ? '2026-02-02' : '2026-05-04',
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);

        return compact('student', 'tcClasse', 'seance', 'annee', 'matiere', 'teacher');
    }
}

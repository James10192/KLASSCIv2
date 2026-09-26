<?php

namespace Tests\Feature\Attendance;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPDailyCode;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Génération d'un code d'émargement depuis /esbtp/attendance-codes, le seul
 * écran qui en génère depuis la suppression de « codes oubliés ».
 *
 * La colonne `type` est un ENUM('session', 'journee', 'personnalise') sous
 * MySQL strict : toute autre valeur lève à l'insertion. Et un code lié à une
 * séance passait par une règle `exists` sur une table qui n'existe pas.
 */
class GenerationCodeEmargementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('attendances.generate_codes', 'web');

        // L'application se croit installée dès qu'un superAdmin existe ; sans lui,
        // toute requête repart vers /install. Ce compte n'est jamais celui qui agit.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
    }

    public function test_un_code_general_est_cree_avec_un_type_accepte_par_l_enum(): void
    {
        $user = $this->utilisateur(['attendances.generate_codes']);

        $this->actingAs($user)
            ->post(route('esbtp.attendance-codes.generate'), [
                'description' => 'Enseignant sans téléphone ce matin',
                'duration_minutes' => 30,
            ])
            ->assertRedirect(route('esbtp.attendance-codes.index'))
            ->assertSessionHas('success');

        $code = ESBTPDailyCode::sole();
        $this->assertSame('journee', $code->type);
        $this->assertNull($code->seance_id);
        $this->assertSame('Enseignant sans téléphone ce matin', $code->description);
        $this->assertSame($user->id, (int) $code->created_by);
        $this->assertTrue($code->isValid());
    }

    public function test_un_code_lie_a_une_seance_est_cree(): void
    {
        $user = $this->utilisateur(['attendances.generate_codes']);
        $seance = $this->seance();

        $this->actingAs($user)
            ->post(route('esbtp.attendance-codes.generate'), [
                'seance_id' => $seance->id,
                'duration_minutes' => 60,
            ])
            ->assertRedirect(route('esbtp.attendance-codes.index'))
            ->assertSessionHas('success');

        $code = ESBTPDailyCode::sole();
        $this->assertSame('session', $code->type);
        $this->assertSame($seance->id, (int) $code->seance_id);
    }

    public function test_un_utilisateur_sans_la_permission_est_refuse(): void
    {
        $user = $this->utilisateur([]);

        $this->actingAs($user)
            ->post(route('esbtp.attendance-codes.generate'), ['duration_minutes' => 30])
            ->assertForbidden();

        $this->assertSame(0, ESBTPDailyCode::count());
    }

    private function utilisateur(array $permissions): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user->fresh();
    }

    private function seance(): ESBTPSeanceCours
    {
        $teacherUser = User::factory()->create();
        $teacher = ESBTPTeacher::create([
            'user_id' => $teacherUser->id,
            'matricule' => 'ENS-' . $teacherUser->id,
            'status' => 'active',
        ]);
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning test code',
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1',
            'date_debut' => now()->subMonth()->toDateString(),
            'date_fin' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => ESBTPMatiere::factory()->create()->id,
            'teacher_id' => $teacher->id,
            'jour' => 'lundi',
            'heure_debut' => '10:00:00',
            'heure_fin' => '12:00:00',
            'annee_universitaire_id' => $annee->id,
            'date_seance' => now()->toDateString(),
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }
}

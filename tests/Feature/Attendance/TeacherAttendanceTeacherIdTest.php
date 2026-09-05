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
use App\Models\ESBTPSessionWorkflow;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * esbtp_teacher_attendances.teacher_id référence users.id (FK), jamais
 * esbtp_teachers.id. Ces tests fixent la vérité du schéma côté écrivains
 * (émargement) et côté migration de réalignement des données historiques.
 */
class TeacherAttendanceTeacherIdTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_09_05_030221_realign_teacher_id_on_esbtp_teacher_attendances.php';

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('enseignant', 'web');
        foreach (['attendances.sign', 'attendances.view_own'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        InstallationHelper::flushCachedStatus();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_signing_stores_the_user_id_not_the_teacher_profile_id(): void
    {
        // Un compte « leurre » créé avant : avec l'ancien code, l'id du profil
        // enseignant tombait sur ce compte (ou violait la FK s'il n'existait pas).
        User::factory()->create();

        $user = $this->makeTeacherUser();
        $teacher = $user->teacherProfile;
        $this->assertNotSame((int) $teacher->id, (int) $user->id, 'Précondition : ids profil et compte doivent différer.');

        // 10:05 : dans la fenêtre « présent » (heure_debut + 20 min).
        Carbon::setTestNow(Carbon::today()->setTime(10, 5));
        $seance = $this->makeSeance($teacher, Carbon::today(), '10:00:00', '12:00:00');
        $code = $this->makeDailyCode($user);

        $response = $this->actingAs($user)->post(route('esbtp.teacher.attendance.sign'), [
            'code' => $code->code,
            'course_id' => $seance->id,
        ]);

        $response->assertRedirect(route('teacher.select-call-type', $seance->id));

        $this->assertDatabaseHas('esbtp_teacher_attendances', [
            'course_id' => $seance->id,
            'teacher_id' => $user->id,
            'type' => 'start',
            'status' => 'present',
        ]);
        $this->assertDatabaseMissing('esbtp_teacher_attendances', [
            'course_id' => $seance->id,
            'teacher_id' => $teacher->id,
        ]);

        $attendance = ESBTPTeacherAttendance::where('course_id', $seance->id)->firstOrFail();
        $this->assertTrue($attendance->teacher->is($user), 'teacher() doit renvoyer le compte qui a émargé.');

        $workflow = ESBTPSessionWorkflow::where('seance_cours_id', $seance->id)
            ->where('teacher_id', $user->id)
            ->firstOrFail();
        $this->assertTrue((bool) $workflow->attendance_start_signed);
        $this->assertSame('call_start', $workflow->current_step);
    }

    public function test_migration_realigns_profile_ids_and_leaves_ambiguous_and_correct_rows(): void
    {
        // Trois profils enseignants dont les ids sont choisis pour reproduire les
        // trois cas : l'un porte l'id d'un compte non-enseignant (réalignable),
        // l'autre porte l'id d'un compte qui est le user_id d'un troisième profil
        // (ambigu), et une ligne déjà bonne ne doit pas bouger.
        $student = User::factory()->create();           // compte non-enseignant
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        $profileA = $this->insertTeacherProfile((int) $student->id, (int) $userA->id); // id = compte étudiant
        $profileB = $this->insertTeacherProfile((int) $userB->id, (int) $userC->id);   // id = compte de C…
        $profileC = $this->insertTeacherProfile((int) $userC->id + 1000, (int) $userB->id); // …dont B est le user_id

        $seance = $this->makeSeance(ESBTPTeacher::findOrFail($profileA), Carbon::today(), '08:00:00', '10:00:00');

        $realignable = $this->insertAttendance($profileA, $seance->id, 'start');   // teacher_id = profil A = compte étudiant
        $ambiguous = $this->insertAttendance($profileB, $seance->id, 'start');     // teacher_id = profil B = user_id de C
        $alreadyGood = $this->insertAttendance((int) $userA->id, $seance->id, 'end'); // teacher_id = compte de A

        $this->runRealignMigration();

        $this->assertSame((int) $userA->id, (int) DB::table('esbtp_teacher_attendances')->where('id', $realignable)->value('teacher_id'),
            'La ligne qui portait l\'id du profil doit désormais porter le users.id du compte.');
        $this->assertSame($profileB, (int) DB::table('esbtp_teacher_attendances')->where('id', $ambiguous)->value('teacher_id'),
            'Une valeur qui est déjà le user_id d\'un enseignant ne doit pas être réinterprétée.');
        $this->assertSame((int) $userA->id, (int) DB::table('esbtp_teacher_attendances')->where('id', $alreadyGood)->value('teacher_id'));

        // Idempotence : un second passage ne change rien.
        $before = DB::table('esbtp_teacher_attendances')->orderBy('id')->pluck('teacher_id', 'id')->all();
        $this->runRealignMigration();
        $after = DB::table('esbtp_teacher_attendances')->orderBy('id')->pluck('teacher_id', 'id')->all();
        $this->assertSame($before, $after);

        // Rien n'est supprimé.
        $this->assertSame(3, DB::table('esbtp_teacher_attendances')->count());
        $this->assertSame($profileC, (int) DB::table('esbtp_teachers')->where('id', $profileC)->value('id'));
    }

    private function runRealignMigration(): void
    {
        $migration = require database_path('migrations/' . self::MIGRATION);
        $migration->up();
    }

    private function makeTeacherUser(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole('enseignant');
        $user->givePermissionTo(['attendances.sign', 'attendances.view_own']);

        ESBTPTeacher::create([
            'user_id' => $user->id,
            'matricule' => 'ENS-' . $user->id,
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function insertTeacherProfile(int $id, int $userId): int
    {
        DB::table('esbtp_teachers')->insert([
            'id' => $id,
            'user_id' => $userId,
            'matricule' => 'ENS-MIG-' . $id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function insertAttendance(int $teacherId, int $seanceId, string $type): int
    {
        return (int) DB::table('esbtp_teacher_attendances')->insertGetId([
            'teacher_id' => $teacherId,
            'course_id' => $seanceId,
            'date' => Carbon::today()->toDateString(),
            'status' => 'present',
            'type' => $type,
            'attempts' => 1,
            'validated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeDailyCode(User $creator): ESBTPDailyCode
    {
        return ESBTPDailyCode::create([
            'code' => 'ABC123',
            'valid_from' => now()->subHour(),
            'valid_until' => now()->addHours(12),
            'is_active' => true,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }

    private function makeSeance(ESBTPTeacher $teacher, Carbon $date, string $debut, string $fin): ESBTPSeanceCours
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $matiere = ESBTPMatiere::factory()->create();
        $emploiTemps = ESBTPEmploiTemps::create([
            'titre' => 'Planning test émargement',
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'semestre' => 'semestre1',
            'date_debut' => $date->copy()->subMonth()->toDateString(),
            'date_fin' => $date->copy()->addMonth()->toDateString(),
            'is_active' => true,
            'is_current' => true,
        ]);

        return ESBTPSeanceCours::create([
            'emploi_temps_id' => $emploiTemps->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'teacher_id' => $teacher->id,
            'jour' => 'lundi',
            'heure_debut' => $debut,
            'heure_fin' => $fin,
            'annee_universitaire_id' => $annee->id,
            'date_seance' => $date->toDateString(),
            'type' => ESBTPSeanceCours::TYPE_COURSE,
            'type_seance' => 'cours',
            'is_active' => true,
        ]);
    }
}

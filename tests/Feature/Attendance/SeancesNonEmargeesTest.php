<?php

namespace Tests\Feature\Attendance;

use App\Models\ESBTPTeacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * attendance:mark-unattended-teacher-sessions écrit le COMPTE de l'enseignant
 * (users.id), jamais l'id de son profil de séance, et ne s'arrête plus sur une
 * séance dont le profil n'a pas de compte.
 */
class SeancesNonEmargeesTest extends TestCase
{
    use RefreshDatabase;
    use Concerns\FixturesEmargement;

    private const MIGRATION_NON_EMARGEES = '2026_10_02_144132_realign_not_signed_teacher_attendances_on_user_id.php';

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('enseignant', 'web');
        foreach (['attendances.sign', 'attendances.view_own'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_seance_non_emargee_est_rattachee_au_compte_et_pas_au_profil(): void
    {
        // Un compte « leurre » qui porte l'id du profil : l'ancien code y rattachait l'émargement.
        User::factory()->create();
        $user = $this->makeTeacherUser();
        $teacher = $user->teacherProfile;
        $this->assertNotSame((int) $teacher->id, (int) $user->id);

        Carbon::setTestNow(Carbon::today()->setTime(12, 30));
        $seance = $this->makeSeance($teacher, Carbon::today(), '08:00:00', '10:00:00');

        $this->artisan('attendance:mark-unattended-teacher-sessions')->assertExitCode(0);

        $this->assertDatabaseHas('esbtp_teacher_attendances', ['course_id' => $seance->id, 'teacher_id' => $user->id, 'status' => 'not_signed']);
        $this->assertDatabaseMissing('esbtp_teacher_attendances', ['course_id' => $seance->id, 'teacher_id' => $teacher->id]);

        // Second passage : rien de plus.
        $this->artisan('attendance:mark-unattended-teacher-sessions')->assertExitCode(0);
        $this->assertSame(1, DB::table('esbtp_teacher_attendances')->where('course_id', $seance->id)->count());
    }

    /**
     * Le cas d'esbtp-abidjan le 2 octobre 2026 : le profil n° 40 n'existe pas
     * comme compte. L'ancien code levait sur la clé étrangère et s'arrêtait là.
     */
    public function test_un_profil_dont_le_numero_n_est_pas_un_compte_ne_bloque_plus_rien(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(12, 30));
        $compte = User::factory()->create();
        $profil = $this->insertTeacherProfile((int) User::max('id') + 500, (int) $compte->id);
        $autre = $this->makeTeacherUser();

        $premiere = $this->makeSeance(ESBTPTeacher::findOrFail($profil), Carbon::today(), '07:00:00', '08:00:00');
        $suivante = $this->makeSeance($autre->teacherProfile, Carbon::today(), '08:00:00', '10:00:00');

        $this->artisan('attendance:mark-unattended-teacher-sessions')->assertExitCode(0);

        $this->assertDatabaseHas('esbtp_teacher_attendances', ['course_id' => $premiere->id, 'teacher_id' => $compte->id, 'status' => 'not_signed']);
        $this->assertDatabaseHas('esbtp_teacher_attendances', ['course_id' => $suivante->id, 'teacher_id' => $autre->id, 'status' => 'not_signed']);
    }

    public function test_la_migration_remet_les_non_emargees_sur_le_compte_et_laisse_le_reste(): void
    {
        $etudiant = User::factory()->create();
        $enseignant = User::factory()->create();
        // Profil dont l'id tombe sur le compte de l'étudiant : le cas qui corrompait.
        $profil = $this->insertTeacherProfile((int) $etudiant->id, (int) $enseignant->id);
        $seance = $this->makeSeance(ESBTPTeacher::findOrFail($profil), Carbon::today(), '08:00:00', '10:00:00');

        $fausse = $this->ligne((int) $etudiant->id, $seance->id, 'not_signed', 'start');
        $presente = $this->ligne((int) $etudiant->id, $seance->id, 'present', 'end'); // pas écrite par la tâche : on n'y touche pas

        $this->migrer();
        $this->assertSame((int) $enseignant->id, (int) DB::table('esbtp_teacher_attendances')->where('id', $fausse)->value('teacher_id'));
        $this->assertSame((int) $etudiant->id, (int) DB::table('esbtp_teacher_attendances')->where('id', $presente)->value('teacher_id'));

        $avant = DB::table('esbtp_teacher_attendances')->orderBy('id')->pluck('teacher_id', 'id')->all();
        $this->migrer();
        $this->assertSame($avant, DB::table('esbtp_teacher_attendances')->orderBy('id')->pluck('teacher_id', 'id')->all());
        $this->assertSame(2, DB::table('esbtp_teacher_attendances')->count());
    }

    private function ligne(int $teacherId, int $seanceId, string $statut, string $type): int
    {
        return (int) DB::table('esbtp_teacher_attendances')->insertGetId([
            'teacher_id' => $teacherId,
            'course_id' => $seanceId,
            'date' => Carbon::today()->toDateString(),
            'status' => $statut,
            'type' => $type,
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrer(): void
    {
        (require database_path('migrations/'.self::MIGRATION_NON_EMARGEES))->up();
    }
}

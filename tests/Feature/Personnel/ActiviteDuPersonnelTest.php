<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Services\Personnel\ActiviteDuPersonnel;
use App\Services\Personnel\FenetreDActivite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * L'activité du personnel : des faits prévus / réalisés, jamais une note.
 *
 * Ce que ces tests verrouillent : une séance se relie à son émargement par la
 * SÉANCE et jamais par un identifiant de personne (l'ancien score comptait les
 * séances d'un autre dès que deux numéros coïncidaient), une pause n'est pas
 * une séance à émarger, et la page d'une personne ne s'ouvre qu'à elle-même ou
 * à qui voit tout le personnel.
 */
class ActiviteDuPersonnelTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    private User $enseignante;

    private int $ficheEnseignante;

    private int $emploiDuTemps;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
        $this->annee->forceFill(['is_current' => true, 'start_date' => now()->subMonths(2)->toDateString(), 'end_date' => now()->addMonths(6)->toDateString()])->save();

        foreach (['performance.view', 'performance.view_all'] as $nom) {
            Permission::findOrCreate($nom, 'web');
        }
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole(Role::findOrCreate('superAdmin', 'web'));

        $this->enseignante = User::factory()->create(['name' => 'Aminata KONE', 'must_change_password' => false, 'password_changed_at' => now()]);
        $this->ficheEnseignante = $this->fiche($this->enseignante);
        $this->emploiDuTemps = DB::table('esbtp_emploi_temps')->insertGetId([
            'titre' => 'EDT', 'classe_id' => $this->classe->id, 'semestre' => 'semestre1',
            'date_debut' => now()->subMonths(2)->toDateString(), 'date_fin' => now()->addMonths(2)->toDateString(),
            'annee_universitaire_id' => $this->annee->id, 'is_active' => 1, 'is_current' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function fiche(User $user): int
    {
        return DB::table('esbtp_teachers')->insertGetId([
            'user_id' => $user->id, 'matricule' => 'ENS-'.$user->id, 'status' => 'active', 'regime' => 'permanent',
            'teaching_hours_due' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seance(int $fiche, int $ilYAJours, string $type = 'course'): int
    {
        return DB::table('esbtp_seance_cours')->insertGetId([
            'emploi_temps_id' => $this->emploiDuTemps, 'classe_id' => $this->classe->id, 'jour' => 'lundi',
            'date_seance' => now()->subDays($ilYAJours)->toDateString(), 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00',
            'annee_universitaire_id' => $this->annee->id, 'teacher_id' => $fiche, 'type' => $type,
            'is_recurring' => 0, 'priority' => 0, 'is_active' => 1, 'type_seance' => 'CM',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function emarger(int $seance, User $par, string $statut = 'present'): void
    {
        DB::table('esbtp_teacher_attendances')->insert([
            'teacher_id' => $par->id, 'course_id' => $seance, 'date' => now()->toDateString(),
            'status' => $statut, 'type' => 'start', 'attempts' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ligne(User $user): ?array
    {
        return app(ActiviteDuPersonnel::class)->lignes(FenetreDActivite::pour('annee'), (int) $user->id)->first();
    }

    public function test_les_seances_tenues_se_comptent_par_la_seance(): void
    {
        $this->emarger($this->seance($this->ficheEnseignante, 10), $this->enseignante);
        $this->emarger($this->seance($this->ficheEnseignante, 9), $this->enseignante, 'late');
        $this->seance($this->ficheEnseignante, 8);
        $this->seance($this->ficheEnseignante, 7, 'break');

        $ligne = $this->ligne($this->enseignante);

        self::assertSame(3, $ligne['seances_prevues'], 'La pause ne se compte pas comme une séance.');
        self::assertSame(2, $ligne['seances_tenues']);
        self::assertSame(1, $ligne['retards']);
        self::assertSame(1, $ligne['seances_non_emargees']);
    }

    public function test_les_seances_d_un_autre_ne_sont_jamais_comptees(): void
    {
        // Le compte d'un collègue porte le même numéro que la FICHE de
        // l'enseignante : l'ancien calcul lui attribuait ses séances.
        $collegue = User::factory()->create();
        DB::table('esbtp_teachers')->where('id', $this->ficheEnseignante)->update(['id' => $collegue->id]);
        $this->ficheEnseignante = (int) $collegue->id;

        $this->seance($this->ficheEnseignante, 5);

        self::assertNull($this->ligne($collegue), 'Le collègue n’a aucune séance : il ne doit rien hériter.');
        self::assertSame(1, $this->ligne($this->enseignante)['seances_prevues']);
    }

    public function test_les_notes_rendues_comparent_recues_et_attendues(): void
    {
        $un = $this->etudiantInscrit();
        $this->etudiantInscrit();
        $evaluation = $this->evaluationDe(\App\Models\ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]));
        $evaluation->forceFill(['date_evaluation' => now()->subDays(20), 'enseignant_id' => $this->enseignante->id])->save();
        $this->noter($un, $evaluation);

        $ligne = $this->ligne($this->enseignante);

        self::assertSame(2, $ligne['notes_attendues']);
        self::assertSame(1, $ligne['notes_recues']);
        self::assertSame(1, $ligne['evaluations_en_retard']);
    }

    public function test_la_liste_exige_de_voir_tout_le_personnel(): void
    {
        $secretaire = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $secretaire->givePermissionTo('performance.view');

        $this->actingAs($secretaire)->get(route('esbtp.personnel.performance.index'))->assertForbidden();

        $direction = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $direction->givePermissionTo('performance.view_all');
        $this->emarger($this->seance($this->ficheEnseignante, 3), $this->enseignante);

        $html = $this->actingAs($direction)->getJson(route('esbtp.personnel.performance.data'))->assertOk()->json('html');
        self::assertStringContainsString('Aminata KONE', $html);
        self::assertStringContainsString('1 / 1', $html);
    }

    public function test_chacun_voit_sa_propre_activite_et_pas_celle_des_autres(): void
    {
        $this->enseignante->givePermissionTo('performance.view');
        $autre = User::factory()->create();

        $this->actingAs($this->enseignante)->get(route('esbtp.personnel.performance.moi'))
            ->assertRedirect(route('esbtp.personnel.performance.show', ['user' => $this->enseignante->id]));
        $this->actingAs($this->enseignante)->get(route('esbtp.personnel.performance.show', $this->enseignante))
            ->assertOk()->assertSee('Mon activité');
        $this->actingAs($this->enseignante)->get(route('esbtp.personnel.performance.show', $autre))->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Matiere;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPMatiere;
use App\Models\User;
use App\Services\LMD\CodeDeMatiere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'ecran BTS des matieres face a un code tenu par une matiere supprimee.
 *
 * L'index unique `esbtp_matieres_code_unique` compte les lignes supprimees en
 * douceur. `unique:` les comptait aussi, et la generation automatique du code
 * les ignorait : la premiere refusait avec un message pointant vers une
 * matiere invisible, la seconde laissait l'insertion lever en 500. Le code
 * est desormais libere (renomme `CODE~suppr-<id>`) et le message le dit.
 */
class CodeArchiveMatiereBtsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin.access', 'matieres.view', 'matieres.create', 'matieres.edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        InstallationHelper::flushCachedStatus();

        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->assignRole('superAdmin');
        $user->givePermissionTo(['admin.access', 'matieres.view', 'matieres.create', 'matieres.edit']);
        $this->actingAs($user);
    }

    public function test_creer_avec_un_code_tenu_par_une_matiere_supprimee_libere_le_code(): void
    {
        $archivee = $this->matiereSupprimee('MATH101');

        $this->post(route('esbtp.matieres.store'), $this->formulaire(['code' => 'MATH101']))
            ->assertRedirect(route('esbtp.matieres.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn (string $message) => str_contains($message, 'MATH101~suppr-' . $archivee->id));

        $this->assertDatabaseHas('esbtp_matieres', ['code' => 'MATH101', 'name' => 'Mathématiques', 'deleted_at' => null]);
        $this->assertSame('MATH101~suppr-' . $archivee->id, ESBTPMatiere::withTrashed()->find($archivee->id)->code);
    }

    public function test_un_code_tenu_par_une_matiere_active_reste_refuse_et_rien_n_est_libere(): void
    {
        ESBTPMatiere::factory()->create(['code' => 'MATH101']);

        $this->post(route('esbtp.matieres.store'), $this->formulaire(['code' => 'MATH101']))
            ->assertSessionHasErrors('code');

        $this->assertSame(1, ESBTPMatiere::withTrashed()->where('code', 'like', 'MATH101%')->count());
    }

    public function test_le_code_genere_evite_celui_d_une_matiere_supprimee(): void
    {
        // « Mathématiques » donne le code de base « MAT » (trois premiers octets).
        $archivee = $this->matiereSupprimee('MAT');

        $this->post(route('esbtp.matieres.store'), $this->formulaire(['code' => null]))
            ->assertRedirect(route('esbtp.matieres.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('esbtp_matieres', ['code' => 'MAT1', 'deleted_at' => null]);
        // L'archive n'a pas ete touchee : le generateur l'a contournee.
        $this->assertSame('MAT', ESBTPMatiere::withTrashed()->find($archivee->id)->code);
    }

    public function test_modifier_vers_un_code_tenu_par_une_matiere_supprimee_libere_le_code(): void
    {
        $archivee = $this->matiereSupprimee('PHY201');
        $matiere = ESBTPMatiere::factory()->create(['code' => 'PHY200', 'unite_enseignement_id' => null]);

        $this->put(route('esbtp.matieres.update', $matiere), $this->formulaire(['code' => 'PHY201', 'coefficient' => 2]))
            ->assertRedirect(route('esbtp.matieres.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('PHY201', $matiere->fresh()->code);
        $this->assertSame('PHY201~suppr-' . $archivee->id, ESBTPMatiere::withTrashed()->find($archivee->id)->code);
    }

    public function test_une_collision_a_l_ecriture_devient_un_refus_nomme(): void
    {
        // Simule la saisie concurrente : entre la validation et l'ecriture,
        // une autre saisie a pris le code. L'index leve 1062 ; le service doit
        // le transformer en refus sur le champ, pas en erreur serveur.
        ESBTPMatiere::factory()->create(['code' => 'CHIM1', 'name' => 'Chimie']);

        try {
            app(CodeDeMatiere::class)->sousUnicite('code', 'CHIM1', fn () => DB::table('esbtp_matieres')->insert([
                'name' => 'Chimie bis',
                'code' => 'CHIM1',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            $this->fail('La collision aurait dû être refusée.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Chimie', $e->errors()['code'][0]);
        }
    }

    private function matiereSupprimee(string $code): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['code' => $code, 'unite_enseignement_id' => null]);
        $matiere->delete();

        return $matiere;
    }

    private function formulaire(array $surcharge): array
    {
        return array_merge([
            'name' => 'Mathématiques',
            'coefficient' => 1,
            'type_formation' => 'generale',
            'is_active' => 1,
        ], $surcharge);
    }
}

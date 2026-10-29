<?php

namespace Tests\Feature\Listes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Evaluations, matieres et paiements gardent leur propre defilement, mais
 * suivent le contrat des listes par tranches (App\Support\ListeInfinie) :
 *   - un departage par identifiant, sans lequel deux lignes de meme date ou de
 *     meme nom changent de place d'une tranche a l'autre ;
 *   - la suite repond en lignes seules, sans recalculer les compteurs que la
 *     page a deja.
 */
class ListesAuDefilementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');
        \App\Helpers\InstallationHelper::flushCachedStatus();

        foreach (['admin.access', 'matieres.view', 'paiements.view'] as $p) {
            \Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
        }
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->givePermissionTo(['admin.access', 'matieres.view', 'paiements.view']);
        $this->actingAs($user);
        Gate::before(fn ($u, string $ability) => in_array($ability, \App\Policies\ESBTPPaiementPolicy::CAPACITES_D_ETAT, true) ? null : true);
    }

    /**
     * @return list<string>
     */
    private function requetes(callable $action): array
    {
        $sql = [];
        DB::listen(function ($q) use (&$sql) { $sql[] = $q->sql; });
        $action();

        return $sql;
    }

    private function tranche2(string $route, array $query = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route($route, $query + ['page' => 2, 'mode' => 'rows']));
    }

    public function test_evaluations_la_suite_arrive_en_lignes_departagees(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $annee->update(['is_current' => true]);
        // Toutes le meme jour : seul l'identifiant les departage.
        $classe = ESBTPClasse::factory()->create();
        ESBTPEvaluation::factory()->count(20)->create([
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'date_evaluation' => now()->toDateString(),
        ]);

        $sql = $this->requetes(function () use (&$json) {
            $json = $this->tranche2('esbtp.evaluations.index', ['per_page' => 15])
                ->assertOk()
                ->assertJsonPath('pagination.current_page', 2)
                ->assertJsonPath('pagination.has_more', false)
                ->assertJsonPath('pagination.affiches', 20)
                ->json();
        });

        preg_match_all('/data-li-cle="(\d+)"/', $json['rows_html'], $m);
        $this->assertCount(5, $m[1]);
        $this->assertTrue(
            collect($sql)->contains(fn ($s) => str_contains($s, 'from `esbtp_evaluations`') && preg_match('/order by .*`date_evaluation` desc, `id` desc/', $s)),
            'La liste des evaluations se departage par identifiant.'
        );
        $this->assertFalse(
            collect($sql)->contains(fn ($s) => str_contains($s, 'select distinct `type`')),
            'La suite ne recharge pas les filtres de la page.'
        );
    }

    public function test_matieres_la_suite_arrive_en_lignes_sans_compteurs(): void
    {
        ESBTPMatiere::factory()->count(20)->create(['name' => 'Même nom', 'unite_enseignement_id' => null]);

        $sql = $this->requetes(function () use (&$json) {
            $json = $this->tranche2('esbtp.matieres.index', ['per_page' => 15])->assertOk()->json();
        });

        preg_match_all('/data-li-cle="(\d+)"/', $json['rows_html'], $m);
        $this->assertGreaterThanOrEqual(5, count($m[1]));
        $this->assertCount(count($m[1]), array_unique($m[1]));
        $this->assertTrue(
            collect($sql)->contains(fn ($s) => preg_match('/order by `name` asc, `esbtp_matieres`.`id` asc/', $s)),
            'La liste des matieres se departage par identifiant.'
        );
        $this->assertFalse(
            collect($sql)->contains(fn ($s) => str_contains($s, 'AS avec_liaisons')),
            'La suite ne recalcule pas les compteurs.'
        );
    }

    public function test_matieres_la_taille_de_tranche_est_plafonnee(): void
    {
        ESBTPMatiere::factory()->count(3)->create(['unite_enseignement_id' => null]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.matieres.index', ['per_page' => 100000, 'page' => 1, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.par_page', 100);
    }

    public function test_paiements_la_suite_ne_recalcule_pas_les_compteurs(): void
    {
        $classe = ESBTPClasse::factory()->create();
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::whereKey($classe->annee_universitaire_id)->update(['is_current' => true]);
        $inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
        ]);
        $frais = ESBTPFraisCategory::create([
            'name' => 'Scolarité défilement', 'code' => 'SCOL_DEF_'.uniqid(),
            'is_mandatory' => true, 'is_active' => true, 'category_type' => 'academic',
            'sort_order' => 1, 'default_amount' => 100000, 'payment_deadline_days' => 30,
        ]);
        $meme = now()->startOfSecond();
        for ($i = 0; $i < 20; $i++) {
            $p = ESBTPPaiement::create([
                'inscription_id' => $inscription->id,
                'etudiant_id' => $inscription->etudiant_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'frais_category_id' => $frais->id,
                'montant' => 1000,
                'mode_paiement' => 'Espèces',
                'date_paiement' => now()->toDateString(),
                'status' => 'validé',
                'nature' => 'encaissement',
                'numero_recu' => 'REC-DEF-'.uniqid(),
            ]);
            $p->forceFill(['created_at' => $meme])->saveQuietly();
        }

        $sql = $this->requetes(function () use (&$json) {
            $json = $this->tranche2('esbtp.paiements.index')->assertOk()->json();
        });

        $this->assertStringContainsString('data-paiement-id', $json['rows']);
        $this->assertFalse($json['has_more']);
        $this->assertTrue(
            collect($sql)->contains(fn ($s) => str_contains($s, 'from `esbtp_paiements`') && preg_match('/order by `created_at` desc, `esbtp_paiements`.`id` desc/', $s)),
            'La liste des paiements se departage par identifiant.'
        );
        $this->assertFalse(
            collect($sql)->contains(fn ($s) => preg_match('/select count\(\*\) as aggregate from `esbtp_paiements`.*`status` = \?/', $s) === 1),
            'La suite ne recalcule pas les compteurs par statut.'
        );
    }
}

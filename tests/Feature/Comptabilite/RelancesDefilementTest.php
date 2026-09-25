<?php

namespace Tests\Feature\Comptabilite;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use App\Services\RelanceCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La liste des relances se charge au defilement. Le solde vient de
 * l'echeancier, lourd a monter en test : le calcul est remplace ici par un
 * solde fixe, ce qui est teste est le decoupage en tranches.
 */
class RelancesDefilementTest extends TestCase
{
    use DatabaseTransactions;

    /** Nombre de lignes calculees : le calcul d'echeancier est ce qui coute. */
    public static int $calculs = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['comptabilite.access', 'comptabilite.dashboard.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');
        Cache::flush();
        MobileProfileResolver::oublier();

        $comptable = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $comptable->givePermissionTo(['comptabilite.access', 'comptabilite.dashboard.view']);
        $this->actingAs($comptable);

        $this->app->bind(RelanceCalculationService::class, fn ($app) => new class(
            $app->make(\App\Services\EcheancierComputationService::class),
            $app->make(\App\Services\EcheancierSnapshotService::class),
        ) extends RelanceCalculationService {
            public function preloadForInscriptions(Collection $inscriptions): static
            {
                return $this;
            }

            public function buildRow(ESBTPInscription $inscription): object
            {
                RelancesDefilementTest::$calculs++;

                return (object) [
                    'inscription' => $inscription, 'totalDu' => 100000, 'totalPaye' => 0,
                    'totalPayeEnAttente' => 0, 'soldeRestant' => 100000, 'pourcentage' => 0,
                    'risk' => 'critical', 'riskLabel' => 'Impayé',
                ];
            }
        });
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_la_suite_arrive_en_lignes_seules_sans_repetition(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        ESBTPInscription::factory()->count(30)->create([
            'annee_universitaire_id' => $annee->id,
            'workflow_step' => 'etudiant_cree',
            'created_at' => now()->startOfSecond(),
        ]);

        $page = $this->get(route('esbtp.comptabilite.relances.index', ['annee_id' => $annee->id]))
            ->assertOk()
            ->assertSee('data-page-suivante="2"', false)
            ->assertDontSee('name="per_page"', false)
            ->getContent();

        $suite = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.comptabilite.relances.index', ['annee_id' => $annee->id, 'page' => 2, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.total', 30)
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonMissingPath('kpis')
            ->json('rows_html');

        preg_match_all('/<tr data-li-cle="(\d+)"/', $page, $a);
        preg_match_all('/<tr data-li-cle="(\d+)"/', $suite, $b);
        $this->assertCount(25, $a[1]);
        $this->assertCount(5, $b[1]);
        $this->assertCount(30, array_unique(array_merge($a[1], $b[1])));
    }

    public function test_une_tranche_ne_recalcule_que_ses_propres_lignes(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        ESBTPInscription::factory()->count(30)->create([
            'annee_universitaire_id' => $annee->id,
            'workflow_step' => 'etudiant_cree',
        ]);

        $this->get(route('esbtp.comptabilite.relances.index', ['annee_id' => $annee->id]))->assertOk();
        self::$calculs = 0;

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.comptabilite.relances.index', ['annee_id' => $annee->id, 'page' => 2, 'mode' => 'rows']))
            ->assertOk();

        $this->assertSame(5, self::$calculs, 'La tranche 2 relit l\'index en cache et ne calcule que ses 5 lignes.');
    }
}

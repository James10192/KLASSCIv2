<?php

namespace Tests\Feature\Comptabilite;

use App\Domain\Comptabilite\Relances\PopulationDesRelances;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRelance;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use App\Services\NotificationService;
use App\Services\RelanceCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Un eleve dont l'inscription n'est plus active garde un solde. Par defaut,
 * l'ecole ne le relance plus : il sort de la liste, de ses compteurs et des
 * envois. Un reglage permet de continuer a le relancer.
 *
 * Le solde vient de l'echeancier, lourd a monter en test : chaque inscription
 * doit ici 100 000 FCFA, echus depuis 90 jours.
 */
class RelancesInscriptionsInactivesTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPInscription $active;

    private ESBTPInscription $annulee;

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

            public function getFinancialState(ESBTPInscription $inscription): array
            {
                return ['overdue_amount' => 100000, 'overdue_days' => 90, 'remaining_total' => 100000];
            }

            public function buildRow(ESBTPInscription $inscription): object
            {
                return (object) [
                    'inscription' => $inscription, 'totalDu' => 100000, 'totalPaye' => 0,
                    'totalPayeEnAttente' => 0, 'soldeRestant' => 100000, 'pourcentage' => 0,
                    'risk' => 'critical', 'riskLabel' => 'Impayé',
                ];
            }
        });

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->active = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->annee->id, 'workflow_step' => 'etudiant_cree', 'status' => 'active',
        ]);
        $this->annulee = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->annee->id, 'workflow_step' => 'etudiant_cree', 'status' => 'annulée',
        ]);

        DB::table('settings')->updateOrInsert(['key' => 'relances.montant_minimum'], ['value' => '0', 'group' => 'relances']);
        DB::table('settings')->updateOrInsert(['key' => 'relances.delai_niveau_1'], ['value' => '1', 'group' => 'relances']);
        DB::table('settings')->where('key', PopulationDesRelances::CLE_REGLAGE)->delete();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    private function reglerInclusion(bool $inclure): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => PopulationDesRelances::CLE_REGLAGE],
            ['value' => $inclure ? '1' : '0', 'group' => 'relances']
        );
    }

    /** @return array{ids: list<string>, total: int} */
    private function liste(): array
    {
        $html = $this->get(route('esbtp.comptabilite.relances.index', ['annee_id' => $this->annee->id]))
            ->assertOk()->getContent();
        preg_match_all('/<tr data-li-cle="(\d+)"/', $html, $m);

        $kpis = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.comptabilite.relances.index', ['annee_id' => $this->annee->id, 'ajax' => '1']))
            ->assertOk()->json('kpis');

        return ['ids' => $m[1], 'total' => (int) $kpis['total_etudiants']];
    }

    /** @return list<int> les inscriptions pour lesquelles une relance est planifiee */
    private function planifier(): array
    {
        app(NotificationService::class)->planifierRelancesAvancees(['types_relance' => ['email']]);

        return ESBTPRelance::whereIn('inscription_id', [$this->active->id, $this->annulee->id])
            ->pluck('inscription_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    public function test_par_defaut_l_inscription_annulee_sort_de_la_liste_des_compteurs_et_des_envois(): void
    {
        $liste = $this->liste();

        $this->assertSame([(string) $this->active->id], $liste['ids']);
        $this->assertSame(1, $liste['total']);
        $this->assertSame([$this->active->id], $this->planifier());
    }

    public function test_reglage_coche_l_inscription_annulee_est_relancee(): void
    {
        $this->reglerInclusion(true);

        $liste = $this->liste();

        $this->assertEqualsCanonicalizing([(string) $this->active->id, (string) $this->annulee->id], $liste['ids']);
        $this->assertSame(2, $liste['total']);
        $this->assertSame(collect([$this->active->id, $this->annulee->id])->sort()->values()->all(), $this->planifier());
    }

    public function test_changer_le_reglage_se_voit_des_la_tranche_suivante_malgre_le_cache(): void
    {
        $this->get(route('esbtp.comptabilite.relances.index', ['annee_id' => $this->annee->id]))->assertOk();

        $this->reglerInclusion(true);

        // Une tranche suivante relit l'index en cache : la cle porte le
        // reglage, donc l'ancien index (sans l'inscription annulee) n'est pas servi.
        $suite = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.comptabilite.relances.index', ['annee_id' => $this->annee->id, 'page' => 1, 'mode' => 'rows']))
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);

        $this->assertStringContainsString('data-li-cle="'.$this->annulee->id.'"', $suite->json('rows_html'));
    }

    public function test_une_relance_deja_planifiee_ne_part_pas_si_l_eleve_est_parti_depuis(): void
    {
        Mail::fake();
        $relance = ESBTPRelance::create([
            'etudiant_id' => $this->annulee->etudiant_id,
            'inscription_id' => $this->annulee->id,
            'type' => 'email',
            'niveau' => 1,
            'template_utilise' => 'relance_niveau_1',
            'date_envoi' => now()->subHour(),
            'statut' => 'planifiee',
        ]);

        $resultats = app(NotificationService::class)->executerRelancesEnAttente();

        $this->assertSame('echec', $relance->fresh()->statut);
        $this->assertGreaterThanOrEqual(1, $resultats['echecs']);
        Mail::assertNothingSent();
    }

    public function test_un_statut_absent_compte_comme_actif(): void
    {
        $requete = PopulationDesRelances::restreindre(ESBTPInscription::query(), false)->toSql();

        $this->assertStringContainsString('`esbtp_inscriptions`.`status` is null', $requete);
    }
}

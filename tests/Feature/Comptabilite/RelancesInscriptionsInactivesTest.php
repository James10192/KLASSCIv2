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

    private function relance(string $statut, ?int $inscriptionId, int $etudiantId): ESBTPRelance
    {
        return ESBTPRelance::create([
            'etudiant_id' => $etudiantId,
            'inscription_id' => $inscriptionId,
            'type' => 'email',
            'niveau' => 1,
            'template_utilise' => 'relance_niveau_1',
            'date_envoi' => now()->subHours(3),
            'statut' => $statut,
        ]);
    }

    /** Une relance envoyee prend le statut « envoyee » et garde son texte (Mail::raw n'est pas enregistre par le faux). */
    private function assertNonEnvoyee(ESBTPRelance $relance): void
    {
        $relance->refresh();
        $this->assertSame(ESBTPRelance::STATUT_ECARTEE, $relance->statut);
        $this->assertNull($relance->contenu_message);
        $this->assertFalse($relance->peutEtreRenvoyee());
    }

    public function test_une_relance_deja_planifiee_est_ecartee_et_comptee_a_part(): void
    {
        Mail::fake();
        $partie = $this->relance('planifiee', $this->annulee->id, $this->annulee->etudiant_id);
        $valable = $this->relance('planifiee', $this->active->id, $this->active->etudiant_id);

        $resultats = app(NotificationService::class)->executerRelancesEnAttente();

        $this->assertNonEnvoyee($partie);
        $this->assertSame('envoyee', $valable->fresh()->statut);
        $this->assertSame(1, $resultats['ecartees']);
        $this->assertSame(0, $resultats['echecs']);
    }

    public function test_renvoyer_ne_contourne_pas_la_garde(): void
    {
        Mail::fake();
        Permission::findOrCreate('comptabilite.relances.send', 'web');
        auth()->user()->givePermissionTo('comptabilite.relances.send');

        // Une relance en echec d'avant ce changement, pour un eleve parti :
        // le renvoi passe par le meme point d'envoi, donc elle est ecartee.
        $ancienEchec = $this->relance('echec', $this->annulee->id, $this->annulee->etudiant_id);
        $this->postJson(route('esbtp.comptabilite.relances.renvoyer', $ancienEchec->id))
            ->assertOk()->assertJsonPath('success', true);
        $this->assertNonEnvoyee($ancienEchec);

        // Une fois ecartee, elle ne se renvoie plus.
        $this->postJson(route('esbtp.comptabilite.relances.renvoyer', $ancienEchec->id))
            ->assertOk()->assertJsonPath('success', false);
        \App\Jobs\EnvoyerRelanceJob::dispatchSync($ancienEchec->fresh());
        $this->assertNonEnvoyee($ancienEchec);
    }

    public function test_une_relance_ecartee_ne_fait_pas_monter_de_niveau(): void
    {
        $ecartee = $this->relance(ESBTPRelance::STATUT_ECARTEE, $this->active->id, $this->active->etudiant_id);
        $ecartee->forceFill(['created_at' => now()->subDays(30)])->save();

        app(NotificationService::class)->planifierRelancesAvancees(['types_relance' => ['email']]);

        $nouvelle = ESBTPRelance::where('inscription_id', $this->active->id)->whereKeyNot($ecartee->id)->sole();
        $this->assertSame(1, (int) $nouvelle->niveau);
    }

    public function test_la_fiche_d_une_relance_ecartee_dit_pourquoi(): void
    {
        Permission::findOrCreate('comptabilite.relances.send', 'web');
        auth()->user()->givePermissionTo('comptabilite.relances.send');
        $relance = $this->relance('planifiee', $this->annulee->id, $this->annulee->etudiant_id);
        app(NotificationService::class)->envoyerRelance($relance);

        $this->get(route('esbtp.comptabilite.relances.show', $relance->id))
            ->assertOk()
            ->assertSee('Relance écartée, non envoyée')
            ->assertSee("L'inscription de l'élève n'est plus active");
    }

    public function test_une_relance_sans_inscription_se_resout_par_l_eleve_sur_l_annee_courante(): void
    {
        Mail::fake();
        $deLActive = $this->relance('planifiee', null, $this->active->etudiant_id);
        $deLAnnulee = $this->relance('planifiee', null, $this->annulee->etudiant_id);
        $sansInscription = $this->relance('planifiee', null, \App\Models\ESBTPEtudiant::factory()->create()->id);

        app(NotificationService::class)->executerRelancesEnAttente();

        $this->assertSame('envoyee', $deLActive->fresh()->statut);
        $this->assertNonEnvoyee($deLAnnulee);
        $this->assertNonEnvoyee($sansInscription);
    }

    public function test_le_compteur_a_relancer_du_tableau_de_bord_suit_la_liste(): void
    {
        $build = app(\App\Actions\Comptabilite\BuildDashboardDataAction::class);

        $parDefaut = $build->pourAnnee($this->annee);
        $this->assertSame(1, $parDefaut['countARelancer']);
        $this->assertSame(2, $parDefaut['countOverdueTotal'], 'La balance agee, elle, compte toujours tous les retards.');

        $this->reglerInclusion(true);
        $this->assertSame(2, $build->pourAnnee($this->annee)['countARelancer'], 'Le reglage est dans la cle du cache.');
        $this->assertSame(count($this->liste()['ids']), $build->pourAnnee($this->annee)['countARelancer']);
    }

    public function test_la_regle_en_memoire_et_la_requete_disent_la_meme_chose(): void
    {
        foreach ([false, true] as $inclure) {
            $parRequete = PopulationDesRelances::restreindre(ESBTPInscription::query(), $inclure)
                ->whereIn('id', [$this->active->id, $this->annulee->id])->pluck('id')->sort()->values()->all();
            $enMemoire = collect([$this->active, $this->annulee])
                ->filter(fn ($i) => PopulationDesRelances::admet($i, $inclure))->pluck('id')->sort()->values()->all();
            $this->assertSame($parRequete, $enMemoire);
        }
    }

    public function test_l_ecran_de_configuration_enregistre_le_reglage(): void
    {
        Permission::findOrCreate('comptabilite.relances.send', 'web');
        auth()->user()->givePermissionTo('comptabilite.relances.send');
        $parametres = [
            'delai_niveau_1' => 7, 'delai_niveau_2' => 14, 'delai_niveau_3' => 21,
            'montant_minimum' => 0, 'heure_envoi' => '08:00', 'relances_automatiques' => false,
        ];

        $this->postJson(route('esbtp.comptabilite.relances.config.parametres'), $parametres + ['inclure_inscriptions_inactives' => true])
            ->assertOk();
        $this->assertTrue(PopulationDesRelances::inclutLesInactives());
        $this->get(route('esbtp.comptabilite.relances.config'))
            ->assertOk()
            ->assertSee("Relancer aussi les élèves dont l'inscription n'est plus active", false)
            ->assertSee('inclure_inscriptions_inactives\\u0022:true', false);

        $this->postJson(route('esbtp.comptabilite.relances.config.parametres'), $parametres + ['inclure_inscriptions_inactives' => false])
            ->assertOk();
        $this->assertFalse(PopulationDesRelances::inclutLesInactives());
    }
}

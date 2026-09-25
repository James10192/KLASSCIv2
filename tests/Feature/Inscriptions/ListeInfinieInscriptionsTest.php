<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\ESBTPInscriptionService;
use App\Services\Inscriptions\FiltresListeInscriptions;
use App\Services\Inscriptions\SelectionDInscriptions;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La liste des inscriptions se charge au defilement, et une action groupee
 * peut porter sur tout le filtre plutot que sur les seules lignes chargees.
 */
class ListeInfinieInscriptionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['admin.access', 'inscriptions.view', 'inscriptions.validate', 'inscriptions.cancel'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Cache::flush();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['admin.access', 'inscriptions.view', 'inscriptions.validate', 'inscriptions.cancel']);
        $this->actingAs($this->user);

        $this->classe = ESBTPClasse::factory()->create();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
    }

    private function inscriptions(int $n, ?ESBTPClasse $classe = null): void
    {
        $classe ??= $this->classe;
        for ($i = 0; $i < $n; $i++) {
            ESBTPInscription::factory()->create([
                'classe_id' => $classe->id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id,
                'annee_universitaire_id' => $this->annee->id,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'created_by' => $this->user->id,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function filtre(array $plus = []): array
    {
        return ['annee' => $this->annee->id, 'status' => 'all', 'per_page' => 15] + $plus;
    }

    public function test_la_page_rend_la_premiere_tranche_et_le_bas_de_liste_sans_pagination(): void
    {
        $this->inscriptions(20);

        $this->get(route('esbtp.inscriptions.index', $this->filtre()))
            ->assertOk()
            ->assertSee('data-liste-infinie', false)
            ->assertSee('data-page-suivante="2"', false)
            ->assertSee('data-total="20"', false)
            ->assertDontSee('class="pagination', false);
    }

    public function test_la_tranche_suivante_rend_les_lignes_seules(): void
    {
        $this->inscriptions(20);

        $reponse = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('esbtp.inscriptions.index', $this->filtre(['page' => 2, 'mode' => 'rows'])));

        $reponse->assertOk()
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.total', 20)
            ->assertJsonPath('pagination.affiches', 20)
            ->assertJsonMissingPath('stats');

        $this->assertSame(5, substr_count($reponse->json('rows_html'), 'data-inscription-id='));
    }

    public function test_l_export_porte_sur_tout_le_filtre_et_seulement_lui(): void
    {
        $this->inscriptions(3);
        $autreClasse = ESBTPClasse::factory()->create();
        $this->inscriptions(2, $autreClasse);

        $reponse = $this->post(route('esbtp.inscriptions.bulk-export'), $this->filtre([
            'scope' => 'filtre',
            'niveau' => $this->classe->niveau_etude_id,
        ]));

        $reponse->assertOk();
        $csv = $reponse->streamedContent();
        // En-tete + une ligne par inscription du filtre.
        $lignes = array_values(array_filter(explode("\n", trim($csv))));
        $attendues = ESBTPInscription::where('annee_universitaire_id', $this->annee->id)
            ->where('niveau_id', $this->classe->niveau_etude_id)->count();
        $this->assertCount($attendues + 1, $lignes);
    }

    public function test_une_recherche_libre_ne_definit_pas_une_portee(): void
    {
        $this->inscriptions(2);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-export'), $this->filtre(['scope' => 'filtre', 'search' => 'kouassi']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Une recherche libre ne définit pas une portée : videz la recherche, ou cochez les lignes.');
    }

    public function test_sans_portee_ni_lignes_l_action_est_refusee(): void
    {
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-export'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('inscription_ids');
    }

    public function test_chaque_tri_finit_par_l_identifiant(): void
    {
        // Sur une egalite de tri, MySQL rend les lignes dans un ordre libre a
        // chaque LIMIT/OFFSET : une tranche repete des lignes et en saute
        // d'autres. Sur une petite table le hasard ne se voit pas, donc on
        // verifie la requete elle-meme.
        $this->inscriptions(2);

        foreach (['status', 'created_at', 'date_inscription', 'nom'] as $tri) {
            $requetes = [];
            DB::listen(function ($q) use (&$requetes) {
                $requetes[] = $q->sql;
            });

            $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('esbtp.inscriptions.index', $this->filtre(['sort' => $tri, 'dir' => 'asc', 'page' => 2, 'mode' => 'rows'])))
                ->assertOk();

            $liste = collect($requetes)->first(fn ($sql) => str_contains($sql, 'from `esbtp_inscriptions`') && str_contains($sql, 'limit'));
            $this->assertNotNull($liste, "Aucune requête paginée pour le tri {$tri}.");
            $this->assertMatchesRegularExpression('/order by .*`esbtp_inscriptions`\.`id` asc limit/', $liste, "Le tri {$tri} n'a pas de départage unique.");
            DB::flushQueryLog();
            $this->app['events']->forget(\Illuminate\Database\Events\QueryExecuted::class);
        }
    }

    public function test_la_validation_groupee_porte_sur_tout_le_filtre(): void
    {
        $this->inscriptions(3);
        $attendus = ESBTPInscription::where('annee_universitaire_id', $this->annee->id)->pluck('id')->sort()->values()->all();

        $service = Mockery::mock(ESBTPInscriptionService::class)->makePartial();
        $service->shouldReceive('processBulkValidation')->once()
            ->withArgs(function (array $ids) use ($attendus) {
                sort($ids);

                return $ids === $attendus;
            })
            ->andReturn([]);
        $service->shouldReceive('buildBulkValidationMessage')->andReturn('ok');
        $service->shouldReceive('extractBulkProblems')->andReturn([]);
        $this->app->instance(ESBTPInscriptionService::class, $service);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-valider'), $this->filtre(['scope' => 'filtre']))
            ->assertOk();
    }

    public function test_l_annulation_groupee_ne_traite_pas_deux_fois_une_ligne_cochee_deux_fois(): void
    {
        $this->inscriptions(2);
        $ids = ESBTPInscription::where('annee_universitaire_id', $this->annee->id)->pluck('id')->all();

        $service = Mockery::mock(ESBTPInscriptionService::class)->makePartial();
        $service->shouldReceive('annulerInscription')->times(2)->andReturn(['success' => true]);
        $this->app->instance(ESBTPInscriptionService::class, $service);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-annuler'), [
                'inscription_ids' => [$ids[0], $ids[1], $ids[0]],
                'motif' => 'Doublon de saisie',
            ])
            ->assertOk()
            ->assertJsonPath('success_count', 2);
    }

    public function test_au_dela_du_plafond_l_ecriture_est_refusee_pour_le_filtre_comme_pour_les_lignes_cochees(): void
    {
        $this->inscriptions(3);
        $this->app->instance(SelectionDInscriptions::class, new class(app(FiltresListeInscriptions::class)) extends SelectionDInscriptions {
            protected function plafond(): int
            {
                return 2;
            }
        });
        $ids = ESBTPInscription::where('annee_universitaire_id', $this->annee->id)->pluck('id')->all();

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-annuler'), $this->filtre(['scope' => 'filtre', 'motif' => 'Hors délai']))
            ->assertStatus(422);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('esbtp.inscriptions.bulk-valider'), ['inscription_ids' => $ids])
            ->assertStatus(422);

        // L'export n'ecrit rien : il n'est pas borne.
        $this->post(route('esbtp.inscriptions.bulk-export'), $this->filtre(['scope' => 'filtre']))->assertOk();
    }
}

<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIUsageReportController;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Usage\UsageReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsageReportCliTest extends TestCase
{
    use RefreshDatabase;

    private const WEB = 'https://ecole.klassci.com/esbtp/inscriptions';
    private const CLI = 'https://ecole.klassci.com/api/cli/inscriptions/12/validate';

    private User $secretaire;
    private User $comptable;
    private User $technicien;
    private User $etudiant;
    private User $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretaire = $this->userWithRole('Awa Secretaire', 'secretaire');
        $this->comptable = $this->userWithRole('Koffi Comptable', 'comptable');
        $this->technicien = $this->userWithRole('Tech KLASSCI', 'serviceTechnique');
        $this->etudiant = $this->userWithRole('Eleve Un', 'etudiant');
        $this->support = $this->userWithRole('Compte Support', 'superAdmin');

        // Secretaire : trois jours ordinaires + une journee d'import
        $this->audit($this->secretaire, ESBTPInscription::class, '2026-08-03 09:15:00', 4);
        $this->audit($this->secretaire, ESBTPInscription::class, '2026-08-04 10:00:00', 2);
        $this->audit($this->secretaire, ESBTPNote::class, '2026-08-10 15:30:00', 3);
        $this->audit($this->secretaire, ESBTPInscription::class, '2026-08-12 08:00:00', 350);

        // Comptable : ne fait que consulter des paiements
        $this->audit($this->comptable, ESBTPPaiement::class, '2026-08-05 11:00:00', 6, 'retrieved');

        // Nos operations : CLI (compte superAdmin) et compte technique
        $this->audit($this->support, ESBTPInscription::class, '2026-08-06 12:00:00', 40, 'updated', self::CLI);
        $this->audit($this->technicien, ESBTPInscription::class, '2026-08-07 12:00:00', 5);

        // Commande serveur, sans compte
        $this->audit(null, ESBTPInscription::class, '2026-08-08 02:00:00', 20, 'created', 'artisan tenant:backfill');

        // Etudiant, et une action hors periode
        $this->audit($this->etudiant, ESBTPInscription::class, '2026-08-09 18:00:00', 1);
        $this->audit($this->secretaire, ESBTPInscription::class, '2026-06-01 09:00:00', 9);
    }

    /** @test */
    public function route_is_registered(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri());

        $this->assertTrue($uris->contains('api/cli/usage/report'));
    }

    /** @test */
    public function requires_cli_read(): void
    {
        $this->assertSame(403, $this->report([], ['from' => '2026-08-01', 'to' => '2026-08-31'])->getStatusCode());
    }

    /** @test */
    public function rejects_a_window_longer_than_the_configured_maximum(): void
    {
        $response = $this->report(['cli:read'], ['from' => '2025-01-01', 'to' => '2026-08-31']);

        $this->assertSame(422, $response->getStatusCode());
    }

    /** @test */
    public function names_require_cli_admin(): void
    {
        $response = $this->report(['cli:read'], ['from' => '2026-08-01', 'to' => '2026-08-31', 'nominatif' => '1']);

        $this->assertSame(403, $response->getStatusCode());
    }

    /** @test */
    public function our_own_operations_never_count_as_school_usage(): void
    {
        $groupes = $this->data()['groupes'];

        $this->assertSame(4 + 2 + 3 + 350, $groupes['ecole']['actions']);
        $this->assertSame(40 + 5, $groupes['klassci']['actions']);
        $this->assertSame(20, $groupes['systeme']['actions']);
        $this->assertSame(1, $groupes['etudiants']['actions']);
        $this->assertSame(6, $groupes['ecole']['lectures']);
        $this->assertSame(1, $groupes['ecole']['comptes_actifs'], 'la comptable ne fait que lire');
    }

    /** @test */
    public function active_days_and_bulk_days_are_counted_per_account(): void
    {
        $data = $this->data();
        $awa = collect($data['comptes_actifs'])->firstWhere('user_id', $this->secretaire->id);

        $this->assertSame(4, $awa['jours_actifs']);
        $this->assertSame(1, $awa['jours_de_masse']);
        $this->assertSame('2026-08-03', $awa['premiere_action']);
        $this->assertSame('Compte ' . $this->secretaire->id, $awa['compte'], 'pseudonyme par defaut');
        $this->assertCount(1, $data['journees_de_masse']);
        $this->assertSame('2026-08-12', $data['journees_de_masse'][0]['jour']);

        $week = collect($data['semaines'])->firstWhere('semaine', '2026-08-10');
        $this->assertSame(353, $week['actions']);
        $this->assertSame(3, $week['actions_hors_masse']);
    }

    /** @test */
    public function modules_split_school_work_from_ours(): void
    {
        $modules = collect($this->data()['modules'])->keyBy('module');

        $this->assertSame(356, $modules['inscriptions']['actions_ecole']);
        $this->assertSame(40, $modules['inscriptions']['actions_klassci']);
        $this->assertSame(20, $modules['inscriptions']['actions_systeme']);
        $this->assertSame(3, $modules['inscriptions']['jours']);
        $this->assertSame(3, $modules['notes']['actions_ecole']);
        $this->assertSame(0, $modules['presences']['actions_ecole']);
    }

    /** @test */
    public function heatmap_uses_iso_weekdays(): void
    {
        $monday = collect($this->data()['heures'])->first(fn ($c) => $c['jour_semaine'] === 1 && $c['heure'] === 9);

        $this->assertSame(4, $monday['actions'], '3 aout 2026 est un lundi');
    }

    /** @test */
    public function an_excluded_account_moves_to_klassci(): void
    {
        $data = app(UsageReportService::class)->build(
            \App\Services\Usage\UsageWindow::fromDates('2026-08-01', '2026-08-31'),
            [$this->secretaire->id]
        );

        $this->assertArrayNotHasKey('ecole', array_filter($data['groupes'], fn ($g) => $g['actions'] > 0));
        $this->assertSame(45 + 359, $data['groupes']['klassci']['actions']);
    }

    /** @test */
    public function account_coverage_counts_dormant_staff(): void
    {
        $this->secretaire->forceFill(['last_login_at' => '2026-08-12 08:00:00'])->save();
        $this->comptable->forceFill(['last_login_at' => null])->save();

        $parc = collect($this->data()['parc_de_comptes']['personnel_par_role'])->keyBy('role');

        $this->assertSame(1, $parc['comptable']['jamais_connectes']);
        $this->assertSame(1, $parc['secretaire']['actifs_dans_la_periode']);
        $this->assertArrayNotHasKey('serviceTechnique', $parc->all());
    }

    /** @test */
    public function concrete_outcomes_count_school_work_by_entity_and_month(): void
    {
        $data = $this->data();
        $notes = collect($data['realisations'])->firstWhere('entite', 'ESBTPNote');
        $inscriptions = collect($data['realisations'])->firstWhere('entite', 'ESBTPInscription');

        $this->assertSame(3, $notes['crees']);
        $this->assertSame(4 + 2 + 350, $inscriptions['crees'], 'le CLI, le compte technique et l\'etudiant sont exclus');
        $this->assertSame(1, $inscriptions['comptes']);

        $august = collect($data['creations_par_mois'])->firstWhere('mois', '2026-08');
        $this->assertSame(356, collect($august['creations'])->max('nombre'));
    }

    /** @test */
    public function monthly_summary_counts_distinct_days_and_accounts(): void
    {
        $august = collect($this->data()['mois'])->firstWhere('mois', '2026-08');

        $this->assertSame(359, $august['actions']);
        $this->assertSame(4, $august['jours']);
        $this->assertSame(1, $august['comptes']);
    }

    /** @test */
    public function payments_are_summed_per_month_of_payment(): void
    {
        $etudiantId = \App\Models\ESBTPEtudiant::factory()->create()->id;
        $anneeId = \App\Models\ESBTPAnneeUniversitaire::factory()->create()->id;
        \App\Models\ESBTPPaiement::factory()->create(['etudiant_id' => $etudiantId, 'annee_universitaire_id' => $anneeId, 'montant' => 150000, 'date_paiement' => '2026-08-04', 'status' => 'validé']);
        \App\Models\ESBTPPaiement::factory()->create(['etudiant_id' => $etudiantId, 'annee_universitaire_id' => $anneeId, 'montant' => 50000, 'date_paiement' => '2026-08-20', 'status' => 'validé']);
        \App\Models\ESBTPPaiement::factory()->create(['etudiant_id' => $etudiantId, 'annee_universitaire_id' => $anneeId, 'montant' => 99000, 'date_paiement' => '2026-08-21', 'status' => 'en_attente']);
        \App\Models\ESBTPPaiement::factory()->create(['etudiant_id' => $etudiantId, 'annee_universitaire_id' => $anneeId, 'montant' => 70000, 'date_paiement' => '2026-06-02', 'status' => 'validé']);

        $payments = $this->data()['paiements_par_mois'];

        $this->assertCount(1, $payments, 'juin est hors periode, le paiement en attente ne compte pas');
        $this->assertSame('2026-08', $payments[0]['mois']);
        $this->assertSame(2, $payments[0]['nombre']);
        $this->assertEquals(200000, $payments[0]['montant'], 'le JSON rend 200000.0 en entier');
    }

    private function data(): array
    {
        $response = $this->report(['cli:read'], ['from' => '2026-08-01', 'to' => '2026-08-31']);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        return $response->getData(true)['data'];
    }

    private function report(array $abilities, array $query)
    {
        $request = Request::create('/api/cli/usage/report', 'GET', $query);
        $request->setUserResolver(fn () => new class($abilities) {
            public function __construct(private array $abilities) {}

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->abilities, true);
            }
        });

        return app(CLIUsageReportController::class)->report($request, app(UsageReportService::class));
    }

    private function userWithRole(string $name, string $role): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    private function audit(?User $user, string $model, string $at, int $count, string $event = 'created', string $url = self::WEB): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'user_type' => $user ? User::class : null,
                'user_id' => $user?->id,
                'event' => $event,
                'auditable_type' => $model,
                'auditable_id' => $i + 1,
                'url' => $url,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('audits')->insert($chunk);
        }
    }
}

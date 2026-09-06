<?php

namespace Tests\Feature\Console;

use App\Console\Commands\EcheanciersRecompute;
use App\Http\Controllers\API\CLI\CLIEcheancierController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionEcheancierSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * La regeneration des snapshots d'echeancier, par la console et par le CLI.
 *
 * Trois choses a tenir : la simulation n'ecrit rien, le passage reel cree un
 * snapshot par inscription active du perimetre, et relancer ne cree rien de
 * plus — il met a jour. Le CLI repond des memes chiffres et refuse sans
 * cli:admin.
 */
class EcheanciersRecomputeTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        // Une categorie obligatoire avec un montant : sans elle le snapshot
        // existerait mais sans aucune tranche, et le test ne prouverait rien.
        ESBTPFraisCategory::factory()->create(['default_amount' => 150000]);
    }

    /** @test */
    public function la_commande_recommandee_par_le_diagnostic_existe(): void
    {
        $this->assertArrayHasKey(EcheanciersRecompute::NOM, Artisan::all());
    }

    /** @test */
    public function la_simulation_compte_sans_rien_ecrire(): void
    {
        $this->deuxInscriptionsActives();

        $this->artisan(EcheanciersRecompute::NOM, ['--dry-run' => true])
            ->expectsOutputToContain('Simulation')
            ->assertExitCode(0);

        $this->assertSame(0, ESBTPInscriptionEcheancierSnapshot::count());
    }

    /** @test */
    public function le_passage_reel_cree_un_snapshot_par_inscription_active(): void
    {
        [$a, $b] = $this->deuxInscriptionsActives();

        // Hors perimetre : inscription d'une autre annee, et dossier non termine.
        ESBTPInscription::factory()->create();
        ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->annee->id,
            'workflow_step' => 'en_attente_paiement',
        ]);

        $this->artisan(EcheanciersRecompute::NOM)->assertExitCode(0);

        $this->assertSame(2, ESBTPInscriptionEcheancierSnapshot::count());
        $this->assertNotNull($a->fresh()->echeancierSnapshot);
        $this->assertNotNull($b->fresh()->echeancierSnapshot);

        $payload = $a->fresh()->echeancierSnapshot->payload;
        $this->assertNotEmpty($payload['due_lines'], 'la categorie obligatoire a bien ete projetee en tranche(s)');
        $this->assertSame(150000.0, (float) $a->fresh()->echeancierSnapshot->metadata['total_due']);
    }

    /** @test */
    public function relancer_met_a_jour_sans_creer_de_doublon(): void
    {
        $this->deuxInscriptionsActives();

        $this->artisan(EcheanciersRecompute::NOM)->assertExitCode(0);
        $premiers = ESBTPInscriptionEcheancierSnapshot::orderBy('id')->pluck('id')->all();

        $this->artisan(EcheanciersRecompute::NOM)->assertExitCode(0);

        $this->assertSame(2, ESBTPInscriptionEcheancierSnapshot::count());
        $this->assertSame($premiers, ESBTPInscriptionEcheancierSnapshot::orderBy('id')->pluck('id')->all());
    }

    /** @test */
    public function une_inscription_nommee_est_seule_traitee(): void
    {
        [$a] = $this->deuxInscriptionsActives();

        $this->artisan(EcheanciersRecompute::NOM, ['--inscription' => $a->id])->assertExitCode(0);

        $this->assertSame(1, ESBTPInscriptionEcheancierSnapshot::count());
        $this->assertSame($a->id, ESBTPInscriptionEcheancierSnapshot::first()->inscription_id);
    }

    /** @test */
    public function sans_annee_courante_la_commande_echoue_proprement(): void
    {
        $this->annee->update(['is_current' => false]);

        $this->artisan(EcheanciersRecompute::NOM)
            ->expectsOutputToContain('Aucune annee universitaire courante')
            ->assertExitCode(1);
    }

    /** @test */
    public function la_route_cli_est_enregistree_en_post(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/cli/echeanciers/recompute');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    /** @test */
    public function le_cli_exige_le_droit_cli_admin(): void
    {
        $reponse = app(CLIEcheancierController::class)->recompute(
            $this->requeteAvecDroits([], []),
            app(\App\Services\EcheancierRecomputeService::class)
        );

        $this->assertSame(403, $reponse->getStatusCode());
    }

    /** @test */
    public function le_cli_simule_par_defaut_puis_ecrit_sur_demande(): void
    {
        $this->deuxInscriptionsActives();
        $controleur = app(CLIEcheancierController::class);
        $service = app(\App\Services\EcheancierRecomputeService::class);

        $simulation = $controleur->recompute($this->requeteAvecDroits(['cli:admin'], []), $service)->getData(true)['data'];

        $this->assertTrue($simulation['dry_run']);
        $this->assertSame(2, $simulation['perimetre']);
        $this->assertSame(2, $simulation['a_creer']);
        $this->assertSame(0, ESBTPInscriptionEcheancierSnapshot::count());

        $reel = $controleur->recompute($this->requeteAvecDroits(['cli:admin'], ['dry_run' => false]), $service)->getData(true)['data'];

        $this->assertFalse($reel['dry_run']);
        $this->assertSame(2, $reel['creees']);
        $this->assertSame(0, $reel['mises_a_jour']);
        $this->assertSame(0, $reel['erreurs']);
        $this->assertSame(2, ESBTPInscriptionEcheancierSnapshot::count());

        $encore = $controleur->recompute($this->requeteAvecDroits(['cli:admin'], ['dry_run' => false]), $service)->getData(true)['data'];

        $this->assertSame(0, $encore['creees']);
        $this->assertSame(2, $encore['mises_a_jour']);
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPInscription}
     */
    private function deuxInscriptionsActives(): array
    {
        return [
            ESBTPInscription::factory()->create(['annee_universitaire_id' => $this->annee->id]),
            ESBTPInscription::factory()->create(['annee_universitaire_id' => $this->annee->id]),
        ];
    }

    /**
     * @param  array<int, string>  $droits
     * @param  array<string, mixed>  $charge
     */
    private function requeteAvecDroits(array $droits, array $charge): Request
    {
        $utilisateur = new User();
        $utilisateur->id = 1;
        $requete = Request::create('/', 'POST', $charge);
        $requete->setUserResolver(fn () => new class($utilisateur, $droits) extends User
        {
            private array $droits = [];

            public function __construct(?User $utilisateur = null, array $droits = [])
            {
                parent::__construct();
                $this->droits = $droits;
                if ($utilisateur) {
                    $this->setRawAttributes($utilisateur->getAttributes());
                }
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->droits, true);
            }
        });

        return $requete;
    }
}

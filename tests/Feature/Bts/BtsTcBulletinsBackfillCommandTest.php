<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinService;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * c9 — Commande de backfill idempotente des bulletins annuels BTS Tronc Commun.
 *
 * Le détecteur compare la moyenne_generale PERSISTÉE du bulletin annuel officiel
 * avec la moyenne RECALCULÉE par BulletinService::genererDonneesBulletin. Quand un
 * écart >= 0.01 existe, le bulletin est « affected ». En dry-run rien n'est écrit ;
 * en run réel BulletinConsistencyService::regenerateOfficialBulletin est appelé.
 *
 * On contrôle le recalcul et la régénération via des doublures liées au conteneur,
 * pour rendre le test déterministe sans dépendre du pipeline complet de notes.
 *
 * BTS uniquement (LMD intouché). DB : klassci_testing (RefreshDatabase).
 */
class BtsTcBulletinsBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Aligne l'instance courante sur le tenant testé (garde-fou).
        config(['app.tenant_code' => 'presentation']);
    }

    /** @test */
    public function it_aborts_on_tenant_mismatch_without_writing(): void
    {
        $this->bindRecomputedMoyenne(15.00);
        $consistency = $this->spyConsistency();

        $this->makeAffectedScenario(persisted: 12.00);

        $exit = $this->artisan('bts:tc-bulletins-backfill', [
            'tenant' => 'mauvais-tenant',
            '--annee' => $this->anneeId,
        ])->run();

        $this->assertSame(1, $exit, 'Le garde-fou tenant doit renvoyer le code de sortie 1');
        $this->assertSame(0, $consistency->regenerationCalls, 'Aucune régénération sur mismatch tenant');
    }

    /** @test */
    public function dry_run_detects_divergence_without_writing(): void
    {
        $this->bindRecomputedMoyenne(15.00);
        $consistency = $this->spyConsistency();

        $bulletin = $this->makeAffectedScenario(persisted: 12.00);

        $exit = $this->artisan('bts:tc-bulletins-backfill', [
            'tenant' => 'presentation',
            '--annee' => $this->anneeId,
            '--dry-run' => true,
        ])->run();

        $this->assertSame(0, $exit);
        $this->assertSame(0, $consistency->regenerationCalls, 'Le dry-run ne doit JAMAIS régénérer');

        // Aucune écriture : la moyenne persistée reste inchangée.
        $this->assertEqualsWithDelta(
            12.00,
            (float) $bulletin->fresh()->moyenne_generale,
            0.001,
            'Le dry-run ne doit écrire aucune donnée'
        );
    }

    /** @test */
    public function run_regenerates_affected_annual_bulletin(): void
    {
        $this->bindRecomputedMoyenne(15.00);
        $consistency = $this->spyConsistency();

        $bulletin = $this->makeAffectedScenario(persisted: 12.00);

        $exit = $this->artisan('bts:tc-bulletins-backfill', [
            'tenant' => 'presentation',
            '--annee' => $this->anneeId,
        ])->run();

        $this->assertSame(0, $exit);
        $this->assertSame(1, $consistency->regenerationCalls, 'Le run doit régénérer le bulletin obsolète');
        $this->assertSame(
            [(int) $bulletin->etudiant_id, (int) $bulletin->classe_id, (int) $this->anneeId, 'annuel'],
            $consistency->lastRegenerationArgs
        );
    }

    /** @test */
    public function it_is_idempotent_second_run_finds_no_divergence(): void
    {
        // 2e run : la moyenne persistée == recalculée → aucun affected.
        $this->bindRecomputedMoyenne(12.00);
        $consistency = $this->spyConsistency();

        $this->makeAffectedScenario(persisted: 12.00);

        $exit = $this->artisan('bts:tc-bulletins-backfill', [
            'tenant' => 'presentation',
            '--annee' => $this->anneeId,
        ])->run();

        $this->assertSame(0, $exit);
        $this->assertSame(0, $consistency->regenerationCalls, 'Sans écart, aucune régénération (idempotence)');
    }

    /** @test */
    public function it_skips_when_no_official_annual_bulletin(): void
    {
        $this->bindRecomputedMoyenne(15.00);
        $consistency = $this->spyConsistency();

        // Scénario éligible (phases orienté) MAIS sans bulletin annuel persisté.
        $this->makePhaseBasedScenario();

        $exit = $this->artisan('bts:tc-bulletins-backfill', [
            'tenant' => 'presentation',
            '--annee' => $this->anneeId,
        ])->run();

        $this->assertSame(0, $exit);
        $this->assertSame(0, $consistency->regenerationCalls, 'Sans bulletin officiel, on skippe sans régénérer');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private int $anneeId = 0;

    /**
     * Lie une doublure de BulletinService dont genererDonneesBulletin retourne une
     * moyenne globale contrôlée (le reste des méthodes délègue à la vraie instance).
     */
    private function bindRecomputedMoyenne(float $moyenneGlobale): void
    {
        $this->app->bind(BulletinService::class, function () use ($moyenneGlobale) {
            return new class($moyenneGlobale) extends BulletinService
            {
                public function __construct(private float $forcedMoyenne)
                {
                    // Pas d'appel parent : on ne se sert que de genererDonneesBulletin ici.
                }

                public function genererDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode = 'semestre1')
                {
                    return ['moyenneGlobale' => $this->forcedMoyenne];
                }
            };
        });
    }

    private function spyConsistency(): object
    {
        // Étend la vraie classe pour satisfaire le type-hint du conteneur, mais
        // bypasse le constructeur (deps lourdes) et override la seule méthode utile.
        $spy = new class extends BulletinConsistencyService
        {
            public int $regenerationCalls = 0;

            /** @var array<int, mixed> */
            public array $lastRegenerationArgs = [];

            public function __construct()
            {
                // Pas d'appel parent : on n'a besoin que de regenerateOfficialBulletin.
            }

            public function regenerateOfficialBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
            {
                $this->regenerationCalls++;
                $this->lastRegenerationArgs = [$etudiantId, $classeId, $anneeUniversitaireId, $periode];

                return [];
            }
        };

        $this->app->instance(BulletinConsistencyService::class, $spy);

        return $spy;
    }

    /**
     * Étudiant orienté via phases, classe de spécialité (filière fille TC) + bulletin
     * annuel persisté avec une moyenne stale.
     */
    private function makeAffectedScenario(float $persisted): ESBTPBulletin
    {
        [$inscription, , $specClasse] = $this->makePhaseBasedScenario();

        return ESBTPBulletin::factory()->create([
            'etudiant_id' => $inscription->etudiant_id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $this->anneeId,
            'periode' => 'annuel',
            'moyenne_generale' => $persisted,
        ]);
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makePhaseBasedScenario(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $this->anneeId = $annee->id;

        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'parent_id' => null]);
        $specFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => $tcFiliere->id]);

        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $etudiant = ESBTPEtudiant::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
            'inscription_origine_id' => null,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return [$inscription, $tcClasse, $specClasse];
    }
}

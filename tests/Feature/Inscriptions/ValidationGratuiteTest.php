<?php

namespace Tests\Feature\Inscriptions;

use App\Domain\Inscriptions\ExonerationInscription;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\ApplicableFraisResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ValidationGratuiteTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $categorie;
    private ESBTPFraisSubscription $souscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['admin.access', 'inscriptions.validate', 'frais.souscriptions.ajuster'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'inscriptions.validate', 'frais.souscriptions.ajuster']);
        $this->actingAs($this->agent);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'status' => 'en_attente',
            'workflow_step' => 'en_validation',
        ]);
        $this->categorie = ESBTPFraisCategory::factory()->create([
            'is_mandatory' => true, 'is_active' => true, 'default_amount' => 50000,
        ]);
        $this->souscription = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->categorie->id,
            'amount' => 50000,
            'created_by' => $this->agent->id,
        ]);

        // Isoler le barème obligatoire du tenant dans les tests. En production,
        // c'est le resolver réel qui détecte les autres frais manquants.
        $resolver = Mockery::mock(ApplicableFraisResolver::class);
        $resolver->shouldReceive('resolveMandatoryFeesForInscription')
            ->andReturn(collect([['category' => $this->categorie, 'amount' => 50000]]));
        $this->app->instance(ApplicableFraisResolver::class, $resolver);
    }

    private function exonere(): void
    {
        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), [
            'amount' => 0,
            'reason' => 'Dispense approuvée par la direction pour cette inscription',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_le_zero_motive_et_audite_permet_la_validation_sans_paiement(): void
    {
        $this->exonere();
        $this->assertTrue(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('mode', 'explicit_waiver')->assertJsonPath('eligible', true);

        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame('active', $this->inscription->fresh()->status);
        $this->assertSame('etudiant_cree', $this->inscription->fresh()->workflow_step);
        $this->assertSame($this->agent->id, $this->inscription->fresh()->validated_by);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
        $this->assertStringContainsString(ExonerationInscription::TRACE, (string) $this->souscription->fresh()->notes);
        $this->assertStringContainsString('Dispense approuvée', (string) $this->souscription->fresh()->notes);

        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_un_zero_non_approuve_ne_vaut_pas_gratuite(): void
    {
        $this->souscription->update(['amount' => 0]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_un_zero_deja_saisi_peut_etre_reconfirme_avec_motif(): void
    {
        $this->souscription->update(['amount' => 0]);
        $this->exonere();
        $this->assertTrue(app(ExonerationInscription::class)->estApplicable($this->inscription));
    }

    public function test_un_autre_frais_positif_bloque_la_validation_sans_versement(): void
    {
        $this->exonere();
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => ESBTPFraisCategory::factory()->optionnelle()->create()->id,
            'amount' => 12500,
            'created_by' => $this->agent->id,
        ]);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_un_paiement_en_attente_ne_devient_pas_un_paiement_valide(): void
    {
        $this->exonere();
        ESBTPPaiement::factory()->pour($this->inscription)->create(['status' => 'en_attente']);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_un_paiement_etranger_ne_valide_pas_ce_dossier(): void
    {
        $autre = ESBTPInscription::factory()->create();
        $paiement = ESBTPPaiement::factory()->pour($autre)->create(['status' => 'validé']);
        $this->inscription->update(['paiement_validation_id' => $paiement->id]);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_sans_souscription_une_inscription_ne_devient_pas_gratuite(): void
    {
        $this->souscription->delete();
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
    }

    public function test_une_augmentation_revoque_la_dispense(): void
    {
        $this->exonere();
        $this->souscription->update(['amount' => 30000]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
    }

    public function test_un_retour_a_zero_non_audite_ne_reutilise_pas_une_ancienne_dispense(): void
    {
        $this->exonere();
        $this->souscription->update(['amount' => 30000]);
        $this->souscription->update(['amount' => 0]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
    }

    public function test_le_lot_admet_le_zero_autorise_et_refuse_le_zero_sans_preuve(): void
    {
        $this->exonere();
        $autre = ESBTPInscription::factory()->create([
            'classe_id' => $this->inscription->classe_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'status' => 'en_attente', 'workflow_step' => 'en_validation',
        ]);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $autre->id,
            'frais_category_id' => $this->categorie->id,
            'amount' => 0, 'created_by' => $this->agent->id,
        ]);
        $this->postJson(route('esbtp.inscriptions.bulk-valider'), [
            'inscription_ids' => [$this->inscription->id, $autre->id],
        ])->assertOk()->assertJsonPath('stats.validees_direct', 1);
        $this->assertSame('active', $this->inscription->fresh()->status);
        $this->assertSame('en_attente', $autre->fresh()->status);
    }

    public function test_la_cli_exige_permission_et_accepte_une_dispense_valide(): void
    {
        $this->exonere();
        $sansDroit = User::factory()->create();
        Sanctum::actingAs($sansDroit, ['cli:read', 'cli:write']);
        $this->postJson("/api/cli/inscriptions/{$this->inscription->id}/validate")->assertStatus(403);

        Sanctum::actingAs($this->agent, ['cli:read', 'cli:write']);
        $this->postJson("/api/cli/inscriptions/{$this->inscription->id}/validate")->assertOk();
        $this->assertSame('active', $this->inscription->fresh()->status);
    }

    public function test_une_derogation_de_capacite_est_interdite_sans_permission(): void
    {
        $this->postJson(route('esbtp.inscriptions.bulk-valider'), [
            'inscription_ids' => [$this->inscription->id],
            'force' => true,
        ])->assertForbidden();
    }

    public function test_les_permissions_refusent_la_validation_et_l_ajustement_non_autorises(): void
    {
        $simple = User::factory()->create();
        $simple->givePermissionTo('admin.access');
        $this->actingAs($simple);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(403);
        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), ['amount' => 0, 'reason' => 'Décision approuvée et motivée'])->assertStatus(403);
    }
}

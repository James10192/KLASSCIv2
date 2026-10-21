<?php

namespace Tests\Feature\Inscriptions;

use App\Domain\Inscriptions\ExonerationInscription;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
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
            ->andReturn(collect([[
                'category' => $this->categorie,
                'configuration' => new ESBTPFraisConfiguration(['amount' => 50000, 'is_active' => true]),
                'amount' => 50000,
            ]]));
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

    public function test_le_post_urlencode_simulant_put_valide_depuis_la_fiche(): void
    {
        $this->exonere();
        $reponse = $this->actingAs($this->agent)->call(
            'POST', route('esbtp.inscriptions.valider', $this->inscription),
            ['_method' => 'PUT', '_token' => 'csrf-test'], [], [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
             'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $reponse->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('inscription_id', $this->inscription->id);
        $this->assertSame('etudiant_cree', $this->inscription->fresh()->workflow_step);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_annulation_passe_par_le_form_request_sans_validator_fantome(): void
    {
        Permission::findOrCreate('inscriptions.cancel', 'web');
        $this->agent->givePermissionTo('inscriptions.cancel');

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->putJson(route('esbtp.inscriptions.annuler', $this->inscription),
                ['motif' => 'Inscription enregistrée par erreur'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame('annulée', $this->inscription->fresh()->status);
    }

    public function test_annulation_sans_motif_est_refusee_par_le_form_request(): void
    {
        Permission::findOrCreate('inscriptions.cancel', 'web');
        $this->agent->givePermissionTo('inscriptions.cancel');

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->putJson(route('esbtp.inscriptions.annuler', $this->inscription),
                ['motif' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');
        $this->assertNotSame('annulée', $this->inscription->fresh()->status);
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
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))->assertForbidden();
        $this->postJson(route('esbtp.inscriptions.bulk-validation-preview'), [
            'inscription_ids' => [$this->inscription->id],
        ])->assertForbidden();
        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), ['amount' => 0, 'reason' => 'Décision approuvée et motivée'])->assertStatus(403);
    }

    /**
     * Remplacer le mock historique par le resolver réel pour couvrir les
     * configurations SQL et la sélection LMD/BTS selon le statut d'affectation.
     */
    private function baremeReel(string $systeme, int $affecte, int $nonAffecte = 280000): ESBTPFraisConfiguration
    {
        $this->app->forgetInstance(ApplicableFraisResolver::class);

        $classe = $this->inscription->classe;
        $parcoursId = null;
        if ($systeme === 'LMD') {
            $domaine = ESBTPLMDDomaine::create([
                'name' => 'Sciences humaines', 'code' => 'SH-CI', 'is_active' => true,
            ]);
            $mention = ESBTPLMDMention::create([
                'name' => 'Communication', 'code' => 'COM-CI',
                'domaine_id' => $domaine->id, 'is_active' => true,
            ]);
            $parcours = ESBTPLMDParcours::create([
                'name' => 'Communication des Organisations', 'code' => 'CO-CI',
                'mention_id' => $mention->id, 'filiere_id' => $classe->filiere_id,
                'is_active' => true,
            ]);
            $parcoursId = $parcours->id;
            $classe->update(['systeme_academique' => 'LMD', 'parcours_id' => $parcoursId]);
        }

        return ESBTPFraisConfiguration::create([
            'frais_category_id' => $this->categorie->id,
            'systeme_academique' => $systeme,
            'filiere_id' => $systeme === 'LMD' ? null : $classe->filiere_id,
            'parcours_id' => $parcoursId,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'amount' => $nonAffecte,
            'amount_affecte' => $affecte,
            'amount_reaffecte' => $affecte,
            'amount_non_affecte' => $nonAffecte,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $this->agent->id,
        ]);
    }

    public function test_lmd_gratuite_effective_parcours_niveau_sans_exoneration_ni_recu(): void
    {
        $configuration = $this->baremeReel('LMD', 0);
        $this->inscription->update([
            'statut_etablissement' => 'ancien',
            'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
        ]);
        $this->souscription->update(['amount' => 0]);

        $frais = app(ApplicableFraisResolver::class)->resolveMandatoryFeesForInscription($this->inscription)->first();
        $this->assertTrue($configuration->is($frais['configuration']));
        $this->assertSame(0.0, $frais['amount']);
        $this->assertSame('LMD', $frais['scope']['systeme']);
        $this->assertSame($configuration->parcours_id, $frais['scope']['parcours_id']);

        $apercu = $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)->assertJsonPath('mode', 'configured_free');
        $this->assertStringContainsString('Gratuité configurée', $apercu->json('message'));
        $this->postJson(route('esbtp.inscriptions.bulk-validation-preview'), [
            'inscription_ids' => [$this->inscription->id],
        ])->assertOk()->assertJsonPath('ready', 1)
            ->assertJsonPath('configured_free', 1)
            ->assertJsonPath('items.0.mode', 'configured_free');
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame('active', $this->inscription->fresh()->status);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
        $this->assertStringNotContainsString(ExonerationInscription::TRACE, (string) $this->souscription->fresh()->notes);
    }

    public function test_bts_gratuite_effective_filiere_niveau_reste_valide(): void
    {
        $configuration = $this->baremeReel('BTS', 0);
        $this->souscription->update(['amount' => 0]);
        $frais = app(ApplicableFraisResolver::class)->resolveMandatoryFeesForInscription($this->inscription)->first();
        $this->assertTrue($configuration->is($frais['configuration']));
        $this->assertSame('BTS', $frais['scope']['systeme']);
        $this->assertSame($configuration->filiere_id, $frais['scope']['filiere_id']);

        $this->postJson(route('esbtp.inscriptions.bulk-valider'), [
            'inscription_ids' => [$this->inscription->id],
        ])->assertOk()->assertJsonPath('stats.validees_direct', 1);
        $this->assertSame('active', $this->inscription->fresh()->status);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_absence_de_configuration_ne_se_transforme_pas_en_gratuite_meme_par_defaut_zero(): void
    {
        $this->app->forgetInstance(ApplicableFraisResolver::class);
        $this->categorie->update(['default_amount' => 0]);
        $this->souscription->update(['amount' => 0]);

        $apercu = $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->assertStringContainsString('Barème manquant', $apercu->json('message'));
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
        $this->assertSame('en_attente', $this->inscription->fresh()->status);
    }

    public function test_bareme_inactif_ou_hors_scope_reste_bloquant(): void
    {
        $configuration = $this->baremeReel('BTS', 0);
        $this->souscription->update(['amount' => 0]);
        $configuration->update(['is_active' => false]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));

        $autreNiveau = \App\Models\ESBTPNiveauEtude::factory()->create();
        $configuration->update(['is_active' => true, 'niveau_id' => $autreNiveau->id]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
    }

    public function test_bareme_normal_positif_zero_sans_audit_refuse_et_avec_motif_autorise(): void
    {
        $this->baremeReel('LMD', 50000);
        $this->souscription->update(['amount' => 0]);
        $apercu = $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->assertStringContainsString('Exonération individuelle à confirmer', $apercu->json('message'));
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);

        $this->exonere();
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', true)->assertJsonPath('mode', 'explicit_waiver');
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertOk();
        $this->assertStringContainsString(ExonerationInscription::TRACE, (string) $this->souscription->fresh()->notes);
    }

    public function test_gratuite_configuree_respecte_paiement_en_attente_et_annulation(): void
    {
        $this->baremeReel('BTS', 0);
        $this->souscription->update(['amount' => 0]);
        $paiement = ESBTPPaiement::factory()->pour($this->inscription)->create(['status' => 'en_attente']);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
        $paiement->delete();

        $this->inscription->update(['status' => 'annulée']);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_cli_unitaire_accepte_gratuite_configuree_et_refuse_droits_insuffisants(): void
    {
        $this->baremeReel('LMD', 0);
        $this->souscription->update(['amount' => 0]);
        $sansDroit = User::factory()->create();
        Sanctum::actingAs($sansDroit, ['cli:read', 'cli:write']);
        $this->postJson("/api/cli/inscriptions/{$this->inscription->id}/validate")->assertForbidden();

        Sanctum::actingAs($this->agent, ['cli:read', 'cli:write']);
        $this->postJson("/api/cli/inscriptions/{$this->inscription->id}/validate")->assertOk();
        $this->assertSame('active', $this->inscription->fresh()->status);
    }

    public function test_statut_non_affecte_ne_beneficie_pas_du_zero_affecte(): void
    {
        $this->baremeReel('LMD', 0, 280000);
        $this->inscription->update(['affectation_status' => 'non_affecte']);
        $this->souscription->update(['amount' => 0]);
        $frais = app(ApplicableFraisResolver::class)->resolveMandatoryFeesForInscription($this->inscription)->first();
        $this->assertSame(280000.0, $frais['amount']);
        $apercu = $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->assertStringContainsString('Exonération individuelle à confirmer', $apercu->json('message'));
    }

    public function test_gratuite_bareme_ne_contourne_pas_doublon_ou_capacite(): void
    {
        $this->baremeReel('BTS', 0);
        $this->souscription->update(['amount' => 0]);

        // La base impose (étudiant, année, classe) unique : le doublon
        // métier à tester est dans une AUTRE classe de la même année.
        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $this->inscription->filiere_id,
            'niveau_etude_id' => $this->inscription->niveau_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
        ]);
        $doublon = ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'classe_id' => $autreClasse->id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
        $doublon->delete();

        $this->inscription->classe->update(['places_totales' => 1]);
        ESBTPInscription::factory()->create([
            'classe_id' => $this->inscription->classe_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }
}

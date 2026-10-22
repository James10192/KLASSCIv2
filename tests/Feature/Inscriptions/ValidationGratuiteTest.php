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


    public function test_reconfirmation_historique_de_zero_produit_nouvel_audit_et_nouvel_auteur(): void
    {
        $this->souscription->update(['amount' => 0, 'notes' => 'Ancienne réduction à zéro sans preuve signée']);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $avant = \OwenIt\Auditing\Models\Audit::where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $this->souscription->id)->max('id') ?? 0;

        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), ['amount' => 0, 'reason' => 'Confirmation actuelle de la dispense précédemment accordée'])
            ->assertOk()->assertJsonPath('success', true);

        $audit = \OwenIt\Auditing\Models\Audit::where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $this->souscription->id)->orderByDesc('id')->firstOrFail();
        $this->assertGreaterThan($avant, $audit->id);
        $this->assertSame($this->agent->id, (int) $audit->user_id);
        $this->assertStringContainsString(ESBTPFraisSubscription::TAG_EXONERATION, (string) $audit->tags);
        $this->assertStringContainsString('Confirmation actuelle', (string) $this->souscription->fresh()->notes);
        $this->assertTrue(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_audit_desactive_refuse_la_reconfirmation_et_ne_change_aucun_montant(): void
    {
        $this->souscription->update(['amount' => 0, 'notes' => 'Montant nul ancien sans preuve']);
        $avant = $this->souscription->fresh()->notes;
        config()->set('audit.enabled', false);

        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), ['amount' => 0, 'reason' => 'Décision de reconfirmation motivée aujourd’hui'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame($avant, $this->souscription->fresh()->notes);
    }

    public function test_suppression_de_preuve_retire_l_eligibilite_sans_creer_de_paiement(): void
    {
        $this->exonere();
        \OwenIt\Auditing\Models\Audit::where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $this->souscription->id)
            ->where('event', 'updated')->delete();
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])->assertStatus(422);
    }

    public function test_regeneration_necrase_jamais_une_dispense_reconfirmee(): void
    {
        $this->exonere();
        $notes = (string) $this->souscription->fresh()->notes;
        app(\App\Services\ESBTPInscriptionService::class)
            ->regenererFraisInscription($this->inscription->fresh());
        $this->assertSame(0.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame($notes, (string) $this->souscription->fresh()->notes);
        app(\App\Services\ESBTPInscriptionService::class)->saveGeneratedFeesAsSubscriptions(
            $this->inscription->fresh(),
            [['category_id' => $this->categorie->id, 'amount' => 50000, 'type' => 'mandatory', 'description' => 'Frais obligatoire']]
        );
        $this->assertSame(0.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame($notes, (string) $this->souscription->fresh()->notes);
    }


    public function test_audit_desactive_refuse_aussi_la_remise_partielle(): void
    {
        $avant = (float) $this->souscription->amount;
        config()->set('audit.enabled', false);
        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id,
            'subscription' => $this->souscription->id,
        ]), ['amount' => 20000, 'reason' => 'Remise partielle approuvée par la direction'])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame($avant, (float) $this->souscription->fresh()->amount);
    }

    public function test_regeneration_protege_ancienne_remise_partielle_non_auditee(): void
    {
        config()->set('audit.enabled', false);
        $this->souscription->update([
            'amount' => 20000, 'notes' => 'Remise de solidarité accordée par la direction avant le journal',
        ]);
        config()->set('audit.enabled', true);
        app(\App\Services\ESBTPInscriptionService::class)
            ->regenererFraisInscription($this->inscription->fresh());
        $this->assertSame(20000.0, (float) $this->souscription->fresh()->amount);
        app(\App\Services\ESBTPInscriptionService::class)->saveGeneratedFeesAsSubscriptions(
            $this->inscription->fresh(),
            [['category_id' => $this->categorie->id, 'amount' => 50000, 'type' => 'mandatory', 'description' => 'Frais obligatoire']]
        );
        $this->assertSame(20000.0, (float) $this->souscription->fresh()->amount);
    }


    public function test_reconfirmation_partielle_historique_ne_modifie_pas_le_montant_et_audite_auteur(): void
    {
        config()->set('audit.enabled', false);
        $this->souscription->update(['amount' => 20000, 'notes' => 'Remise ancienne de rentrée']);
        config()->set('audit.enabled', true);
        $avant = \OwenIt\Auditing\Models\Audit::where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $this->souscription->id)->max('id') ?? 0;

        $this->putJson(route('esbtp.inscriptions.update-subscription', [
            'inscription' => $this->inscription->id, 'subscription' => $this->souscription->id,
        ]), ['amount' => 20000, 'reason' => 'Reconfirmation de la remise de rentrée par la direction'])
            ->assertOk()->assertJsonPath('success', true);

        $audit = \OwenIt\Auditing\Models\Audit::where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $this->souscription->id)->orderByDesc('id')->firstOrFail();
        $this->assertGreaterThan($avant, $audit->id);
        $this->assertSame($this->agent->id, (int) $audit->user_id);
        $this->assertSame(20000.0, (float) $this->souscription->fresh()->amount);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
    }


    public function test_une_resouscription_ne_peut_pas_ecraser_une_dispense_existante(): void
    {
        $this->exonere();
        $notes = (string) $this->souscription->fresh()->notes;
        try {
            ESBTPFraisSubscription::subscribe(
                $this->inscription->id, $this->categorie->id, 50000,
                $this->agent->id, 'Nouvelle souscription non autorisée'
            );
            $this->fail('La souscription protégée aurait dû être refusée.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('déjà souscrit', $e->getMessage());
        }
        $this->assertSame(0.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame($notes, (string) $this->souscription->fresh()->notes);
        $this->assertTrue(app(ExonerationInscription::class)->estApplicable($this->inscription));
    }


    public function test_fiche_inscription_valide_reinscription_lmd_ancien_workflow_avec_exoneration_auditee(): void
    {
        $this->inscription->update([
            'type_inscription' => 'réinscription',
            'workflow_step' => 'documents_complets',
            'status' => 'active',
        ]);
        $this->exonere();
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)->assertJsonPath('mode', 'explicit_waiver');

        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [
            'observations' => 'Réinscription historique confirmée depuis la fiche.',
        ])->assertOk()->assertJsonPath('success', true);

        $validee = $this->inscription->fresh();
        $this->assertSame('active', $validee->status);
        $this->assertSame('etudiant_cree', $validee->workflow_step);
        $this->assertSame($this->agent->id, $validee->validated_by);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $validee->id)->count());
        $history = \App\Models\ESBTPInscriptionWorkflowHistory::query()
            ->where('inscription_id', $validee->id)
            ->where('action', 'validation_finale')->firstOrFail();
        $this->assertSame('documents_complets', $history->etape_from);
        $this->assertSame('exoneration_sans_paiement', $history->metadata['mode_validation']);
        $this->assertStringContainsString('Réinscription historique', (string) $history->commentaires);
    }

    public function test_route_historique_refuse_zero_non_audite_sans_valider_ni_faux_recu(): void
    {
        $this->inscription->update(['type_inscription' => 'réinscription', 'workflow_step' => 'documents_complets']);
        $this->souscription->update(['amount' => 0, 'notes' => 'Remise historique sans preuve']);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', false);
        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame('documents_complets', $this->inscription->fresh()->workflow_step);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_route_historique_refuse_revalidation_et_annulation_sans_autre_historique(): void
    {
        $this->exonere();
        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [])
            ->assertStatus(422);
        $this->assertSame(1, \App\Models\ESBTPInscriptionWorkflowHistory::where('inscription_id', $this->inscription->id)
            ->where('action', 'validation_finale')->count());

        $this->inscription->update(['status' => 'annulée', 'workflow_step' => 'en_validation']);
        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [])
            ->assertStatus(422);
        $this->assertSame('annulée', $this->inscription->fresh()->status);
    }

    public function test_route_historique_ne_valide_pas_un_paiement_en_attente(): void
    {
        $this->exonere();
        $this->inscription->update(['type_inscription' => 'réinscription', 'workflow_step' => 'documents_complets']);
        ESBTPPaiement::factory()->pour($this->inscription)->create(['status' => 'en_attente']);
        $this->postJson(route('esbtp.inscriptions.valider-definitivement', $this->inscription), [])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame('documents_complets', $this->inscription->fresh()->workflow_step);
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

    public function test_formulaire_urlencode_post_methode_put_valide_sans_recu_fictif(): void
    {
        $this->exonere();

        // Le navigateur envoie POST + _method=PUT en application/x-www-form-urlencoded.
        // La route PUT réelle et les autorisations restent applicables.
        $response = $this->call(
            'POST',
            route('esbtp.inscriptions.valider', $this->inscription),
            ['_method' => 'PUT', 'redirect_to' => 'etudiant'],
            [], [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame('etudiant_cree', $this->inscription->fresh()->workflow_step);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_validation_en_lot_formulaire_urlencode_preserve_les_regles_de_gratuite(): void
    {
        $this->exonere();
        $response = $this->call(
            'POST',
            route('esbtp.inscriptions.bulk-valider'),
            ['inscription_ids' => [$this->inscription->id]],
            [], [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame('etudiant_cree', $this->inscription->fresh()->workflow_step);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_un_zero_non_approuve_ne_vaut_pas_gratuite(): void
    {
        $this->souscription->update(['amount' => 0]);
        $this->assertFalse(app(ExonerationInscription::class)->estApplicable($this->inscription));
        $preview = $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))->assertOk();
        $this->assertStringContainsString((string) $this->categorie->name, $preview->json('message'));
        $this->assertStringContainsString('reconfirmer', $preview->json('message'));
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

    /**
     * Régression de création : l'ancienne sauvegarde ignorait systématiquement
     * les frais obligatoires à 0 FCFA, malgré un barème BTS/LMD explicite.
     */
    private function genererLaSouscriptionAbsente(): ESBTPFraisSubscription
    {
        // Reproduire une nouvelle inscription, avant toute souscription.
        // Suppression SQL ciblée pour ne pas dépendre du soft-delete du modèle.
        \Illuminate\Support\Facades\DB::table('esbtp_frais_subscriptions')
            ->where('id', $this->souscription->id)
            ->delete();

        $service = app(\App\Services\ESBTPInscriptionService::class);
        $frais = $service->generateFeesForInscription(
            $this->inscription,
            [],
            $this->inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS
        );
        $service->saveGeneratedFeesAsSubscriptions($this->inscription, $frais);

        return ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)
            ->where('frais_category_id', $this->categorie->id)
            ->firstOrFail();
    }


    /**
     * Les souscriptions gratuites des dossiers antérieurs peuvent manquer.
     * Le rattrapage ne les crée que pour un barème explicite et applicable.
     */
    public function test_regeneration_zero_configure_bts_repare_et_valide_sans_paiement(): void
    {
        $this->baremeReel('BTS', 0);
        \Illuminate\Support\Facades\DB::table('esbtp_frais_subscriptions')
            ->where('id', $this->souscription->id)->delete();

        $service = app(\App\Services\Frais\SouscriptionsObligatoiresManquantes::class);
        $apercu = $service->executer(false, null, [$this->inscription->id], true);
        $this->assertSame(1, $apercu['total_ajouter']);
        $this->assertSame(0.0, $apercu['lignes'][0]['montant']);
        $this->assertSame(0, ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->count());
        $service->executer(true, null, [$this->inscription->id], true);
        $souscription = ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->firstOrFail();
        $this->assertSame(0.0, (float) $souscription->amount);
        $this->assertTrue((bool) $souscription->is_active);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)->assertJsonPath('mode', 'configured_free');
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_regeneration_zero_configure_lmd_respecte_parcours_et_annee(): void
    {
        $this->baremeReel('LMD', 0);
        \Illuminate\Support\Facades\DB::table('esbtp_frais_subscriptions')
            ->where('id', $this->souscription->id)->delete();

        $service = app(\App\Services\Frais\SouscriptionsObligatoiresManquantes::class);
        $apercu = $service->executer(false, null, [$this->inscription->id], true);
        $this->assertSame(1, $apercu['total_ajouter']);
        $this->assertSame(0.0, $apercu['lignes'][0]['montant']);
        $service->executer(true, null, [$this->inscription->id], true);
        $souscription = ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->firstOrFail();
        $this->assertSame(0.0, (float) $souscription->amount);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)->assertJsonPath('mode', 'configured_free');
    }

    public function test_regeneration_zero_sans_bareme_ne_cree_rien(): void
    {
        $this->app->forgetInstance(ApplicableFraisResolver::class);
        $this->categorie->update(['default_amount' => 0]);
        \Illuminate\Support\Facades\DB::table('esbtp_frais_subscriptions')
            ->where('id', $this->souscription->id)->delete();

        $service = app(\App\Services\Frais\SouscriptionsObligatoiresManquantes::class);
        $apercu = $service->executer(false, null, [$this->inscription->id], true);
        $this->assertSame(0, $apercu['total_ajouter']);
        $service->executer(true, null, [$this->inscription->id], true);
        $this->assertSame(0, ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->count());
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
    }

    public function test_regeneration_zero_necrase_pas_un_montant_existant(): void
    {
        $this->baremeReel('BTS', 0);
        $service = app(\App\Services\Frais\SouscriptionsObligatoiresManquantes::class);
        $apercu = $service->executer(false, null, [$this->inscription->id], true);
        $this->assertSame(0, $apercu['total_ajouter']);
        $service->executer(true, null, [$this->inscription->id], true);
        $this->assertSame(50000.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_creation_bts_a_zero_cree_la_souscription_et_valide_sans_paiement(): void
    {
        $this->baremeReel('BTS', 0);
        $souscription = $this->genererLaSouscriptionAbsente();
        $this->assertSame(0.0, (float) $souscription->amount);
        $this->assertTrue((bool) $souscription->is_active);

        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)
            ->assertJsonPath('mode', 'configured_free');
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
        $this->assertStringNotContainsString(ExonerationInscription::TRACE, (string) $souscription->fresh()->notes);
    }

    public function test_creation_lmd_a_zero_respecte_parcours_et_annee_sans_recu(): void
    {
        $this->baremeReel('LMD', 0);
        $souscription = $this->genererLaSouscriptionAbsente();
        $this->assertSame(0.0, (float) $souscription->amount);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', true)
            ->assertJsonPath('mode', 'configured_free');
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_creation_a_zero_sans_configuration_reste_interdite(): void
    {
        $this->app->forgetInstance(ApplicableFraisResolver::class);
        $this->categorie->update(['default_amount' => 0]);
        $souscription = $this->genererLaSouscriptionAbsente();
        $this->assertSame(0.0, (float) $souscription->amount);

        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertOk()->assertJsonPath('eligible', false);
        $this->putJson(route('esbtp.inscriptions.valider', $this->inscription), [])
            ->assertStatus(422);
    }

    public function test_creation_zero_necrase_pas_une_souscription_payante_existante(): void
    {
        $this->baremeReel('BTS', 0);
        $service = app(\App\Services\ESBTPInscriptionService::class);
        $frais = $service->generateFeesForInscription($this->inscription);
        $service->saveGeneratedFeesAsSubscriptions($this->inscription, $frais);

        $this->assertSame(50000.0, (float) $this->souscription->fresh()->amount);
        $this->getJson(route('esbtp.inscriptions.validation-preview', $this->inscription))
            ->assertJsonPath('eligible', false);
    }

    public function test_creer_souscription_bts_respecte_la_priorite_du_bareme_annuel(): void
    {
        $classe = $this->inscription->classe;
        // L'ancienne deuxième requête prenait ce premier barème global
        // au lieu du barème annuel prioritaire réellement sélectionné.
        ESBTPFraisConfiguration::create([
            'frais_category_id' => $this->categorie->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => null,
            'amount' => 99000,
            'amount_affecte' => 99000,
            'amount_reaffecte' => 99000,
            'amount_non_affecte' => 99000,
            'payment_deadline_days' => 30,
            'is_active' => true,
            'created_by' => $this->agent->id,
        ]);
        $configuration = $this->baremeReel('BTS', 42000, 42000);
        $service = app(\App\Services\ESBTPInscriptionService::class);
        $frais = $service->generateFeesForInscription($this->inscription);
        $this->assertTrue($configuration->is(
            app(ApplicableFraisResolver::class)->resolveMandatoryFeesForInscription($this->inscription)->first()['configuration']
        ));
        $this->assertSame(42000.0, (float) collect($frais)->firstWhere('category_id', $this->categorie->id)['amount']);

        $service->saveGeneratedFeesAsSubscriptions($this->inscription, $frais);
        $this->assertSame(42000.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame(42000.0, (float) $this->souscription->fresh()->chargedAmount());
    }

    public function test_creer_souscription_lmd_nutilise_pas_un_bareme_bts_de_meme_filiere(): void
    {
        $classe = $this->inscription->classe;
        // Un tarif BTS et un tarif LMD peuvent cohabiter sur la même
        // filière historique : seule la clé parcours+niveau LMD fait foi.
        ESBTPFraisConfiguration::create([
            'frais_category_id' => $this->categorie->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => null,
            'amount' => 88000,
            'amount_affecte' => 88000,
            'amount_reaffecte' => 88000,
            'amount_non_affecte' => 88000,
            'payment_deadline_days' => 30,
            'is_active' => true,
            'created_by' => $this->agent->id,
        ]);
        $configuration = $this->baremeReel('LMD', 39000, 39000);
        $service = app(\App\Services\ESBTPInscriptionService::class);
        $frais = $service->generateFeesForInscription($this->inscription);
        $this->assertTrue($configuration->is(
            app(ApplicableFraisResolver::class)->resolveMandatoryFeesForInscription($this->inscription)->first()['configuration']
        ));
        $this->assertSame(39000.0, (float) collect($frais)->firstWhere('category_id', $this->categorie->id)['amount']);

        $service->saveGeneratedFeesAsSubscriptions($this->inscription, $frais);
        $this->assertSame(39000.0, (float) $this->souscription->fresh()->amount);
        $this->assertSame(39000.0, (float) $this->souscription->fresh()->chargedAmount());
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

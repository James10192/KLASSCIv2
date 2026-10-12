<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\AvoirService;
use App\Domain\Comptabilite\Paiements\Actions\ValiderPaiement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReliquatCaisseAjaxTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPReliquatDetail $reliquat;
    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['admin.access', 'module.caisse.access', 'paiements.create', 'paiements.create.non_cash', 'paiements.create.mobile_money',
            'finances.etudiants.voir', 'inscriptions.view', 'identity.enrollment_officer', 'reinscriptions.reliquats.report'] as $droit) {
            Permission::findOrCreate($droit, 'web');
        }

        $annee1 = ESBTPAnneeUniversitaire::factory()->create(['name' => '2024-2025']);
        $annee2 = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026']);
        $ancien = ESBTPInscription::factory()->create(['annee_universitaire_id' => $annee1->id]);
        $this->inscription = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $annee2->id,
            'etudiant_id' => $ancien->etudiant_id,
        ]);
        $cat = ESBTPFraisCategory::factory()->create();
        $sub = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $ancien->id, 'frais_category_id' => $cat->id, 'amount' => 100000,
        ]);
        $this->reliquat = ESBTPReliquatDetail::create([
            'inscription_source_id' => $ancien->id,
            'inscription_destination_id' => $this->inscription->id,
            'frais_subscription_id' => $sub->id,
            'montant_attendu' => 100000, 'montant_paye' => 0,
            'montant_reliquat' => 100000, 'montant_regle' => 0,
            'statut' => 'actif', 'date_creation' => now(), 'created_by' => User::factory()->create()->id,
        ]);
    }

    private function caissier(array $perms = ['paiements.create']): User
    {
        $u = User::factory()->create();
        $u->givePermissionTo(array_values(array_unique(array_merge(['admin.access', 'module.caisse.access'], $perms))));
        return $u;
    }

    private function versement(float $montant, string $mode = 'virement', ?string $key = null): array
    {
        return [
            'reliquat_id' => $this->reliquat->id,
            'montant' => $montant,
            'mode_paiement' => $mode,
            'request_key' => $key ?? '4f999ee3-e1c8-4c88-82fe-88b1f8eb0001',
        ];
    }

    public function test_a_la_caisse_create_les_reliquats_utilisent_un_dialogue_distinct_des_frais_courants(): void
    {
        $this->actingAs($this->caissier())->get(route('esbtp.paiements.create', [
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
        ]))->assertOk()
            ->assertSee('Encaisser un paiement')
            ->assertSee('Régler un arriéré')
            ->assertSee('name="mode_paiement"', false)
            ->assertSee('id="payment-form"', false);
    }

    public function test_la_fiche_ne_propose_pas_un_frais_deja_transfere_en_reliquat(): void
    {
        $source = $this->reliquat->inscriptionSource;
        $this->actingAs($this->caissier(['paiements.create', 'finances.etudiants.voir', 'inscriptions.view']));

        $this->getJson(route('esbtp.inscriptions.frais-restants', $source->id))
            ->assertOk()
            ->assertJsonPath('categories', []);

        $categorieId = $this->reliquat->fraisSubscription->frais_category_id;
        $this->getJson(route('esbtp.inscriptions.frais.montant-restant', [$source->id, $categorieId]))
            ->assertOk()
            ->assertJsonPath('montant_restant', 0);
    }

    public function test_un_ancien_workflow_d_inscription_ne_peut_pas_etre_reactive_par_un_paiement(): void
    {
        $source = $this->reliquat->inscriptionSource;
        $source->update(['status' => 'terminee']);
        $this->actingAs($this->caissier());
        $avant = ESBTPPaiement::count();

        $this->postJson(route('esbtp.inscriptions.valider-avec-paiement', $source->id), [
            'fee_category_id' => $this->reliquat->fraisSubscription->frais_category_id,
            'montant' => 10000,
            'mode_paiement' => 'virement',
            'date_paiement' => now()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('inscription_id');

        $this->assertSame($avant, ESBTPPaiement::count());
        $this->assertSame('terminee', $source->fresh()->status);
    }

    public function test_get_affiche_le_disponible_et_les_paiements_reserves_sans_le_solder(): void
    {
        $this->actingAs($this->caissier());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(25000))
            ->assertCreated()->assertJsonPath('success', true);
        $this->getJson(route('esbtp.paiements.reliquats.etat', $this->inscription->id))
            ->assertOk()
            ->assertJsonPath('reliquats.0.attente', 25000)
            ->assertJsonPath('reliquats.0.disponible', 75000)
            ->assertJsonPath('reliquats.0.origine', '2024-2025')
            ->assertJsonPath('soldes.arrieres', 100000);
        $p = ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)->firstOrFail();
        $this->assertSame('en_attente', $p->status);
        $this->assertSame('reliquat', $p->type_paiement);
        $this->assertSame($this->inscription->id, (int) $p->inscription_id);
        $this->assertSame(0.0, (float) $this->reliquat->fresh()->montant_regle);
    }

    public function test_deux_requetes_identiques_ne_produisent_pas_deux_paiements(): void
    {
        $this->actingAs($this->caissier());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(40000))->assertCreated();
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(40000))->assertCreated();
        $this->assertSame(1, ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)->count());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(70000, 'virement', '9acc674a-6e57-4b3b-8c8e-b80000009999'))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
    }

    public function test_une_cle_rejouee_avec_un_montant_different_est_refusee(): void
    {
        $this->actingAs($this->caissier());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(10000))->assertCreated();
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(11000))
            ->assertUnprocessable()->assertJsonValidationErrors('request_key');
        $this->assertSame(1, ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)->count());
    }

    public function test_permission_report_seul_ne_permet_pas_d_encaisser(): void
    {
        $this->actingAs($this->caissier(['reinscriptions.reliquats.report']));
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(2000))->assertForbidden();
        $this->getJson(route('esbtp.paiements.reliquats.etat', $this->inscription->id))->assertForbidden();
        $this->assertSame(0, ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)->count());
    }

    public function test_habilitation_mobile_rejette_les_especes_et_autorise_wave(): void
    {
        $this->actingAs($this->caissier(['paiements.create.mobile_money']));
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(10000, 'especes'))->assertForbidden();
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(10000, 'wave'))->assertCreated();
    }

    public function test_la_fiche_inscription_utilise_un_seul_dialogue_ajax_pour_toutes_les_actions_reliquat(): void
    {
        $agent = $this->caissier(['paiements.create', 'inscriptions.view', 'finances.etudiants.voir', 'identity.enrollment_officer']);
        $this->actingAs($agent)->get(route('esbtp.inscriptions.show', $this->inscription))
            ->assertOk()
            ->assertSee('reliquat-caisse-ouvrir', false)
            ->assertSee('Régler un arriéré')
            ->assertDontSee('id="reliquatPaymentForm"', false)
            ->assertDontSee('prepareReliquatPaymentModal', false);
    }

    public function test_deux_caissiers_ne_peuvent_pas_reserver_plus_que_le_solde_cumule(): void
    {
        $premier = $this->caissier();
        $second = $this->caissier();
        $this->actingAs($premier)->postJson(route('esbtp.reliquats.pay'),
            $this->versement(65000, 'virement', 'e3a70bf6-b184-4076-ae74-78f017be4837'))->assertCreated();
        $this->actingAs($second)->postJson(route('esbtp.reliquats.pay'),
            $this->versement(45000, 'virement', '9e43db6e-64e4-4503-9b33-7294394d79fd'))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->actingAs($second)->postJson(route('esbtp.reliquats.pay'),
            $this->versement(35000, 'virement', '9e43db6e-64e4-4503-9b33-7294394d79fc'))->assertCreated();
        $this->assertSame(100000.0, (float) ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)
            ->where('status', 'en_attente')->sum('montant'));
        $this->assertSame(0.0, (float) $this->reliquat->fresh()->montant_regle);
    }

    public function test_validation_et_avoir_actualisent_le_solde_origine_sans_toucher_aux_frais_de_l_annee(): void
    {
        $agent = $this->caissier();
        $this->actingAs($agent);
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(40000))->assertCreated();
        $paiement = ESBTPPaiement::where('reliquat_detail_id', $this->reliquat->id)->firstOrFail();
        $avant = app(\App\Services\Finances\SituationFinanciereEtudiant::class)
            ->pour($this->inscription->etudiant_id, $this->inscription->id);
        app(ValiderPaiement::class)->execute($paiement, $agent);
        $apres = app(\App\Services\Finances\SituationFinanciereEtudiant::class)
            ->pour($this->inscription->etudiant_id, $this->inscription->id);
        $this->assertSame($avant['courant'], $apres['courant']);
        $this->assertSame($avant['arrieres'] - 40000, $apres['arrieres']);
        $this->assertSame(40000.0, (float) $this->reliquat->fresh()->montant_regle);
        app(AvoirService::class)->issue($paiement->fresh(), 15000, AvoirService::KIND_CREDIT,
            'Annulation partielle du règlement ancien', $agent->id);
        $this->assertSame(25000.0, (float) $this->reliquat->fresh()->montant_regle);
        $this->assertSame($avant['courant'], app(\App\Services\Finances\SituationFinanciereEtudiant::class)
            ->pour($this->inscription->etudiant_id, $this->inscription->id)['courant']);
    }

    public function test_dette_deja_reglee_n_est_pas_encaissee_nouveau(): void
    {
        $this->reliquat->update(['montant_regle' => 100000, 'statut' => 'totalement_regle']);
        $this->actingAs($this->caissier());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(1000))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
    }
}

<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
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
        foreach (['admin.access', 'module.caisse.access', 'paiements.create', 'paiements.create.non_cash', 'paiements.create.mobile_money',
            'finances.etudiants.voir', 'reinscriptions.reliquats.report'] as $droit) {
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

    public function test_dette_deja_reglee_n_est_pas_encaissee_nouveau(): void
    {
        $this->reliquat->update(['montant_regle' => 100000, 'statut' => 'totalement_regle']);
        $this->actingAs($this->caissier());
        $this->postJson(route('esbtp.reliquats.pay'), $this->versement(1000))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
    }
}

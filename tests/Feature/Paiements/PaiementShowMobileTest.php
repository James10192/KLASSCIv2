<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Fiche d'un paiement en mobile : le recu m-* est rendu a cote du DOM de
 * bureau, et la correction du mode de reglement repond en JSON quand la
 * feuille mobile la demande — sans casser le formulaire de bureau.
 */
class PaiementShowMobileTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $scolarite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        // Toutes les permissions : le profil mobile se deduit de
        // module.caisse.access, donc « caissier ».
        Gate::before(fn () => true);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::create([
            'name' => 'Scolarité mobile',
            'code' => 'SCOL_MOB_'.uniqid(),
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 100000,
            'payment_deadline_days' => 30,
        ]);
    }

    private function paiement(string $status = 'validé', string $mode = 'Espèces'): ESBTPPaiement
    {
        return ESBTPPaiement::create([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => 50000,
            'mode_paiement' => $mode,
            'date_paiement' => now()->toDateString(),
            'status' => $status,
            'nature' => 'encaissement',
            'created_by' => $this->user->id,
            'numero_recu' => 'REC-MOB-'.uniqid(),
        ]);
    }

    public function test_la_fiche_rend_le_recu_mobile_a_cote_du_dom_de_bureau(): void
    {
        $paiement = $this->paiement();

        $reponse = $this->get(route('esbtp.paiements.show', $paiement->id));

        $reponse->assertOk()
            ->assertSee('m-recu', false)
            ->assertSee('m-only-mobile', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('data-m-sheet="psm-actions"', false)
            ->assertSee('data-m-sheet="psm-mode"', false)
            ->assertSee($paiement->numero_recu)
            ->assertSee('Nouvel encaissement');
    }

    public function test_un_paiement_en_attente_propose_valider_mais_pas_corriger_le_mode(): void
    {
        $paiement = $this->paiement('en_attente');

        $reponse = $this->get(route('esbtp.paiements.show', $paiement->id));

        $reponse->assertOk()
            ->assertSee('Valider le paiement')
            ->assertSee('data-m-sheet="psm-rejeter"', false)
            ->assertDontSee('data-m-sheet="psm-mode"', false);
    }

    public function test_la_correction_du_mode_repond_en_json_a_la_feuille_mobile(): void
    {
        $paiement = $this->paiement('validé', 'Espèces');

        $reponse = $this->patchJson(route('esbtp.paiements.mode-reglement.update', $paiement->id), [
            'mode_paiement' => 'Wave',
            'motif' => 'Cheque coche par erreur au guichet',
        ]);

        $reponse->assertOk()
            ->assertJson(['success' => true, 'mode_paiement' => 'Wave']);

        $this->assertSame('Wave', $paiement->fresh()->mode_paiement);
    }

    public function test_un_refus_metier_vaut_422_en_json_et_ne_modifie_rien(): void
    {
        $paiement = $this->paiement('en_attente', 'Espèces');

        $reponse = $this->patchJson(route('esbtp.paiements.mode-reglement.update', $paiement->id), [
            'mode_paiement' => 'Wave',
            'motif' => 'Cheque coche par erreur au guichet',
        ]);

        $reponse->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('Espèces', $paiement->fresh()->mode_paiement);
    }

    public function test_le_formulaire_de_bureau_garde_sa_redirection(): void
    {
        $paiement = $this->paiement('validé', 'Espèces');

        $reponse = $this->from(route('esbtp.paiements.show', $paiement->id))
            ->patch(route('esbtp.paiements.mode-reglement.update', $paiement->id), [
                'mode_paiement' => 'Wave',
                'motif' => 'Cheque coche par erreur au guichet',
            ]);

        $reponse->assertRedirect(route('esbtp.paiements.show', $paiement->id))
            ->assertSessionHas('success');
    }
}

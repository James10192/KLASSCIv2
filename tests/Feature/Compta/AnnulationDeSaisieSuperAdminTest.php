<?php

namespace Tests\Feature\Compta;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * « Annuler ma saisie » décrit un état, pas un droit : c'est moi qui l'ai
 * saisi, il est encore en attente, il y a moins de N minutes.
 *
 * Le passe-droit du superAdmin (Gate::before) rendait ces trois faits vrais
 * pour tout paiement : le bouton s'affichait sur un reçu validé saisi par un
 * autre, et la suppression partait sans motif.
 */
class AnnulationDeSaisieSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $caissier;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('paiements.cancel_own', 'web');
        Role::findOrCreate('superAdmin', 'web');
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('superAdmin');
        $this->caissier = User::factory()->create();
        InstallationHelper::flushCachedStatus();

        Carbon::setTestNow(Carbon::parse('2026-09-04 10:45:00'));

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $this->inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_le_superadmin_ne_peut_pas_annuler_la_saisie_validee_d_un_autre(): void
    {
        $paiement = $this->versement($this->caissier, ['status' => 'validé', 'created_at' => '2026-09-01 09:00:00']);

        $this->assertFalse($this->superAdmin->can('cancelOwnRecent', $paiement));

        $this->actingAs($this->superAdmin)
            ->postJson(route('esbtp.paiements.cancel-own', $paiement->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted($paiement);
    }

    public function test_le_superadmin_ne_peut_pas_annuler_la_saisie_recente_d_un_autre(): void
    {
        $paiement = $this->versement($this->caissier, ['status' => 'en_attente', 'created_at' => '2026-09-04 10:44:00']);

        $this->assertFalse($this->superAdmin->can('cancelOwnRecent', $paiement));
    }

    public function test_le_superadmin_annule_encore_sa_propre_saisie_dans_le_delai(): void
    {
        $recent = $this->versement($this->superAdmin, ['status' => 'en_attente', 'created_at' => '2026-09-04 10:44:00']);
        $ancien = $this->versement($this->superAdmin, ['status' => 'en_attente', 'created_at' => '2026-09-04 10:00:00']);

        $this->assertTrue($this->superAdmin->can('cancelOwnRecent', $recent));
        $this->assertFalse($this->superAdmin->can('cancelOwnRecent', $ancien));

        $this->actingAs($this->superAdmin)
            ->postJson(route('esbtp.paiements.cancel-own', $recent->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted($recent);
    }

    public function test_la_fiche_mobile_ne_propose_l_annulation_que_sur_sa_propre_saisie(): void
    {
        $mien = $this->versement($this->superAdmin, ['status' => 'en_attente', 'created_at' => '2026-09-04 10:44:00']);
        $autre = $this->versement($this->caissier, ['status' => 'validé', 'created_at' => '2026-09-01 09:00:00']);

        $this->actingAs($this->superAdmin)
            ->get(route('esbtp.paiements.show', $mien->id))
            ->assertOk()
            ->assertSee('data-m-sheet="psm-annuler"', false)
            ->assertSee('Oui, annuler ma saisie');

        $this->actingAs($this->superAdmin)
            ->get(route('esbtp.paiements.show', $autre->id))
            ->assertOk()
            ->assertDontSee('data-m-sheet="psm-annuler"', false)
            ->assertDontSee('Oui, annuler ma saisie');
    }

    private function versement(User $auteur, array $attributs): ESBTPPaiement
    {
        return ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->create(array_merge([
                'created_by' => $auteur->id,
                'date_paiement' => '2026-09-04',
                'montant' => 50000,
            ], $attributs));
    }
}

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
 * saisi, il n'est ni rejeté ni rapproché, il y a moins de N minutes.
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

    /**
     * La ou la caisse valide a l'encaissement, un versement n'est jamais en
     * attente : exiger ce statut rendait le geste introuvable pour l'agent qui
     * venait de se tromper. Un versement valide recent de son auteur s'annule
     * donc aussi, par la suppression officielle, avec un motif pose d'office.
     */
    public function test_l_auteur_annule_sa_saisie_validee_recente_avec_un_motif_trace(): void
    {
        $caissier = User::factory()->create();
        // `admin.access` : la garde commune de l'espace /esbtp, que tout
        // personnel d'encaissement porte.
        Permission::findOrCreate('admin.access', 'web');
        $caissier->givePermissionTo(['paiements.cancel_own', 'admin.access']);
        $recent = $this->versement($caissier, ['status' => 'validé', 'created_at' => '2026-09-04 10:44:00']);
        $ancien = $this->versement($caissier, ['status' => 'validé', 'created_at' => '2026-09-04 10:00:00']);
        $rejete = $this->versement($caissier, ['status' => 'rejeté', 'created_at' => '2026-09-04 10:44:00']);

        $this->assertTrue($caissier->can('cancelOwnRecent', $recent));
        $this->assertFalse($caissier->can('cancelOwnRecent', $ancien));
        $this->assertFalse($caissier->can('cancelOwnRecent', $rejete));

        $this->actingAs($caissier)
            ->postJson(route('esbtp.paiements.cancel-own', $recent->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted($recent);
        $supprime = ESBTPPaiement::withTrashed()->find($recent->id);
        $this->assertSame($caissier->id, (int) $supprime->deleted_by);
        $this->assertStringContainsString('annulée par son auteur', (string) $supprime->motif_suppression);
    }

    /**
     * Quand « Annuler ma saisie » n'est plus ouvert, reste la suppression avec
     * motif : elle doit se trouver la ou l'on voit le versement — la liste des
     * paiements et la fiche d'inscription, pas seulement la fiche du paiement.
     */
    public function test_la_suppression_avec_motif_est_proposee_sur_la_liste_et_la_fiche_inscription(): void
    {
        $paiement = $this->versement($this->caissier, ['status' => 'validé', 'created_at' => '2026-09-01 09:00:00']);

        $this->actingAs($this->superAdmin)
            ->get(route('esbtp.paiements.index'))
            ->assertOk()
            ->assertSee('supprimerPaiementModal'.$paiement->id, false);

        $this->actingAs($this->superAdmin)
            ->get(route('esbtp.inscriptions.show', $this->inscription->id))
            ->assertOk()
            ->assertSee('supprimerVersement'.$paiement->id, false)
            ->assertSee(route('esbtp.paiements.show', $paiement->id), false);
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

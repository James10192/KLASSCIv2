<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Paie des enseignants en mobile (issue #963, lot 2a, maquette
 * S['comptable:paie']) : l'état de paie du mois et la fiche d'un bulletin sont
 * rendus en m-* à côté du DOM de bureau pour une personne profilée
 * « comptable » ; les feuilles n'existent que derrière leur permission ;
 * « Valider » et « Marquer payé » répondent en JSON sans rechargement.
 */
class PaieEnseignantsMobileTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'comptabilite.access',
        'comptabilite.salaires.view',
        'comptabilite.salaires.create',
        'comptabilite.salaires.validate',
        'comptabilite.salaires.pay',
        'comptabilite.salaires.export',
    ];

    private User $comptable;

    protected function setUp(): void
    {
        parent::setUp();

        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe :
        // sans lui, toutes les pages repondent 302 vers /install.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        foreach (array_merge(self::PERMISSIONS, ['comptabilite.salaires.validate_own']) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // comptabilite.access sans module.caisse.access : profil « comptable ».
        $this->comptable = User::factory()->create([
            'name' => 'Koné Ibrahim',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(self::PERMISSIONS);

        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '1');
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    /** Un bulletin en brouillon préparé par une autre personne que le comptable connecté. */
    private function bulletinBrouillon(string $statut = ESBTPSalaire::ST_BROUILLON): ESBTPSalaire
    {
        $preparateur = User::factory()->create();
        $enseignantUser = User::factory()->create(['name' => 'Dr Kouamé Yves']);
        $teacher = ESBTPTeacher::create([
            'user_id' => $enseignantUser->id,
            'matricule' => 'ENS-PAIE-' . uniqid(),
            'status' => 'active',
        ]);
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        return ESBTPSalaire::create([
            'user_id' => $enseignantUser->id,
            'teacher_id' => $teacher->id,
            'annee_universitaire_id' => $annee->id,
            'mois' => 8,
            'annee' => 2026,
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
            'salaire_base' => 486000,
            'heures_total' => 36,
            'primes' => 0,
            'retenues' => 48600,
            'impot_its' => 30000,
            'cnps' => 18600,
            'net_a_payer' => 437400,
            'statut' => 'en attente',
            'workflow_status' => $statut,
            'createur_id' => $preparateur->id,
            'prepared_by' => $preparateur->id,
            'prepared_at' => now(),
        ]);
    }

    public function test_l_etat_de_paie_mobile_est_rendu_a_cote_du_dom_de_bureau(): void
    {
        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.salaires.index'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('m-only-mobile m-screen pym-screen', false)
            ->assertSee('m-only-desktop', false)
            // App bar : titre de la maquette, changement de mois.
            ->assertSee('Paie enseignants')
            ->assertSee('aria-label="Changer de mois"', false)
            // Héro : l'état de paie du mois, jamais un montant écrit en dur.
            ->assertSee('État de paie ·')
            // Segments de statut.
            ->assertSee('À préparer')
            ->assertSee('À valider')
            // Feuilles : mois toujours ; export et préparation derrière leur permission.
            ->assertSee('data-m-sheet="pym-mois"', false)
            ->assertSee('data-m-sheet="pym-export"', false)
            ->assertSee('data-m-sheet="pym-preparer"', false)
            ->assertSee(route('esbtp.comptabilite.salaires.export.excel'), false)
            // Fabrique Alpine sous garde ; le DOM de bureau reste là.
            ->assertSee("if (typeof window.pymPaie !== 'function')", false)
            ->assertSee('pay-hero', false);
    }

    public function test_sans_permission_export_ni_creation_les_feuilles_correspondantes_n_existent_pas(): void
    {
        $this->comptable->revokePermissionTo(['comptabilite.salaires.export', 'comptabilite.salaires.create']);

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.salaires.index'));

        $reponse->assertOk()
            ->assertSee('pym-screen', false)
            ->assertDontSee('data-m-sheet="pym-export"', false)
            ->assertDontSee('data-m-sheet="pym-preparer"', false)
            ->assertDontSee('Préparer un bulletin');
    }

    public function test_reglage_coupe_seul_le_dom_de_bureau_est_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.salaires.index'));

        $reponse->assertOk()
            ->assertDontSee('has-m-shell', false)
            ->assertDontSee('pym-screen', false)
            ->assertDontSee('data-m-sheet="pym-mois"', false)
            ->assertSee('pay-hero', false);
    }

    public function test_les_donnees_en_mode_mobile_renvoient_les_cartes_lignes_et_les_kpis(): void
    {
        $bulletin = $this->bulletinBrouillon();

        $reponse = $this->actingAs($this->comptable)->getJson(
            route('esbtp.comptabilite.salaires.data', ['preset' => 'month', 'mois' => 8, 'annee' => 2026, 'mode' => 'mobile'])
        );

        $reponse->assertOk()
            ->assertJsonStructure(['list_html', 'kpis_html', 'period_label', 'mobile_html', 'mobile_count', 'kpis' => ['total_net', 'heures_total', 'nb_total', 'nb_brouillon']])
            ->assertJsonPath('mobile_count', 1)
            ->assertJsonPath('kpis.nb_brouillon', 1);

        // La ligne mène au bulletin existant et porte la puce « À valider ».
        $this->assertStringContainsString(route('esbtp.comptabilite.salaires.show', $bulletin->id), $reponse->json('mobile_html'));
        $this->assertStringContainsString('À valider', $reponse->json('mobile_html'));
        $this->assertStringContainsString('437 400 FCFA', $reponse->json('mobile_html'));
    }

    public function test_sans_mode_mobile_la_reponse_ne_porte_pas_les_cles_mobiles(): void
    {
        $reponse = $this->actingAs($this->comptable)->getJson(
            route('esbtp.comptabilite.salaires.data', ['preset' => 'month', 'mois' => 8, 'annee' => 2026])
        );

        $reponse->assertOk()->assertJsonMissing(['mobile_count']);
        $this->assertArrayNotHasKey('mobile_html', $reponse->json());
    }

    public function test_la_fiche_mobile_du_bulletin_expose_valider_et_payer_derriere_leurs_permissions(): void
    {
        $bulletin = $this->bulletinBrouillon();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.salaires.show', $bulletin->id));

        $reponse->assertOk()
            ->assertSee('m-only-mobile m-screen pysm-screen', false)
            ->assertSee('Bulletin de paie')
            ->assertSee('Dr Kouamé Yves')
            // Bulletin compact : brut, retenues, net — les montants enregistrés, pas recalculés.
            ->assertSee('486 000 FCFA')
            ->assertSee('437 400 FCFA')
            ->assertSee('Net à payer')
            // Le comptable n'a pas préparé ce bulletin : il peut le valider ; il peut aussi payer.
            ->assertSee('data-m-sheet="pysm-valider"', false)
            ->assertSee('data-m-sheet="pysm-payer"', false)
            ->assertSee(route('esbtp.comptabilite.salaires.payslip', $bulletin->id), false)
            ->assertSee("if (typeof window.pysmBulletin !== 'function')", false)
            ->assertSee('pys-hero', false);
    }

    public function test_le_preparateur_sans_validate_own_ne_voit_pas_la_feuille_valider(): void
    {
        $bulletin = $this->bulletinBrouillon();
        $bulletin->update(['prepared_by' => $this->comptable->id, 'createur_id' => $this->comptable->id]);

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.salaires.show', $bulletin->id));

        $reponse->assertOk()
            ->assertSee('pysm-screen', false)
            ->assertDontSee('data-m-sheet="pysm-valider"', false)
            ->assertSee('attend sa validation par une autre personne');
    }

    public function test_valider_en_json_repond_sans_rechargement(): void
    {
        $bulletin = $this->bulletinBrouillon();

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.salaires.validate', $bulletin->id));

        $reponse->assertOk()
            ->assertJson(['success' => true, 'statut' => ESBTPSalaire::ST_VALIDE, 'valide_par' => 'Koné Ibrahim'])
            ->assertJsonStructure(['message', 'statut_label', 'date_validation']);

        $bulletin->refresh();
        $this->assertSame(ESBTPSalaire::ST_VALIDE, $bulletin->workflow_status);
        $this->assertSame($this->comptable->id, (int) $bulletin->validateur_id);
    }

    public function test_valider_un_bulletin_deja_valide_est_refuse_en_422_json(): void
    {
        $bulletin = $this->bulletinBrouillon(ESBTPSalaire::ST_VALIDE);

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.salaires.validate', $bulletin->id));

        $reponse->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(ESBTPSalaire::ST_VALIDE, $bulletin->fresh()->workflow_status);
    }

    public function test_marquer_paye_en_json_enregistre_le_reglement(): void
    {
        $bulletin = $this->bulletinBrouillon(ESBTPSalaire::ST_VALIDE);
        $mode = array_key_first(config('payment_modes.labels'));

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.salaires.pay', $bulletin->id), [
                'mode_paiement' => $mode,
                'reference_paiement' => 'TX-2026-0042',
                'date_paiement' => '2026-09-05',
            ]);

        $reponse->assertOk()
            ->assertJson(['success' => true, 'statut' => ESBTPSalaire::ST_PAYE, 'reference' => 'TX-2026-0042', 'date_paiement' => '05/09/2026'])
            ->assertJsonStructure(['message', 'paye_par', 'mode_label']);

        $bulletin->refresh();
        $this->assertSame(ESBTPSalaire::ST_PAYE, $bulletin->workflow_status);
        $this->assertSame($mode, $bulletin->mode_paiement);
        $this->assertSame($this->comptable->id, (int) $bulletin->paid_by);
    }

    public function test_marquer_paye_un_brouillon_est_refuse_en_422_json(): void
    {
        $bulletin = $this->bulletinBrouillon();

        $reponse = $this->actingAs($this->comptable)
            ->postJson(route('esbtp.comptabilite.salaires.pay', $bulletin->id), [
                'mode_paiement' => array_key_first(config('payment_modes.labels')),
            ]);

        $reponse->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(ESBTPSalaire::ST_BROUILLON, $bulletin->fresh()->workflow_status);
    }

    public function test_le_formulaire_de_bureau_garde_sa_redirection(): void
    {
        $bulletin = $this->bulletinBrouillon();

        $reponse = $this->actingAs($this->comptable)
            ->from(route('esbtp.comptabilite.salaires.show', $bulletin->id))
            ->post(route('esbtp.comptabilite.salaires.validate', $bulletin->id));

        $reponse->assertRedirect(route('esbtp.comptabilite.salaires.show', $bulletin->id))
            ->assertSessionHas('success');
    }
}

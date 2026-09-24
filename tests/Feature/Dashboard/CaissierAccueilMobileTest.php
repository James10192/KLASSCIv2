<?php

namespace Tests\Feature\Dashboard;

use App\Enums\CashSessionStatus;
use App\Helpers\InstallationHelper;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCashSession;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Accueil mobile du caissier (shell mobile, profil « caissier », maquette
 * S['caissier:accueil']).
 *
 * Le tableau de bord expose `caisseMobile` : session de caisse du jour, encaisse
 * par famille de mode (espèces / mobile / autres), versements à valider et
 * versements encore annulables par le guichet. Le DOM mobile n'affiche un lien
 * que sous la garde réelle de sa route ; le DOM de bureau reste rendu à côté.
 */
class CaissierAccueilMobileTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'admin.access', 'dashboard.view', 'module.caisse.access', 'cash_session.manage',
            'paiements.view_own', 'paiements.view', 'paiements.create', 'paiements.cancel_own',
            'comptabilite.access', 'comptabilite.journal.view', 'comptabilite.reconciliation.open',
            'inscriptions.create',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        MobileProfileResolver::oublier();

        Carbon::setTestNow(Carbon::parse('2026-09-04 10:45:00'));

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 2, 'type' => 'Licence']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $classe = ESBTPClasse::factory()->create([
            'name' => 'L2 Droit privé A',
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => 'LMD',
        ]);
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => 'Kouassi', 'prenoms' => 'Aya']);
        $this->inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_l_accueil_mobile_expose_la_session_l_encaisse_par_mode_et_les_annulables(): void
    {
        $caissier = $this->caissier(['paiements.view_own', 'paiements.create', 'paiements.cancel_own']);
        $autre = $this->caissier([]);

        ESBTPCashSession::create([
            'cashier_user_id' => $caissier->id,
            'business_date' => '2026-09-04',
            'status' => CashSessionStatus::OPEN,
            'opened_at' => Carbon::parse('2026-09-04 07:58:00'),
        ]);

        $scolarite = ESBTPFraisCategory::factory()->create(['name' => 'Scolarité T2']);

        // Espèces validé, Wave validé : deux familles de mode.
        $especes = $this->versement($caissier, ['montant' => 150000, 'mode_paiement' => 'espèces', 'frais_category_id' => $scolarite->id, 'created_at' => '2026-09-04 10:42:00']);
        $this->versement($caissier, ['montant' => 25000, 'mode_paiement' => 'wave', 'created_at' => '2026-09-04 10:12:00']);
        // Un virement : ni tiroir, ni mobile money.
        $this->versement($caissier, ['montant' => 300000, 'mode_paiement' => 'virement', 'created_at' => '2026-09-04 09:00:00']);
        // Deux en attente : l'un saisi à l'instant (annulable), l'autre hors fenêtre.
        $this->versement($caissier, ['montant' => 75000, 'mode_paiement' => 'espèces', 'status' => 'en_attente', 'created_at' => '2026-09-04 10:44:00']);
        $this->versement($caissier, ['montant' => 50000, 'mode_paiement' => 'espèces', 'status' => 'en_attente', 'created_at' => '2026-09-04 10:00:00']);
        // Un rejeté : ne compte nulle part.
        $this->versement($caissier, ['montant' => 10000, 'mode_paiement' => 'espèces', 'status' => 'rejeté', 'created_at' => '2026-09-04 09:30:00']);
        // Hier : hors du jour.
        $this->versement($caissier, ['montant' => 999000, 'mode_paiement' => 'espèces', 'created_at' => '2026-09-03 15:00:00']);
        // Un autre guichet : pas le mien.
        $this->versement($autre, ['montant' => 888000, 'mode_paiement' => 'espèces', 'created_at' => '2026-09-04 10:30:00']);

        $response = $this->actingAs($caissier)->get(route('dashboard'));

        $response->assertOk();
        $caisse = $response->viewData('caisseMobile');

        $this->assertSame('open', $caisse['session']['statut']);
        $this->assertSame('07:58', $caisse['session']['ouverte_a']);
        $this->assertSame(['count' => 1, 'total' => 150000.0], $caisse['especes']);
        $this->assertSame(['count' => 1, 'total' => 25000.0], $caisse['mobile']);
        $this->assertSame(['count' => 1, 'total' => 300000.0], $caisse['autres']);
        $this->assertSame(2, $caisse['a_valider']);
        // L'espèces validé de 10h42 et l'attente de 10h44 : un versement
        // validé récent de son auteur s'annule aussi (la caisse valide à
        // l'encaissement dans plusieurs écoles).
        $this->assertSame(2, $caisse['annulables']);
        $this->assertTrue($caisse['peut_annuler']);
        $this->assertSame(5, $caisse['fenetre_annulation_minutes']);

        // Le DOM mobile est rendu, à côté du bureau.
        $response->assertSee('has-m-shell m-profile-caissier', false);
        $response->assertSee('m-only-mobile m-screen cxm-screen', false);
        $response->assertSee('dashboard-acasi m-only-desktop', false);
        $response->assertSee(e(SettingsHelper::getSchoolInfo()['name'] . ' · Caisse'), false);
        $response->assertSee('session ouverte 07:58', false);
        $response->assertSee('Annulables · 5 min', false);
        $response->assertSee('Autres modes 1', false);
        // Les dernières opérations mènent au reçu (view_own) et portent le mode non-espèces en puce.
        $response->assertSee(route('esbtp.paiements.show', $especes->id), false);
        $response->assertSee('Scolarité T2', false);
        $response->assertSee('>Wave<', false);
        $response->assertSee('Annulable 5 min', false);
        // Bouton flottant « + » : paiements.create.
        $response->assertSee('class="m-fab"', false);
        // Aucun lien vers le journal ni la réconciliation sans leurs permissions.
        $response->assertDontSee(route('esbtp.comptabilite.journal-caisse.index'), false);
        $response->assertDontSee(route('esbtp.comptabilite.reconciliation.create'), false);
    }

    public function test_sans_droit_d_annulation_la_carte_annulables_laisse_place_aux_versements_du_jour(): void
    {
        $caissier = $this->caissier(['paiements.view_own']);
        $this->versement($caissier, ['montant' => 75000, 'mode_paiement' => 'espèces', 'status' => 'en_attente', 'created_at' => '2026-09-04 10:44:00']);

        $response = $this->actingAs($caissier)->get(route('dashboard'));

        $response->assertOk();
        $caisse = $response->viewData('caisseMobile');
        $this->assertFalse($caisse['peut_annuler']);
        $this->assertSame(0, $caisse['annulables']);
        $this->assertSame(1, $caisse['a_valider']);

        $response->assertDontSee('Annulables ·', false);
        $response->assertSee('Versements du jour', false);
        // Pas de fenêtre d'annulation : la ligne dit « À valider ».
        $response->assertSee('À valider', false);
        $response->assertDontSee('Annulable 5 min', false);
        // Sans paiements.create : ni bouton flottant, ni « Encaisser ».
        $response->assertDontSee('class="m-fab"', false);
        $response->assertDontSee(route('esbtp.paiements.create'), false);
    }

    public function test_le_journal_n_apparait_qu_avec_les_deux_permissions_exigees_par_sa_route(): void
    {
        // journal.view seul : le contrôleur exige aussi comptabilite.access → pas de lien.
        $sansAcces = $this->caissier(['comptabilite.journal.view']);
        $response = $this->actingAs($sansAcces)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee(route('esbtp.comptabilite.journal-caisse.index'), false);

        // Les deux : le lien apparaît (bureau et mobile).
        $avecAcces = $this->caissier(['comptabilite.journal.view', 'comptabilite.access', 'comptabilite.reconciliation.open']);
        $response = $this->actingAs($avecAcces)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee(route('esbtp.comptabilite.journal-caisse.index'), false);
        $response->assertSee(route('esbtp.comptabilite.reconciliation.create'), false);
        // « Ma caisse » suit la garde de sa route (module.caisse.access suffit).
        $response->assertSee(route('esbtp.caisse.ma-caisse'), false);
    }

    public function test_sans_session_ni_versement_l_accueil_reste_lisible(): void
    {
        $caissier = $this->caissier(['paiements.create']);

        $response = $this->actingAs($caissier)->get(route('dashboard'));

        $response->assertOk();
        $caisse = $response->viewData('caisseMobile');
        $this->assertNull($caisse['session']['statut']);
        $this->assertSame(['count' => 0, 'total' => 0.0], $caisse['especes']);

        $response->assertSee('caisse non ouverte', false);
        // Un zéro est une valeur : il s'affiche, avec son unité.
        $response->assertSee('<span class="v">0<small>FCFA</small></span>', false);
        $response->assertSee('Aucune opération pour l&#039;instant', false);
    }

    public function test_shell_coupe_le_dom_mobile_n_est_pas_rendu(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();
        $caissier = $this->caissier(['paiements.view_own']);

        $response = $this->actingAs($caissier)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('m-screen cxm-screen', false);
        $response->assertDontSee('m-only-desktop', false);
        $response->assertSee('cx-hero', false);
    }

    /**
     * @param string[] $permissions
     */
    private function caissier(array $permissions): User
    {
        $user = User::factory()->create([
            'name' => 'Koné Ibrahim',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo(array_merge(['admin.access', 'module.caisse.access'], $permissions));

        return $user;
    }

    private function versement(User $caissier, array $attributs): ESBTPPaiement
    {
        return ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->create(array_merge([
                'created_by' => $caissier->id,
                'date_paiement' => '2026-09-04',
            ], $attributs));
    }
}

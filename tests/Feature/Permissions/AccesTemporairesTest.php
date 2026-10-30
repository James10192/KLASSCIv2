<?php

namespace Tests\Feature\Permissions;

use App\Domain\Permissions\AccesTemporaireRefuse;
use App\Domain\Permissions\AccesTemporaires;
use App\Models\TemporaryPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Une permission ouverte pour un temps limite tient tant que sa date n'est pas
 * passee, et tombe d'elle-meme ensuite — sans tache planifiee.
 */
class AccesTemporairesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['notes.edit', 'notes.view', 'module.notes_evaluations.access', 'paiements.view', 'identity.coordinate', 'identity.teach', 'identity.student', 'permissions.temporaires.manage', 'permissions.individuels.permanents.manage', 'personnel.manage', 'users.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superAdmin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): AccesTemporaires
    {
        // Le memo vit le temps d'une requete : on repart d'une instance neuve.
        app()->forgetInstance(AccesTemporaires::class);

        return app(AccesTemporaires::class);
    }

    private function peut(User $user, string $ability): bool
    {
        app()->forgetInstance(AccesTemporaires::class);

        return $user->fresh()->can($ability);
    }

    public function test_l_acces_ouvre_la_permission_puis_tombe_a_l_echeance(): void
    {
        $coord = User::factory()->create();
        $this->assertFalse($this->peut($coord, 'notes.edit'));

        $this->service()->accorder($coord, 'notes.edit', now(), now()->addDays(2), 'Correction des notes S2', $this->admin);
        $this->assertTrue($this->peut($coord, 'notes.edit'));

        Carbon::setTestNow(now()->addDays(2)->addMinute());
        $this->assertFalse($this->peut($coord, 'notes.edit'));
    }

    public function test_un_acces_retire_ne_donne_plus_rien(): void
    {
        $coord = User::factory()->create();
        $grant = $this->service()->accorder($coord, 'notes.edit', now(), now()->addDay(), 'Correction des notes S2', $this->admin);

        $this->service()->retirer($grant, $this->admin);

        $this->assertFalse($this->peut($coord, 'notes.edit'));
        $this->assertNotNull($grant->fresh()->revoked_at);
    }

    public function test_un_acces_a_venir_n_ouvre_rien_avant_son_debut(): void
    {
        $coord = User::factory()->create();
        $this->service()->accorder($coord, 'notes.edit', now()->addDay(), now()->addDays(3), 'Session de rattrapage', $this->admin);

        $this->assertFalse($this->peut($coord, 'notes.edit'));
        Carbon::setTestNow(now()->addDays(2));
        $this->assertTrue($this->peut($coord, 'notes.edit'));
    }

    public function test_on_ne_donne_pas_ce_qu_on_ne_detient_pas(): void
    {
        $auteur = User::factory()->create();
        $auteur->givePermissionTo('permissions.temporaires.manage');

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), now()->addDay(), 'Correction des notes S2', $auteur);
    }

    public function test_les_permissions_d_identite_ne_s_accordent_pas(): void
    {
        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'identity.coordinate', now(), now()->addDay(), 'Essai de promotion', $this->admin);
    }

    public function test_la_porte_financiere_lit_aussi_les_acces_temporaires(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($this->peut($user, 'finances.etudiants.voir'));

        $this->service()->accorder($user, 'paiements.view', now(), now()->addDay(), 'Audit ponctuel des paiements', $this->admin);

        $this->assertTrue($this->peut($user, 'finances.etudiants.voir'));
    }

    public function test_l_ecran_accorde_puis_retire_un_acces(): void
    {
        $coord = User::factory()->create();

        $reponse = $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id,
            'permission' => 'notes.edit',
            'fin' => now()->addDays(2)->format('Y-m-d H:i'),
            'motif' => 'Correction des notes du semestre 2',
        ]);
        $reponse->assertCreated()->assertJsonPath('acces.0.statut', 'active');

        $grant = TemporaryPermissionGrant::where('user_id', $coord->id)->firstOrFail();
        $this->actingAs($this->admin)->deleteJson(route('esbtp.acces-temporaires.destroy', $grant))
            ->assertOk()->assertJsonPath('acces.0.statut', 'retiree');
    }

    public function test_l_ecran_refuse_un_motif_trop_court_et_un_refus_metier(): void
    {
        $coord = User::factory()->create();

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id, 'permission' => 'notes.edit',
            'fin' => now()->addDay()->format('Y-m-d H:i'), 'motif' => 'court',
        ])->assertStatus(422)->assertJsonValidationErrors('motif');

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $coord->id, 'permission' => 'identity.coordinate',
            'fin' => now()->addDay()->format('Y-m-d H:i'), 'motif' => 'Essai de promotion interdit',
        ])->assertStatus(422);
    }

    public function test_l_ecran_est_ferme_sans_la_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('esbtp.acces-temporaires.index'))
            ->assertForbidden();
    }

    public function test_l_ecran_s_affiche_pour_l_administrateur(): void
    {
        $this->actingAs($this->admin)
            ->get(route('esbtp.acces-temporaires.index'))
            ->assertOk()
            ->assertSee('Accès individuels');
    }

    public function test_ouvert_depuis_une_fiche_l_ecran_choisit_deja_la_personne(): void
    {
        $cible = User::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('esbtp.acces-temporaires.index', ['user_id' => $cible->id]))
            ->assertOk()
            ->assertSee('data-current="'.$cible->id.'"', false);

        // Un identifiant hors de la liste (ici un compte inexistant) n'est pas repris.
        $this->actingAs($this->admin)
            ->get(route('esbtp.acces-temporaires.index', ['user_id' => 999999]))
            ->assertOk()
            ->assertSee('data-current=""', false);
    }

    public function test_le_bouton_de_la_liste_du_personnel_suit_les_gardes_de_l_ecran(): void
    {
        $cible = User::factory()->create();
        $bouton = '<x-personnel.bouton-acces-temporaire :user-id="$id" />';
        $lien = route('esbtp.acces-individuels.index', ['user_id' => $cible->id]);

        $this->actingAs($this->admin);
        $this->assertStringContainsString($lien, (string) $this->blade($bouton, ['id' => $cible->id]));
        // Jamais sur son propre compte.
        $this->assertStringNotContainsString('acces-individuels', (string) $this->blade($bouton, ['id' => $this->admin->id]));

        // « Accorder des accès temporaires » sans « Gérer le personnel » : la route refuserait.
        $sansPersonnel = User::factory()->create();
        $sansPersonnel->givePermissionTo('permissions.temporaires.manage');
        $this->actingAs($sansPersonnel->fresh());
        $this->assertStringNotContainsString('acces-individuels', (string) $this->blade($bouton, ['id' => $cible->id]));

        $sansPersonnel->givePermissionTo('personnel.manage');
        $this->actingAs($sansPersonnel->fresh());
        $this->assertStringContainsString($lien, (string) $this->blade($bouton, ['id' => $cible->id]));
    }

    public function test_le_module_notes_est_propose_dans_les_acces_individuels_et_pas_les_autres_modules(): void
    {
        $service = $this->service();
        $this->assertTrue($service->estAccordable('module.notes_evaluations.access'));
        $this->assertFalse($service->estAccordable('module.lmd.access'));
        $this->assertFalse($service->estAccordable('module.comptabilite.access'));
        $this->assertFalse($service->estAccordable('module.technical_support.access'));

        $this->actingAs($this->admin)
            ->get(route('esbtp.acces-individuels.index'))
            ->assertOk()
            ->assertSee('value="module.notes_evaluations.access"', false)
            ->assertDontSee('value="module.lmd.access"', false);
    }

    public function test_le_lien_sidebar_notes_suit_les_acces_individuels_sans_doublon_enseignant(): void
    {
        $personne = User::factory()->create();
        $personne->givePermissionTo(['notes.view', 'identity.teach']);
        $this->service()->accorder(
            $personne, 'module.notes_evaluations.access', now(), null,
            'Consultation nominative des notes depuis le menu lateral', $this->admin, true,
        );

        $reponse = $this->actingAs($personne->fresh())->get(route('esbtp.notes.index'));
        $reponse->assertOk();
        $reponse->assertSee('<span>Gestion des notes</span>', false);
        $this->assertSame(
            1, substr_count($reponse->getContent(), '<span>Gestion des notes</span>'),
            'La sidebar canonique doit proposer un lien unique vers les notes.',
        );
        $reponse->assertDontSee('<div class="menu-text">Gestion des notes</div>', false);
    }

    public function test_octroi_definitif_du_module_notes_ne_modifie_pas_le_role_et_se_revoque(): void
    {
        $beneficiaire = User::factory()->create();
        $autre = User::factory()->create();

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $beneficiaire->id,
            'permission' => 'module.notes_evaluations.access',
            'mode' => 'definitif',
            'motif' => 'Autoriser personnellement la consultation des notes BTS',
        ])->assertCreated();

        $grant = TemporaryPermissionGrant::where('user_id', $beneficiaire->id)
            ->where('permission', 'module.notes_evaluations.access')
            ->firstOrFail();

        $this->assertTrue($grant->is_permanent);
        $this->assertTrue($this->peut($beneficiaire, 'module.notes_evaluations.access'));
        $this->assertFalse($this->peut($beneficiaire, 'notes.view'));
        $this->assertFalse($this->peut($autre, 'module.notes_evaluations.access'));
        $this->assertTrue($beneficiaire->fresh()->roles->isEmpty());

        $this->actingAs($this->admin)
            ->deleteJson(route('esbtp.acces-temporaires.destroy', $grant))
            ->assertOk();

        $this->assertFalse($this->peut($beneficiaire, 'module.notes_evaluations.access'));
    }

    public function test_le_module_notes_temporaire_expire_et_un_autre_module_reste_interdit(): void
    {
        $beneficiaire = User::factory()->create();
        $this->service()->accorder(
            $beneficiaire, 'module.notes_evaluations.access', now(), now()->addDay(),
            'Acces individuel aux notes pour une journee', $this->admin,
        );

        $this->assertTrue($this->peut($beneficiaire, 'module.notes_evaluations.access'));
        Carbon::setTestNow(now()->addDays(2));
        $this->assertFalse($this->peut($beneficiaire, 'module.notes_evaluations.access'));

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            'user_id' => $beneficiaire->id,
            'permission' => 'module.lmd.access',
            'mode' => 'definitif',
            'motif' => 'Tentative de changement de module hors liste',
        ])->assertUnprocessable();
    }

    public function test_ce_qui_modifie_comptes_roles_ou_reglages_ne_s_accorde_pas(): void
    {
        $service = $this->service();
        foreach (['personnel.manage', 'users.manage', 'settings.edit', 'system.manage', 'module.lmd.access', 'paywall.manage'] as $permission) {
            $this->assertFalse($service->estAccordable($permission), $permission);
        }
        $this->assertTrue($service->estAccordable('notes.edit'));
    }

    public function test_un_compte_etudiant_ne_recoit_rien(): void
    {
        $etudiant = User::factory()->create();
        $etudiant->givePermissionTo('identity.student');

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder($etudiant, 'paiements.view', now(), now()->addDay(), 'Essai sur un compte parent', $this->admin);
    }

    public function test_une_date_avec_decalage_est_enregistree_dans_le_fuseau_de_l_application(): void
    {
        config(['app.timezone' => 'UTC']);
        $fin = Carbon::parse(now('UTC')->addDays(2)->format('Y-m-d').'T10:00:00+01:00');

        $grant = $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), $fin, 'Correction des notes S2', $this->admin);

        $this->assertSame('09:00', $grant->fresh()->expires_at->format('H:i'));
    }

    public function test_deux_acces_qui_se_chevauchent_sont_refuses(): void
    {
        $coord = User::factory()->create();
        $this->service()->accorder($coord, 'notes.edit', now(), now()->addDays(3), 'Correction des notes S2', $this->admin);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder($coord, 'notes.edit', now()->addDay(), now()->addDays(5), 'Deuxieme demande en double', $this->admin);
    }

    public function test_la_duree_maximale_est_tenue(): void
    {
        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), now()->addDays(AccesTemporaires::DUREE_MAX_JOURS_DEFAUT + 1), 'Beaucoup trop long', $this->admin);
    }

    public function test_un_acces_ne_se_programme_pas_au_dela_de_la_duree_maximale(): void
    {
        $debut = now()->addDays(AccesTemporaires::DUREE_MAX_JOURS_DEFAUT + 5);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', $debut, $debut->copy()->addDay(), 'Programme bien trop loin', $this->admin);
    }

    public function test_sans_gerer_le_personnel_l_ecran_reste_ferme(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.temporaires.manage');

        $this->actingAs($user->fresh())->get(route('esbtp.acces-temporaires.index'))->assertForbidden();
    }

    public function test_un_acces_definitif_survit_aux_limites_temporelles_et_reste_revocable(): void
    {
        $user = User::factory()->create();
        $acces = $this->service()->accorder($user, 'notes.edit', now(), null, 'Mission permanente au service des notes', $this->admin, true);

        $this->assertTrue($acces->is_permanent);
        $this->assertNull($acces->expires_at);
        $this->assertSame('definitif', $acces->statut());

        Carbon::setTestNow(now()->addYear());
        $this->assertTrue($this->peut($user, 'notes.edit'));

        $this->service()->retirer($acces, $this->admin);
        $this->assertFalse($this->peut($user, 'notes.edit'));
        $this->assertNotNull($acces->fresh()->revoked_at);
    }

    public function test_une_ancienne_date_de_fin_nulle_ne_se_transforme_pas_en_acces_definitif(): void
    {
        $user = User::factory()->create();
        $acces = TemporaryPermissionGrant::create([
            'user_id' => $user->id, 'permission' => 'notes.edit',
            'starts_at' => now()->subDay(), 'expires_at' => null,
            'motif' => 'Historique incomplet avant le nouveau mode', 'granted_by' => $this->admin->id,
        ]);

        $this->assertFalse($acces->fresh()->is_permanent);
        $this->assertFalse($acces->fresh()->estActive());
        $this->assertFalse($this->peut($user, 'notes.edit'));
    }

    public function test_un_gestionnaire_temporaire_ne_peut_pas_accorder_de_definitif(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['permissions.temporaires.manage', 'personnel.manage', 'notes.edit']);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), null, 'Mission durable sans habilitation', $manager, true);
    }

    public function test_un_gestionnaire_explicitement_habilite_peut_accorder_du_definitif(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(['permissions.temporaires.manage', 'permissions.individuels.permanents.manage', 'personnel.manage', 'notes.edit']);

        $grant = $this->service()->accorder(User::factory()->create(), 'notes.edit', now(), null, 'Mission durable du service des notes', $manager, true);
        $this->assertTrue($grant->is_permanent);
    }

    public function test_une_permission_individuelle_ne_permet_pas_de_se_donner_un_autre_droit(): void
    {
        $this->expectException(AccesTemporaireRefuse::class);
        $this->expectExceptionMessage('Vous ne pouvez pas vous accorder');
        $this->service()->accorder($this->admin, 'notes.edit', now(), null, 'Auto-attribution interdite du personnel', $this->admin, true);
    }

    public function test_un_definitif_et_un_temporaire_ne_peuvent_pas_se_chevaucher_dans_les_deux_sens(): void
    {
        $user = User::factory()->create();
        $temporaire = $this->service()->accorder($user, 'notes.edit', now(), now()->addDays(2), 'Correction temporaire de notes', $this->admin);

        try {
            $this->service()->accorder($user, 'notes.edit', now(), null, 'Droit définitif en doublon interdit', $this->admin, true);
            $this->fail('Le definitif doit etre refuse pendant un temporaire.');
        } catch (AccesTemporaireRefuse $e) {
            $this->assertStringContainsString('couvre déjà', $e->getMessage());
        }

        $this->service()->retirer($temporaire, $this->admin);
        $this->service()->accorder($user, 'notes.edit', now(), null, 'Droit definitif apres retrait', $this->admin, true);

        $this->expectException(AccesTemporaireRefuse::class);
        $this->service()->accorder($user, 'notes.edit', now(), now()->addDay(), 'Temporaire en doublon interdit', $this->admin);
    }

    public function test_les_permissions_de_contournement_et_la_gestion_des_acces_definitifs_sont_non_accordables(): void
    {
        foreach (['sod.bypass', 'paiements.validate.self_override', 'comptabilite.period.bypass_lock',
            'comptabilite.reconciliation.bypass_lock', 'permissions.individuels.permanents.manage'] as $ability) {
            $this->assertFalse($this->service()->estAccordable($ability), $ability);
        }
    }

    public function test_un_acces_accorde_a_un_personnel_ne_suit_pas_son_promotion_vers_compte_etudiant(): void
    {
        $user = User::factory()->create();
        $this->service()->accorder($user, 'notes.edit', now(), null, 'Ancienne mission du service des notes', $this->admin, true);
        $user->givePermissionTo('identity.student');

        $this->assertFalse($this->peut($user, 'notes.edit'));
    }

    public function test_un_changement_de_role_etudiant_invalide_un_acces_deja_memoise_dans_la_meme_requete(): void
    {
        Role::findOrCreate('etudiant', 'web');
        $user = User::factory()->create();
        $service = $this->service();
        $service->accorder($user, 'notes.edit', now(), null, 'Mission personnelle avant changement de role', $this->admin, true);

        $this->assertTrue($service->detient($user, 'notes.edit'));

        // Même instance de service, même objet User, sans rafraîchissement de requête.
        $user->assignRole('etudiant');

        $this->assertFalse($service->detient($user, 'notes.edit'));
    }

    public function test_le_formulaire_definitif_refuse_une_date_et_accepte_une_duree_absente(): void
    {
        $user = User::factory()->create();
        $payload = [
            'user_id' => $user->id, 'permission' => 'notes.edit',
            'mode' => 'definitif', 'motif' => 'Délégation durable du service des notes',
        ];
        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), [
            ...$payload, 'fin' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertUnprocessable()->assertJsonValidationErrors('fin');

        $this->actingAs($this->admin)->postJson(route('esbtp.acces-temporaires.store'), $payload)
            ->assertCreated()->assertJsonPath('acces.0.statut', 'definitif');
    }

    public function test_un_definitif_plus_ancien_que_200_operations_reste_visible_et_retirable(): void
    {
        $user = User::factory()->create();
        $permanent = $this->service()->accorder($user, 'notes.edit', now(), null, 'Acces durable a retrouver dans les archives', $this->admin, true);

        for ($i = 0; $i < 205; $i++) {
            TemporaryPermissionGrant::create([
                'user_id' => $user->id, 'permission' => 'notes.edit',
                'starts_at' => now()->subDays(3), 'expires_at' => now()->subDay(),
                'motif' => 'Accès historique sans effet aujourd’hui', 'granted_by' => $this->admin->id,
            ]);
        }

        $reponse = $this->actingAs($this->admin)->getJson(route('esbtp.acces-temporaires.data', ['scope' => 'en_cours']));
        $reponse->assertOk()->assertJsonPath('statistiques.actifs', 1);
        $this->assertContains($permanent->id, array_column($reponse->json('acces'), 'id'));
    }

    public function test_la_liste_paginee_charge_les_auteurs_et_beneficiaires_sans_n_plus_un(): void
    {
        // Les relations doivent etre eager-loadees : 30 personnes ne doivent
        // pas produire 30 requetes SQL pour leurs noms ou les auteurs.
        $this->actingAs($this->admin);

        $premier = User::factory()->create();
        $this->service()->accorder($premier, 'notes.edit', now(), now()->addDay(), 'Délégation numéro un du personnel', $this->admin);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(route('esbtp.acces-temporaires.data', ['scope' => 'en_cours']))->assertOk();
        $requeteUne = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($i = 0; $i < 32; $i++) {
            $user = User::factory()->create();
            $this->service()->accorder($user, 'notes.edit', now(), now()->addDay(), 'Délégation individuelle du personnel', $this->admin);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson(route('esbtp.acces-temporaires.data', ['scope' => 'en_cours', 'page' => 1]))
            ->assertOk()->assertJsonPath('pagination.total', 33);
        $requeteTrente = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $this->assertLessThanOrEqual($requeteUne + 3, $requeteTrente, 'Une requête supplémentaire par ligne signalerait un N+1.');
    }
}

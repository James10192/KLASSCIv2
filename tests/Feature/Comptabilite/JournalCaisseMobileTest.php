<?php

namespace Tests\Feature\Comptabilite;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Journal de caisse en mobile (issue #963, lot 2a, maquette S['comptable:journal']) :
 * l'écran m-* est rendu à côté du DOM de bureau pour une personne profilée
 * « comptable », et le même contrôleur répond en données groupées par jour
 * (mode=mobile), paginées par jour, avec les filtres du bureau.
 */
class JournalCaisseMobileTest extends TestCase
{
    use DatabaseTransactions;

    // admin.access : le groupe de routes /esbtp exige une permission d'identite
    // (admin.access pour comptable et caissier dans config/permissions.php).
    private const PERMISSIONS = ['admin.access', 'comptabilite.access', 'comptabilite.journal.view', 'paiements.view'];

    private User $comptable;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $scolarite;

    protected function setUp(): void
    {
        parent::setUp();

        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe :
        // sans lui, toutes les pages repondent 302 vers /install.
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // comptabilite.access sans module.caisse.access : profil « comptable ».
        $this->comptable = User::factory()->create([
            'name' => 'Koné Ibrahim',
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $this->comptable->givePermissionTo(self::PERMISSIONS);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->comptable->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::create([
            'name' => 'Scolarité journal mobile',
            'code' => 'SCOL_JM_'.uniqid(),
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 100000,
            'payment_deadline_days' => 30,
        ]);

        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '1');
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    private function paiement(array $attributs = []): ESBTPPaiement
    {
        return ESBTPPaiement::create(array_merge([
            'inscription_id' => $this->inscription->id,
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $this->scolarite->id,
            'montant' => 50000,
            'mode_paiement' => 'Espèces',
            'date_paiement' => now()->toDateString(),
            'status' => 'validé',
            'nature' => 'encaissement',
            'created_by' => $this->comptable->id,
            'numero_recu' => 'REC-JM-'.uniqid(),
        ], $attributs));
    }

    public function test_l_ecran_mobile_est_rendu_a_cote_du_dom_de_bureau(): void
    {
        $paiement = $this->paiement();

        $reponse = $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.journal-caisse.index'));

        $reponse->assertOk()
            ->assertSee('has-m-shell m-profile-comptable', false)
            ->assertSee('m-only-desktop', false)
            ->assertSee('m-only-mobile m-screen jcm-screen', false)
            // App bar : titre, conformité OHADA, export (permission présente).
            ->assertSee('Journal de caisse')
            ->assertSee('Conforme OHADA')
            ->assertSee('aria-label="Exporter le journal"', false)
            ->assertSee('data-m-sheet="jcm-export"', false)
            ->assertSee('window.jcmJournal', false)
            // La première page part avec la vue : pas de second aller-retour.
            ->assertSee($paiement->numero_recu)
            // Le tableau de bureau est toujours là, simplement caché sous 992px.
            ->assertSee('jc-table-card', false);
    }

    public function test_sans_shell_mobile_la_page_ne_rend_que_le_bureau(): void
    {
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $this->actingAs($this->comptable)->get(route('esbtp.comptabilite.journal-caisse.index'))
            ->assertOk()
            ->assertDontSee('m-only-mobile m-screen jcm-screen', false)
            ->assertDontSee('m-only-desktop', false);
    }

    public function test_le_journal_repond_en_donnees_groupees_par_jour(): void
    {
        $encaissement = $this->paiement(['montant' => 150000]);
        $remboursement = $this->paiement([
            'montant' => 40000,
            'nature' => 'avoir',
            'avoir_kind' => 'refund',
            'numero_avoir' => 'AV-JM-'.uniqid(),
            'parent_paiement_id' => $encaissement->id,
        ]);
        // Un avoir « crédit » n'est pas un mouvement de caisse : absent du journal.
        $this->paiement(['montant' => 10000, 'nature' => 'avoir', 'avoir_kind' => 'credit']);
        // Le statut par défaut est « validé » : un paiement à valider n'apparaît pas.
        $this->paiement(['montant' => 999, 'status' => 'en_attente']);

        $reponse = $this->actingAs($this->comptable)
            ->getJson(route('esbtp.comptabilite.journal-caisse.index', ['mode' => 'mobile']));

        $reponse->assertOk()
            ->assertJsonStructure([
                'jours' => [['date', 'libelle', 'solde', 'nb', 'mouvements' => [['id', 'url', 'initiales', 'icone', 'titre', 'sous_titre', 'montant', 'sortie', 'statut']]]],
                'has_more', 'next_page', 'nb_jours',
                'mois' => [['cle', 'libelle', 'date_debut', 'date_fin', 'on']],
                'filtres' => ['date_debut', 'date_fin', 'statut'],
                'url',
            ])
            ->assertJsonCount(1, 'jours')
            ->assertJsonPath('nb_jours', 1)
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('jours.0.date', now()->toDateString())
            ->assertJsonPath('jours.0.nb', 2)
            // Solde du jour = encaissements moins remboursements.
            ->assertJsonPath('jours.0.solde', 110000)
            ->assertJsonPath('url', route('esbtp.comptabilite.journal-caisse.index'));

        $mouvements = collect($reponse->json('jours.0.mouvements'));

        $entree = $mouvements->firstWhere('id', $encaissement->id);
        $this->assertNotNull($entree);
        $this->assertFalse($entree['sortie']);
        $this->assertSame(150000, $entree['montant']);
        $this->assertSame('validé', $entree['statut']);
        $this->assertSame(route('esbtp.paiements.show', $encaissement->id), $entree['url']);
        $this->assertStringContainsString('Scolarité journal mobile', $entree['titre']);
        $this->assertStringContainsString('Reçu '.$encaissement->numero_recu, $entree['sous_titre']);
        $this->assertStringContainsString('Espèces', $entree['sous_titre']);
        // Le caissier tient sur une ligne : « Koné I. ».
        $this->assertStringContainsString('Koné I.', $entree['sous_titre']);
        $this->assertNull($entree['icone']);
        $this->assertNotSame('', $entree['initiales']);

        $sortie = $mouvements->firstWhere('id', $remboursement->id);
        $this->assertNotNull($sortie);
        $this->assertTrue($sortie['sortie']);
        $this->assertSame('cash', $sortie['icone']);
        $this->assertStringStartsWith('Remboursement', $sortie['titre']);
        $this->assertStringContainsString('Avoir '.$remboursement->numero_avoir, $sortie['sous_titre']);

        // Le mois courant est le segment actif.
        $moisOn = collect($reponse->json('mois'))->firstWhere('on', true);
        $this->assertNotNull($moisOn);
        $this->assertSame(now()->format('Y-m'), $moisOn['cle']);
    }

    public function test_les_jours_sont_pagines_du_plus_recent_au_plus_ancien(): void
    {
        // Six jours distincts dans le mois : 5 par page, donc deux pages.
        $debut = now()->startOfMonth();
        for ($i = 0; $i < 6; $i++) {
            $this->paiement(['date_paiement' => $debut->copy()->addDays($i)->toDateString()]);
        }
        // Deux mouvements le même jour ne font qu'un seul jour.
        $this->paiement(['date_paiement' => $debut->toDateString()]);

        $premiere = $this->actingAs($this->comptable)->getJson(route('esbtp.comptabilite.journal-caisse.index', [
            'mode' => 'mobile',
            'date_debut' => $debut->toDateString(),
            'date_fin' => $debut->copy()->endOfMonth()->toDateString(),
        ]));

        $premiere->assertOk()
            ->assertJsonCount(5, 'jours')
            ->assertJsonPath('nb_jours', 6)
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('next_page', 2)
            ->assertJsonPath('jours.0.date', $debut->copy()->addDays(5)->toDateString());

        $seconde = $this->actingAs($this->comptable)->getJson(route('esbtp.comptabilite.journal-caisse.index', [
            'mode' => 'mobile',
            'page' => 2,
            'date_debut' => $debut->toDateString(),
            'date_fin' => $debut->copy()->endOfMonth()->toDateString(),
        ]));

        $seconde->assertOk()
            ->assertJsonCount(1, 'jours')
            ->assertJsonPath('jours.0.date', $debut->toDateString())
            ->assertJsonPath('jours.0.nb', 2)
            ->assertJsonPath('has_more', false)
            // Les paramètres techniques ne remontent pas dans l'URL navigable.
            ->assertJsonPath('url', route('esbtp.comptabilite.journal-caisse.index', [
                'date_debut' => $debut->toDateString(),
                'date_fin' => $debut->copy()->endOfMonth()->toDateString(),
            ]));
    }

    public function test_les_filtres_du_bureau_s_appliquent_au_journal_mobile(): void
    {
        $this->paiement(['mode_paiement' => 'Espèces']);
        $virement = $this->paiement(['mode_paiement' => 'Virement']);

        $reponse = $this->actingAs($this->comptable)
            ->getJson(route('esbtp.comptabilite.journal-caisse.index', ['mode' => 'mobile', 'mode_paiement' => 'Virement']));

        $reponse->assertOk()
            ->assertJsonPath('jours.0.nb', 1)
            ->assertJsonPath('jours.0.mouvements.0.id', $virement->id)
            ->assertJsonPath('filtres.mode_paiement', 'Virement');
    }

    public function test_sans_droit_sur_les_paiements_la_ligne_ne_renvoie_pas_vers_le_recu(): void
    {
        $this->comptable->revokePermissionTo('paiements.view');
        $this->paiement();

        $this->actingAs($this->comptable)
            ->getJson(route('esbtp.comptabilite.journal-caisse.index', ['mode' => 'mobile']))
            ->assertOk()
            ->assertJsonPath('jours.0.mouvements.0.url', null);
    }

    public function test_une_periode_vide_repond_sans_jour(): void
    {
        $this->actingAs($this->comptable)
            ->getJson(route('esbtp.comptabilite.journal-caisse.index', ['mode' => 'mobile']))
            ->assertOk()
            ->assertJsonCount(0, 'jours')
            ->assertJsonPath('nb_jours', 0)
            ->assertJsonPath('has_more', false);
    }
}

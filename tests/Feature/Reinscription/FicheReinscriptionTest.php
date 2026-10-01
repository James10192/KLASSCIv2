<?php

namespace Tests\Feature\Reinscription;

use App\Helpers\SettingsHelper;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Chatbot\Tools\DiagnostiquerReinscriptionTool;
use App\Services\Inscriptions\NormalisationTypeInscription;
use App\Services\ReeinscriptionService;
use App\Services\Reinscription\EligibiliteReinscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La fiche de réinscription, la garde du service et Nanan donnent le même
 * verdict : un seul service les calcule (EligibiliteReinscription).
 */
class FicheReinscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private User $agent;
    private User $admin;
    private ESBTPInscription $inscription;
    private ESBTPAnneeUniversitaire $courante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);

        Role::findOrCreate('superAdmin', 'web');
        foreach (['identity.registrar', 'inscriptions.view', 'students.view', 'finances.etudiants.voir', 'paiements.create'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->agent = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $this->agent->givePermissionTo(['identity.registrar', 'inscriptions.view', 'finances.etudiants.voir']);
        $this->admin = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $this->admin->assignRole('superAdmin');

        // Aucune autre année ouverte : l'année visée se déduit des seules années du test.
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false, 'is_active' => false]);
        $quittee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'is_current' => false,
            'start_date' => now()->subYear()->startOfMonth(), 'end_date' => now()->subMonths(2)]);
        $this->courante = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true,
            'start_date' => now()->subMonth()->startOfMonth(), 'end_date' => now()->addMonths(9)]);

        $this->inscription = ESBTPInscription::factory()->create(['annee_universitaire_id' => $quittee->id]);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => ESBTPFraisCategory::factory()->create()->id,
            'amount' => 220000,
        ]);
    }

    private function fiche(User $qui)
    {
        return $this->actingAs($qui)->get(route('esbtp.reinscription.show', $this->inscription->etudiant_id));
    }

    public function test_un_impaye_bloque_et_dit_quoi_faire(): void
    {
        $this->fiche($this->agent)->assertOk()
            ->assertSee('Réinscription bloquée')
            ->assertSee('220 000 FCFA')
            ->assertSee('2025-2026')
            ->assertSee('2026-2027')
            ->assertDontSee('Procéder à la finalisation');
    }

    public function test_le_superadmin_voit_le_blocage_et_la_derogation(): void
    {
        $this->fiche($this->admin)->assertOk()
            ->assertSee('réinscription possible par dérogation')
            ->assertSee('Réinscrire avec reliquat');

        // Nanan dit la même chose : le dossier est bloqué, le lecteur peut déroger.
        $d = app(DiagnostiquerReinscriptionTool::class)
            ->executeAuthorized(['etudiant_id' => $this->inscription->etudiant_id], $this->admin)['diagnostic'];
        $this->assertTrue($d['bloquee']);
        $this->assertTrue($d['peut_autoriser_reliquat']);
        $this->assertSame('solde_impaye', $d['cause']);
    }

    public function test_la_tolerance_de_l_ecole_vaut_pour_la_fiche_et_la_garde(): void
    {
        SettingsHelper::set('reinscription.tolerance_solde', 250000);
        if ((float) SettingsHelper::get('reinscription.tolerance_solde', 0) !== 250000.0) {
            $this->markTestSkipped('Réglage non enregistrable dans cette base de test.');
        }

        $this->fiche($this->agent)->assertOk()->assertSee('Réinscription autorisée')->assertSee('Procéder à la finalisation');
        $this->assertTrue(app(ReeinscriptionService::class)->peutSeReinscrire($this->inscription->etudiant_id));
    }

    public function test_un_dossier_solde_sans_frais_n_affiche_pas_zero_pour_cent_en_rouge(): void
    {
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->delete();

        $this->fiche($this->agent)->assertOk()
            ->assertSee('Réinscription autorisée')
            ->assertSee('Aucun frais souscrit sur cette inscription')
            ->assertSee('100 %');
    }

    public function test_une_inscription_de_l_annee_courante_d_un_autre_type_compte_comme_deja_inscrit(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::PREMIERE,
            'status' => 'en_attente',
        ]);

        $this->fiche($this->agent)->assertOk()->assertSee('Déjà inscrit pour 2026-2027')->assertSee('Dossier en cours')->assertSee("Ouvrir l'inscription", false);
    }

    public function test_une_reinscription_annulee_ne_masque_pas_le_blocage(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'annulée',
        ]);

        $this->assertSame(EligibiliteReinscription::IMPAYE, app(EligibiliteReinscription::class)->pour($this->inscription->etudiant_id)['etat']);
        $this->fiche($this->agent)->assertOk()->assertSee('Réinscription bloquée');
    }

    /**
     * Campagne ouverte avant la bascule : l'élève est entré cette année, rien
     * avant. Il n'est PAS « déjà inscrit » : on juge le solde de cette année,
     * et la finalisation choisit l'année suivante.
     */
    public function test_un_eleve_entre_cette_annee_n_est_pas_deja_inscrit(): void
    {
        $this->inscription->update(['annee_universitaire_id' => $this->courante->id]);

        $e = app(EligibiliteReinscription::class)->pour($this->inscription->etudiant_id);
        $this->assertSame($this->inscription->id, $e['inscription']->id);
        $this->assertSame(EligibiliteReinscription::IMPAYE, $e['etat']);
        $this->fiche($this->agent)->assertOk()->assertSee('Réinscription bloquée');
    }

    public function test_deja_inscrit_la_fiche_offre_la_correction_et_la_finalisation_reste_ouverte(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'active',
            'workflow_step' => 'documents_complets',
        ]);
        ESBTPAnneeUniversitaire::where('id', '!=', $this->courante->id)->where('start_date', '>', $this->courante->start_date)->update(['is_active' => false]);

        // Réinscription faite, dossier pas encore validé : la fiche le dit.
        $this->fiche($this->agent)->assertOk()->assertSee('Déjà inscrit pour 2026-2027')->assertDontSee('Corriger la réinscription')
            ->assertSee("Dossier d'inscription en attente", false);
        $this->fiche($this->admin)->assertOk()->assertSee('Corriger la réinscription');
        // Pas de formulaire inerte : la finalisation s'ouvre (l'année se choisit à l'envoi).
        $this->actingAs($this->agent)->get(route('esbtp.reinscription.create', $this->inscription->etudiant_id))->assertOk();
    }

    /**
     * Avant la bascule : courante = N, l'élève est déjà réinscrit en N, l'école
     * prépare N+1. « Préparer N+1 » part de la classe de N, présélectionne N+1,
     * et ne touche pas l'inscription de N. Viser N lui-même est refusé.
     */
    public function test_preparer_l_annee_suivante_part_de_l_annee_en_cours_sans_la_defaire(): void
    {
        $enCours = ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);
        ESBTPAnneeUniversitaire::where('start_date', '>', $this->courante->start_date)->update(['is_active' => false]);
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => '2027-2028', 'is_current' => false, 'is_active' => true,
            'start_date' => $this->courante->start_date->copy()->addYear(), 'end_date' => $this->courante->end_date->copy()->addYear()]);

        $lien = route('esbtp.reinscription.create', ['etudiant' => $this->inscription->etudiant_id, 'annee_academique' => '2026-2027', 'annee_cible_id' => $suivante->id]);
        $this->fiche($this->agent)->assertOk()->assertSee('Préparer 2027-2028')->assertSee(e($lien), false)
            ->assertDontSee('Corriger la réinscription');
        // Une année suivante ouverte ne retire pas au superadministrateur la
        // correction d'une réinscription faite par erreur.
        $this->fiche($this->admin)->assertOk()->assertSee('Préparer 2027-2028')->assertSee('Corriger la réinscription')
            ->assertDontSee("Dossier d'inscription en attente", false);

        // N pas encore finalisée : préparer N+1 partirait de N-1 et sauterait N.
        $enCours->update(['workflow_step' => 'documents_complets']);
        $this->fiche($this->admin)->assertOk()->assertDontSee('Préparer 2027-2028')
            ->assertSee("Dossier d'inscription en attente", false);
        $enCours->update(['workflow_step' => 'etudiant_cree']);

        $e = app(EligibiliteReinscription::class)->pour($this->inscription->etudiant_id, $this->agent, $suivante->id);
        $this->assertSame($enCours->id, $e['inscription']->id, 'on quitte N, pas N-1');

        $this->actingAs($this->admin)->get($lien)->assertOk()
            ->assertSee('<option value="'.$suivante->id.'" selected', false)
            ->assertDontSee('<option value="'.$this->courante->id.'" selected', false);

        // Dette soldée : c'est bien la garde « déjà inscrit » qui doit refuser.
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->update(['amount' => 0]);
        $this->actingAs($this->agent);
        try {
            app(ReeinscriptionService::class)->effectuerReinscription($this->inscription->etudiant_id, $enCours->classe_id, 'passage', null, [], null, $this->courante->id, null, false, false);
            $this->fail("Un agent ne doit pas pouvoir remplacer l'inscription en cours.");
        } catch (\App\Exceptions\ReinscriptionRefuseeException $ex) {
            $this->assertStringContainsString('déjà inscrit', $ex->getMessage());
        }
        $this->assertSame('active', $enCours->fresh()->status);
    }

    public function test_corriger_vers_la_meme_classe_est_refuse_proprement(): void
    {
        $enCours = ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'active',
        ]);
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->update(['amount' => 0]);

        $this->actingAs($this->admin)
            ->from(route('esbtp.reinscription.create', $this->inscription->etudiant_id))
            ->put(route('esbtp.reinscription.update', $this->inscription->etudiant_id), [
                'nouvelle_classe_id' => $enCours->classe_id, 'decision' => 'passage', 'annee_universitaire_id' => $this->courante->id,
            ])
            ->assertSessionHasErrors('error');

        $this->assertStringContainsString('déjà inscrit dans cette classe', session('errors')->first('error'));
        $this->assertStringNotContainsString('SQLSTATE', session('errors')->first('error'));
        $this->assertSame('active', $enCours->fresh()->status);
    }

    public function test_on_ne_saute_pas_une_annee_dont_le_dossier_est_en_cours(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'active',
            'workflow_step' => 'documents_complets',
        ]);
        ESBTPAnneeUniversitaire::where('start_date', '>', $this->courante->start_date)->update(['is_active' => false]);
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => '2027-2028', 'is_current' => false, 'is_active' => true,
            'start_date' => $this->courante->start_date->copy()->addYear(), 'end_date' => $this->courante->end_date->copy()->addYear()]);
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->update(['amount' => 0]);
        $avant = ESBTPInscription::where('etudiant_id', $this->inscription->etudiant_id)->count();

        $this->actingAs($this->admin)
            ->from(route('esbtp.reinscription.create', $this->inscription->etudiant_id))
            ->put(route('esbtp.reinscription.update', $this->inscription->etudiant_id), [
                'nouvelle_classe_id' => $this->inscription->classe_id, 'decision' => 'passage', 'annee_universitaire_id' => $suivante->id,
            ])
            ->assertSessionHasErrors('error');

        $this->assertStringContainsString("n'est pas finalisé", session('errors')->first('error'));
        $this->assertSame($avant, ESBTPInscription::where('etudiant_id', $this->inscription->etudiant_id)->count());
    }

    /**
     * Après la bascule (courante = N+1), un dossier N en cours : la fiche, la
     * finalisation, la garde et Nanan disent tous qu'une année reste à régler.
     */
    public function test_apres_la_bascule_un_dossier_intermediaire_bloque_partout(): void
    {
        $n = ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $this->courante->id,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'active',
            'workflow_step' => 'documents_complets',
        ]);
        $this->courante->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create(['name' => '2027-2028', 'is_current' => true, 'is_active' => true,
            'start_date' => $this->courante->start_date->copy()->addYear(), 'end_date' => $this->courante->end_date->copy()->addYear()]);
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->update(['amount' => 0]);

        $e = app(EligibiliteReinscription::class)->pour($this->inscription->etudiant_id, $this->admin);
        $this->assertSame(EligibiliteReinscription::ANNEE_INTERMEDIAIRE, $e['etat']);
        $this->assertFalse($e['peut_poursuivre'], 'aucune dérogation ne saute une année');

        $this->fiche($this->admin)->assertOk()->assertSee('Une année reste à régler')
            ->assertSee("n'est pas finalisé")->assertDontSee('Réinscription autorisée');
        $this->actingAs($this->agent)->get(route('esbtp.reinscription.create', $this->inscription->etudiant_id))
            ->assertRedirect(route('esbtp.reinscription.show', $this->inscription->etudiant_id));

        $nanan = app(DiagnostiquerReinscriptionTool::class)->executeAuthorized(['etudiant_id' => $this->inscription->etudiant_id], $this->agent);
        $this->assertTrue($nanan['diagnostic']['bloquee']);
        $this->assertSame('dossier_intermediaire', $nanan['diagnostic']['cause']);
        $this->assertStringContainsString("n'est pas finalisé", $nanan['diagnostic']['que_faire']);
        // Le mode opératoire lui dit quoi faire de cette cause, et ce qu'il ne faut pas proposer.
        $prompt = app(\App\Domain\Assistant\Harnais\ConstructeurDePrompt::class)->systeme($this->agent, null, null);
        $this->assertStringContainsString('Cause « dossier_intermediaire »', $prompt);
        $this->assertStringContainsString('ne propose aucun ajustement de frais', $prompt);

        // Une inscription « terminée » n'est pas un dossier à finaliser : le
        // message le dit au lieu de conseiller une annulation.
        $n->update(['status' => 'terminée', 'workflow_step' => 'etudiant_cree']);
        $this->fiche($this->admin)->assertOk()->assertSee('est terminée sans être la dernière inscription suivie');

        // Déjà une inscription sur l'année visée : la correction non plus ne
        // saute pas l'année restée en suspens.
        $n->update(['status' => 'active', 'workflow_step' => 'documents_complets']);
        $visee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => $visee->id,
            'status' => 'active',
        ]);
        $e = app(EligibiliteReinscription::class)->pour($this->inscription->etudiant_id, $this->admin);
        $this->assertSame(EligibiliteReinscription::DEJA_INSCRIT, $e['etat']);
        $this->assertFalse($e['peut_rejouer']);
        $this->fiche($this->admin)->assertOk()->assertDontSee('Corriger la réinscription');
    }

    public function test_une_finalisation_bloquee_renvoie_a_la_fiche(): void
    {
        $this->actingAs($this->agent)->get(route('esbtp.reinscription.create', $this->inscription->etudiant_id))
            ->assertRedirect(route('esbtp.reinscription.show', $this->inscription->etudiant_id));
    }

    public function test_la_garde_juge_l_annee_visee(): void
    {
        $this->assertFalse(app(ReeinscriptionService::class)->peutSeReinscrire($this->inscription->etudiant_id, $this->courante->id));
        ESBTPFraisSubscription::where('inscription_id', $this->inscription->id)->update(['amount' => 0]);
        $this->assertTrue(app(ReeinscriptionService::class)->peutSeReinscrire($this->inscription->etudiant_id, $this->courante->id));
    }

    public function test_sans_droit_finances_les_montants_restent_caches(): void
    {
        $sansFinances = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $sansFinances->givePermissionTo(['identity.registrar']);

        $this->fiche($sansFinances)->assertOk()->assertSee('Réinscription bloquée')->assertDontSee('220 000');
    }
}

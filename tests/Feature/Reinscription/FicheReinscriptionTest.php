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

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $quittee = ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'is_current' => false,
            'start_date' => now()->subYear()->startOfMonth(), 'end_date' => now()->subMonths(2)]);
        $this->courante = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => true]);

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

    public function test_sans_droit_finances_les_montants_restent_caches(): void
    {
        $sansFinances = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $sansFinances->givePermissionTo(['identity.registrar']);

        $this->fiche($sansFinances)->assertOk()->assertSee('Réinscription bloquée')->assertDontSee('220 000');
    }
}

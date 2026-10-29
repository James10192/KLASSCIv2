<?php

namespace Tests\Feature\Etudiants;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La fiche étudiant ne doit pas confondre l'année de travail `is_current`
 * avec l'année dans laquelle l'élève doit être réinscrit.
 */
class FicheEtudiantReinscriptionCibleTest extends TestCase
{
    use DatabaseTransactions;

    private User $agent;

    private ESBTPAnneeUniversitaire $courante;

    private ESBTPAnneeUniversitaire $suivante;

    private ESBTPEtudiant $etudiant;

    private ESBTPClasse $classe;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-07 12:00:00');
        $this->withoutMiddleware([CheckInstalled::class, EnsureInstalled::class, PaywallMiddleware::class]);

        foreach (['admin.access', 'students.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false, 'is_active' => false]);

        $this->courante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'is_active' => true,
        ]);
        $this->suivante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $this->classe = ESBTPClasse::factory()->create([
            'annee_universitaire_id' => $this->courante->id,
            'systeme_academique' => 'BTS',
        ]);
        $this->etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->courante->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo(['admin.access', 'students.view']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fiche(): string
    {
        return $this->actingAs($this->agent->fresh())
            ->get(route('esbtp.etudiants.show', $this->etudiant))
            ->assertOk()
            ->getContent();
    }

    public function test_annee_courante_echue_propose_la_suivante_sans_rendez_vous(): void
    {
        $html = $this->fiche();

        $this->assertStringContainsString("L'année 2025-2026 est échue", $html);
        $this->assertStringContainsString('Réinscrire pour 2026-2027', $html);
        $this->assertStringContainsString('même sans prise de rendez-vous préalable', $html);
        $this->assertStringContainsString('data-reinscription-cible="'.$this->suivante->id.'"', $html);
    }

    public function test_annee_courante_non_echue_ne_propose_pas_n_plus_un(): void
    {
        $this->courante->update([
            'name' => '2026-2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
        ]);
        $this->suivante->update([
            'name' => '2027-2028',
            'start_date' => '2027-09-01',
            'end_date' => '2028-07-31',
        ]);

        $html = $this->fiche();

        $this->assertStringNotContainsString('Réinscrire pour 2027-2028', $html);
        $this->assertStringNotContainsString('data-reinscription-cible="'.$this->suivante->id.'"', $html);
    }

    public function test_apres_bascule_de_l_annee_courante_le_cta_reste_visible(): void
    {
        $this->courante->update(['is_current' => false]);
        $this->suivante->update(['is_current' => true]);

        $html = $this->fiche();

        $this->assertStringContainsString('Réinscrire pour 2026-2027', $html);
        $this->assertStringContainsString('data-reinscription-cible="'.$this->suivante->id.'"', $html);
    }

    public function test_une_inscription_deja_existante_dans_la_cible_supprime_le_cta(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->suivante->id,
            'status' => 'en_attente',
            'workflow_step' => 'documents_complets',
        ]);

        $html = $this->fiche();

        $this->assertStringNotContainsString('Réinscrire pour 2026-2027', $html);
        $this->assertStringNotContainsString('data-reinscription-cible="'.$this->suivante->id.'"', $html);
    }
}

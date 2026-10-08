<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La fiche etudiant rendue pour un profil pedagogique.
 *
 * Constate sur esbtp-abidjan (septembre 2026) : un directeur des etudes, sans
 * aucune permission financiere, lisait sur la fiche le montant paye, le
 * pourcentage regle et tout l'onglet Finances, alors que /esbtp/paiements lui
 * repondait 403.
 */
class FicheEtudiantFinancesMasqueesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['admin.access', 'students.view', 'paiements.view', 'paiements.create'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    private function ficheVuePar(array $permissions): string
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $etudiant = ESBTPEtudiant::factory()->create();

        return $this->actingAs($user->fresh())
            ->get(route('esbtp.etudiants.show', $etudiant))
            ->assertOk()
            ->getContent();
    }

    public function test_un_profil_sans_permission_financiere_ne_voit_aucune_finance(): void
    {
        $html = $this->ficheVuePar(['admin.access', 'students.view']);

        $this->assertStringNotContainsString('data-tab="finances"', $html);
        $this->assertStringNotContainsString('id="tab-finances"', $html);
        $this->assertStringNotContainsString('Payé (FCFA)', $html);
        $this->assertStringNotContainsString('Paiements réglés', $html);
    }

    public function test_un_profil_financier_voit_l_onglet_finances(): void
    {
        $html = $this->ficheVuePar(['admin.access', 'students.view', 'paiements.view']);

        $this->assertStringContainsString('data-tab="finances"', $html);
        $this->assertStringContainsString('id="tab-finances"', $html);
    }

    public function test_une_ancienne_inscription_a_solder_peut_ouvrir_la_modale_de_paiement(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'students.view', 'paiements.view', 'paiements.create']);
        $etudiant = ESBTPEtudiant::factory()->create();
        $ancienne = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => false,
            'name' => '2024-2025',
        ]);
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $ancienne->id,
        ]);
        $categorie = ESBTPFraisCategory::factory()->create(['name' => 'Scolarite historique']);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => 100000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $html = $this->actingAs($user->fresh())
            ->get(route('esbtp.etudiants.show', $etudiant))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="etudiantPaymentModal"', $html);
        $this->assertStringContainsString(
            'onclick="prepareEtudiantPaymentModal('.$inscription->id.')"',
            $html
        );
        $this->assertStringContainsString('Enregistrer un paiement', $html);
    }
}

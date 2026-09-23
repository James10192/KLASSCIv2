<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La fiche de réinscription, ouverte au directeur des études et aux profils de
 * scolarité, qui n'ont aucune permission financière.
 *
 * Le premier masquage du bloc financier y avait emporté les variables que la
 * carte « Procéder à la réinscription », plus bas, lit aussi : la page tombait
 * en 500 pour ces profils, sur tout étudiant pas encore réinscrit.
 */
class FicheReinscriptionFinancesMasqueesTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPEtudiant $etudiant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['identity.direct_studies', 'paiements.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $classe = ESBTPClasse::factory()->create();
        $this->etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }

    private function ficheVuePar(array $permissions): string
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $this->actingAs($user->fresh())
            ->get(route('esbtp.reinscription.show', $this->etudiant->id))
            ->assertOk()
            ->getContent();
    }

    public function test_le_directeur_des_etudes_ouvre_la_fiche_sans_voir_de_montant(): void
    {
        $html = $this->ficheVuePar(['identity.direct_studies']);

        $this->assertStringNotContainsString('Situation Financière', $html);
        $this->assertStringNotContainsString('FCFA', $html);
    }

    public function test_un_profil_financier_voit_la_situation_financiere(): void
    {
        $html = $this->ficheVuePar(['identity.direct_studies', 'paiements.view']);

        $this->assertStringContainsString('Situation Financière', $html);
    }
}

<?php

namespace Tests\Feature\Etudiants;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Le diagnostic de réparation d'inscription porte les montants versés à deux
 * endroits de chaque profil : `payments` et `score`, qui les recopie pour
 * départager les doublons. Masquer le premier seul laissait passer le second.
 * Le test lit donc toute la réponse, pas une clé.
 */
class DiagnosticReparationMontantsMasquesTest extends TestCase
{
    use DatabaseTransactions;

    private const MONTANT = 123457;

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

        foreach (['admin.access', 'students.view', 'paiements.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::flush();

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => ESBTPClasse::factory()->create()->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
        ESBTPPaiement::factory()->pour($inscription)->create(['montant' => self::MONTANT]);
    }

    private function diagnosticVuPar(array $permissions): string
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $this->actingAs($user->fresh())
            ->getJson(route('esbtp.etudiants.inscriptions.repair-diagnostic', $this->etudiant))
            ->assertOk()
            ->getContent();
    }

    public function test_sans_porte_financiere_aucun_montant_ne_sort(): void
    {
        $json = $this->diagnosticVuPar(['admin.access', 'students.view']);

        $this->assertStringNotContainsString((string) self::MONTANT, $json);
        $this->assertStringContainsString('"valid_count":1', $json);
    }

    public function test_un_profil_financier_voit_le_montant(): void
    {
        $json = $this->diagnosticVuPar(['admin.access', 'students.view', 'paiements.view']);

        $this->assertStringContainsString((string) self::MONTANT, $json);
    }
}

<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * /esbtp/inscriptions rendu pour quelqu'un qui ne voit pas les montants.
 *
 * Le modal « Valider le paiement » n'est pas rendu quand les montants sont
 * masques (agent d'inscription). Le @endsection de la vue etait pose DANS ce
 * bloc conditionnel : pour ce profil la section 'content' n'etait jamais
 * fermee, Blade vidait le tampon avant le layout, et la page arrivait avec son
 * contenu AVANT le <!DOCTYPE>, hors de la mise en page (sous la barre
 * laterale, sans en-tete).
 */
class IndexDansLeLayoutMontantsMasquesTest extends TestCase
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

        foreach (['inscriptions.view', 'identity.enrollment_officer', 'admin.access'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Cache::flush();

        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_la_liste_reste_dans_le_layout_quand_les_montants_sont_masques(): void
    {
        $agent = User::factory()->create();
        $agent->givePermissionTo(['inscriptions.view', 'identity.enrollment_officer']);

        $this->assertTrue(app(\App\Services\EnrollmentAmountVisibility::class)->hideAmounts($agent));

        $html = $this->actingAs($agent)->get(route('esbtp.inscriptions.index'))->assertOk()->getContent();

        $this->assertDansLeCorpsDuLayout($html);
        $this->assertStringNotContainsString('id="modalValiderPaiement"', $html);
    }

    public function test_la_liste_reste_dans_le_layout_quand_les_montants_sont_visibles(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(['inscriptions.view', 'admin.access']);

        $html = $this->actingAs($admin)->get(route('esbtp.inscriptions.index'))->assertOk()->getContent();

        $this->assertDansLeCorpsDuLayout($html);
        $this->assertStringContainsString('id="modalValiderPaiement"', $html);
    }

    private function assertDansLeCorpsDuLayout(string $html): void
    {
        $contenu = strpos($html, 'class="dashboard-acasi"');
        $this->assertNotFalse($contenu, 'La liste des inscriptions n est pas rendue.');

        // Le layout ouvre par un commentaire, puis le DOCTYPE, puis <body> :
        // le contenu doit venir APRES les deux, jamais avant le document.
        $this->assertNotFalse(strpos($html, '<!DOCTYPE html>'));
        $this->assertLessThan($contenu, strpos($html, '<!DOCTYPE html>'), 'Le contenu est sorti avant le document.');
        $this->assertLessThan($contenu, strpos($html, '<body'), 'La liste doit etre rendue dans le corps du layout.');
    }
}

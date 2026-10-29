<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Apres une validation AJAX, /esbtp/paiements la remplace cellule par cellule
 * avec le rendu de refresh-ligne. Ce rendu doit avoir exactement les colonnes
 * de l'en-tete : une cellule « Encaissé par » manquante supprimait la derniere
 * cellule de la ligne et decalait les autres sous le mauvais titre.
 */
class RafraichirLignePaiementTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPPaiement $paiement;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['paiements.view', 'paiements.view_own', 'identity.enrollment_officer'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Cache::flush();

        $this->paiement = ESBTPPaiement::factory()
            ->pour(ESBTPInscription::factory()->create())
            ->create(['status' => 'validé']);
    }

    public function test_la_ligne_rafraichie_garde_la_colonne_encaisse_par(): void
    {
        $html = $this->ligneRafraichie($this->quiPeut(['paiements.view']));

        $this->assertStringContainsString('pi-cell-creator', $html);
        $this->assertSame(10, $this->nombreDeCellules($html));
    }

    public function test_sans_vue_globale_la_colonne_reste_absente_des_deux_cotes(): void
    {
        $html = $this->ligneRafraichie($this->quiPeut(['paiements.view_own']));

        $this->assertStringNotContainsString('pi-cell-creator', $html);
        $this->assertSame(9, $this->nombreDeCellules($html));
    }

    private function ligneRafraichie(User $user): string
    {
        $reponse = $this->actingAs($user)
            ->getJson(route('esbtp.paiements.refresh-ligne', $this->paiement->id));

        $reponse->assertOk()->assertJsonPath('success', true);

        return (string) $reponse->json('html');
    }

    private function nombreDeCellules(string $html): int
    {
        $this->assertSame(1, preg_match('/<tr\b[^>]*data-paiement-id[^>]*>(.*?)<\/tr>/s', $html, $ligne));

        return preg_match_all('/<td\b/', $ligne[1]);
    }

    private function quiPeut(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['identity.enrollment_officer'], $permissions));

        return $user;
    }
}

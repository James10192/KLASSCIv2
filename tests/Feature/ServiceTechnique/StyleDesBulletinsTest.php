<?php

namespace Tests\Feature\ServiceTechnique;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Le service technique choisit le gabarit des bulletins sans rechargement :
 * la page se rend avec les deux aperçus, l'enregistrement répond en JSON et
 * change réellement le gabarit lu par le service des bulletins.
 */
class StyleDesBulletinsTest extends TestCase
{
    use DatabaseTransactions;

    private User $st;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Role::findOrCreate('serviceTechnique', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->st = User::factory()->create();
        $this->st->assignRole('serviceTechnique');
    }

    public function test_la_page_montre_les_deux_gabarits_et_le_gabarit_actif(): void
    {
        $this->actingAs($this->st)
            ->get(route('esbtp.bulletin-style.index'))
            ->assertOk()
            ->assertSee('Style des bulletins')
            ->assertSee('gabarit-bulletin-yakro.webp', false)
            ->assertSee('gabarit-bulletin-abidjan.webp', false)
            ->assertSee('data-actif="yakro"', false);
    }

    public function test_l_enregistrement_en_json_change_le_gabarit_des_bulletins(): void
    {
        $this->actingAs($this->st)->get(route('esbtp.bulletin-style.index'));

        $this->actingAs($this->st)
            ->postJson(route('esbtp.bulletin-style.update'), ['bulletin_style' => 'abidjan'])
            ->assertOk()
            ->assertJson(['success' => true, 'style' => 'abidjan']);

        $this->assertSame('abidjan', Setting::where('key', 'bulletin_style')->value('value'));
        $this->assertSame('esbtp.bulletins.pdf-configurable-abidjan', app(\App\Services\BulletinService::class)->getBulletinTemplateView());
    }

    public function test_un_gabarit_inconnu_est_refuse(): void
    {
        $this->actingAs($this->st)
            ->postJson(route('esbtp.bulletin-style.update'), ['bulletin_style' => 'autre'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('bulletin_style');
    }

    public function test_hors_service_technique_la_page_est_refusee(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('esbtp.bulletin-style.index'))
            ->assertForbidden();
    }
}

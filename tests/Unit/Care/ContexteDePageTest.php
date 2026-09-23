<?php

namespace Tests\Unit\Care;

use App\Domain\Support\Services\AnalyseNavigateur;
use App\Domain\Support\Services\ContexteDePage;
use App\Domain\Support\Services\ModuleDeRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Ce qui part au Master avec un signalement. Chaque test ici ferme une fuite :
 * une cle hors liste, une chaine de requete, un identifiant de forme libre.
 */
class ContexteDePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('permission:module.lmd.access')->get('/_care/lmd/{jury}', fn () => 'ok')->name('_care.lmd.show');
        Route::get('/_care/notes', fn () => 'ok')->name('esbtp.notes._care');
        Route::getRoutes()->refreshNameLookups();
    }

    private function assainir(array $brut, string $ua = ''): array
    {
        $request = Request::create('/support/demandes', 'POST', [], [], [], ['HTTP_USER_AGENT' => $ua]);

        return ContexteDePage::assainir($brut, $request);
    }

    /** @test */
    public function le_module_se_lit_sur_la_route_avant_le_prefixe(): void
    {
        $this->assertSame('lmd', ModuleDeRoute::pour('_care.lmd.show'));
        $this->assertSame('notes_evaluations', ModuleDeRoute::pour('esbtp.notes._care'));
        $this->assertNull(ModuleDeRoute::pour('route.inconnue'));
        $this->assertNull(ModuleDeRoute::pour(null));
    }

    /** @test */
    public function il_ne_garde_que_ce_qui_se_verifie(): void
    {
        $c = $this->assainir([
            'route_name' => '_care.lmd.show',
            'url_path' => '/_care/lmd/12?token=secret#x',
            'entity' => ['type' => 'jury', 'id' => '12'],
            'viewport' => '390x844',
            'request_ids' => ['01J8ZQ4Y5K3M2N1P0QRSTVWXYZ', "x\ny", '<script>'],
            'extras' => ['semestre' => 2, 'cookie' => 'abc', 'html' => '<div>'],
            'cookie' => 'abc',
        ]);

        $this->assertSame('_care.lmd.show', $c['route_name']);
        $this->assertSame('lmd', $c['module']);
        $this->assertSame('/_care/lmd/12', $c['url_path']);
        $this->assertSame(['type' => 'jury', 'id' => 12], $c['entity']);
        $this->assertSame(['01J8ZQ4Y5K3M2N1P0QRSTVWXYZ'], $c['request_ids']);
        $this->assertSame(['semestre' => '2'], $c['extras']);
        $this->assertArrayNotHasKey('cookie', $c);
    }

    /** @test */
    public function un_identifiant_nul_ne_part_pas_le_master_le_refuserait_pour_toujours(): void
    {
        $c = $this->assainir(['entity' => ['type' => 'jury', 'id' => '0'], 'class_id' => '0']);

        $this->assertArrayNotHasKey('entity', $c);
        $this->assertArrayNotHasKey('class_id', $c);
    }

    /** @test */
    public function il_ecarte_une_route_inexistante_et_un_type_inconnu(): void
    {
        $c = $this->assainir([
            'route_name' => 'route.inventee',
            'entity' => ['type' => 'user', 'id' => 1],
            'module' => 'comptabilite',
            'viewport' => '9999999x1',
        ]);

        $this->assertArrayNotHasKey('route_name', $c);
        $this->assertArrayNotHasKey('entity', $c);
        $this->assertArrayNotHasKey('module', $c, 'le module se recalcule, il ne se croit pas');
        $this->assertArrayNotHasKey('viewport', $c);
    }

    /** @test */
    public function le_contexte_courant_lit_la_route_servie(): void
    {
        $request = Request::create('/_care/lmd/7');
        $route = Route::getRoutes()->match($request);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertSame(
            ['route_name' => '_care.lmd.show', 'module' => 'lmd', 'entity' => ['type' => 'jury', 'id' => 7]],
            ContexteDePage::courant($request),
        );
    }

    /** @test */
    public function il_reconnait_les_navigateurs_courants(): void
    {
        $android = AnalyseNavigateur::depuis('Mozilla/5.0 (Linux; Android 13; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36');
        $this->assertSame(['browser' => ['family' => 'Chrome', 'version' => '128'], 'os' => 'Android', 'device' => 'mobile'], $android);

        $windows = AnalyseNavigateur::depuis('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0');
        $this->assertSame('Edge', $windows['browser']['family']);
        $this->assertSame('desktop', $windows['device']);

        $this->assertSame(['browser' => ['family' => null, 'version' => null], 'os' => null, 'device' => 'desktop'], AnalyseNavigateur::depuis(null));
    }
}

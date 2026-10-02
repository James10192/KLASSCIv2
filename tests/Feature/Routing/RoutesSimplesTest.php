<?php

namespace Tests\Feature\Routing;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use Tests\TestCase;

/**
 * Les anciennes closures, passées en actions de contrôleur : mêmes réponses.
 */
class RoutesSimplesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class]);
    }

    public function test_l_accueil_renvoie_au_login(): void
    {
        $this->get('/')->assertRedirect(route('login'))->assertStatus(302);
    }

    public function test_le_jeton_csrf_est_rendu_en_json(): void
    {
        $this->get('/csrf-token-refresh')->assertOk()->assertJsonStructure(['token']);
    }

    public function test_les_anciennes_adresses_de_paiement_redirigent(): void
    {
        $this->get('/esbtp/comptabilite/paiements/42')->assertStatus(302)->assertRedirect(route('esbtp.paiements.show', 42));
        $this->get('/esbtp/comptabilite/paiements/42/edit')->assertStatus(302)->assertRedirect(route('esbtp.paiements.edit', 42));
        $this->get('/esbtp/comptabilite/paiements/42/recu')->assertStatus(302)->assertRedirect(route('esbtp.paiements.recu', 42));
    }

    public function test_la_documentation_lms_est_publique(): void
    {
        $this->getJson('/api/lms/documentation')->assertOk()->assertJsonPath('title', 'API LMS-KLASSCI Integration');
    }

    public function test_api_user_exige_une_authentification(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }
}

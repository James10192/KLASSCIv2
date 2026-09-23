<?php

namespace Tests\Unit\Services\Master;

use App\Services\GroupCacheInvalidator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GroupCacheInvalidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Le seuil d'avertissement vit dans le cache : chaque test repart d'un cache vide.
        config()->set('cache.default', 'array');
        Cache::flush();
    }

    private function configurer(?string $url = 'https://master.test/api/', ?string $jeton = 'jeton-tenant', ?string $code = 'esbtp-yakro'): void
    {
        config()->set('services.master.api_url', $url);
        config()->set('services.master.api_token', $jeton);
        config()->set('app.tenant_code', $code);
    }

    /** @test */
    public function il_poste_vers_le_master_avec_le_jeton_du_tenant(): void
    {
        $this->configurer();
        Http::fake(['master.test/*' => Http::response(['ok' => true], 200)]);

        app(GroupCacheInvalidator::class)->invalidate('paiement_validated');

        // L'appel part après la réponse, c'est-à-dire à la terminaison de l'application.
        Http::assertNothingSent();
        $this->app->terminate();

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://master.test/api/tenants/esbtp-yakro/cache/invalidate'
                && $request->hasHeader('Authorization', 'Bearer jeton-tenant')
                && $request['trigger'] === 'paiement_validated';
        });
    }

    /** @test */
    public function il_ne_lit_pas_les_anciennes_cles_url_et_token(): void
    {
        // Les clés que le service lisait à tort : elles ne doivent plus suffire.
        $this->configurer(null, null);
        config()->set('services.master.url', 'https://master.test/api');
        config()->set('services.master.token', 'jeton-tenant');
        Http::fake();
        Log::spy();

        app(GroupCacheInvalidator::class)->invalidate('paiement_validated');
        $this->app->terminate();

        Http::assertNothingSent();
    }

    /** @test */
    public function sans_configuration_rien_ne_part_et_un_seul_avertissement_est_journalise(): void
    {
        $this->configurer(null);
        Http::fake();
        Log::spy();

        $invalidator = app(GroupCacheInvalidator::class);
        $invalidator->invalidate('paiement_validated');
        $invalidator->invalidate('paiement_cancelled');
        $this->app->terminate();

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return str_contains($message, 'configuration master absente')
                    && $context['manquants'] === ['services.master.api_url (MASTER_API_URL)'];
            });
    }

    /** @test */
    public function un_code_etablissement_de_repli_default_compte_comme_manquant(): void
    {
        // config/app.php retombe sur 'default' quand TENANT_CODE n'est pas renseigné.
        $this->configurer('https://master.test/api/', 'jeton-tenant', 'default');
        Http::fake();
        Log::spy();

        app(GroupCacheInvalidator::class)->invalidate('paiement_validated');
        $this->app->terminate();

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $context['manquants'] === ['app.tenant_code (TENANT_CODE)']);
    }

    /** @test */
    public function une_reponse_en_erreur_du_master_est_journalisee_avec_son_statut(): void
    {
        $this->configurer();
        Http::fake(['master.test/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);
        Log::spy();

        app(GroupCacheInvalidator::class)->invalidate('paiement_validated');
        $this->app->terminate();

        Http::assertSentCount(1);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => str_contains($message, 'GroupCacheInvalidator failed')
                && $context['status'] === 401
                && $context['url'] === 'https://master.test/api/tenants/esbtp-yakro/cache/invalidate');
    }
}

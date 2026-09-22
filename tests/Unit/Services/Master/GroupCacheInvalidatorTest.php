<?php

namespace Tests\Unit\Services\Master;

use App\Services\GroupCacheInvalidator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ReflectionProperty;
use Tests\TestCase;

class GroupCacheInvalidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Le signalement est unique par processus : chaque test repart d'un état neuf.
        $drapeau = new ReflectionProperty(GroupCacheInvalidator::class, 'configurationManquanteSignalee');
        $drapeau->setAccessible(true);
        $drapeau->setValue(null, false);
    }

    /** @test */
    public function il_poste_vers_le_master_avec_le_jeton_du_tenant(): void
    {
        config()->set('services.master.api_url', 'https://master.test/api/');
        config()->set('services.master.api_token', 'jeton-tenant');
        config()->set('app.tenant_code', 'esbtp-yakro');

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
        config()->set('services.master.url', 'https://master.test/api');
        config()->set('services.master.token', 'jeton-tenant');
        config()->set('services.master.api_url', null);
        config()->set('services.master.api_token', null);
        config()->set('app.tenant_code', 'esbtp-yakro');

        Http::fake();
        Log::spy();

        app(GroupCacheInvalidator::class)->invalidate('paiement_validated');
        $this->app->terminate();

        Http::assertNothingSent();
    }

    /** @test */
    public function sans_configuration_rien_ne_part_et_un_avertissement_unique_est_journalise(): void
    {
        config()->set('services.master.api_url', null);
        config()->set('services.master.api_token', 'jeton-tenant');
        config()->set('app.tenant_code', 'esbtp-yakro');

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
}

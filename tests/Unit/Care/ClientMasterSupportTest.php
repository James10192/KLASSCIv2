<?php

namespace Tests\Unit\Care;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Services\Care\ClientMasterSupport;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Le client vers KLASSCI Care : bon identifiant, bon en-tete, delais courts,
 * et un coupe-circuit qui empeche une instance d'attendre le Master a chaque
 * page quand il est tombe.
 */
class ClientMasterSupportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.api_token', 'ANCIEN-JETON-SANS-PORTEE');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
    }

    /** @test */
    public function il_envoie_l_identifiant_dedie_en_en_tete_avec_la_cle_et_le_code_de_suivi(): void
    {
        Http::fake(['master.test/*' => Http::response(['reference' => 'KC-2026-000001'], 201)]);

        $r = app(ClientMasterSupport::class)->creerTicket(['report' => []], 'cle-0001', '01J8ZQ4Y5K3M2N1P0QRSTVWXYZ');

        $this->assertSame('KC-2026-000001', $r['reference']);
        Http::assertSent(function (Request $req) {
            return $req->url() === 'https://master.test/api/v1/support/tickets'
                && $req->method() === 'POST'
                && $req->hasHeader('Authorization', 'Bearer kc_abcdefghijkl_'.str_repeat('A', 40))
                && $req->hasHeader('Idempotency-Key', 'cle-0001')
                && $req->hasHeader('X-Request-ID', '01J8ZQ4Y5K3M2N1P0QRSTVWXYZ')
                && ! str_contains($req->url(), 'token')
                && ! str_contains(json_encode($req->headers()), 'ANCIEN-JETON');
        });
    }

    /** @test */
    public function une_erreur_serveur_ouvre_le_coupe_circuit(): void
    {
        Http::fake(['master.test/*' => Http::response([], 503)]);
        $client = app(ClientMasterSupport::class);

        foreach ([1, 2] as $_) {
            try {
                $client->creerTicket([], 'cle-0002');
                $this->fail('Indisponibilité attendue.');
            } catch (MasterSupportIndisponible) {
            }
        }

        Http::assertSentCount(1);
    }

    /** @test */
    public function un_refus_est_distingue_d_une_indisponibilite(): void
    {
        Http::fake(['master.test/*' => Http::response([
            'error' => 'validation_failed', 'message' => 'Mal formée.', 'errors' => ['report.description' => ['Trop court.']],
        ], 422)]);

        try {
            app(ClientMasterSupport::class)->creerTicket([], 'cle-0003');
            $this->fail('Refus attendu.');
        } catch (MasterSupportRefus $e) {
            $this->assertSame(422, $e->statut);
            $this->assertSame('validation_failed', $e->codeErreur);
            $this->assertArrayHasKey('report.description', $e->erreurs);
        }
    }

    /** @test */
    public function une_demande_introuvable_rend_null(): void
    {
        Http::fake(['master.test/*' => Http::response(['error' => 'not_found'], 404)]);

        $this->assertNull(app(ClientMasterSupport::class)->afficher('KC-2026-000009', 42));
    }

    /** @test */
    public function sans_identifiant_dedie_il_n_appelle_jamais_le_master(): void
    {
        config()->set('services.master.support_token', null);
        Http::fake();

        $this->assertSame([], app(ClientMasterSupport::class)->fonctionnalites());
        Http::assertNothingSent();
    }

    /** @test */
    public function les_fonctionnalites_sont_mises_en_cache_et_survivent_a_une_panne(): void
    {
        Http::fakeSequence()
            ->push(['fonctionnalites' => ['support_widget' => true, 'support_customer_portal' => false]])
            ->push([], 500);
        $client = app(ClientMasterSupport::class);

        $this->assertTrue($client->fonctionnaliteActive('support_widget'));
        $this->assertTrue($client->fonctionnaliteActive('support_widget'));
        Http::assertSentCount(1);

        // Le cache expire, le Master tombe : la derniere reponse connue tient.
        Cache::forget('care:master:fonctionnalites');
        $this->assertTrue($client->fonctionnaliteActive('support_widget'));
        $this->assertFalse($client->fonctionnaliteActive('support_customer_portal'));
    }
}

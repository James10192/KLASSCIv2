<?php

namespace Tests\Unit\Care;

use App\Domain\Support\Exceptions\MasterSupportIndisponible;
use App\Domain\Support\Exceptions\MasterSupportRefus;
use App\Services\Care\ClientMasterSupport;
use App\Domain\Support\Exceptions\DebitLimiteAtteint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
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
        Cache::forget('care:master:bootstrap');
        $this->assertTrue($client->fonctionnaliteActive('support_widget'));
        $this->assertFalse($client->fonctionnaliteActive('support_customer_portal'));
    }

    /** @test */
    public function les_limites_de_saisie_viennent_du_master(): void
    {
        Http::fake(['*' => Http::response(['fonctionnalites' => [], 'limites' => ['description_min' => 25, 'description_max' => 3000, 'pieces_max' => 4]])]);

        // Ce que le Master ne donne pas retombe sur les valeurs locales.
        $this->assertSame([
            'description_min' => 25, 'description_max' => 3000, 'reponse_min' => 2,
            'piece_octets_max' => min(5 * 1024 * 1024, UploadedFile::getMaxFilesize()), 'pieces_max' => 4,
        ], app(ClientMasterSupport::class)->limites());
    }

    /** @test */
    public function un_identifiant_refuse_est_une_indisponibilite_pas_un_refus(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unauthenticated'], 401)]);

        $this->expectException(MasterSupportIndisponible::class);
        try {
            app(ClientMasterSupport::class)->creerTicket(['report' => []], 'cle');
        } finally {
            $this->assertTrue(app(ClientMasterSupport::class)->coupeCircuitOuvert());
        }
    }

    /** @test */
    public function la_taille_annoncee_ne_depasse_jamais_ce_que_php_accepte(): void
    {
        Http::fake(['*' => Http::response(['fonctionnalites' => [], 'limites' => ['piece_octets_max' => PHP_INT_MAX]])]);

        $this->assertSame(UploadedFile::getMaxFilesize(), app(ClientMasterSupport::class)->limites()['piece_octets_max']);
    }

    /** @test */
    public function une_limite_de_debit_n_ouvre_pas_le_coupe_circuit(): void
    {
        Http::fake(['*' => Http::response(['error' => 'too_many_requests'], 429)]);
        $client = app(ClientMasterSupport::class);

        try {
            $client->joindre('KC-2026-000042', 42, 'PNG', 'a.png', null, 'cle');
            $this->fail('Limite de debit attendue.');
        } catch (DebitLimiteAtteint) {
        }

        $this->assertFalse($client->coupeCircuitOuvert());
    }

    /** @test */
    public function un_transfert_qui_echoue_n_ouvre_pas_le_coupe_circuit_mais_un_appel_ordinaire_si(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
        $client = app(ClientMasterSupport::class);

        foreach ([fn () => $client->joindre('KC-2026-000042', 42, 'PNG', 'a.png', null, 'cle'),
                  fn () => $client->piece('KC-2026-000042', 42, 'mine', 7)] as $transfert) {
            try {
                $transfert();
                $this->fail('Indisponibilité attendue.');
            } catch (MasterSupportIndisponible) {
            }
        }
        $this->assertFalse($client->coupeCircuitOuvert());

        try {
            $client->afficher('KC-2026-000042', 42);
        } catch (MasterSupportIndisponible) {
        }
        $this->assertTrue($client->coupeCircuitOuvert());
    }
}

<?php

namespace Tests\Feature\Care;

use App\Services\Care\ClientMasterSupport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InterventionRoutingClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_interventions_sont_lues_avec_identite_instance_et_sans_corps_dossier(): void
    {
        Cache::flush();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));

        Http::fake([
            'master.test/api/v1/support/interventions*' => Http::response([
                'data' => [
                    ['id' => 7, 'reference' => 'KC-2026-000042', 'permission_requise' => 'inscriptions.validate',
                        'utilisateur_cible' => null, 'etat' => 'PENDING_ROUTING'],
                ],
                'meta' => ['page' => 1, 'pages' => 1, 'total' => 1],
            ]),
        ]);

        $data = app(ClientMasterSupport::class)->interventionsAOrienter();

        $this->assertSame('inscriptions.validate', $data['data'][0]['permission_requise']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/support/interventions?page=1')
            && $req->hasHeader('Authorization', 'Bearer kc_abcdefghijkl_'.str_repeat('A', 40)));
    }
}

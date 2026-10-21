<?php

namespace Tests\Unit\Domain\Assistant;

use App\Http\Middleware\LogRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class McpBridgeLogPrivacyTest extends TestCase
{
    public function test_linking_code_never_reaches_logs_during_claim_or_administrator_approval(): void
    {
        config()->set('logging.corps_des_requetes', true);
        Log::spy();

        foreach (['/nanan/mcp/connections/claim', '/nanan/mcp/connections/approve'] as $path) {
            $request = Request::create($path, 'POST', [
                'code' => 'SECRET_PAIRING_CODE_SHOULD_NOT_BE_LOGGED',
                'user_id' => 123,
                'consent' => '1',
                'identity_verified' => '1',
            ]);
            (new LogRequests())->handle($request, fn () => response('ok'));
        }

        Log::shouldHaveReceived('info')->withArgs(
            fn ($message, $context) =>
                $message === 'Incoming request'
                && ($context['input']['_masque'] ?? null) === 'corps non journalise (route publique)'
                && ! str_contains(json_encode($context), 'SECRET_PAIRING_CODE_SHOULD_NOT_BE_LOGGED')
                && ! str_contains(json_encode($context), 'user_id')
        )->twice();
    }

    public function test_signed_mcp_body_never_reaches_global_logs_even_in_verbose_mode(): void
    {
        config()->set('logging.corps_des_requetes', true);
        Log::spy();

        $request = Request::create('/api/mcp-bridge', 'POST', [
            'issuer' => 'https://demo.auth0.com/',
            'subject' => 'auth0|sensitive-user',
            'organization' => 'org_school123',
            'arguments' => ['name' => 'classe réservée'],
            'signature' => 'secret-example',
        ]);

        (new LogRequests())->handle($request, fn () => response('ok'));

        Log::shouldHaveReceived('info')->withArgs(
            fn ($message, $context) =>
                $message === 'Incoming request'
                && ($context['input']['_masque'] ?? null) === 'corps non journalise (route publique)'
                && ! str_contains(json_encode($context), 'sensitive-user')
                && ! str_contains(json_encode($context), 'classe réservée')
                && ! str_contains(json_encode($context), 'secret-example')
        )->once();
    }
}

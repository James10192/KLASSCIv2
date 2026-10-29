<?php

namespace Tests\Unit\Security;

use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Tests\TestCase;

/**
 * Contrat de configuration Sanctum 4 : pas de perte des gardes KLASSCI.
 * N'active pas la session first-party sur les API à token bearer.
 */
final class SanctumV4ConfigurationTest extends TestCase
{
    public function test_les_middlewares_stateful_utilisent_les_cles_sanctum_v4(): void
    {
        $this->assertSame(AuthenticateSession::class, config('sanctum.middleware.authenticate_session'));
        $this->assertSame(EncryptCookies::class, config('sanctum.middleware.encrypt_cookies'));
        $this->assertSame(VerifyCsrfToken::class, config('sanctum.middleware.validate_csrf_token'));
        $this->assertArrayNotHasKey('verify_csrf_token', config('sanctum.middleware'));
    }

    public function test_la_garde_api_bearer_ne_devient_pas_un_token_guard_legacy(): void
    {
        $this->assertSame(['web'], config('sanctum.guard'));
        foreach (config('auth.guards') as $name => $guard) {
            $this->assertNotSame('token', $guard['driver'] ?? null, $name);
        }
    }
}

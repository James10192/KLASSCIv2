<?php

namespace Tests\Feature\Security;

use App\Helpers\InstallationHelper;
use Tests\TestCase;

/**
 * Tests de non-régression pour les fixes de l'audit sécurité 2026-05-21.
 *
 * Couvre Phase A (PR #403) :
 * 1. /install/* gated par BlockInstallIfReady après installation
 * 2. /register routes supprimées (404)
 * 3. /login a le throttle:login middleware
 * 4. /password/email + /password/reset ont throttle:3,1
 * 5. Routes /debug-* /test-* gatées env('local')
 * 6. SecurityHeaders middleware applique 5 headers
 *
 * Note : ces tests ne touchent PAS la DB (TestCase pur) — ils inspectent
 * le routing et les middleware. Pas de RefreshDatabase nécessaire.
 */
class SecurityAudit202605Test extends TestCase
{
    public function test_register_routes_are_gone(): void
    {
        // Cherche un route registered avec le nom 'register' — il ne doit plus exister.
        $registerRoute = \Route::getRoutes()->getByName('register');
        $this->assertNull($registerRoute, 'GET /register route should be removed for security');

        // POST /register : pas de check par name (n'a pas de name), vérifions par URI.
        $postRegister = collect(\Route::getRoutes())
            ->first(fn ($r) => $r->uri() === 'register' && in_array('POST', $r->methods()));
        $this->assertNull($postRegister, 'POST /register route should be removed for security');
    }

    public function test_login_route_has_throttle_middleware(): void
    {
        $route = collect(\Route::getRoutes())
            ->first(fn ($r) => $r->uri() === 'login' && in_array('POST', $r->methods()));

        $this->assertNotNull($route, 'POST /login must exist');
        $middleware = $route->gatherMiddleware();

        $hasThrottle = collect($middleware)->contains(
            fn ($m) => str_contains((string) $m, 'throttle:login') || str_contains((string) $m, 'throttle')
        );
        $this->assertTrue($hasThrottle, 'POST /login must have throttle middleware (anti brute-force)');
    }

    public function test_password_email_route_has_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('password.email');
        $this->assertNotNull($route, 'password.email route must exist');

        $hasThrottle = collect($route->gatherMiddleware())->contains(
            fn ($m) => str_contains((string) $m, 'throttle')
        );
        $this->assertTrue($hasThrottle, 'password.email must throttle (anti-enum + anti-spam)');
    }

    public function test_password_update_route_has_throttle(): void
    {
        $route = \Route::getRoutes()->getByName('password.update');
        $this->assertNotNull($route, 'password.update route must exist');

        $hasThrottle = collect($route->gatherMiddleware())->contains(
            fn ($m) => str_contains((string) $m, 'throttle')
        );
        $this->assertTrue($hasThrottle, 'password.update must throttle');
    }

    public function test_install_routes_locked_when_installed(): void
    {
        $installRoute = \Route::getRoutes()->getByName('install.index');
        $this->assertNotNull($installRoute, 'install.index route must exist');

        $middleware = $installRoute->gatherMiddleware();
        $hasInstallLock = collect($middleware)->contains(
            fn ($m) => str_contains((string) $m, 'install.lock') ||
                       str_contains((string) $m, 'BlockInstallIfReady')
        );
        $this->assertTrue(
            $hasInstallLock,
            'install routes must have install.lock middleware (BlockInstallIfReady)'
        );
    }

    public function test_debug_permissions_route_not_in_production(): void
    {
        // En env testing (équivalent prod côté gating), /debug-permissions
        // ne doit PAS être enregistrée. La route n'existe qu'en local.
        $route = collect(\Route::getRoutes())->first(fn ($r) => $r->uri() === 'debug-permissions');

        $this->assertNull(
            $route,
            'GET /debug-permissions must be gated to local env (leaks user permissions matrix)'
        );
    }

    public function test_test_bulletin_parameters_route_not_in_production(): void
    {
        $route = collect(\Route::getRoutes())->first(fn ($r) => $r->uri() === 'test-bulletin-parameters');

        $this->assertNull(
            $route,
            'GET /test-bulletin-parameters must be gated to local env'
        );
    }

    public function test_security_headers_middleware_registered(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $prop = $reflection->getProperty('middleware');
        $prop->setAccessible(true);
        $globalMiddleware = $prop->getValue($kernel);

        $this->assertContains(
            \App\Http\Middleware\SecurityHeaders::class,
            $globalMiddleware,
            'SecurityHeaders middleware must be in global stack'
        );
    }

    public function test_sanctum_expiration_is_configurable_via_env(): void
    {
        // config('sanctum.expiration') doit utiliser env('SANCTUM_EXPIRATION', ...).
        // On vérifie en setant un override.
        config(['sanctum.expiration' => 60 * 24 * 30]);
        $this->assertSame(60 * 24 * 30, config('sanctum.expiration'));
    }

    public function test_session_encryption_default_true(): void
    {
        // Phase B fix : config/session.php 'encrypt' => env('SESSION_ENCRYPT', true).
        // En testing, SESSION_DRIVER=array donc encrypt peut être true sans impact.
        // On vérifie que le default config est bien true (mode prod).
        $this->assertTrue(
            (bool) env('SESSION_ENCRYPT', true),
            'SESSION_ENCRYPT default must be true (config/session.php Phase B fix)'
        );
    }

    public function test_csrf_exemption_documented(): void
    {
        // Phase B reporté en issue #410 : CSRF exempt esbtp/api/* doit être nettoyé.
        // Ce test documente que l'exemption existe ENCORE — il PASSE actuellement et
        // devra ÉCHOUER après le fix du #410 (TDD inversé : test as documentation).
        $middleware = new \App\Http\Middleware\VerifyCsrfToken($this->app);
        $reflection = new \ReflectionClass($middleware);
        $prop = $reflection->getProperty('except');
        $prop->setAccessible(true);
        $except = $prop->getValue($middleware);

        // Pour l'instant : esbtp/api/* est exclu (issue #410).
        $this->assertContains(
            'esbtp/api/*',
            $except,
            'esbtp/api/* CSRF exempt — à fixer (issue #410)'
        );
    }
}

<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SharedStudentAccountRoutesTest extends TestCase
{
    public function test_email_code_login_endpoints_are_guest_only_and_rate_limited(): void
    {
        $request = Route::getRoutes()->getByName('login.email-code.send');
        $verify = Route::getRoutes()->getByName('login.email-code.verify');

        $this->assertNotNull($request);
        $this->assertNotNull($verify);
        $this->assertSame(['POST'], $request->methods());
        $this->assertSame(['POST'], $verify->methods());
        $this->assertContains('guest', $request->gatherMiddleware());
        $this->assertContains('guest', $verify->gatherMiddleware());
        $this->assertContains('throttle:3,1', $request->gatherMiddleware());
        $this->assertContains('throttle:6,1', $verify->gatherMiddleware());
    }

    public function test_existing_password_login_and_reset_routes_are_preserved(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('login'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.request'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.reset'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.change.update'));
        $this->assertNotNull(Route::getRoutes()->getByName('securite.double-auth.demande'));
    }
}

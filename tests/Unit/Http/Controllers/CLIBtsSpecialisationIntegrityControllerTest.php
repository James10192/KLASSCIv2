<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\API\CLI\CLIBtsSpecialisationIntegrityController;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class CLIBtsSpecialisationIntegrityControllerTest extends TestCase
{
    public function test_transient_web_token_is_rejected(): void
    {
        $this->assertFalse($this->hasAbility(new TransientToken(), true, 'cli:admin'));
    }

    public function test_personal_access_token_requires_ability_and_permission(): void
    {
        $token = new PersonalAccessToken(['abilities' => ['cli:read']]);

        $this->assertTrue($this->hasAbility($token, true, 'cli:read'));
        $this->assertFalse($this->hasAbility($token, false, 'cli:read'));
        $this->assertFalse($this->hasAbility($token, true, 'cli:admin'));
    }

    private function hasAbility(object $token, bool $hasPermission, string $ability): bool
    {
        $user = new class($token, $hasPermission) {
            public function __construct(private object $token, private bool $hasPermission)
            {
            }

            public function currentAccessToken(): object
            {
                return $this->token;
            }

            public function can(string $permission): bool
            {
                return $permission === 'inscriptions.specialisation.manage' && $this->hasPermission;
            }
        };

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $controller = (new ReflectionClass(CLIBtsSpecialisationIntegrityController::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($controller, 'hasCliAbility');

        return $method->invoke($controller, $request, $ability);
    }
}

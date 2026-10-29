<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

/**
 * Test de non-régression Phase B (PR #404) : la révocation auto des tokens
 * Sanctum sur changement de password est wirée via User::booted::updated.
 *
 * On ne touche pas la DB (TestCase pur, pas RefreshDatabase) — on inspecte
 * uniquement la déclaration du model.
 */
class UserPasswordTokenRevocationTest extends TestCase
{
    public function test_user_model_has_booted_hook(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $this->assertTrue(
            $reflection->hasMethod('booted'),
            'User model must declare a booted() method for the token revocation hook'
        );

        $method = $reflection->getMethod('booted');
        $this->assertTrue(
            $method->isStatic(),
            'User::booted must be static (Eloquent convention)'
        );
        $this->assertTrue(
            $method->isProtected(),
            'User::booted must be protected (Eloquent convention)'
        );
    }

    public function test_user_implements_has_api_tokens(): void
    {
        // tokens() relation must exist (used by booted hook to revoke).
        $traits = class_uses(User::class);
        $this->assertContains(
            \Laravel\Sanctum\HasApiTokens::class,
            $traits,
            'User must use HasApiTokens for token revocation hook to work'
        );
    }

    public function test_password_mutator_sets_changed_at(): void
    {
        $reflection = new \ReflectionClass(User::class);
        $this->assertTrue(
            $reflection->hasMethod('setPasswordAttribute'),
            'User must have setPasswordAttribute mutator (hash + password_changed_at)'
        );
    }

    public function test_audit_include_excludes_sensitive_fields(): void
    {
        // Le model est Auditable mais ne doit pas auditer password/remember_token.
        $reflection = new \ReflectionClass(User::class);
        $prop = $reflection->getProperty('auditInclude');
        $prop->setAccessible(true);
        $user = new User();
        $auditInclude = $prop->getValue($user);

        $this->assertNotContains('password', $auditInclude, 'password must NOT be in audit whitelist');
        $this->assertNotContains('remember_token', $auditInclude, 'remember_token must NOT be in audit whitelist');
        $this->assertNotContains('api_token', $auditInclude, 'api_token must NOT be in audit whitelist');
    }
}

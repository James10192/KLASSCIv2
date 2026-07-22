<?php

namespace Tests\Unit\Services\Chatbot\Tools;

use App\Services\Chatbot\Tools\ChatbotTool;
use Mockery;
use Tests\TestCase;

class ChatbotToolAuthorizationTest extends TestCase
{
    public function test_unconfigured_tool_is_not_exposed_or_executed(): void
    {
        config()->set('chatbot.tools', []);
        $tool = new AuthorizationSpyTool();
        $user = Mockery::mock();

        $this->assertFalse($tool->isAvailableFor($user));
        $this->assertSame(['error' => 'Outil indisponible.'], $tool->executeAuthorized([], $user));
        $this->assertFalse($tool->executed);
    }

    public function test_tool_requires_its_configured_canonical_permission_at_execution_time(): void
    {
        config()->set('chatbot.tools.authorization_spy', [
            'enabled' => true,
            'any_permissions' => ['notes.view'],
        ]);
        $tool = new AuthorizationSpyTool();
        $deniedUser = Mockery::mock();
        $deniedUser->shouldReceive('can')->with('notes.view')->andReturnFalse();

        $this->assertFalse($tool->isAvailableFor($deniedUser));
        $this->assertSame(['error' => 'Outil indisponible.'], $tool->executeAuthorized([], $deniedUser));
        $this->assertFalse($tool->executed);

        $allowedUser = Mockery::mock();
        $allowedUser->shouldReceive('can')->with('notes.view')->twice()->andReturnTrue();

        $this->assertTrue($tool->isAvailableFor($allowedUser));
        $this->assertSame(['results' => [], 'count' => 0], $tool->executeAuthorized([], $allowedUser));
        $this->assertTrue($tool->executed);
    }

    public function test_sensitive_tools_remain_disabled_by_default_and_do_not_expose_http_status(): void
    {
        config()->set('chatbot.tools.search_payments.enabled', false);
        $tool = new AuthorizationSpyTool();
        $user = Mockery::mock();

        $this->assertSame(['error' => 'Outil indisponible.'], $tool->executeAuthorized([], $user));
    }

    public function test_config_uses_only_registered_permissions_for_chatbot_tools(): void
    {
        $permissions = config('permissions.permissions');

        foreach (config('chatbot.tools') as $tool) {
            foreach (array_merge($tool['all_permissions'] ?? [], $tool['any_permissions'] ?? []) as $permission) {
                $this->assertArrayHasKey($permission, $permissions);
            }
        }
    }
}

class AuthorizationSpyTool extends ChatbotTool
{
    public bool $executed = false;

    public function name(): string { return 'authorization_spy'; }
    public function description(): string { return 'Test'; }
    public function parameters(): array { return ['type' => 'object', 'properties' => []]; }

    public function execute(array $args, $user): array
    {
        $this->executed = true;

        return ['results' => [], 'count' => 0];
    }
}

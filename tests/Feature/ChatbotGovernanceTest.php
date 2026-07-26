<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatbotGovernanceTest extends TestCase
{
    public function test_chatbot_routes_are_throttled(): void
    {
        $route = \Route::getRoutes()->getByName('chatbot.message.stream');

        $this->assertNotNull($route);
        $this->assertTrue(
            collect($route->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'throttle')),
            'Les routes chatbot doivent avoir un throttle dedie.'
        );
    }

    public function test_chatbot_action_approval_routes_exist(): void
    {
        $approve = \Route::getRoutes()->getByName('chatbot.actions.approve');
        $reject = \Route::getRoutes()->getByName('chatbot.actions.reject');

        $this->assertNotNull($approve);
        $this->assertNotNull($reject);
        $this->assertContains('POST', $approve->methods());
        $this->assertContains('POST', $reject->methods());
        $this->assertTrue(collect($approve->gatherMiddleware())->contains(fn ($middleware) => str_contains((string) $middleware, 'throttle')));
    }

    public function test_chatbot_streaming_audits_tool_calls(): void
    {
        $content = file_get_contents(app_path('Services/Chatbot/ChatbotService.php'));

        $this->assertStringContainsString('protected function auditToolCalls', $content);
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'auditToolCalls($conversation'));
        $this->assertStringContainsString("'last_tool_calls' => \$agentResponse['tool_calls'] ?? []", $content);
        $this->assertStringContainsString("'channel' => request()->expectsJson() ? 'json' : 'stream'", $content);
    }

    public function test_chatbot_action_log_supports_approval_fields(): void
    {
        $model = file_get_contents(app_path('Models/ChatbotActionLog.php'));
        $migration = collect(glob(database_path('migrations/*_add_approval_fields_to_chatbot_actions_log_table.php')))
            ->map(fn ($path) => file_get_contents($path))
            ->implode("\n");

        $this->assertStringContainsString("'approved_by'", $model);
        $this->assertStringContainsString("'idempotency_key'", $model);
        $this->assertStringContainsString('approvedBy()', $model);
        $this->assertStringContainsString('scopeProposed', $model);
        $this->assertStringContainsString('scopeExpired', $model);

        $this->assertStringContainsString("ENUM('success','failed','pending','proposed','approved','rejected','executed','expired')", $migration);
        $this->assertStringContainsString("string('idempotency_key', 80)", $migration);
        $this->assertStringContainsString("foreignId('approved_by')", $migration);
    }

    public function test_mandatory_fee_category_flow_requires_approval_before_execution(): void
    {
        $content = file_get_contents(app_path('Http/Controllers/ChatbotController.php'));

        $this->assertStringContainsString("'status' => 'proposed'", $content);
        $this->assertStringContainsString("'approval_required' => true", $content);
        $this->assertStringContainsString('public function approveAction', $content);
        $this->assertStringContainsString('public function rejectAction', $content);
        $this->assertStringContainsString('protected function executeApprovedAction', $content);
        $this->assertStringContainsString("'status' => 'executed'", $content);
        $this->assertStringContainsString('DB::beginTransaction();', $content);
    }
}

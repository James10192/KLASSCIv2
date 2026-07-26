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

    public function test_chatbot_streaming_audits_tool_calls(): void
    {
        $content = file_get_contents(app_path('Services/Chatbot/ChatbotService.php'));

        $this->assertStringContainsString('protected function auditToolCalls', $content);
        $this->assertGreaterThanOrEqual(2, substr_count($content, 'auditToolCalls($conversation'));
        $this->assertStringContainsString("'last_tool_calls' => \$agentResponse['tool_calls'] ?? []", $content);
        $this->assertStringContainsString("'channel' => request()->expectsJson() ? 'json' : 'stream'", $content);
    }
}

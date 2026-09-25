<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatbotGovernanceTest extends TestCase
{
    /** Les cinq scripts de public/js/assistant/, dans l'ordre de chargement. */
    private function scriptsAssistant(): string
    {
        return collect(['noyau', 'markdown', 'rendus', 'vue', 'composant'])
            ->map(fn ($nom) => file_get_contents(public_path("js/assistant/{$nom}.js")))
            ->implode("\n");
    }

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
        $this->assertStringContainsString('configure_mandatory_frais', $content);
        $this->assertStringContainsString('protected function executeFraisConfigAction', $content);
        $this->assertStringContainsString('protected function requiredPermissionForAction', $content);
        $this->assertStringContainsString("'configure_mandatory_frais' => 'frais.configure'", $content);
        $this->assertStringContainsString("'status' => 'executed'", $content);
        $this->assertStringContainsString('DB::beginTransaction();', $content);
    }

    public function test_assistant_renders_and_executes_approval_requests(): void
    {
        $content = $this->scriptsAssistant();

        // Les demandes de validation arrivent en partie data-approval-request (ou display_type
        // approval_request dans l'historique) et appellent les routes approve / reject en POST.
        $this->assertStringContainsString("'approval-request': function (data, ctx)", $content);
        $this->assertStringContainsString('approval.approve_url', $content);
        $this->assertStringContainsString('approval.reject_url', $content);
        $this->assertStringContainsString('deciderAction: function (url, approuver)', $content);
        $this->assertStringContainsString("method: methode || 'POST'", $content);
        $this->assertStringContainsString("type.replace(/_/g, '-')", $content);
    }

    public function test_assistant_sanitizes_model_text_and_never_injects_data_as_html(): void
    {
        $content = $this->scriptsAssistant();

        $this->assertStringContainsString('window.DOMPurify.sanitize(', $content);
        $this->assertStringContainsString('return echapper(texte)', $content);
        $this->assertStringNotContainsString('.innerHTML', $content);
        $this->assertStringContainsString("integrity: 'sha384-", $content);
    }

    public function test_old_widget_is_gone(): void
    {
        $this->assertFileDoesNotExist(public_path('js/chatbot-widget.js'));
        $this->assertFileDoesNotExist(public_path('css/chatbot-widget.css'));
        $this->assertFileDoesNotExist(resource_path('views/components/chatbot/widget.blade.php'));

        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('<x-chatbot.assistant />', $layout);
        $this->assertStringNotContainsString('KLASSCI_CHATBOT_CONFIG', $layout);
    }
}

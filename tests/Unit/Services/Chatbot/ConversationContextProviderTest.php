<?php

namespace Tests\Unit\Services\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\User;
use App\Services\Chatbot\ConversationContextProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConversationContextProviderTest extends TestCase
{
    use RefreshDatabase;

    protected ConversationContextProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ConversationContextProvider();
    }

    public function test_summary_block_returns_null_when_no_context(): void
    {
        $conversation = $this->makeConversation();

        $this->assertNull($this->provider->summaryBlock($conversation));
    }

    public function test_update_ignores_empty_results(): void
    {
        $conversation = $this->makeConversation();

        $this->provider->updateFromToolResult($conversation, 'search_classes', [], [
            'results' => [],
            'count' => 0,
        ]);

        $this->assertNull($this->provider->summaryBlock($conversation->fresh()));
    }

    public function test_update_ignores_error_results(): void
    {
        $conversation = $this->makeConversation();

        $this->provider->updateFromToolResult($conversation, 'search_classes', [], [
            'error' => 'Something broke',
        ]);

        $this->assertNull($this->provider->summaryBlock($conversation->fresh()));
    }

    public function test_update_stores_minimal_summary(): void
    {
        $conversation = $this->makeConversation();

        $this->provider->updateFromToolResult($conversation, 'search_classes', [
            'filiere' => 'BTP',
            'has_places' => true,
        ], [
            'results' => [
                ['id' => 1, 'nom' => 'BTP L1'],
                ['id' => 2, 'nom' => 'BTP L2'],
            ],
            'count' => 2,
            'total' => 5,
        ]);

        $summary = $this->provider->summaryBlock($conversation->fresh());

        $this->assertStringContainsString('tool=search_classes', $summary);
        $this->assertStringContainsString('filiere=BTP', $summary);
        $this->assertStringContainsString('has_places=true', $summary);
        $this->assertStringContainsString('count=2', $summary);
        $this->assertStringContainsString('ids=[1,2]', $summary);
        $this->assertStringContainsString('"BTP L1"', $summary);
    }

    public function test_update_caps_ids_at_max_sample(): void
    {
        $conversation = $this->makeConversation();

        $results = [];
        for ($i = 1; $i <= 10; $i++) {
            $results[] = ['id' => $i, 'nom' => "Classe {$i}"];
        }

        $this->provider->updateFromToolResult($conversation, 'search_classes', [], [
            'results' => $results,
            'count' => 10,
        ]);

        $stored = $conversation->fresh()->context['last_result_summary'];

        $this->assertCount(ConversationContextProvider::MAX_SAMPLE, $stored['ids']);
        $this->assertCount(ConversationContextProvider::MAX_SAMPLE, $stored['names']);
        $this->assertSame([1, 2, 3, 4, 5], $stored['ids']);
    }

    public function test_update_drops_non_scalar_filters(): void
    {
        $conversation = $this->makeConversation();

        $this->provider->updateFromToolResult($conversation, 'search_students', [
            'name' => 'Koné',
            'payload' => ['deep' => 'object'],
            'empty' => '',
        ], [
            'results' => [['id' => 42, 'etudiant' => 'Koné Aminata']],
            'count' => 1,
        ]);

        $stored = $conversation->fresh()->context['last_result_summary'];

        $this->assertArrayHasKey('name', $stored['filters']);
        $this->assertArrayNotHasKey('payload', $stored['filters']);
        $this->assertArrayNotHasKey('empty', $stored['filters']);
    }

    public function test_update_writes_under_context_key_without_clobbering_other_keys(): void
    {
        $conversation = $this->makeConversation([
            'context' => ['some_other_flag' => true],
        ]);

        $this->provider->updateFromToolResult($conversation, 'search_classes', [], [
            'results' => [['id' => 7, 'nom' => 'Test']],
            'count' => 1,
        ]);

        $context = $conversation->fresh()->context;
        $this->assertTrue($context['some_other_flag']);
        $this->assertArrayHasKey('last_result_summary', $context);
    }

    protected function makeConversation(array $extra = []): ChatbotConversation
    {
        $user = User::factory()->create(['username' => 'ctx_' . Str::lower(Str::random(8))]);

        return ChatbotConversation::create(array_merge([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'title' => 'Unit test',
            'last_activity_at' => now(),
            'is_active' => true,
        ], $extra));
    }
}

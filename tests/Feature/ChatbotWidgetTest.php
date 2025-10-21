<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ChatbotWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            EnsureInstalled::class,
            CheckInstalled::class,
        ]);
    }

    protected function createUser(): User
    {
        return User::factory()->create([
            'username' => 'user_' . Str::lower(Str::random(8)),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticated_user_can_send_message(): void
    {
        $user = $this->createUser();

        $responsePayload = [
            'success' => true,
            'message' => 'Voici les paiements en attente.',
            'display_type' => 'text',
            'conversation_id' => (string) Str::uuid(),
        ];

        $chatbot = Mockery::mock(ChatbotService::class);
        $chatbot->shouldReceive('sendMessage')
            ->once()
            ->with('Bonjour', null)
            ->andReturn($responsePayload);

        $this->instance(ChatbotService::class, $chatbot);

        $response = $this
            ->actingAs($user)
            ->postJson(route('chatbot.message'), ['message' => 'Bonjour']);

        $response
            ->assertOk()
            ->assertJson($responsePayload);
    }

    public function test_guest_cannot_send_message(): void
    {
        $this
            ->postJson(route('chatbot.message'), ['message' => 'Bonjour'])
            ->assertStatus(401);
    }

    public function test_user_can_list_conversations(): void
    {
        $user = $this->createUser();

        $conversation = ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'title' => 'Test conversation',
            'last_activity_at' => now()->subMinutes(5),
            'is_active' => true,
        ]);

        ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Bonjour assistant',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('chatbot.conversations'));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'conversations')
            ->assertJsonPath('conversations.0.id', $conversation->session_id);
    }

    public function test_user_can_fetch_conversation_history(): void
    {
        $user = $this->createUser();

        $conversation = ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'title' => 'Historique',
            'last_activity_at' => now(),
            'is_active' => true,
        ]);

        $userMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Montre-moi les étudiants.',
            'display_type' => 'text',
        ]);

        $botMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Voici les premiers étudiants.',
            'display_type' => 'text',
            'display_data' => ['rows' => []],
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('chatbot.history', ['conversationId' => $conversation->session_id]));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.id', $userMessage->id)
            ->assertJsonPath('messages.1.id', $botMessage->id);
    }

    public function test_user_can_delete_conversation(): void
    {
        $user = $this->createUser();

        $conversation = ChatbotConversation::create([
            'user_id' => $user->id,
            'session_id' => (string) Str::uuid(),
            'title' => 'À supprimer',
            'last_activity_at' => now(),
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->deleteJson(route('chatbot.delete', ['conversationId' => $conversation->session_id]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertFalse($conversation->fresh()->is_active);
    }
}

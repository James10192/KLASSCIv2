<?php

namespace Tests\Feature\API;

use App\Http\Middleware\CheckInstalled;
use App\Models\ParentChatbotInboundEvent;
use App\Services\ParentChatbot\ParentChatbotResponder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ParentChatbotInboundControllerTest extends TestCase
{
    private const WEBHOOK_SECRET = '12345678901234567890123456789012';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        config()->set('services.mailpulse.parent_chatbot_webhook_secret', self::WEBHOOK_SECRET);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');
        $this->withoutMiddleware(CheckInstalled::class);

        Schema::create('parent_chatbot_inbound_events', function (Blueprint $table) {
            $table->id();
            $table->string('source_event_id', 100)->unique();
            $table->char('payload_hash', 64);
            $table->string('outcome', 40)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_token', 64)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_expires_at')->nullable();
            $table->text('response_ciphertext')->nullable();
            $table->timestamp('response_recorded_at')->nullable();
            $table->timestamps();
        });

    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_same_event_id_and_payload_is_an_accepted_duplicate_without_a_second_response(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder);
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $payload = [
            'event_id' => 'evt-retry',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ];

        $this->signedInboundRequest($payload)->assertStatus(202)->assertJson(['accepted' => true]);
        $this->signedInboundRequest($payload)
            ->assertStatus(202)
            ->assertJson(['accepted' => true, 'duplicate' => true]);
    }

    public function test_changed_payload_for_an_existing_event_id_is_rejected_without_invoking_the_responder(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder);
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest([
            'event_id' => 'evt-conflict',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ])->assertStatus(202);

        $this->signedInboundRequest([
            'event_id' => 'evt-conflict',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'NOTES'],
        ])->assertStatus(409)->assertJson(['message' => 'Conflict.']);
    }

    public function test_an_event_with_an_active_lease_is_retryable_and_not_acknowledged_as_a_duplicate(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $responder->shouldNotReceive('handle');
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $payload = [
            'event_id' => 'evt-busy',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ];
        ParentChatbotInboundEvent::claim($payload['event_id'], hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)));

        $this->signedInboundRequest($payload)
            ->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertJson(['accepted' => false]);
    }

    public function test_an_expired_lease_is_claimed_and_processed(): void
    {
        $payload = [
            'event_id' => 'evt-expired-lease',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ];
        $event = ParentChatbotInboundEvent::claim(
            $payload['event_id'],
            hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        )->event;
        DB::table('parent_chatbot_inbound_events')->where('id', $event->id)->update([
            'processing_expires_at' => now()->subSecond(),
        ]);

        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder);
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest($payload)->assertStatus(202)->assertJson(['accepted' => true]);
    }

    public function test_failed_dispatch_releases_the_inbound_event_for_retry(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder, new \RuntimeException('dispatch pending'));
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest([
            'event_id' => 'evt-pending',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ])->assertStatus(503)->assertJson(['accepted' => false]);

        $event = DB::table('parent_chatbot_inbound_events')->where('source_event_id', 'evt-pending')->first();
        $this->assertNull($event->processed_at);
        $this->assertNull($event->processing_token);
    }

    public function test_a_dispatch_exception_is_not_acknowledged_and_leaves_the_event_unprocessed(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder, new \RuntimeException('dispatch failure'));
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest([
            'event_id' => 'evt-dispatch-exception',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ])->assertStatus(503)->assertJson(['accepted' => false]);

        $event = DB::table('parent_chatbot_inbound_events')
            ->where('source_event_id', 'evt-dispatch-exception')
            ->first();
        $this->assertNull($event->processed_at);
        $this->assertNull($event->processing_token);
    }

    public function test_a_dispatch_timeout_replays_the_encrypted_response_with_the_same_idempotency_key(): void
    {
        $payload = [
            'event_id' => 'evt-timeout-replay',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ];
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $this->expectsPreparedResponse($responder, new \RuntimeException('dispatch timeout'));
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest($payload)->assertStatus(503);

        $event = ParentChatbotInboundEvent::query()->where('source_event_id', $payload['event_id'])->sole();
        $stored = $event->recordedResponse();
        $this->assertSame('klassci-parent-inbound-evt-timeout-replay', $stored['idempotency_key']);
        $this->assertStringNotContainsString('Lien requis.', (string) $event->response_ciphertext);

        $responder = Mockery::mock(ParentChatbotResponder::class);
        $responder->shouldReceive('prepareInboundResponse')
            ->once()
            ->withArgs(function (ParentChatbotInboundEvent $replayedEvent) use ($stored): bool {
                return $replayedEvent->hasRecordedResponse()
                    && $replayedEvent->recordedResponse() === $stored;
            })
            ->andReturn($stored);
        $responder->shouldReceive('dispatchRecordedResponse')
            ->once()
            ->withArgs(fn (ParentChatbotInboundEvent $replayedEvent, string $token): bool => $replayedEvent->id === $event->id
                && $token === $replayedEvent->processing_token)
            ->andReturn('unlinked');
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest($payload)->assertStatus(202)->assertJson(['accepted' => true]);
        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function test_an_unreadable_recorded_response_is_replaced_and_dispatched_once(): void
    {
        $payload = [
            'event_id' => 'evt-corrupt-response',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'AIDE'],
        ];
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => $payload['event_id'],
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'received_at' => now(),
            'processing_token' => 'expired-token',
            'processing_started_at' => now()->subMinute(),
            'processing_expires_at' => now()->subSecond(),
        ]);
        DB::table('parent_chatbot_inbound_events')->where('id', $event->id)->update([
            'response_ciphertext' => 'not-a-valid-ciphertext',
            'response_recorded_at' => now()->subMinute(),
        ]);

        $responder = Mockery::mock(ParentChatbotResponder::class);
        $prepareAttempts = 0;
        $responder->shouldReceive('prepareInboundResponse')
            ->twice()
            ->andReturnUsing(function (ParentChatbotInboundEvent $claimed) use (&$prepareAttempts, $event): array {
                $this->assertSame($event->id, $claimed->id);

                if ($prepareAttempts++ === 0) {
                    $this->assertTrue($claimed->hasRecordedResponse());
                    throw new \UnexpectedValueException('Unreadable response.');
                }

                $this->assertFalse($claimed->hasRecordedResponse());

                return [
                    'phone' => '+2250102030405',
                    'intent' => 'unlinked',
                    'outcome' => 'unlinked',
                    'reply' => 'Lien requis.',
                    'should_dispatch' => true,
                    'disclosure' => null,
                    'authorization_claim' => null,
                ];
            });
        $responder->shouldReceive('dispatchRecordedResponse')
            ->once()
            ->withArgs(function (ParentChatbotInboundEvent $repaired, string $token): bool {
                return $token === $repaired->processing_token
                    && $repaired->fresh()->recordedResponse()['reply'] === 'Lien requis.';
            })
            ->andReturn('unlinked');
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest($payload)->assertStatus(202)->assertJson(['accepted' => true]);

        $repaired = $event->fresh();
        $this->assertNotSame('not-a-valid-ciphertext', $repaired->response_ciphertext);
        $this->assertSame('Lien requis.', $repaired->recordedResponse()['reply']);
        $this->assertNotNull($repaired->processed_at);
    }

    public function test_a_recorded_response_with_an_unknown_intent_is_rejected_as_untrusted(): void
    {
        $event = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-invalid-intent',
            'payload_hash' => hash('sha256', 'payload'),
            'received_at' => now(),
        ]);
        $response = [
            'phone' => '+2250102030405',
            'intent' => 'invalid_intent',
            'outcome' => 'unlinked',
            'reply' => 'Lien requis.',
            'idempotency_key' => 'klassci-parent-inbound-evt-invalid-intent',
            'should_dispatch' => true,
            'disclosure' => null,
            'authorization_claim' => null,
        ];
        DB::table('parent_chatbot_inbound_events')->where('id', $event->id)->update([
            'response_ciphertext' => Crypt::encryptString(json_encode($response, JSON_THROW_ON_ERROR)),
            'response_recorded_at' => now(),
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $event->fresh()->recordedResponse();
    }

    public function test_it_persists_the_disclosure_before_dispatching_the_response(): void
    {
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $responder->shouldReceive('prepareInboundResponse')->once()->andReturn([
            'phone' => '+2250102030405', 'intent' => 'published_grades', 'outcome' => 'published_grades',
            'reply' => 'Notes publiées.', 'should_dispatch' => true,
            'disclosure' => ['type' => 'grades', 'student_id' => 42, 'resource_ids' => [8]],
        ]);
        $responder->shouldReceive('dispatchRecordedResponse')->once()
            ->withArgs(function (ParentChatbotInboundEvent $event, string $token): bool {
                return $token === $event->processing_token
                    && $event->fresh()->recordedResponse()['disclosure'] === ['type' => 'grades', 'student_id' => 42, 'resource_ids' => [8]];
            })
            ->andReturn('published_grades');
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest([
            'event_id' => 'evt-disclosure',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'NOTES'],
        ])->assertStatus(202)->assertJson(['accepted' => true]);
    }

    public function test_it_redacts_processed_responses_and_dead_letters_released_failures_outside_retention(): void
    {
        $expired = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-expired-response',
            'payload_hash' => hash('sha256', 'evt-expired-response'),
            'received_at' => now()->subDays(8),
            'processed_at' => now()->subDays(8),
        ]);
        $unreleased = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-pending-response',
            'payload_hash' => hash('sha256', 'evt-pending-response'),
            'received_at' => now()->subDays(8),
        ]);
        $released = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-released-response',
            'payload_hash' => hash('sha256', 'evt-released-response'),
            'outcome' => ParentChatbotInboundEvent::OUTCOME_DISPATCH_PENDING,
            'received_at' => now()->subDays(8),
        ]);
        $leased = ParentChatbotInboundEvent::create([
            'source_event_id' => 'evt-leased-response',
            'payload_hash' => hash('sha256', 'evt-leased-response'),
            'outcome' => ParentChatbotInboundEvent::OUTCOME_DISPATCH_PENDING,
            'received_at' => now()->subDays(8),
            'processing_token' => 'active-lease',
            'processing_started_at' => now()->subMinute(),
            'processing_expires_at' => now()->addMinute(),
        ]);
        $response = Crypt::encryptString(json_encode([
            'phone' => '+2250102030405',
            'intent' => 'unlinked',
            'outcome' => 'unlinked',
            'reply' => 'Lien requis.',
            'idempotency_key' => 'klassci-parent-inbound-prune',
            'should_dispatch' => true,
        ], JSON_THROW_ON_ERROR));
        DB::table('parent_chatbot_inbound_events')->whereIn('id', [
            $expired->id,
            $unreleased->id,
            $released->id,
            $leased->id,
        ])->update([
            'response_ciphertext' => $response,
            'response_recorded_at' => now()->subDays(8),
        ]);

        $this->assertSame(
            ['redacted' => 1, 'dead_lettered' => 1],
            ParentChatbotInboundEvent::pruneRecordedResponsesBefore(now()->subDays(7), 10),
        );
        $this->assertNull($expired->fresh()->response_ciphertext);
        $this->assertNotNull($unreleased->fresh()->response_ciphertext);
        $this->assertNull($released->fresh()->response_ciphertext);
        $this->assertNull($released->fresh()->processed_at);
        $this->assertSame(ParentChatbotInboundEvent::OUTCOME_DISPATCH_DEAD_LETTERED, $released->fresh()->outcome);
        $this->assertNotNull($leased->fresh()->response_ciphertext);
        $this->assertSame(ParentChatbotInboundEvent::OUTCOME_DISPATCH_PENDING, $leased->fresh()->outcome);
    }

    public function test_a_dead_lettered_event_is_terminal_without_being_acknowledged_as_processed(): void
    {
        $payload = [
            'event_id' => 'evt-dead-lettered',
            'sender' => ['phone' => '+2250102030405'],
            'message' => ['text' => 'NOTES'],
        ];
        ParentChatbotInboundEvent::create([
            'source_event_id' => $payload['event_id'],
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'outcome' => ParentChatbotInboundEvent::OUTCOME_DISPATCH_DEAD_LETTERED,
            'received_at' => now()->subDays(8),
        ]);
        $responder = Mockery::mock(ParentChatbotResponder::class);
        $responder->shouldNotReceive('prepareInboundResponse');
        $responder->shouldNotReceive('dispatchRecordedResponse');
        $this->app->instance(ParentChatbotResponder::class, $responder);

        $this->signedInboundRequest($payload)
            ->assertStatus(410)
            ->assertJson(['accepted' => false, 'dead_lettered' => true]);

        $event = ParentChatbotInboundEvent::query()->where('source_event_id', $payload['event_id'])->sole();
        $this->assertNull($event->processed_at);
        $this->assertNull($event->processing_token);
    }

    private function signedInboundRequest(array $payload)
    {
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$content, self::WEBHOOK_SECRET);

        return $this->call('POST', '/api/v1/integrations/mailpulse/parent-chatbot/inbound', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_MAILPULSE_TIMESTAMP' => $timestamp,
            'HTTP_X_MAILPULSE_SIGNATURE' => $signature,
        ], $content);
    }

    private function expectsPreparedResponse(ParentChatbotResponder $responder, ?\Throwable $dispatchFailure = null): void
    {
        $responder->shouldReceive('prepareInboundResponse')
            ->once()
            ->andReturn([
                'phone' => '+2250102030405',
                'intent' => 'unlinked',
                'outcome' => 'unlinked',
                'reply' => 'Lien requis.',
                'should_dispatch' => true,
                'disclosure' => null,
            ]);

        $dispatch = $responder->shouldReceive('dispatchRecordedResponse')->once()
            ->withArgs(fn (ParentChatbotInboundEvent $event, string $token): bool => $token === $event->processing_token);
        $dispatchFailure === null ? $dispatch->andReturn('unlinked') : $dispatch->andThrow($dispatchFailure);
    }
}

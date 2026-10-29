<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseTestNotificationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MailPulseTestNotificationServiceTest extends TestCase
{
    /** @test */
    public function dry_run_returns_clear_shape_without_http_call(): void
    {
        config()->set('services.mailpulse.test_notification_email', 'parent@example.com');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        Http::fake();

        $result = app(MailPulseTestNotificationService::class)
            ->send('payment_received', 'both', true);

        $this->assertTrue($result['ok']);
        $this->assertSame('payment_received', $result['event']);
        $this->assertSame('dry-run-contact', $result['contactId']);
        $this->assertTrue($result['email']['attempted']);
        $this->assertSame('dry_run', $result['email']['status']);
        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertSame('dry_run', $result['whatsapp']['status']);
        $this->assertFalse($result['sms']['attempted']);
        $this->assertSame('skipped', $result['sms']['status']);
        Http::assertNothingSent();
    }

    /** @test */
    public function dry_run_can_target_sms_without_email(): void
    {
        config()->set('services.mailpulse.test_notification_email', '');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        Http::fake();

        $result = app(MailPulseTestNotificationService::class)
            ->send('grade_published', 'sms', true);

        $this->assertTrue($result['ok']);
        $this->assertSame('grade_published', $result['event']);
        $this->assertFalse($result['email']['attempted']);
        $this->assertFalse($result['whatsapp']['attempted']);
        $this->assertTrue($result['sms']['attempted']);
        $this->assertSame('dry_run', $result['sms']['status']);
        $this->assertArrayHasKey('sms_text', $result['preview']);
        Http::assertNothingSent();
    }

    /** @test */
    public function real_sms_send_uses_sms_contact_preference_and_sms_payload_only(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.contacts_endpoint', '/api/v1/contacts');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.test_notification_email', '');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        Http::fake([
            'mailpulse.test/api/v1/contacts' => Http::response(['id' => 'contact-1', 'status' => 'sent'], 200),
            'mailpulse.test/api/v1/messages' => Http::response([
                'dispatch' => ['state' => 'pending', 'sms_fallback_eligible' => false],
                'message' => ['id' => 'sms-1', 'status' => 'queued'],
            ], 202),
        ]);

        $result = app(MailPulseTestNotificationService::class)
            ->send('grade_published', 'sms', false);

        $this->assertTrue($result['ok']);
        $this->assertSame('contact-1', $result['contactId']);
        $this->assertFalse($result['email']['attempted']);
        $this->assertFalse($result['whatsapp']['attempted']);
        $this->assertTrue($result['sms']['attempted']);
        $this->assertSame('queued', $result['sms']['status']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/contacts'
            && $request['phone'] === '+2250707123456'
            && $request['preferred_channel'] === 'sms'
            && ! isset($request['email']));
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/messages'
            && $request['channel'] === 'sms'
            && $request['recipient']['type'] === 'phone'
            && $request['recipient']['value'] === '+2250707123456'
            && $request['content']['type'] === 'text'
            && str_starts_with($request['content']['text'], '[TEST KLASSCI]'));
        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/messages'
            && in_array($request['channel'], ['email', 'whatsapp'], true));
    }

    /** @test */
    public function real_both_send_keeps_sms_out_of_the_legacy_combined_channel(): void
    {
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.api_key', 'test-key');
        config()->set('services.mailpulse.base_url', 'https://mailpulse.test');
        config()->set('services.mailpulse.contacts_endpoint', '/api/v1/contacts');
        config()->set('services.mailpulse.messages_endpoint', '/api/v1/messages');
        config()->set('services.mailpulse.test_notification_email', 'parent@example.com');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        Http::fake([
            'mailpulse.test/api/v1/contacts' => Http::response(['id' => 'contact-1', 'status' => 'sent'], 200),
            'mailpulse.test/api/v1/messages' => Http::sequence()
                ->push([
                    'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
                    'message' => ['id' => 'email-1', 'status' => 'sent'],
                ], 202)
                ->push([
                    'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
                    'message' => ['id' => 'whatsapp-1', 'status' => 'sent'],
                ], 202),
        ]);

        $result = app(MailPulseTestNotificationService::class)
            ->send('payment_received', 'both', false);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['email']['attempted']);
        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertFalse($result['sms']['attempted']);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/contacts'
            && $request['preferred_channel'] === 'whatsapp');
        Http::assertNotSent(fn (Request $request) => $request->url() === 'https://mailpulse.test/api/v1/messages'
            && $request['channel'] === 'sms');
    }

    /** @test */
    public function it_requires_test_email_before_any_send(): void
    {
        config()->set('services.mailpulse.test_notification_email', '');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        $this->expectException(ValidationException::class);

        app(MailPulseTestNotificationService::class)
            ->send('payment_received', 'email', true);
    }

    /** @test */
    public function it_requires_test_phone_before_sms_send(): void
    {
        config()->set('services.mailpulse.test_notification_email', 'parent@example.com');
        config()->set('services.mailpulse.test_notification_phone', '');
        config()->set('services.mailpulse.test_notification_phones', '');

        $this->expectException(ValidationException::class);

        app(MailPulseTestNotificationService::class)
            ->send('payment_received', 'sms', true);
    }
}

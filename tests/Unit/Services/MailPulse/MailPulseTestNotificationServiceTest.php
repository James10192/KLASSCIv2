<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseTestNotificationService;
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

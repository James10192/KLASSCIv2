<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIMailPulseController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class MailPulseCliTest extends TestCase
{
    /** @test */
    public function controller_requires_cli_admin_ability(): void
    {
        $user = new User();
        $request = Request::create('/', 'POST', [
            'event' => 'payment_received',
            'channel' => 'email',
            'dryRun' => true,
        ]);
        $request->setUserResolver(fn () => new class($user) {
            public function __construct(private User $user) {}
            public function tokenCan(string $ability): bool { return false; }
            public function __get(string $name) { return $this->user->{$name}; }
        });

        $response = app(CLIMailPulseController::class)->testNotification(
            $request,
            app(\App\Services\MailPulse\MailPulseTestNotificationService::class)
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['ok']);
    }

    /** @test */
    public function controller_returns_dry_run_payload_for_cli_admin(): void
    {
        config()->set('services.mailpulse.test_notification_email', 'parent@example.com');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        $user = new User();
        $request = Request::create('/', 'POST', [
            'event' => 'payment_received',
            'channel' => 'email',
            'dryRun' => true,
        ]);
        $request->setUserResolver(fn () => new class($user) {
            public function __construct(private User $user) {}
            public function tokenCan(string $ability): bool { return $ability === 'cli:admin'; }
            public function __get(string $name) { return $this->user->{$name}; }
        });

        $response = app(CLIMailPulseController::class)->testNotification(
            $request,
            app(\App\Services\MailPulse\MailPulseTestNotificationService::class)
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['ok']);
        $this->assertSame('payment_received', $payload['event']);
        $this->assertTrue($payload['email']['attempted']);
        $this->assertFalse($payload['whatsapp']['attempted']);
        $this->assertFalse($payload['sms']['attempted']);
    }

    /** @test */
    public function controller_returns_sms_dry_run_payload_for_cli_admin(): void
    {
        config()->set('services.mailpulse.test_notification_email', '');
        config()->set('services.mailpulse.test_notification_phone', '0707123456');

        $user = new User();
        $request = Request::create('/', 'POST', [
            'event' => 'grade_published',
            'channel' => 'sms',
            'dryRun' => true,
        ]);
        $request->setUserResolver(fn () => new class($user) {
            public function __construct(private User $user) {}
            public function tokenCan(string $ability): bool { return $ability === 'cli:admin'; }
            public function __get(string $name) { return $this->user->{$name}; }
        });

        $response = app(CLIMailPulseController::class)->testNotification(
            $request,
            app(\App\Services\MailPulse\MailPulseTestNotificationService::class)
        );

        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['ok']);
        $this->assertSame('grade_published', $payload['event']);
        $this->assertFalse($payload['email']['attempted']);
        $this->assertFalse($payload['whatsapp']['attempted']);
        $this->assertTrue($payload['sms']['attempted']);
        $this->assertSame('dry_run', $payload['sms']['status']);
    }
}

<?php

namespace Tests\Unit\Services\MailPulse;

use App\Services\MailPulse\MailPulseMonitoring;
use PHPUnit\Framework\TestCase;

final class MailPulseMonitoringStatusTest extends TestCase
{
    public function test_sent_is_not_a_proof_of_delivery(): void
    {
        $this->assertStringContainsString(
            'non confirmée',
            MailPulseMonitoring::statusLabel('sent')
        );
        $this->assertNotSame(
            MailPulseMonitoring::statusLabel('sent'),
            MailPulseMonitoring::statusLabel('delivered')
        );
    }

    public function test_failure_and_pending_are_distinguishable(): void
    {
        $this->assertSame('En attente', MailPulseMonitoring::statusLabel('pending'));
        $this->assertSame('Échec', MailPulseMonitoring::statusLabel('failed'));
    }

    public function test_unexpected_status_is_sanitized(): void
    {
        $this->assertStringNotContainsString('<script>', MailPulseMonitoring::statusLabel('<script>'));
    }
}

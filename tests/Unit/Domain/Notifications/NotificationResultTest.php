<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\Contracts\NotificationResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit du DTO NotificationResult (sans DB, pure architecture).
 *
 * Valide les contrats des factories success/failure/partial et la cohérence
 * du dispatched/errors array.
 */
class NotificationResultTest extends TestCase
{
    public function test_success_factory_creates_result_with_dispatched_channels(): void
    {
        $result = NotificationResult::success('Tout OK', [
            'email' => 'sent',
            'whatsapp' => 'queued',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame('Tout OK', $result->message);
        $this->assertSame(['email' => 'sent', 'whatsapp' => 'queued'], $result->dispatched);
        $this->assertSame([], $result->errors);
    }

    public function test_failure_factory_creates_result_with_errors(): void
    {
        $result = NotificationResult::failure('Erreur SMS', ['sms' => 'Contract expired']);

        $this->assertFalse($result->success);
        $this->assertSame('Erreur SMS', $result->message);
        $this->assertSame([], $result->dispatched);
        $this->assertSame(['sms' => 'Contract expired'], $result->errors);
    }

    public function test_partial_factory_marks_success_with_both_dispatched_and_errors(): void
    {
        $result = NotificationResult::partial(
            'Email OK, SMS échoué',
            ['email' => 'sent'],
            ['sms' => 'Provider down']
        );

        $this->assertTrue($result->success);
        $this->assertSame('Email OK, SMS échoué', $result->message);
        $this->assertSame(['email' => 'sent'], $result->dispatched);
        $this->assertSame(['sms' => 'Provider down'], $result->errors);
    }

    public function test_to_array_serializes_full_state(): void
    {
        $result = NotificationResult::partial('Mixed', ['email' => 'sent'], ['sms' => 'failed']);

        $this->assertSame([
            'success' => true,
            'message' => 'Mixed',
            'dispatched' => ['email' => 'sent'],
            'errors' => ['sms' => 'failed'],
        ], $result->toArray());
    }

    public function test_success_factory_with_empty_dispatched_works(): void
    {
        $result = NotificationResult::success('Nothing to dispatch');

        $this->assertTrue($result->success);
        $this->assertSame([], $result->dispatched);
    }

    public function test_immutability_via_readonly_properties(): void
    {
        $result = NotificationResult::success('Test', ['email' => 'sent']);

        // PHP 8.2 readonly properties — tentative d'assignation lève Error
        $this->expectException(\Error::class);
        $result->success = false;
    }
}

<?php

namespace Tests\Unit\Enums;

use App\Enums\ReconciliationSessionStatus;
use PHPUnit\Framework\TestCase;

class ReconciliationSessionStatusTest extends TestCase
{
    public function test_has_5_cases(): void
    {
        $this->assertSame(5, count(ReconciliationSessionStatus::cases()));
    }

    public function test_draft_can_only_go_to_review(): void
    {
        $draft = ReconciliationSessionStatus::DRAFT;
        $this->assertTrue($draft->canTransitionTo(ReconciliationSessionStatus::REVIEW));
        $this->assertFalse($draft->canTransitionTo(ReconciliationSessionStatus::APPROVED));
        $this->assertFalse($draft->canTransitionTo(ReconciliationSessionStatus::CLOSED));
    }

    public function test_review_can_go_to_approved_or_back_to_draft(): void
    {
        $review = ReconciliationSessionStatus::REVIEW;
        $this->assertTrue($review->canTransitionTo(ReconciliationSessionStatus::APPROVED));
        $this->assertTrue($review->canTransitionTo(ReconciliationSessionStatus::DRAFT));
        $this->assertFalse($review->canTransitionTo(ReconciliationSessionStatus::CLOSED));
    }

    public function test_closed_can_only_be_reopened(): void
    {
        $closed = ReconciliationSessionStatus::CLOSED;
        $this->assertTrue($closed->canTransitionTo(ReconciliationSessionStatus::REOPENED));
        $this->assertFalse($closed->canTransitionTo(ReconciliationSessionStatus::DRAFT));
    }

    public function test_modifiable_only_in_draft_or_reopened(): void
    {
        $this->assertTrue(ReconciliationSessionStatus::DRAFT->isModifiable());
        $this->assertTrue(ReconciliationSessionStatus::REOPENED->isModifiable());
        $this->assertFalse(ReconciliationSessionStatus::REVIEW->isModifiable());
        $this->assertFalse(ReconciliationSessionStatus::APPROVED->isModifiable());
        $this->assertFalse(ReconciliationSessionStatus::CLOSED->isModifiable());
    }

    public function test_only_closed_is_final(): void
    {
        $this->assertTrue(ReconciliationSessionStatus::CLOSED->isFinal());
        foreach ([
            ReconciliationSessionStatus::DRAFT,
            ReconciliationSessionStatus::REVIEW,
            ReconciliationSessionStatus::APPROVED,
            ReconciliationSessionStatus::REOPENED,
        ] as $s) {
            $this->assertFalse($s->isFinal(), "{$s->value} should not be final");
        }
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPDocumentApproval;
use App\Services\PrintDecision;
use Tests\TestCase;

class PrintDecisionTest extends TestCase
{
    public function test_open_is_allowed_without_gates(): void
    {
        $decision = PrintDecision::open();

        $this->assertTrue($decision->allowed);
        $this->assertFalse($decision->gated);
        $this->assertFalse($decision->isUnpaid());
        $this->assertFalse($decision->needsApprovalRequest());
        $this->assertFalse($decision->isApproved());
    }

    public function test_solde_message_uses_the_amount(): void
    {
        $decision = PrintDecision::denied(PrintDecision::SOLDE, 25000);

        $this->assertTrue($decision->isUnpaid());
        $this->assertStringContainsString('25 000', $decision->message());
    }

    public function test_approval_and_permission_messages_are_explicit(): void
    {
        $this->assertSame(
            'Impression bloquée : l\'accord de la responsable scolarité est requis.',
            PrintDecision::denied(PrintDecision::APPROVAL)->message()
        );
        $this->assertSame(
            'Vous n\'avez pas le droit d\'imprimer ce document.',
            PrintDecision::denied(PrintDecision::PERMISSION, 0.0, false)->message()
        );
    }

    public function test_approved_exposes_the_stamp(): void
    {
        $row = new ESBTPDocumentApproval();
        $row->status = ESBTPDocumentApproval::STATUS_APPROVED;

        $decision = PrintDecision::approved($row);

        $this->assertTrue($decision->allowed);
        $this->assertTrue($decision->isApproved());
        $this->assertFalse($decision->needsApprovalRequest());
    }
}

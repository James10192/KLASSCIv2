<?php

namespace Tests\Unit\Services;

use App\Services\Inscription\AdmissionWorkflowPolicy;
use Tests\TestCase;

class AdmissionWorkflowPolicyTest extends TestCase
{
    public function test_unknown_mode_falls_back_to_standard(): void
    {
        $this->assertSame(
            AdmissionWorkflowPolicy::MODE_STANDARD,
            AdmissionWorkflowPolicy::normaliserMode('une-ecole-en-dur'),
        );
    }

    public function test_yakro_target_sequence_is_cashier_then_documents_then_student_class(): void
    {
        $this->assertSame([
            'candidature',
            'rendez_vous',
            'caisse',
            'pieces',
            'classe_etudiant',
            'validation',
        ], AdmissionWorkflowPolicy::sequencePour(
            AdmissionWorkflowPolicy::MODE_CAISSE_PUIS_PIECES,
            true,
        ));
    }

    public function test_documents_can_be_configured_before_cashier_without_tenant_specific_code(): void
    {
        $this->assertSame([
            'candidature',
            'rendez_vous',
            'pieces',
            'caisse',
            'validation',
        ], AdmissionWorkflowPolicy::sequencePour(
            AdmissionWorkflowPolicy::MODE_PIECES_PUIS_CAISSE,
            false,
        ));
    }

    public function test_standard_mode_preserves_agent_class_selection_path(): void
    {
        $this->assertSame([
            'candidature',
            'rendez_vous',
            'inscription_agent',
            'validation',
        ], AdmissionWorkflowPolicy::sequencePour(
            AdmissionWorkflowPolicy::MODE_STANDARD,
            false,
        ));
    }
}

<?php

namespace Tests\Unit\Admissions;

use PHPUnit\Framework\TestCase;

/**
 * Contrat statique : un dispatch accepté ou différé n'est pas une remise prouvée.
 * Aucun appel MailPulse ou à une base de production.
 */
final class ActivationDispatchTruthContractTest extends TestCase
{
    public function test_dispatch_deferred_is_not_reported_as_sent(): void
    {
        $service = file_get_contents(__DIR__.'/../../../app/Services/Admissions/ManagedInscriptionWorkflow.php');

        $this->assertIsString($service);
        $this->assertStringContainsString("'email_pending' =>", $service);
        $this->assertStringContainsString("'email_sent' => false, 'email_pending' =>", $service);
    }

    public function test_confirmation_distinguishes_provider_acceptance_from_delivery(): void
    {
        $controller = file_get_contents(__DIR__.'/../../../app/Http/Controllers/ESBTP/ManagedActivationController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString("remise au destinataire reste à confirmer", $controller);
        $this->assertStringContainsString("email_pending", $controller);
    }

    public function test_school_staff_can_see_expiry_without_revealing_token(): void
    {
        $view = file_get_contents(__DIR__.'/../../../resources/views/esbtp/admissions/workflow/show.blade.php');

        $this->assertIsString($view);
        $this->assertStringContainsString("Expiration du lien", $view);
        $this->assertStringContainsString("Lien expiré", $view);
        $this->assertStringNotContainsString("activation_token_hash }}", $view);
    }
}

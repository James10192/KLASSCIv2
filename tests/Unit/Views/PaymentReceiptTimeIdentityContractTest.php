<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class PaymentReceiptTimeIdentityContractTest extends TestCase
{
    private function source(string $path): string
    {
        $source = file_get_contents(__DIR__.'/../../../'.$path);
        $this->assertNotFalse($source);

        return $source;
    }

    public function test_detail_does_not_invent_midnight_from_date_only(): void
    {
        $page = $this->source('resources/views/esbtp/paiements/show.blade.php');
        $this->assertStringNotContainsString("date_paiement->format('d/m/Y à H:i')", $page);
        $this->assertStringContainsString("created_at->format('H:i')", $page);
        $this->assertStringContainsString('Saisi à', $page);
    }

    public function test_cashier_receipt_name_is_configurable_without_changing_payment_ownership(): void
    {
        $model = $this->source('app/Models/ESBTPPaiement.php');
        $pdf = $this->source('resources/views/esbtp/paiements/partials/recu-exemplaire.blade.php');
        $preview = $this->source('resources/views/esbtp/paiements/preview.blade.php');
        $settings = $this->source('resources/views/esbtp/settings/index.blade.php');
        $controller = $this->source('app/Http/Controllers/ESBTP/ESBTPSettingsController.php');

        $this->assertStringContainsString("get('receipt_cashier_name_format', 'full')", $model);
        $this->assertStringContainsString('if ($format !== \'initials\')', $model);
        $this->assertStringContainsString('$paiement->encaisseur_recu', $pdf);
        $this->assertStringContainsString('$paiement->encaisseur_recu', $preview);
        $this->assertStringContainsString('name="setting_receipt_cashier_name_format"', $settings);
        $this->assertStringContainsString('id="compta-recus"', $settings);
        $this->assertStringContainsString('in_array($value, [\'full\', \'initials\'], true)', $controller);
    }
}

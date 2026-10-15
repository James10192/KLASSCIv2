<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class PaymentActualTimestampDisplayTest extends TestCase
{
    public function test_payment_business_date_is_not_rendered_as_a_timestamp(): void
    {
        $mobile = file_get_contents(__DIR__.'/../../../resources/views/esbtp/paiements/partials/_show-mobile.blade.php');
        $this->assertNotFalse($mobile);

        $this->assertStringContainsString(
            "\$paiement->date_paiement->format('d/m/Y')",
            $mobile
        );
        $this->assertStringNotContainsString(
            "\$paiement->date_paiement->format('d/m/Y H:i')",
            $mobile
        );
        $this->assertStringContainsString(
            "\$paiement->date_validation->format('d/m/Y H:i')",
            $mobile
        );
    }

    public function test_receipt_displays_true_entry_time_separately_and_uses_existing_cashier_setting(): void
    {
        $pdf = file_get_contents(__DIR__.'/../../../resources/views/esbtp/paiements/partials/recu-exemplaire.blade.php');
        $model = file_get_contents(__DIR__.'/../../../app/Models/ESBTPPaiement.php');
        $settings = file_get_contents(__DIR__.'/../../../resources/views/esbtp/settings/index.blade.php');

        $this->assertNotFalse($pdf);
        $this->assertNotFalse($model);
        $this->assertNotFalse($settings);
        $this->assertStringContainsString("Saisi le {{ \$paiement->created_at->format('d/m/Y à H:i') }}", $pdf);
        $this->assertStringContainsString("{{ \$paiement->encaisseur_recu }}", $pdf);
        $this->assertStringContainsString("SettingsHelper::get('receipt_cashier_name_format', 'full')", $model);
        $this->assertStringContainsString('name="setting_receipt_cashier_name_format"', $settings);
    }
}

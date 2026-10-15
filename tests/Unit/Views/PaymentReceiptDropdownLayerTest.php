<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

class PaymentReceiptDropdownLayerTest extends TestCase
{
    public function test_receipt_menu_temporarily_rises_above_overlapping_payment_cards(): void
    {
        $page = file_get_contents(__DIR__.'/../../../resources/views/esbtp/paiements/show.blade.php');
        $this->assertNotFalse($page);

        // Le contenu chevauche volontairement le bas du hero ; son contexte est à z=3.
        $this->assertMatchesRegularExpression('/\\.ps-content\\s*\\{[^}]*z-index:\\s*3;/s', $page);
        $this->assertMatchesRegularExpression('/\\.ps-hero-inner\\s*\\{[^}]*z-index:\\s*2;/s', $page);
        $this->assertStringContainsString(
            '.ps-page.ps-receipt-menu-open .ps-hero-inner { z-index: 5; }',
            $page
        );

        // Le correctif ne reste actif que pendant le dropdown Bootstrap.
        $this->assertStringContainsString(
            "dropdown.addEventListener('show.bs.dropdown', () => page?.classList.add('ps-receipt-menu-open'))",
            $page
        );
        $this->assertStringContainsString(
            "dropdown.addEventListener('hidden.bs.dropdown', () => page?.classList.remove('ps-receipt-menu-open'))",
            $page
        );
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $page);
        $this->assertStringContainsString("route('esbtp.paiements.preview-pdf',", $page);
        $this->assertStringContainsString("route('esbtp.paiements.recu',", $page);
    }
}

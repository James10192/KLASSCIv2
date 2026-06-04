<?php

namespace Tests\Unit\Enums;

use App\Enums\ModePaiement;
use PHPUnit\Framework\TestCase;

class ModePaiementTest extends TestCase
{
    public function test_has_expected_cases(): void
    {
        $this->assertSame(8, count(ModePaiement::cases()));
    }

    public function test_values_returns_strings(): void
    {
        $values = ModePaiement::values();
        $this->assertContains('especes', $values);
        $this->assertContains('mobile_money', $values);
        $this->assertContains('wave', $values);
    }

    public function test_labels_are_french(): void
    {
        $this->assertSame('Espèces', ModePaiement::ESPECES->label());
        $this->assertSame('Orange Money', ModePaiement::ORANGE_MONEY->label());
    }

    public function test_select_options_format(): void
    {
        $options = ModePaiement::selectOptions();
        $this->assertArrayHasKey('especes', $options);
        $this->assertSame('Espèces', $options['especes']);
    }

    public function test_from_legacy_normalizes_variants(): void
    {
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('Espèces'));
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('ESP'));
        $this->assertSame(ModePaiement::ESPECES, ModePaiement::fromLegacy('cash'));
        $this->assertSame(ModePaiement::WAVE, ModePaiement::fromLegacy('Wave CI'));
        $this->assertSame(ModePaiement::ORANGE_MONEY, ModePaiement::fromLegacy('orange money'));
        $this->assertSame(ModePaiement::MTN_MONEY, ModePaiement::fromLegacy('MTN MoMo'));
        $this->assertSame(ModePaiement::MOOV_MONEY, ModePaiement::fromLegacy('Moov'));
        $this->assertSame(ModePaiement::MOOV_MONEY, ModePaiement::fromLegacy('flooz'));
        $this->assertSame(ModePaiement::MOBILE_MONEY, ModePaiement::fromLegacy('mobile générique'));
        $this->assertSame(ModePaiement::VIREMENT, ModePaiement::fromLegacy('virement bank'));
        $this->assertSame(ModePaiement::CHEQUE, ModePaiement::fromLegacy('chèque'));
    }

    public function test_from_legacy_returns_null_for_unknown(): void
    {
        $this->assertNull(ModePaiement::fromLegacy(null));
        $this->assertNull(ModePaiement::fromLegacy(''));
        $this->assertNull(ModePaiement::fromLegacy('xyz_unknown'));
    }
}

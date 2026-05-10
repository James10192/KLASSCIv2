<?php

namespace Tests\Unit\Enums;

use App\Enums\TypeUE;
use PHPUnit\Framework\TestCase;

class TypeUETest extends TestCase
{
    public function test_exposes_seven_uemoa_categories(): void
    {
        $values = TypeUE::values();
        $this->assertCount(7, $values);

        $this->assertContains('fondamentale', $values);
        $this->assertContains('methodologique', $values);
        $this->assertContains('decouverte', $values);
        $this->assertContains('transversale', $values);
        $this->assertContains('culture_generale', $values);
        $this->assertContains('specialite', $values);
        $this->assertContains('libre', $values);
    }

    public function test_legacy_values_remain_valid(): void
    {
        // Ces 4 valeurs étaient les seules acceptées avant l'extension UEMOA.
        // Les UEs déjà en DB (ESBTP-Yakro, etc.) doivent rester valides.
        foreach (['fondamentale', 'methodologique', 'decouverte', 'transversale'] as $legacy) {
            $this->assertNotNull(TypeUE::tryFrom($legacy));
        }
    }

    public function test_new_uemoa_values_are_recognized(): void
    {
        foreach (['culture_generale', 'specialite', 'libre'] as $new) {
            $this->assertNotNull(TypeUE::tryFrom($new));
        }
    }

    public function test_label_is_french_humanized(): void
    {
        $this->assertSame('UE Fondamentale', TypeUE::Fondamentale->label());
        $this->assertSame('UE de Méthodologie', TypeUE::Methodologique->label());
        $this->assertSame('UE de Culture Générale', TypeUE::CultureGenerale->label());
    }
}

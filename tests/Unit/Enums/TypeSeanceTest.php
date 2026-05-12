<?php

namespace Tests\Unit\Enums;

use App\Enums\TypeSeance;
use PHPUnit\Framework\TestCase;

class TypeSeanceTest extends TestCase
{
    public function test_values_returns_all_seven_strings(): void
    {
        $values = TypeSeance::values();
        $this->assertCount(7, $values);
        $this->assertContains('CM', $values);
        $this->assertContains('TD', $values);
        $this->assertContains('TP', $values);
        $this->assertContains('PROJET', $values);
        $this->assertContains('TPE', $values);
        $this->assertContains('EXAMEN', $values);
        $this->assertContains('AUTRE', $values);
    }

    public function test_from_legacy_maps_null_to_autre(): void
    {
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy(null));
    }

    public function test_from_legacy_maps_empty_to_autre(): void
    {
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy(''));
    }

    public function test_from_legacy_maps_cours_to_autre(): void
    {
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy('cours'));
    }

    public function test_from_legacy_maps_examen_to_examen(): void
    {
        $this->assertSame(TypeSeance::EXAMEN, TypeSeance::fromLegacy('examen'));
    }

    public function test_from_legacy_maps_valid_values_correctly(): void
    {
        $this->assertSame(TypeSeance::CM,  TypeSeance::fromLegacy('CM'));
        $this->assertSame(TypeSeance::TD,  TypeSeance::fromLegacy('TD'));
        $this->assertSame(TypeSeance::TP,  TypeSeance::fromLegacy('TP'));
        $this->assertSame(TypeSeance::TPE, TypeSeance::fromLegacy('TPE'));
    }

    public function test_from_legacy_maps_unknown_to_autre(): void
    {
        $this->assertSame(TypeSeance::AUTRE, TypeSeance::fromLegacy('random_legacy_value'));
    }

    public function test_is_volume_tracked_for_cm_td_tp(): void
    {
        $this->assertTrue(TypeSeance::CM->isVolumeTracked());
        $this->assertTrue(TypeSeance::TD->isVolumeTracked());
        $this->assertTrue(TypeSeance::TP->isVolumeTracked());
    }

    public function test_is_volume_tracked_false_for_others(): void
    {
        $this->assertFalse(TypeSeance::PROJET->isVolumeTracked());
        $this->assertFalse(TypeSeance::TPE->isVolumeTracked());
        $this->assertFalse(TypeSeance::EXAMEN->isVolumeTracked());
        $this->assertFalse(TypeSeance::AUTRE->isVolumeTracked());
    }

    public function test_label_returns_french_string(): void
    {
        $this->assertSame('Cours Magistral', TypeSeance::CM->label());
        $this->assertSame('Travaux Dirigés', TypeSeance::TD->label());
        $this->assertSame('Travaux Pratiques', TypeSeance::TP->label());
    }
}

<?php

namespace Tests\Unit\Enums;

use App\Enums\TypeSeance;
use PHPUnit\Framework\TestCase;

class TypeSeanceTest extends TestCase
{
    public function test_values_returns_all_type_seance_strings(): void
    {
        $values = TypeSeance::values();
        $this->assertCount(10, $values);
        $this->assertContains('CM', $values);
        $this->assertContains('TD', $values);
        $this->assertContains('TP', $values);
        $this->assertContains('PROJET', $values);
        $this->assertContains('TPE', $values);
        $this->assertContains('EXAMEN', $values);
        $this->assertContains('PARTIEL', $values);
        $this->assertContains('RATTRAPAGE', $values);
        $this->assertContains('SOUTENANCE', $values);
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
        $this->assertSame(TypeSeance::PARTIEL, TypeSeance::fromLegacy('PARTIEL'));
        $this->assertSame(TypeSeance::RATTRAPAGE, TypeSeance::fromLegacy('RATTRAPAGE'));
        $this->assertSame(TypeSeance::SOUTENANCE, TypeSeance::fromLegacy('SOUTENANCE'));
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
        $this->assertFalse(TypeSeance::PARTIEL->isVolumeTracked());
        $this->assertFalse(TypeSeance::RATTRAPAGE->isVolumeTracked());
        $this->assertFalse(TypeSeance::SOUTENANCE->isVolumeTracked());
        $this->assertFalse(TypeSeance::AUTRE->isVolumeTracked());
    }

    public function test_label_returns_french_string(): void
    {
        $this->assertSame('Cours Magistral', TypeSeance::CM->label());
        $this->assertSame('Travaux Dirigés', TypeSeance::TD->label());
        $this->assertSame('Travaux Pratiques', TypeSeance::TP->label());
    }

    public function test_teaching_types_are_compatible_with_course_not_homework(): void
    {
        foreach ([TypeSeance::CM, TypeSeance::TD, TypeSeance::TP, TypeSeance::PROJET, TypeSeance::AUTRE] as $type) {
            $this->assertTrue($type->isCompatibleWithTopType('course'), $type->value.' should fit a Cours');
            $this->assertFalse($type->isCompatibleWithTopType('homework'), $type->value.' should not fit a Devoir');
        }
    }

    public function test_evaluations_are_compatible_with_homework_not_course(): void
    {
        foreach ([TypeSeance::EXAMEN, TypeSeance::PARTIEL, TypeSeance::RATTRAPAGE, TypeSeance::SOUTENANCE] as $type) {
            $this->assertTrue($type->isCompatibleWithTopType('homework'), $type->value.' should fit a Devoir');
            $this->assertFalse($type->isCompatibleWithTopType('course'), $type->value.' should not fit a Cours');
        }
    }

    public function test_tpe_is_never_compatible_with_an_edt_slot(): void
    {
        $this->assertFalse(TypeSeance::TPE->isCompatibleWithTopType('course'));
        $this->assertFalse(TypeSeance::TPE->isCompatibleWithTopType('homework'));
        $this->assertFalse(TypeSeance::CM->isCompatibleWithTopType('break'));
    }

    public function test_code_of_unwraps_enum_string_and_null(): void
    {
        $this->assertSame('CM', TypeSeance::codeOf(TypeSeance::CM));
        $this->assertSame('TD', TypeSeance::codeOf('TD'));
        $this->assertNull(TypeSeance::codeOf(null));
        $this->assertNull(TypeSeance::codeOf(''));
    }
}

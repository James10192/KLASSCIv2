<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Attendance\AttendanceNoteRule;
use PHPUnit\Framework\TestCase;

class AttendanceNoteRuleTest extends TestCase
{
    /**
     * Ancien algorithme codé en dur (référence de parité).
     */
    private function legacyResolve(array $b, float $just, float $nonJust): float
    {
        if (($just + $nonJust) === 0.0) {
            return $b['zero_unjustified'];
        }
        if ($nonJust < 2.0) {
            return $b['one_unjustified'];
        }
        if ($nonJust < 3.0) {
            return $b['two_unjustified'];
        }
        if ($nonJust < 5.0) {
            return $b['three_to_four_unjustified'];
        }

        return $b['five_or_more_unjustified'];
    }

    public function test_from_legacy_settings_matches_old_algorithm_across_a_grid(): void
    {
        $legacy = [
            'zero_unjustified' => 0.13,
            'one_unjustified' => 0.0,
            'two_unjustified' => -0.13,
            'three_to_four_unjustified' => -0.39,
            'five_or_more_unjustified' => -0.50,
        ];
        $rule = AttendanceNoteRule::fromLegacySettings($legacy);

        // Le justifié seul ne doit JAMAIS changer la note (comportement legacy),
        // sauf en retirant le bonus zéro quand total > 0.
        foreach ([0.0, 0.5, 1.0, 1.9, 2.0, 2.5, 3.0, 4.9, 5.0, 8.0, 20.0] as $nonJust) {
            foreach ([0.0, 1.0, 7.0] as $just) {
                $expected = $this->legacyResolve($legacy, $just, $nonJust);
                $this->assertSame(
                    $expected,
                    $rule->resolve($just, $nonJust),
                    "parité échouée pour just=$just nonJust=$nonJust"
                );
            }
        }
    }

    public function test_zero_bonus_only_when_no_absence_at_all(): void
    {
        $rule = AttendanceNoteRule::fromLegacySettings([
            'zero_unjustified' => 0.13,
            'one_unjustified' => 0.0,
            'two_unjustified' => -0.13,
            'three_to_four_unjustified' => -0.39,
            'five_or_more_unjustified' => -0.50,
        ]);

        $this->assertSame(0.13, $rule->resolve(0.0, 0.0));
        // 2h justifiées, 0 non justifiée → total > 0 → plus de bonus, tranche non justifiée [0,2) = 0.
        $this->assertSame(0.0, $rule->resolve(2.0, 0.0));
    }

    public function test_abidjan_scenario_two_brackets(): void
    {
        // esbtp-abidjan : 0 absence = 0 ; 0..10h non just = -0.13 ; 10h+ = -0.25 ; justifié sans effet.
        $rule = AttendanceNoteRule::fromArray([
            'zero_bonus' => 0.0,
            'unjustified' => [
                ['min' => 0.0, 'max' => 10.0, 'note' => -0.13],
                ['min' => 10.0, 'max' => null, 'note' => -0.25],
            ],
            'justified' => [
                ['min' => 0.0, 'max' => null, 'note' => 0.0],
            ],
        ]);

        $this->assertSame(0.0, $rule->resolve(0.0, 0.0));
        $this->assertSame(-0.13, $rule->resolve(0.0, 0.5));
        $this->assertSame(-0.13, $rule->resolve(0.0, 9.99));
        $this->assertSame(-0.25, $rule->resolve(0.0, 10.0));
        $this->assertSame(-0.25, $rule->resolve(0.0, 25.0));
        // Justifié n'a aucun effet (tranche unique note 0).
        $this->assertSame(-0.13, $rule->resolve(40.0, 3.0));
    }

    public function test_justified_bracket_adds_its_own_malus_when_configured(): void
    {
        $rule = AttendanceNoteRule::fromArray([
            'zero_bonus' => 0.0,
            'unjustified' => [
                ['min' => 0.0, 'max' => null, 'note' => -0.10],
            ],
            'justified' => [
                ['min' => 0.0, 'max' => 5.0, 'note' => 0.0],
                ['min' => 5.0, 'max' => null, 'note' => -0.05],
            ],
        ]);

        // 6h justifiées (>=5 → -0.05) + 1h non justifiée (-0.10) = -0.15 additionné.
        $this->assertEqualsWithDelta(-0.15, $rule->resolve(6.0, 1.0), 0.0001);
    }

    public function test_validation_rejects_non_contiguous_brackets(): void
    {
        $errors = AttendanceNoteRule::validationErrors([
            'zero_bonus' => 0.0,
            'unjustified' => [
                ['min' => 0.0, 'max' => 2.0, 'note' => 0.0],
                ['min' => 3.0, 'max' => null, 'note' => -0.5], // trou 2..3
            ],
            'justified' => [['min' => 0.0, 'max' => null, 'note' => 0.0]],
        ]);

        $this->assertNotEmpty($errors);
    }

    public function test_validation_rejects_closed_last_bracket_and_accepts_open(): void
    {
        $closed = AttendanceNoteRule::validationErrors([
            'zero_bonus' => 0.0,
            'unjustified' => [['min' => 0.0, 'max' => 5.0, 'note' => -0.5]], // dernière fermée
            'justified' => [['min' => 0.0, 'max' => null, 'note' => 0.0]],
        ]);
        $this->assertNotEmpty($closed);

        $open = AttendanceNoteRule::validationErrors([
            'zero_bonus' => 0.0,
            'unjustified' => [['min' => 0.0, 'max' => null, 'note' => -0.5]],
            'justified' => [['min' => 0.0, 'max' => null, 'note' => 0.0]],
        ]);
        $this->assertSame([], $open);
    }

    public function test_validation_rejects_out_of_bounds_note(): void
    {
        $errors = AttendanceNoteRule::validationErrors([
            'zero_bonus' => 0.0,
            'unjustified' => [['min' => 0.0, 'max' => null, 'note' => -99]],
            'justified' => [['min' => 0.0, 'max' => null, 'note' => 0.0]],
        ]);
        $this->assertNotEmpty($errors);
    }

    public function test_round_trip_to_array_and_from_array(): void
    {
        $data = [
            'zero_bonus' => 0.0,
            'unjustified' => [
                ['min' => 0.0, 'max' => 10.0, 'note' => -0.13],
                ['min' => 10.0, 'max' => null, 'note' => -0.25],
            ],
            'justified' => [['min' => 0.0, 'max' => null, 'note' => 0.0]],
        ];

        $rule = AttendanceNoteRule::fromArray($data);
        $again = AttendanceNoteRule::fromArray($rule->toArray());

        $this->assertSame($rule->toArray(), $again->toArray());
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\NoteCalculationService;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs (sans DB) du service unifié de calcul de moyennes.
 *
 * Ce service est la SOURCE UNIQUE de toute logique de calcul de moyenne dans
 * KLASSCI (BTS + LMD). Les bugs critiques découverts en mai 2026 sont
 * verrouillés ici par des tests :
 *  - Inclusion des notes 0 légitimes (anti-bug "filter > 0").
 *  - Normalisation par barème (15/30 + 10/20 → 10, pas 12.5).
 *  - Exclusion stricte des absences.
 *  - Garde-fou contre barèmes / coefficients invalides.
 *
 * @see \App\Services\NoteCalculationService
 * @see docs/architecture/note-calculation-service.md
 */
class NoteCalculationServiceTest extends TestCase
{
    private NoteCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NoteCalculationService();
    }

    // -------------------------------------------------------------------
    // studentMatiereAverage()
    // -------------------------------------------------------------------

    /**
     * BUG #1 historique : 10/20 + 0/20 → moyenne attendue 5 (pas 10).
     */
    public function test_it_includes_zero_grade_in_average(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 0,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(5.0, $this->service->studentMatiereAverage($notes));
    }

    /**
     * BUG #2 historique : 15/30 (équiv. 10/20) + 10/20 → moyenne attendue 10 (pas 12.5).
     */
    public function test_it_normalizes_grades_by_bareme(): void
    {
        $notes = [
            ['note' => 15, 'coefficient' => 1, 'bareme' => 30, 'is_absent' => false],
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(10.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_excludes_absent_notes(): void
    {
        $notes = [
            ['note' => 12, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 0,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => true], // absence
        ];

        // Seule la note 12 est prise → moyenne = 12.
        $this->assertSame(12.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_applies_coefficient(): void
    {
        $notes = [
            ['note' => 8,  'coefficient' => 3, 'bareme' => 20, 'is_absent' => false],
            ['note' => 16, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        // ((8*3) + (16*1)) / (3+1) = 40 / 4 = 10
        $this->assertSame(10.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_handles_empty_notes_returns_zero(): void
    {
        $this->assertSame(0.0, $this->service->studentMatiereAverage([]));
    }

    public function test_it_handles_invalid_bareme_skips_silently(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 0,  'is_absent' => false], // skip
            ['note' => 14, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        // Seule la 2e note est prise → moyenne = 14.
        $this->assertSame(14.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_handles_negative_bareme_skips_silently(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => -5, 'is_absent' => false],
            ['note' => 14, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(14.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_rounds_to_two_decimals(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 11, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 13, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        // (10+11+13)/3 = 11.333... → arrondi 11.33
        $this->assertSame(11.33, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_skips_invalid_coefficient(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 0,  'bareme' => 20, 'is_absent' => false], // skip
            ['note' => 18, 'coefficient' => 1,  'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(18.0, $this->service->studentMatiereAverage($notes));
    }

    public function test_it_handles_mixed_baremes_and_coefficients(): void
    {
        // Cas réaliste : Devoir noté 12/20 coef 2 + Examen noté 30/40 coef 3.
        // Devoir normalisé : 12/20 * 20 = 12 (coef 2)  → 24
        // Examen normalisé : 30/40 * 20 = 15 (coef 3)  → 45
        // Total points : 69 / Total coefs : 5 → 13.8
        $notes = [
            ['note' => 12, 'coefficient' => 2, 'bareme' => 20, 'is_absent' => false],
            ['note' => 30, 'coefficient' => 3, 'bareme' => 40, 'is_absent' => false],
        ];

        $this->assertSame(13.8, $this->service->studentMatiereAverage($notes));
    }

    // -------------------------------------------------------------------
    // studentGeneralAverage()
    // -------------------------------------------------------------------

    public function test_it_calculates_general_average_pondere_by_matiere_coef(): void
    {
        // Math (coef 4) : 14, Français (coef 2) : 10, EPS (coef 1) : 18
        // (14*4 + 10*2 + 18*1) / (4+2+1) = (56+20+18) / 7 = 94/7 = 13.43
        $matieres = [
            ['moyenne' => 14, 'coefficient' => 4],
            ['moyenne' => 10, 'coefficient' => 2],
            ['moyenne' => 18, 'coefficient' => 1],
        ];

        $this->assertSame(13.43, $this->service->studentGeneralAverage($matieres));
    }

    public function test_it_returns_zero_when_general_average_has_no_matieres(): void
    {
        $this->assertSame(0.0, $this->service->studentGeneralAverage([]));
    }

    public function test_it_skips_invalid_coefficient_in_general_average(): void
    {
        $matieres = [
            ['moyenne' => 5,  'coefficient' => 0], // skip
            ['moyenne' => 15, 'coefficient' => 2],
        ];

        $this->assertSame(15.0, $this->service->studentGeneralAverage($matieres));
    }

    // -------------------------------------------------------------------
    // classEvaluationAverage()
    // -------------------------------------------------------------------

    public function test_it_calculates_class_evaluation_average(): void
    {
        // Évaluation sur 20 — 4 étudiants : 12, 16, 8, 10 → moyenne 11.5
        $notes = [
            ['note' => 12, 'is_absent' => false],
            ['note' => 16, 'is_absent' => false],
            ['note' => 8,  'is_absent' => false],
            ['note' => 10, 'is_absent' => false],
        ];

        $this->assertSame(11.5, $this->service->classEvaluationAverage($notes, 20));
    }

    public function test_it_excludes_absent_and_null_notes_in_class_evaluation_average(): void
    {
        $notes = [
            ['note' => 14, 'is_absent' => false],
            ['note' => 0,  'is_absent' => true],   // absent → skip
            ['note' => null, 'is_absent' => false], // pas saisi → skip
            ['note' => 6,  'is_absent' => false],
        ];

        // (14 + 6) / 2 = 10
        $this->assertSame(10.0, $this->service->classEvaluationAverage($notes, 20));
    }

    public function test_it_normalizes_class_evaluation_average_by_bareme(): void
    {
        // Évaluation sur 40 — notes brutes 30 et 20 → normalisées 15 et 10 → moyenne 12.5
        $notes = [
            ['note' => 30, 'is_absent' => false],
            ['note' => 20, 'is_absent' => false],
        ];

        $this->assertSame(12.5, $this->service->classEvaluationAverage($notes, 40));
    }

    public function test_it_returns_zero_when_class_eval_bareme_invalid(): void
    {
        $notes = [['note' => 10, 'is_absent' => false]];
        $this->assertSame(0.0, $this->service->classEvaluationAverage($notes, 0));
        $this->assertSame(0.0, $this->service->classEvaluationAverage($notes, -5));
    }

    // -------------------------------------------------------------------
    // classMatiereAverage()
    // -------------------------------------------------------------------

    public function test_it_calculates_class_matiere_average_from_student_averages(): void
    {
        $studentAverages = [10.5, 12, 14.25, 8.0];
        // (10.5 + 12 + 14.25 + 8.0) / 4 = 11.19
        $this->assertSame(11.19, $this->service->classMatiereAverage($studentAverages));
    }

    public function test_it_skips_null_values_in_class_matiere_average(): void
    {
        $studentAverages = [12, null, 14, null, 16];
        // (12 + 14 + 16) / 3 = 14
        $this->assertSame(14.0, $this->service->classMatiereAverage($studentAverages));
    }

    public function test_it_returns_zero_when_class_matiere_average_empty(): void
    {
        $this->assertSame(0.0, $this->service->classMatiereAverage([]));
        $this->assertSame(0.0, $this->service->classMatiereAverage([null, null]));
    }

    // -------------------------------------------------------------------
    // lmdUEAverage()
    // -------------------------------------------------------------------

    public function test_it_lmd_ue_average_pondere_by_ects_credits(): void
    {
        // ECUE1 : 14/20 coef 6 ECTS, ECUE2 : 10/20 coef 3 ECTS
        // (14*6 + 10*3) / (6+3) = (84+30)/9 = 12.67
        $ecues = [
            ['moyenne' => 14, 'credits' => 6],
            ['moyenne' => 10, 'credits' => 3],
        ];

        $this->assertSame(12.67, $this->service->lmdUEAverage($ecues));
    }

    public function test_it_lmd_ue_average_skips_zero_credits(): void
    {
        $ecues = [
            ['moyenne' => 14, 'credits' => 0], // skip
            ['moyenne' => 10, 'credits' => 3],
        ];

        $this->assertSame(10.0, $this->service->lmdUEAverage($ecues));
    }

    public function test_it_lmd_semester_average_is_alias_for_ue_average(): void
    {
        // UE1 : 12/20 (8 ECTS), UE2 : 14/20 (6 ECTS), UE3 : 8/20 (3 ECTS)
        // (12*8 + 14*6 + 8*3) / (8+6+3) = (96+84+24)/17 = 12.0
        $ues = [
            ['moyenne' => 12, 'credits' => 8],
            ['moyenne' => 14, 'credits' => 6],
            ['moyenne' => 8,  'credits' => 3],
        ];

        $expected = $this->service->lmdUEAverage($ues);
        $this->assertSame($expected, $this->service->lmdSemesterAverage($ues));
        $this->assertSame(12.0, $this->service->lmdSemesterAverage($ues));
    }

    // -------------------------------------------------------------------
    // lmdCreditsValidated()
    // -------------------------------------------------------------------

    public function test_it_lmd_credits_validated_at_threshold_10(): void
    {
        $ues = [
            ['moyenne' => 14, 'credits' => 6], // validée → 6
            ['moyenne' => 10, 'credits' => 3], // validée (>= 10) → 3
            ['moyenne' => 9,  'credits' => 4], // non validée → 0
        ];

        $this->assertSame(9, $this->service->lmdCreditsValidated($ues));
    }

    public function test_it_lmd_credits_validated_below_threshold_excluded(): void
    {
        $ues = [
            ['moyenne' => 9.99, 'credits' => 6], // pile sous le seuil → exclu
            ['moyenne' => 7,    'credits' => 3], // exclu
        ];

        $this->assertSame(0, $this->service->lmdCreditsValidated($ues));
    }

    public function test_it_lmd_credits_validated_with_custom_threshold(): void
    {
        $ues = [
            ['moyenne' => 14, 'credits' => 6], // validée à 12 → 6
            ['moyenne' => 11, 'credits' => 3], // exclue à 12 → 0
        ];

        $this->assertSame(6, $this->service->lmdCreditsValidated($ues, 12.0));
    }

    // -------------------------------------------------------------------
    // getMention()
    // -------------------------------------------------------------------

    public function test_it_get_mention_returns_cames_labels(): void
    {
        $this->assertSame('Très Bien',   $this->service->getMention(18.5));
        $this->assertSame('Très Bien',   $this->service->getMention(16.0));
        $this->assertSame('Bien',        $this->service->getMention(15.99));
        $this->assertSame('Bien',        $this->service->getMention(14.0));
        $this->assertSame('Assez Bien',  $this->service->getMention(13.5));
        $this->assertSame('Assez Bien',  $this->service->getMention(12.0));
        $this->assertSame('Passable',    $this->service->getMention(11));
        $this->assertSame('Passable',    $this->service->getMention(10.0));
        $this->assertSame('Insuffisant', $this->service->getMention(9.99));
        $this->assertSame('Insuffisant', $this->service->getMention(0.0));
    }

    public function test_appreciation_is_alias_for_mention(): void
    {
        foreach ([0.0, 9.99, 10.0, 12.0, 14.0, 16.0, 18.5] as $m) {
            $this->assertSame(
                $this->service->getMention($m),
                $this->service->getAppreciation($m),
                "getAppreciation should equal getMention for {$m}"
            );
        }
    }
}

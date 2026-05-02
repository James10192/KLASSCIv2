<?php

namespace Tests\Feature\Notes;

use App\Services\NoteCalculationService;
use PHPUnit\Framework\TestCase;

/**
 * JS ↔ PHP consistency contract for the moyenne calculation.
 *
 * Le calcul de moyenne doit donner le MÊME résultat côté JS (saisie temps
 * réel `/esbtp/notes`) que côté PHP (bulletins, exports, preview impact).
 *
 * Ce test EST le contrat. Les 10 cas listés ci-dessous sont la SOURCE DE
 * VÉRITÉ. Toute évolution de la formule de moyenne doit :
 *  1. Modifier ces 10 cas si le résultat attendu change.
 *  2. Vérifier que la fonction JS `calculateStudentAverage()` (cf.
 *     `resources/views/esbtp/notes/index.blade.php`) produit les MÊMES
 *     résultats sur ces 10 cas.
 *
 * Voir docs/architecture/note-calculation-service.md section "Cas tests
 * JS ↔ PHP" — ces cas y sont reproduits dans un format facilement
 * adaptable pour Jest / Vitest si la suite frontend est mise en place.
 *
 * Cas tests = paires (input, expected) couvrant :
 *  - inclusion des notes 0 légitimes
 *  - normalisation par barème
 *  - exclusion des absences
 *  - coefficients > 1
 *  - barèmes mixtes
 *  - barèmes invalides ignorés
 *  - notes décimales
 *  - cas vide
 *  - une seule note
 *  - notes saisies en string (cas réel des inputs HTML)
 *
 * @see \App\Services\NoteCalculationService::studentMatiereAverage()
 */
class JsPhpCalculationConsistencyTest extends TestCase
{
    private NoteCalculationService $calc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new NoteCalculationService();
    }

    /**
     * @dataProvider jsPhpConsistencyCases
     */
    public function test_php_calculation_matches_documented_contract(
        string $label,
        array $notes,
        float $expected
    ): void {
        $actual = $this->calc->studentMatiereAverage($notes);
        $this->assertSame(
            $expected,
            $actual,
            "Cas '{$label}' : attendu {$expected}, obtenu {$actual}"
        );
    }

    /**
     * 10 cas tests de référence — à reproduire à l'identique côté JS.
     */
    public static function jsPhpConsistencyCases(): array
    {
        return [
            // Cas #1 — Inclusion note 0 légitime
            'cas-01-zero-grade-included' => [
                'cas-01-zero-grade-included',
                [
                    ['note' => 10, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                    ['note' => 0,  'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                5.0,
            ],

            // Cas #2 — Normalisation par barème (15/30 = 10/20, + 10/20 → 10)
            'cas-02-bareme-normalization' => [
                'cas-02-bareme-normalization',
                [
                    ['note' => 15, 'bareme' => 30, 'coefficient' => 1, 'is_absent' => false],
                    ['note' => 10, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                10.0,
            ],

            // Cas #3 — Absence exclue (la note saisie = 0 mais is_absent = true)
            'cas-03-absent-excluded' => [
                'cas-03-absent-excluded',
                [
                    ['note' => 12, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                    ['note' => 0,  'bareme' => 20, 'coefficient' => 1, 'is_absent' => true],
                ],
                12.0,
            ],

            // Cas #4 — Coefficient > 1
            'cas-04-coefficient-applied' => [
                'cas-04-coefficient-applied',
                [
                    ['note' => 8,  'bareme' => 20, 'coefficient' => 3, 'is_absent' => false],
                    ['note' => 16, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                10.0, // (8*3 + 16*1) / 4 = 10
            ],

            // Cas #5 — Barèmes + coefs mixtes (devoir 12/20 coef 2 + examen 30/40 coef 3)
            'cas-05-mixed-baremes-coefs' => [
                'cas-05-mixed-baremes-coefs',
                [
                    ['note' => 12, 'bareme' => 20, 'coefficient' => 2, 'is_absent' => false],
                    ['note' => 30, 'bareme' => 40, 'coefficient' => 3, 'is_absent' => false],
                ],
                13.8, // (12*2 + 15*3) / 5 = 69/5 = 13.8
            ],

            // Cas #6 — Barème invalide (0) ignoré silencieusement
            'cas-06-invalid-bareme-skipped' => [
                'cas-06-invalid-bareme-skipped',
                [
                    ['note' => 10, 'bareme' => 0,  'coefficient' => 1, 'is_absent' => false],
                    ['note' => 14, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                14.0,
            ],

            // Cas #7 — Notes décimales (avec arrondi)
            'cas-07-decimal-grades' => [
                'cas-07-decimal-grades',
                [
                    ['note' => 12.5, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                    ['note' => 13.75, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                13.13, // (12.5 + 13.75)/2 = 13.125 → arrondi 13.13
            ],

            // Cas #8 — Aucune note → 0 (côté JS aussi : pas de division par zéro)
            'cas-08-empty-returns-zero' => [
                'cas-08-empty-returns-zero',
                [],
                0.0,
            ],

            // Cas #9 — Une seule note exploitable
            'cas-09-single-note' => [
                'cas-09-single-note',
                [
                    ['note' => 17, 'bareme' => 20, 'coefficient' => 1, 'is_absent' => false],
                ],
                17.0,
            ],

            // Cas #10 — Inputs en string (cas réel des <input type="number"> HTML)
            'cas-10-string-inputs' => [
                'cas-10-string-inputs',
                [
                    ['note' => '14', 'bareme' => '20', 'coefficient' => '2', 'is_absent' => false],
                    ['note' => '8',  'bareme' => '20', 'coefficient' => '1', 'is_absent' => false],
                ],
                12.0, // (14*2 + 8*1) / 3 = 36/3 = 12
            ],
        ];
    }
}

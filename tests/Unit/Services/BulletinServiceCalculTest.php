<?php

namespace Tests\Unit\Services;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Services\BulletinService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs (sans DB) pour la logique de calcul de moyenne par matière
 * dans BulletinService::computeMoyenneFromNotesData().
 *
 * Couvre les 2 bugs critiques fixés en mai 2026 :
 *  - JS excluait les notes 0 légitimes (10 et 0 → 10 au lieu de 5)
 *  - Bulletin ne normalisait pas par barème (15/30 + 10/20 → 12.5 au lieu de 10)
 *
 * Pas de DB, pas de Eloquent : on teste uniquement la pure function.
 */
class BulletinServiceCalculTest extends TestCase
{
    private BulletinService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mockery sur le seul collaborateur du constructeur — la fonction testée
        // ne touche à rien de DB-dépendant donc les méthodes du mock ne sont jamais appelées.
        $absenceService = Mockery::mock(ESBTPAbsenceService::class);
        $this->service = new BulletinService($absenceService, new BtsAnnualClassMapResolver(new BtsPhaseResolver()));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * BUG #1 reproduit : 2 notes 10/20 et 0/20 → moyenne attendue 5 (pas 10).
     */
    public function test_it_includes_zero_grade_in_average(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 0,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(5.0, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * BUG #2 reproduit : note 15/30 + note 10/20 → moyenne attendue 10
     * (15/30*20 = 10, 10/20*20 = 10, moyenne = 10).
     */
    public function test_it_normalizes_grades_by_bareme(): void
    {
        $notes = [
            ['note' => 15, 'coefficient' => 1, 'bareme' => 30, 'is_absent' => false],
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(10.0, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Une absence ne doit JAMAIS être comptée comme un 0 — l'évaluation est exclue.
     * 1 note 10/20 + 1 absence → moyenne = 10.
     */
    public function test_it_excludes_absent_grades(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 0,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => true],
        ];

        $this->assertSame(10.0, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Coefficients respectés : 10 (coef 2) + 20 (coef 1) → (20 + 20) / 3 ≈ 13.33.
     */
    public function test_it_applies_coefficient_correctly(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 2, 'bareme' => 20, 'is_absent' => false],
            ['note' => 20, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(13.33, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Aucune note → 0 (pas d'exception, pas de division par 0).
     */
    public function test_it_handles_empty_notes_returns_zero(): void
    {
        $this->assertSame(0.0, $this->service->computeMoyenneFromNotesData([]));
    }

    /**
     * Garde-fou : barème 0 ou négatif → la note est silencieusement ignorée
     * (ne crash pas, ne divise pas par 0).
     */
    public function test_it_handles_invalid_bareme_skips_note(): void
    {
        $notes = [
            ['note' => 10, 'coefficient' => 1, 'bareme' => 0,  'is_absent' => false],  // ignoré
            ['note' => 15, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],  // compté → 15
        ];

        $this->assertSame(15.0, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Une note pleine (30/30) doit donner 20 après normalisation, pas un débordement.
     */
    public function test_it_caps_bareme_normalization_correctly(): void
    {
        $notes = [
            ['note' => 30, 'coefficient' => 1, 'bareme' => 30, 'is_absent' => false],
        ];

        $this->assertSame(20.0, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Arrondi à 2 décimales : (10/3) * 20 ≈ 66.66... mais on cherche un cas réaliste.
     * Avec note 10 (coef 1) + note 20 (coef 2) + note 0 (coef 0) on aurait soucis.
     * Cas concret : 3 notes égales 10 mais pondération particulière → résultat /3 arrondi.
     * Ici : 5 (coef 1) + 10 (coef 1) + 5 (coef 1) → 20/3 = 6.666... → arrondi 6.67.
     */
    public function test_it_rounds_to_two_decimals(): void
    {
        $notes = [
            ['note' => 5,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 10, 'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
            ['note' => 5,  'coefficient' => 1, 'bareme' => 20, 'is_absent' => false],
        ];

        $this->assertSame(6.67, $this->service->computeMoyenneFromNotesData($notes));
    }

    /**
     * Bonus : robustesse aux clés manquantes — `bareme` absent → défaut 20,
     * `is_absent` absent → défaut false, `coefficient` absent → défaut 1.
     */
    public function test_it_uses_default_values_when_keys_missing(): void
    {
        $notes = [
            ['note' => 12],  // bareme défaut 20, coef défaut 1, non absent
            ['note' => 8],
        ];

        $this->assertSame(10.0, $this->service->computeMoyenneFromNotesData($notes));
    }
}

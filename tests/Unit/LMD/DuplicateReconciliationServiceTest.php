<?php

namespace Tests\Unit\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\DuplicateReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du service de détection des doublons UE/ECUE LMD.
 *
 * Couvre :
 *  - similarité / normalisation (pures, sans DB)
 *  - 2 UE même nom, codes différents → 1 groupe de doublons
 *  - pas de faux positif sur noms clairement distincts
 *  - 2 ECUE même nom → 1 groupe
 */
class DuplicateReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DuplicateReconciliationService();
    }

    public function test_normalize_strips_accents_case_and_collapses_spaces(): void
    {
        $this->assertSame('physique des materiaux', $this->service->normalizeName("  Physique   des Matériaux  "));
        $this->assertSame('resistance des materiaux', $this->service->normalizeName('Résistance des Matériaux (RDM)'));
    }

    public function test_similarity_identical_names_is_100(): void
    {
        $this->assertSame(100.0, $this->service->similarity('Physique des matériaux', 'PHYSIQUE DES MATERIAUX'));
    }

    public function test_similarity_distinct_names_is_low(): void
    {
        $this->assertLessThan(50, $this->service->similarity('Géologie', 'Comptabilité analytique'));
    }

    public function test_detects_two_ues_same_name_different_codes_as_one_group(): void
    {
        $niveau = $this->niveau();

        ESBTPUniteEnseignement::create([
            'name' => 'Physique des matériaux', 'code' => 'BPM301',
            'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);
        ESBTPUniteEnseignement::create([
            'name' => 'Physique des matériaux', 'code' => 'TPPM305',
            'credit' => 5, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);

        $groups = $this->service->detectDuplicateUes(['threshold' => 85]);

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]['count']);
        $this->assertTrue($groups[0]['discrepancies']['code']);
        $this->assertTrue($groups[0]['discrepancies']['credit']);
    }

    public function test_does_not_group_distinct_ue_names(): void
    {
        $niveau = $this->niveau();

        ESBTPUniteEnseignement::create([
            'name' => 'Géologie appliquée', 'code' => 'GEO1',
            'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);
        ESBTPUniteEnseignement::create([
            'name' => 'Comptabilité analytique', 'code' => 'CPT1',
            'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);

        $groups = $this->service->detectDuplicateUes(['threshold' => 85]);

        $this->assertCount(0, $groups);
    }

    public function test_same_level_only_prevents_cross_semester_grouping(): void
    {
        $niveau = $this->niveau();

        ESBTPUniteEnseignement::create([
            'name' => 'Physique des matériaux', 'code' => 'A1',
            'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);
        ESBTPUniteEnseignement::create([
            'name' => 'Physique des matériaux', 'code' => 'A2',
            'credit' => 6, 'semestre' => 5, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);

        $sameLevel = $this->service->detectDuplicateUes(['threshold' => 85, 'same_level_only' => true]);
        $this->assertCount(0, $sameLevel);

        $relaxed = $this->service->detectDuplicateUes(['threshold' => 85, 'same_level_only' => false]);
        $this->assertCount(1, $relaxed);
    }

    public function test_detects_two_ecues_same_name_as_one_group(): void
    {
        $niveau = $this->niveau();
        $ueA = ESBTPUniteEnseignement::create([
            'name' => 'UE A', 'code' => 'UEA', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);
        $ueB = ESBTPUniteEnseignement::create([
            'name' => 'UE B', 'code' => 'UEB', 'credit' => 6, 'semestre' => 3, 'niveau_id' => $niveau->id, 'is_active' => true,
        ]);

        ESBTPMatiere::create([
            'name' => 'Résistance des matériaux', 'code' => 'BRDM1',
            'unite_enseignement_id' => $ueA->id, 'niveau_etude_id' => $niveau->id,
            'credit_ecue' => 3, 'coefficient_ecue' => 2, 'is_active' => true,
        ]);
        ESBTPMatiere::create([
            'name' => 'Résistance des matériaux', 'code' => 'TPRDM1',
            'unite_enseignement_id' => $ueB->id, 'niveau_etude_id' => $niveau->id,
            'credit_ecue' => 4, 'coefficient_ecue' => 3, 'is_active' => true,
        ]);

        $groups = $this->service->detectDuplicateEcues(['threshold' => 85]);

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]['count']);
        $this->assertTrue($groups[0]['discrepancies']['credit']);
        $this->assertTrue($groups[0]['discrepancies']['coefficient']);
    }

    private function niveau(): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::create([
            'name' => 'Licence 2', 'code' => 'L2', 'type' => 'Licence', 'year' => 2, 'is_active' => true,
        ]);
    }
}

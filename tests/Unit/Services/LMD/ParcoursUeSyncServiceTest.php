<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\ParcoursUeSyncService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the pure-function diff logic. No DB, no Eloquent.
 * Verifies that re-syncs preserve unmodified pivot data and only touch what changed.
 */
class ParcoursUeSyncServiceTest extends TestCase
{
    private ParcoursUeSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ParcoursUeSyncService();
    }

    public function test_attaches_new_links_when_current_is_empty(): void
    {
        $current = [];
        $desired = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
            '2_1' => ['ue_id' => 2, 'semestre' => 1, 'is_optional' => true, 'ordre' => 5],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertCount(2, $diff['attach']);
        $this->assertEmpty($diff['update']);
        $this->assertEmpty($diff['detach']);
        $this->assertEmpty($diff['unchanged']);
    }

    public function test_marks_identical_links_as_unchanged_no_writes(): void
    {
        $row = ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0];
        $current = ['1_1' => $row];
        $desired = ['1_1' => $row];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertEmpty($diff['attach']);
        $this->assertEmpty($diff['update']);
        $this->assertEmpty($diff['detach']);
        $this->assertCount(1, $diff['unchanged']);
    }

    public function test_updates_only_when_pivot_data_differs(): void
    {
        $current = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];
        $desired = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => true, 'ordre' => 0],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertCount(1, $diff['update']);
        $this->assertSame(true, $diff['update'][0]['is_optional']);
    }

    public function test_detaches_missing_links_in_sync_mode(): void
    {
        $current = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
            '2_1' => ['ue_id' => 2, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];
        $desired = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertEmpty($diff['attach']);
        $this->assertCount(1, $diff['detach']);
        $this->assertSame(2, $diff['detach'][0]['ue_id']);
    }

    public function test_append_mode_never_detaches_existing_links(): void
    {
        $current = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
            '2_1' => ['ue_id' => 2, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];
        $desired = [
            '3_1' => ['ue_id' => 3, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];

        $diff = $this->service->computeDiff($current, $desired, false);

        $this->assertCount(1, $diff['attach']);
        $this->assertEmpty($diff['detach'], 'append mode must never detach');
    }

    public function test_preserves_unrelated_links_when_updating_one(): void
    {
        $stable = ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0];
        $current = [
            '1_1' => $stable,
            '2_1' => ['ue_id' => 2, 'semestre' => 1, 'is_optional' => false, 'ordre' => 5],
        ];
        $desired = [
            '1_1' => $stable,
            '2_1' => ['ue_id' => 2, 'semestre' => 1, 'is_optional' => true, 'ordre' => 5],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertCount(1, $diff['unchanged'], 'untouched row stays untouched');
        $this->assertSame(1, $diff['unchanged'][0]['ue_id']);
        $this->assertCount(1, $diff['update'], 'only changed row is updated');
        $this->assertSame(2, $diff['update'][0]['ue_id']);
        $this->assertEmpty($diff['detach']);
    }

    /**
     * « Absent » n'est pas « nul ».
     *
     * Le modal « Lier à des parcours » n'envoie que le semestre et l'ordre. S'il
     * comptait comme une demande d'effacer le crédit propre à la maquette, ce
     * crédit disparaîtrait à chaque enregistrement, sans que personne ne l'ait
     * demandé — et sans un message.
     */
    public function test_un_appel_qui_ne_parle_pas_du_credit_ne_l_efface_pas(): void
    {
        $current = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0, 'credit' => 6],
        ];
        $desired = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertEmpty($diff['update'], 'aucune écriture : le crédit en base reste tel quel');
        $this->assertCount(1, $diff['unchanged']);
    }

    public function test_un_credit_nul_explicite_est_ecrit(): void
    {
        $current = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0, 'credit' => 6],
        ];
        $desired = [
            '1_1' => ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0, 'credit' => null],
        ];

        $diff = $this->service->computeDiff($current, $desired, true);

        $this->assertCount(1, $diff['update'], 'null explicite = « pas de crédit propre à cette maquette »');
        $this->assertArrayHasKey('credit', $diff['update'][0]);
        $this->assertNull($diff['update'][0]['credit']);
    }

    public function test_un_credit_identique_ne_declenche_aucune_ecriture(): void
    {
        $ligne = ['ue_id' => 1, 'semestre' => 1, 'is_optional' => false, 'ordre' => 0, 'credit' => 6];

        $diff = $this->service->computeDiff(['1_1' => $ligne], ['1_1' => $ligne], true);

        $this->assertEmpty($diff['update']);
        $this->assertCount(1, $diff['unchanged']);
    }
}

<?php

namespace Tests\Unit\Services\Notes;

use App\Domain\Notes\SaisieGroupeeDeNotes;
use PHPUnit\Framework\TestCase;

final class NoteBulkSnapshotTest extends TestCase
{
    public function test_null_expected_is_different_from_absent_expected_key(): void
    {
        $this->assertTrue(SaisieGroupeeDeNotes::snapshotConcorde(null, ['7_21' => null], '7_21'));
        $this->assertFalse(SaisieGroupeeDeNotes::snapshotConcorde(null, [], '7_21'));
    }

    public function test_detects_a_note_modified_elsewhere(): void
    {
        $expected = ['7_21' => ['note' => 12.0, 'absent' => false, 'validee' => false]];
        $this->assertTrue(SaisieGroupeeDeNotes::snapshotConcorde(
            ['note' => 12.0, 'absent' => false, 'validee' => false], $expected, '7_21'
        ));
        $this->assertFalse(SaisieGroupeeDeNotes::snapshotConcorde(
            ['note' => 14.0, 'absent' => false, 'validee' => false], $expected, '7_21'
        ));
        $this->assertFalse(SaisieGroupeeDeNotes::snapshotConcorde(
            ['note' => 12.0, 'absent' => false, 'validee' => true], $expected, '7_21'
        ));
    }
}

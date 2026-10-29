<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use Tests\TestCase;

class ESBTPLMDBulletinJuryScopeTest extends TestCase
{
    public function test_scope_includes_year_parcours_class_and_semester(): void
    {
        $jury = new ESBTPLMDJury([
            'annee_universitaire_id' => 10,
            'parcours_id' => 20,
            'classe_id' => 30,
            'semestre' => 4,
        ]);

        $query = ESBTPLMDBulletin::query()->forJury($jury);

        $this->assertStringContainsString('parcours_id', $query->toSql());
        $this->assertSame([10, 20, 30, 4], $query->getBindings());
    }
}

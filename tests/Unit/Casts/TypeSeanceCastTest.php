<?php

namespace Tests\Unit\Casts;

use App\Casts\TypeSeanceCast;
use App\Enums\TypeSeance;
use App\Models\ESBTPSeanceCours;
use Tests\TestCase;

class TypeSeanceCastTest extends TestCase
{
    public function test_legacy_value_is_read_as_autre(): void
    {
        $cast = new TypeSeanceCast();

        $this->assertSame(
            TypeSeance::AUTRE,
            $cast->get(new ESBTPSeanceCours(), 'type_seance', 'cours', [])
        );
    }

    public function test_enum_and_legacy_values_are_written_as_canonical_strings(): void
    {
        $cast = new TypeSeanceCast();
        $model = new ESBTPSeanceCours();

        $this->assertSame('CM', $cast->set($model, 'type_seance', TypeSeance::CM, []));
        $this->assertSame('EXAMEN', $cast->set($model, 'type_seance', 'examen', []));
        $this->assertSame('AUTRE', $cast->set($model, 'type_seance', 'cours', []));
        $this->assertSame('AUTRE', $cast->set($model, 'type_seance', null, []));
    }
}

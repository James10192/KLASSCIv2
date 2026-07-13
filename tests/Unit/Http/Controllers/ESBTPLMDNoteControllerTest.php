<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ESBTPLMDNoteController;
use ReflectionMethod;
use Tests\TestCase;

class ESBTPLMDNoteControllerTest extends TestCase
{
    public function test_bulk_note_invalidation_contexts_include_class_and_students(): void
    {
        $method = new ReflectionMethod(ESBTPLMDNoteController::class, 'bulkNoteInvalidationContexts');
        $method->setAccessible(true);

        $contexts = $method->invoke(new ESBTPLMDNoteController, [
            'classId' => 10,
            'academicYearId' => 20,
            'period' => 'semestre3',
        ], [101, 102, 101, 0]);

        $this->assertSame([
            ['classId' => 10, 'academicYearId' => 20, 'period' => 'semestre3', 'studentId' => null],
            ['classId' => 10, 'academicYearId' => 20, 'period' => 'semestre3', 'studentId' => 101],
            ['classId' => 10, 'academicYearId' => 20, 'period' => 'semestre3', 'studentId' => 102],
        ], $contexts);
    }
}

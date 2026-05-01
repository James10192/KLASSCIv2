<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Comptabilite\TotalDuResult;
use PHPUnit\Framework\TestCase;

class TotalDuResultTest extends TestCase
{
    public function test_empty_returns_zeros(): void
    {
        $result = TotalDuResult::empty();

        $this->assertSame(0.0, $result->totalDue);
        $this->assertSame(0, $result->countDue);
    }

    public function test_to_array_exposes_canonical_keys(): void
    {
        $result = new TotalDuResult(totalDue: 1234.5, countDue: 7);

        $this->assertSame(['totalDue' => 1234.5, 'countDue' => 7], $result->toArray());
    }
}

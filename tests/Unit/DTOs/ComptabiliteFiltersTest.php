<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ComptabiliteFiltersTest extends TestCase
{
    public function test_empty_returns_null_filters(): void
    {
        $filters = ComptabiliteFilters::empty();

        $this->assertNull($filters->anneeId);
        $this->assertNull($filters->filiereId);
        $this->assertNull($filters->classeId);
    }

    public function test_from_request_casts_strings_to_int(): void
    {
        $request = Request::create('/dashboard', 'GET', [
            'annee' => '12',
            'filiere' => '3',
            'classe' => '99',
        ]);

        $filters = ComptabiliteFilters::fromRequest($request);

        $this->assertSame(12, $filters->anneeId);
        $this->assertSame(3, $filters->filiereId);
        $this->assertSame(99, $filters->classeId);
    }

    public function test_from_request_handles_missing_and_empty_params(): void
    {
        $request = Request::create('/dashboard', 'GET', [
            'annee' => '',
            // filiere absent
            'classe' => null,
        ]);

        $filters = ComptabiliteFilters::fromRequest($request);

        $this->assertNull($filters->anneeId);
        $this->assertNull($filters->filiereId);
        $this->assertNull($filters->classeId);
    }
}

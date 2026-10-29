<?php

namespace Tests\Unit\Domain\Analytics;

use App\Domain\Analytics\DTOs\AnalyticsContext;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AnalyticsContextTest extends TestCase
{
    public function test_empty_returns_all_nulls(): void
    {
        $ctx = AnalyticsContext::empty();

        $this->assertNull($ctx->anneeId);
        $this->assertNull($ctx->filiereId);
        $this->assertNull($ctx->classeId);
        $this->assertNull($ctx->etudiantId);
    }

    public function test_from_request_casts_to_int(): void
    {
        $request = Request::create('/analytics', 'GET', [
            'annee' => '4',
            'filiere' => '12',
            'classe' => '88',
            'etudiant' => '1234',
        ]);

        $ctx = AnalyticsContext::fromRequest($request);

        $this->assertSame(4, $ctx->anneeId);
        $this->assertSame(12, $ctx->filiereId);
        $this->assertSame(88, $ctx->classeId);
        $this->assertSame(1234, $ctx->etudiantId);
    }

    public function test_hash_is_stable_for_same_context(): void
    {
        $a = new AnalyticsContext(4, 12, null, null);
        $b = new AnalyticsContext(4, 12, null, null);

        $this->assertSame($a->hash(), $b->hash());
    }

    public function test_hash_differs_for_different_context(): void
    {
        $a = new AnalyticsContext(4, 12, null, null);
        $b = new AnalyticsContext(4, 13, null, null);

        $this->assertNotSame($a->hash(), $b->hash());
    }
}

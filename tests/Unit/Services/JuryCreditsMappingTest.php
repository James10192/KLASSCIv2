<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ESBTPLMDBulletin;
use PHPUnit\Framework\TestCase;

class JuryCreditsMappingTest extends TestCase
{
    public function test_bulletin_credits_keep_their_values(): void
    {
        $bulletin = new ESBTPLMDBulletin;
        $bulletin->setRawAttributes(['credits_capitalises' => 24, 'credits_totaux' => 30], true);

        $this->assertSame(24, $bulletin->credits_capitalises);
        $this->assertSame(30, $bulletin->credits_totaux);
    }

    public function test_missing_credits_stay_unknown_instead_of_becoming_zero(): void
    {
        $bulletin = new ESBTPLMDBulletin;
        $bulletin->setRawAttributes(['credits_capitalises' => null, 'credits_totaux' => null], true);

        $this->assertNull($bulletin->credits_capitalises);
        $this->assertNull($bulletin->credits_totaux);
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPLMDJuryMembre;
use Tests\TestCase;

class ESBTPLMDJuryMembreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('audit.enabled', false);
    }

    public function test_only_the_present_member_can_sign(): void
    {
        $member = new ESBTPLMDJuryMembre;
        $member->setRawAttributes(['user_id' => 42, 'present' => true], true);

        $this->assertTrue($member->canBeSignedBy(42));
        $this->assertFalse($member->canBeSignedBy(41));
    }

    public function test_an_absent_member_cannot_sign(): void
    {
        $member = new ESBTPLMDJuryMembre;
        $member->setRawAttributes(['user_id' => 42, 'present' => false], true);

        $this->assertFalse($member->canBeSignedBy(42));
    }
}

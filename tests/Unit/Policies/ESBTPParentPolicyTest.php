<?php

namespace Tests\Unit\Policies;

use App\Models\ESBTPParent;
use App\Models\User;
use App\Policies\ESBTPParentPolicy;
use Mockery;
use Tests\TestCase;

class ESBTPParentPolicyTest extends TestCase
{
    public function test_it_requires_the_explicit_parent_chatbot_permission(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->with('parent_chatbot.manage')->andReturn(false);

        $this->assertFalse((new ESBTPParentPolicy)->manageChatbot($user, new ESBTPParent));
    }

    public function test_it_allows_only_an_active_parent_record_in_the_current_tenant(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('can')->with('parent_chatbot.manage')->andReturn(true);

        $this->assertTrue((new ESBTPParentPolicy)->manageChatbot($user, new ESBTPParent));
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ScolariteClerkCapabilities;
use App\Services\TenantScolariteSettings;
use Mockery;
use Tests\TestCase;

class ScolariteClerkCapabilitiesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pedagogie_pack_excludes_planning_and_teachers(): void
    {
        $this->assertNotContains('lmd.planning.view', ScolariteClerkCapabilities::PEDAGOGIE);
        $this->assertNotContains('lmd.planning.edit', ScolariteClerkCapabilities::PEDAGOGIE);
        $this->assertNotContains('teachers.view', ScolariteClerkCapabilities::PEDAGOGIE);
        $this->assertContains('inscriptions.create', ScolariteClerkCapabilities::PEDAGOGIE);
        $this->assertContains('inscriptions.validate', ScolariteClerkCapabilities::PEDAGOGIE);
        $this->assertContains('students.edit', ScolariteClerkCapabilities::PEDAGOGIE);
    }

    public function test_clerk_without_settings_gets_nothing_extra(): void
    {
        $caps = $this->caps(lmd: false, pedagogie: false);

        $this->assertFalse($caps->grants($this->clerk(), 'inscriptions.create'));
        $this->assertFalse($caps->grants($this->clerk(), 'module.lmd.access'));
    }

    public function test_pedagogie_setting_grants_inscriptions_not_planning(): void
    {
        $caps = $this->caps(lmd: false, pedagogie: true);
        $clerk = $this->clerk();

        $this->assertTrue($caps->grants($clerk, 'inscriptions.create'));
        $this->assertTrue($caps->grants($clerk, 'students.edit'));
        $this->assertFalse($caps->grants($clerk, 'lmd.planning.edit'));
        $this->assertFalse($caps->grants($this->stranger(), 'inscriptions.create'));
    }

    public function test_lmd_setting_grants_module_not_planning(): void
    {
        $caps = $this->caps(lmd: true, pedagogie: false);
        $clerk = $this->clerk();

        $this->assertTrue($caps->grants($clerk, 'module.lmd.access'));
        $this->assertFalse($caps->grants($clerk, 'lmd.planning.view'));
        $this->assertFalse($caps->grants($clerk, 'inscriptions.create'));
    }

    private function caps(bool $lmd, bool $pedagogie): ScolariteClerkCapabilities
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('clerkLmdAccess')->andReturn($lmd);
        $settings->shouldReceive('clerkPedagogieAccess')->andReturn($pedagogie);

        return new ScolariteClerkCapabilities($settings);
    }

    private function clerk(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasPermissionTo')->with('identity.registrar_clerk')->andReturn(true);

        return $user;
    }

    private function stranger(): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasPermissionTo')->with('identity.registrar_clerk')->andReturn(false);

        return $user;
    }
}

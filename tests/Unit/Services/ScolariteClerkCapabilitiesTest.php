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
        // Le service scolarite qui corrige un dossier doit pouvoir reprendre la
        // classe d'une inscription deja validee, sans passer par la direction.
        $this->assertContains('inscriptions.edit_validated', ScolariteClerkCapabilities::PEDAGOGIE);
    }

    public function test_teachers_pack_excludes_unified_personnel(): void
    {
        $this->assertContains('teachers.create', ScolariteClerkCapabilities::TEACHERS);
        $this->assertContains('teachers.edit', ScolariteClerkCapabilities::TEACHERS);
        $this->assertContains('module.enseignants.access', ScolariteClerkCapabilities::TEACHERS);
        $this->assertNotContains('personnel.view', ScolariteClerkCapabilities::TEACHERS);
        $this->assertNotContains('personnel.manage', ScolariteClerkCapabilities::TEACHERS);
        $this->assertNotContains('teachers.delete', ScolariteClerkCapabilities::TEACHERS);
    }

    public function test_clerk_without_settings_gets_nothing_extra(): void
    {
        $caps = $this->caps();

        $this->assertFalse($caps->grants($this->clerk(), 'inscriptions.create'));
        $this->assertFalse($caps->grants($this->clerk(), 'module.lmd.access'));
        $this->assertFalse($caps->grants($this->clerk(), 'teachers.create'));
    }

    public function test_pedagogie_setting_grants_inscriptions_not_planning(): void
    {
        $caps = $this->caps(pedagogie: true);
        $clerk = $this->clerk();

        $this->assertTrue($caps->grants($clerk, 'inscriptions.create'));
        $this->assertTrue($caps->grants($clerk, 'students.edit'));
        $this->assertFalse($caps->grants($clerk, 'lmd.planning.edit'));
        $this->assertFalse($caps->grants($this->stranger(), 'inscriptions.create'));
    }

    public function test_lmd_setting_grants_module_not_planning(): void
    {
        $caps = $this->caps(lmd: true);
        $clerk = $this->clerk();

        $this->assertTrue($caps->grants($clerk, 'module.lmd.access'));
        $this->assertFalse($caps->grants($clerk, 'lmd.planning.view'));
        $this->assertFalse($caps->grants($clerk, 'inscriptions.create'));
    }

    public function test_teachers_setting_grants_clerk_and_registrar_not_unified(): void
    {
        $caps = $this->caps(teachers: true);

        $this->assertTrue($caps->grants($this->clerk(), 'teachers.create'));
        $this->assertTrue($caps->grants($this->registrar(), 'teachers.edit'));
        $this->assertTrue($caps->grants($this->registrar(), 'module.enseignants.access'));
        $this->assertFalse($caps->grants($this->clerk(), 'personnel.view'));
        $this->assertFalse($caps->grants($this->stranger(), 'teachers.create'));
        $this->assertFalse($caps->grants($this->registrar(), 'inscriptions.create'));
    }

    private function caps(bool $lmd = false, bool $pedagogie = false, bool $teachers = false): ScolariteClerkCapabilities
    {
        $settings = Mockery::mock(TenantScolariteSettings::class);
        $settings->shouldReceive('clerkLmdAccess')->andReturn($lmd);
        $settings->shouldReceive('clerkPedagogieAccess')->andReturn($pedagogie);
        $settings->shouldReceive('manageTeachers')->andReturn($teachers);

        return new ScolariteClerkCapabilities($settings);
    }

    private function clerk(): User
    {
        return $this->identityUser(clerk: true);
    }

    private function registrar(): User
    {
        return $this->identityUser(registrar: true);
    }

    private function stranger(): User
    {
        return $this->identityUser();
    }

    private function identityUser(bool $clerk = false, bool $registrar = false): User
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasPermissionTo')->andReturnUsing(function (string $permission) use ($clerk, $registrar) {
            return match ($permission) {
                'identity.registrar_clerk' => $clerk,
                'identity.registrar' => $registrar,
                default => false,
            };
        });

        return $user;
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\PermissionSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSyncServiceParentChatbotDependencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_heals_parent_chatbot_management_for_existing_privileged_roles(): void
    {
        $baseline = Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        foreach (['superAdmin', 'secretaire', 'serviceTechnique'] as $name) {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);
            $role->givePermissionTo($baseline);
        }

        app(PermissionSyncService::class)->run();

        foreach (['superAdmin', 'secretaire', 'serviceTechnique'] as $name) {
            $this->assertTrue(
                Role::findByName($name, 'web')->hasPermissionTo('parent_chatbot.manage'),
                $name . ' must receive the parent chatbot management dependency.',
            );
        }
    }
}

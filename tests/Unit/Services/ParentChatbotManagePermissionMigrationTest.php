<?php

namespace Tests\Unit\Services;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentChatbotManagePermissionMigrationTest extends TestCase
{
    private const CONNECTION = 'parent_chatbot_permission_migration_test';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', self::CONNECTION);
        config()->set('database.connections.' . self::CONNECTION, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge(self::CONNECTION);
        DB::setDefaultConnection(self::CONNECTION);
        DB::reconnect(self::CONNECTION);

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
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect(self::CONNECTION);

        parent::tearDown();
    }

    public function test_it_grants_the_parent_chatbot_permission_to_the_expected_roles_idempotently(): void
    {
        DB::table('roles')->insert(array_map(fn (string $name): array => [
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ], ['superAdmin', 'secretaire', 'serviceTechnique']));

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $permissionId = DB::table('permissions')
            ->where('name', 'parent_chatbot.manage')
            ->where('guard_name', 'web')
            ->value('id');

        $this->assertNotNull($permissionId);
        $this->assertSame(3, DB::table('role_has_permissions')->where('permission_id', $permissionId)->count());

        $migration->down();

        $this->assertSame(0, DB::table('role_has_permissions')->where('permission_id', $permissionId)->count());
        $this->assertSame(1, DB::table('permissions')->where('id', $permissionId)->count());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_08_01_000002_grant_parent_chatbot_manage_permission.php');
    }
}

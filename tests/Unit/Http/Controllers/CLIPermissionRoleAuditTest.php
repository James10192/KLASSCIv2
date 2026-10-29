<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\API\CLI\CLIPermissionController;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class CLIPermissionRoleAuditTest extends TestCase
{
    private Capsule $database;

    /** @var array<string, class-string> */
    private array $originalMorphMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalMorphMap = Relation::morphMap() ?? [];

        $app = new Container();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        $app->instance('app', $app);
        $app->instance('config', new Repository([
            'auth' => ['providers' => ['users' => ['model' => RoleAuditUser::class]]],
            'permission' => [
                'table_names' => ['model_has_roles' => 'model_has_roles'],
                'column_names' => ['model_morph_key' => 'subject_id'],
            ],
        ]));

        $this->database = new Capsule($app);
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $this->database->schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        $this->database->schema()->create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('subject_id');
        });
    }

    protected function tearDown(): void
    {
        Relation::morphMap($this->originalMorphMap, false);
        Facade::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_user_count_ignores_deleted_users_and_orphaned_pivots(): void
    {
        $this->database->table('users')->insert([
            ['id' => 1, 'deleted_at' => null],
            ['id' => 2, 'deleted_at' => now()],
        ]);
        $this->database->table('model_has_roles')->insert([
            ['role_id' => 10, 'model_type' => RoleAuditUser::class, 'subject_id' => 1],
            ['role_id' => 10, 'model_type' => RoleAuditUser::class, 'subject_id' => 2],
            ['role_id' => 10, 'model_type' => RoleAuditUser::class, 'subject_id' => 999],
        ]);

        $this->assertSame([10 => 1], $this->invokeUserCountsByRole([10]));
    }

    public function test_user_count_honours_the_model_morph_alias(): void
    {
        Relation::morphMap(['audit_user' => RoleAuditUser::class], false);
        $this->database->table('users')->insert(['id' => 1, 'deleted_at' => null]);
        $this->database->table('model_has_roles')->insert([
            'role_id' => 20,
            'model_type' => 'audit_user',
            'subject_id' => 1,
        ]);

        $this->assertSame([20 => 1], $this->invokeUserCountsByRole([20]));
    }

    /** @param array<int, int> $roleIds */
    private function invokeUserCountsByRole(array $roleIds): array
    {
        $reflection = new \ReflectionClass(CLIPermissionController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        return $reflection->getMethod('userCountsByRole')->invoke($controller, $roleIds);
    }
}

class RoleAuditUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];
}

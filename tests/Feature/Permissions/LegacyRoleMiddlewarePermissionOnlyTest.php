<?php

namespace Tests\Feature\Permissions;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class LegacyRoleMiddlewarePermissionOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['system.manage', 'identity.teach', 'identity.coordinate', 'identity.student'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        Role::findOrCreate('enseignant', 'web');
    }

    private function requestFor(User $user): Request
    {
        $request = Request::create('/legacy-role-test', 'GET');
        $request->setUserResolver(static fn () => $user);

        return $request;
    }

    public function test_le_nom_superadmin_seul_ne_passe_plus_le_middleware_legacy(): void
    {
        $user = User::factory()->create();
        $user->assignRole('superAdmin');
        Auth::login($user);
        $this->expectException(HttpException::class);
        app(CheckRole::class)->handle($this->requestFor($user), fn () => response('autorise'), 'superAdmin');
    }

    public function test_la_permission_permet_l_acces_sans_le_role_correspondant(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('identity.teach');
        Auth::login($user);
        $response = app(CheckRole::class)->handle($this->requestFor($user), fn () => response('autorise'), 'enseignant|teacher');
        $this->assertSame('autorise', $response->getContent());
        $this->assertFalse($user->hasRole('enseignant'));
    }

    public function test_une_permission_retiree_bloque_meme_le_role_enseignant(): void
    {
        $user = User::factory()->create();
        $user->assignRole('enseignant');
        Auth::login($user);
        $this->expectException(HttpException::class);
        app(CheckRole::class)->handle($this->requestFor($user), fn () => response('autorise'), 'enseignant');
    }

    public function test_aucune_route_web_active_n_exige_le_nom_d_un_role(): void
    {
        foreach ([
            'dashboard.superadmin', 'dashboard.secretaire', 'dashboard.etudiant',
            'teacher.dashboard', 'coordinateur.profile', 'coordinateur.attendance-dashboard',
            'esbtp.roles.index', 'esbtp.teacher.attendance.index',
            'esbtp.mon-profil.index', 'esbtp.mes-notifications.index',
        ] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            foreach ($route->gatherMiddleware() as $middleware) {
                $this->assertFalse(str_starts_with($middleware, 'role:'), "$name porte encore $middleware");
            }
        }
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\DashboardWidgetRegistry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\TestCase;

/**
 * Lot 9 — Tests du service DashboardWidgetRegistry.
 *
 * Pas de RefreshDatabase, pas d'accès DB. Mock User + roles.
 * Le config 'dashboard_widgets' est chargé par Laravel boot du TestCase
 * (via config_path('dashboard_widgets.php')).
 */
class DashboardWidgetRegistryTest extends TestCase
{
    private DashboardWidgetRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new DashboardWidgetRegistry();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Crée un mock User avec rôles + permissions arbitraires.
     *
     * @param  list<string>  $roleNames
     * @param  list<string>  $allowedPermissions  Permissions retournées vraies par can()
     * @param  array|null   $dashboardWidgets    Préférences user (null = défaut)
     */
    private function userMock(array $roleNames, array $allowedPermissions = [], ?array $dashboardWidgets = null): User
    {
        $user = Mockery::mock(User::class)->makePartial();

        $rolesCollection = new EloquentCollection(array_map(function ($name) {
            $role = new \stdClass();
            $role->name = $name;
            return $role;
        }, $roleNames));

        $user->shouldReceive('getAttribute')->with('roles')->andReturn($rolesCollection);
        $user->roles = $rolesCollection;

        $user->shouldReceive('can')->andReturnUsing(function ($permission) use ($allowedPermissions) {
            return in_array($permission, $allowedPermissions, true);
        });

        $user->dashboard_widgets = $dashboardWidgets;

        return $user;
    }

    public function test_all_returns_widgets_with_keys(): void
    {
        $all = $this->registry->all();

        $this->assertGreaterThanOrEqual(12, $all->count(), 'Le catalogue doit contenir au moins 12 widgets.');
        $this->assertTrue($all->has('students.total'));
        $this->assertTrue($all->has('paiements.pending'));
        $this->assertTrue($all->has('annonces.recent'));

        // Chaque widget doit avoir une clé dans le payload
        $first = $all->first();
        $this->assertArrayHasKey('key', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('permission', $first);
        $this->assertArrayHasKey('partial', $first);
    }

    public function test_available_for_filters_by_permission(): void
    {
        $user = $this->userMock(['secretaire'], ['students.view', 'inscriptions.view']);

        $available = $this->registry->availableFor($user);

        // Les widgets students.* requièrent 'students.view' → présents
        $this->assertTrue($available->has('students.total'));
        // Les widgets paiements.* requièrent 'paiements.view' ou .validate → absents
        $this->assertFalse($available->has('paiements.pending'));
        $this->assertFalse($available->has('paiements.month_total'));
    }

    public function test_available_for_with_no_permission_returns_empty(): void
    {
        $user = $this->userMock(['etudiant'], []);

        $available = $this->registry->availableFor($user);

        $this->assertCount(0, $available);
    }

    public function test_user_layout_falls_back_to_role_defaults_when_prefs_null(): void
    {
        // SuperAdmin a toutes les permissions
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        $user = $this->userMock(['superAdmin'], $allPermissions, null);

        $layout = $this->registry->userLayout($user);

        $this->assertGreaterThan(0, $layout->count());
        // students.total et paiements.pending sont par défaut pour superAdmin
        $keys = $layout->pluck('key')->all();
        $this->assertContains('students.total', $keys);
        $this->assertContains('paiements.pending', $keys);
    }

    public function test_user_layout_respects_explicit_preferences(): void
    {
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        $prefs = [
            ['key' => 'paiements.pending', 'enabled' => true],
            ['key' => 'students.total', 'enabled' => true],
        ];
        $user = $this->userMock(['superAdmin'], $allPermissions, $prefs);

        $layout = $this->registry->userLayout($user);

        // Ordre préservé selon prefs
        $keys = $layout->pluck('key')->all();
        $this->assertEquals(['paiements.pending', 'students.total'], $keys);
    }

    public function test_user_layout_filters_disabled_widgets(): void
    {
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        $prefs = [
            ['key' => 'students.total', 'enabled' => true],
            ['key' => 'paiements.pending', 'enabled' => false],
            ['key' => 'inscriptions.this_year', 'enabled' => true],
        ];
        $user = $this->userMock(['superAdmin'], $allPermissions, $prefs);

        $layout = $this->registry->userLayout($user);

        $keys = $layout->pluck('key')->all();
        $this->assertContains('students.total', $keys);
        $this->assertNotContains('paiements.pending', $keys);
        $this->assertContains('inscriptions.this_year', $keys);
    }

    public function test_user_layout_filters_widgets_revoked_by_permission_change(): void
    {
        // L'utilisateur a explicitement choisi paiements.pending mais a perdu paiements.validate
        $prefs = [
            ['key' => 'students.total', 'enabled' => true],
            ['key' => 'paiements.pending', 'enabled' => true],
        ];
        $user = $this->userMock(['secretaire'], ['students.view'], $prefs);

        $layout = $this->registry->userLayout($user);

        $keys = $layout->pluck('key')->all();
        $this->assertContains('students.total', $keys);
        $this->assertNotContains('paiements.pending', $keys);
    }

    public function test_user_layout_returns_empty_when_no_widgets_match_role_defaults(): void
    {
        // L'étudiant a la perm 'notes.view_own' mais aucun widget par défaut configuré
        $user = $this->userMock(['etudiant'], ['notes.view_own'], null);

        $layout = $this->registry->userLayout($user);

        // Aucun widget n'a 'etudiant' dans default_for_roles
        $this->assertCount(0, $layout);
    }

    public function test_defaults_for_role_returns_widgets_for_named_role(): void
    {
        $defaults = $this->registry->defaultsForRole('comptable');

        $keys = $defaults->pluck('key')->all();
        $this->assertContains('paiements.pending', $keys);
        $this->assertContains('paiements.month_total', $keys);
        $this->assertContains('paiements.outstanding_balance', $keys);
        $this->assertNotContains('users.active', $keys, 'users.active est superAdmin only');
    }

    public function test_defaults_for_role_returns_empty_for_unknown_role(): void
    {
        $defaults = $this->registry->defaultsForRole('role_inexistant_xyz');

        $this->assertCount(0, $defaults);
    }

    public function test_exists_returns_true_for_valid_keys(): void
    {
        $this->assertTrue($this->registry->exists('students.total'));
        $this->assertTrue($this->registry->exists('paiements.pending'));
        $this->assertFalse($this->registry->exists('totally.fake.widget'));
    }

    public function test_build_layout_payload_sanitizes_keys(): void
    {
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        $user = $this->userMock(['superAdmin'], $allPermissions);

        $payload = $this->registry->buildLayoutPayload($user, [
            'students.total',
            'fake.key',                  // doit être filtré (n'existe pas)
            'paiements.pending',
            'students.total',            // doublon doit être filtré
        ]);

        $this->assertCount(2, $payload);
        $this->assertEquals('students.total', $payload[0]['key']);
        $this->assertTrue($payload[0]['enabled']);
        $this->assertEquals('paiements.pending', $payload[1]['key']);
    }

    public function test_build_layout_payload_filters_widgets_user_cant_see(): void
    {
        $user = $this->userMock(['secretaire'], ['students.view']);

        $payload = $this->registry->buildLayoutPayload($user, [
            'students.total',
            'paiements.pending',  // pas la permission paiements.validate
        ]);

        $this->assertCount(1, $payload);
        $this->assertEquals('students.total', $payload[0]['key']);
    }

    public function test_available_grouped_for_groups_by_group_field(): void
    {
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        $user = $this->userMock(['superAdmin'], $allPermissions);

        $grouped = $this->registry->availableGroupedFor($user);

        $this->assertTrue($grouped->has('Étudiants'));
        $this->assertTrue($grouped->has('Paiements'));
        $this->assertTrue($grouped->has('Inscriptions'));
    }

    public function test_default_for_roles_intersection_works_with_multiple_roles(): void
    {
        $allPermissions = $this->registry->all()->pluck('permission')->unique()->values()->all();
        // User a deux rôles : caissier (annonces.recent défaut) ET enseignant
        $user = $this->userMock(['caissier', 'enseignant'], $allPermissions, null);

        $layout = $this->registry->userLayout($user);

        $keys = $layout->pluck('key')->all();
        // annonces.recent a 'caissier' et 'enseignant' tous les deux dans default_for_roles
        $this->assertContains('annonces.recent', $keys);
        // inscriptions.this_year est défaut pour caissier (pas enseignant) → présent grâce à l'intersection
        $this->assertContains('inscriptions.this_year', $keys);
    }

    public function test_widget_partial_paths_are_well_formed(): void
    {
        // Garde-fou : tous les partials doivent suivre la convention dashboard.widgets.*
        foreach ($this->registry->all() as $widget) {
            $this->assertArrayHasKey('partial', $widget);
            $this->assertStringStartsWith('dashboard.widgets.', $widget['partial']);
        }
    }

    public function test_widget_size_is_sm_md_or_lg(): void
    {
        $allowed = ['sm', 'md', 'lg'];
        foreach ($this->registry->all() as $widget) {
            $size = $widget['size'] ?? 'sm';
            $this->assertContains($size, $allowed, "Widget {$widget['key']} a une taille invalide : {$size}");
        }
    }
}

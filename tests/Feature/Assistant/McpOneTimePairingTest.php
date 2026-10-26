<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Mcp\RequetePontSignee;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class McpOneTimePairingTest extends TestCase
{
    use DatabaseTransactions;

    private const ISSUER = 'https://demo.auth0.example/';
    private const ORG = 'org_pairing_demo';
    private const SECRET = 'pairing-test-secret-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckInstalled::class, EnsureInstalled::class]);
        config()->set('cache.default', 'array');
        Cache::store('array')->flush();
        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.read_only.enabled', true);
        config()->set('mcp.pairing.enabled', true);
        config()->set('mcp.bridge.secret', self::SECRET);
        config()->set('mcp.bridge.organization', self::ORG);
        config()->set('mcp.bridge.issuer', self::ISSUER);
        RateLimiter::clear('mcp:pair:org:'.hash('sha256', self::ORG));
    }

    private function staff(bool $authorized = true): User
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'username' => 'mcp_'.Str::lower(Str::random(12)),
            'is_active' => true,
        ]));
        if ($authorized) {
            Role::firstOrCreate(['name' => 'enseignant', 'guard_name' => 'web']);
            $user->assignRole('enseignant');
            $user->givePermissionTo(Permission::findOrCreate('mcp.connections.connect', 'web'));
        }

        return $user;
    }

    private function admin(): User
    {
        $admin = $this->staff(false);
        Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $admin->assignRole('superAdmin');
        $admin->givePermissionTo(Permission::findOrCreate('mcp.connections.approve', 'web'));
        $admin->givePermissionTo(Permission::findOrCreate('mcp.connections.connect', 'web'));

        return $admin;
    }

    private function approve(User $admin, User $target, string $code): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($admin)->postJson('/nanan/mcp/connections/approve', [
            'code' => $code,
            'user_id' => $target->id,
            'identity_verified' => '1',
        ]);
    }

    private function bridge(string $subject, string $tool, array $arguments = []): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode([
            'issuer' => self::ISSUER,
            'subject' => $subject,
            'organization' => self::ORG,
            'tool' => $tool,
            'arguments' => $arguments,
            'iat' => time(),
            'nonce' => (string) Str::uuid(),
        ], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/mcp-bridge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_KLASSCI_MCP_SIGNATURE' => hash_hmac('sha256', $payload, self::SECRET),
        ], $payload);
    }

    public function test_preuve_oauth_signee_puis_consentement_laravel_lie_une_seule_fois(): void
    {
        $code = str_repeat('p', 43);
        $user = $this->staff();

        // Sans lien, aucune consultation ni profil scolaire.
        $this->bridge('auth0|pair-user', 'get_connected_profile')->assertStatus(403);

        $this->bridge('auth0|pair-user', 'create_pairing_challenge', ['code' => $code])
            ->assertOk()->assertExactJson(['accepted' => true, 'expires_in_seconds' => 600]);

        $this->assertDatabaseHas('mcp_link_challenges', [
            'code_hash' => hash('sha256', $code),
            'identity_hash' => RequetePontSignee::identiteHash(self::ISSUER, 'auth0|pair-user', self::ORG),
        ]);

        // Sans approbation distincte, le code n'a aucune autorité.
        $this->actingAs($user)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);

        $this->approve($this->admin(), $user, $code)
            ->assertOk()->assertJson(['approved' => true]);

        $this->actingAs($user)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertOk()->assertJson(['linked' => true]);

        $this->bridge('auth0|pair-user', 'get_connected_profile')
            ->assertOk()->assertJson(['name' => $user->name]);

        $this->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);

        $this->assertDatabaseCount('mcp_external_identities', 1);
    }

    public function test_code_faux_expire_ou_sans_consentement_ne_cree_aucun_lien(): void
    {
        $code = str_repeat('q', 43);
        $user = $this->staff();

        $this->bridge('auth0|expires', 'create_pairing_challenge', ['code' => $code])
            ->assertOk();

        $this->actingAs($user)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => false,
        ])->assertStatus(422);

        DB::table('mcp_link_challenges')->where('code_hash', hash('sha256', $code))
            ->update(['expires_at' => now()->subSecond()]);

        $this->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);
        $this->assertDatabaseCount('mcp_external_identities', 0);

        $this->bridge('auth0|expires', 'get_connected_profile')->assertStatus(403);
    }

    public function test_compte_non_personnel_ne_peut_pas_reclamer_un_code(): void
    {
        $code = str_repeat('r', 43);
        $this->bridge('auth0|student-attempt', 'create_pairing_challenge', ['code' => $code])->assertOk();

        $this->actingAs($this->staff(false))->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertForbidden();

        $this->assertDatabaseCount('mcp_external_identities', 0);
    }

    public function test_flag_desactive_et_code_deja_lie_ne_donnent_aucun_acces(): void
    {
        config()->set('mcp.pairing.enabled', false);
        $code = str_repeat('s', 43);

        $this->bridge('auth0|flagged', 'create_pairing_challenge', ['code' => $code])
            ->assertStatus(403);
        $this->actingAs($this->staff())->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertNotFound();
        $this->assertDatabaseCount('mcp_link_challenges', 0);
    }

    public function test_le_pont_refuse_codes_invalides_et_limite_les_demandes_du_meme_sujet(): void
    {
        $subject = 'auth0|rate-limited-link';
        $this->bridge($subject, 'create_pairing_challenge', ['code' => 'trop-court'])
            ->assertStatus(422);

        foreach (['1', '2', '3'] as $digit) {
            $this->bridge($subject, 'create_pairing_challenge', [
                'code' => str_repeat($digit, 43),
            ])->assertOk();
        }

        $this->bridge($subject, 'create_pairing_challenge', [
            'code' => str_repeat('4', 43),
        ])->assertStatus(429);
    }

    public function test_un_sujet_oauth_deja_lie_ne_peut_pas_etre_relie_silencieusement(): void
    {
        $code = str_repeat('y', 43);
        $staff = $this->staff();

        $this->bridge('auth0|already-linked', 'create_pairing_challenge',
            ['code' => $code])->assertOk();
        $this->approve($this->admin(), $staff, $code)->assertOk();
        $this->actingAs($staff)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertOk();

        $this->bridge('auth0|already-linked', 'create_pairing_challenge',
            ['code' => str_repeat('z', 43)])->assertStatus(409);
    }

    public function test_retention_limitee_supprime_seulement_les_challenges_expirees_anciens(): void
    {
        $ancien = str_repeat('v', 43);
        $recent = str_repeat('w', 43);
        $this->bridge('auth0|old-token', 'create_pairing_challenge', ['code' => $ancien])->assertOk();
        $this->bridge('auth0|recent-token', 'create_pairing_challenge', ['code' => $recent])->assertOk();
        DB::table('mcp_link_challenges')->where('code_hash', hash('sha256', $ancien))
            ->update(['expires_at' => now()->subDays(2)]);
        \Illuminate\Support\Facades\Artisan::call('nanan:mcp-prune-challenges', ['--limit' => 1000]);
        $this->assertDatabaseMissing('mcp_link_challenges', ['code_hash' => hash('sha256', $ancien)]);
        $this->assertDatabaseHas('mcp_link_challenges', ['code_hash' => hash('sha256', $recent)]);
    }

    public function test_nettoyage_sql_purge_nonces_anciens_sans_toucher_aux_nonces_recents(): void
    {
        $older = hash('sha256', 'old-nonce');
        $recent = hash('sha256', 'recent-nonce');
        DB::table('mcp_bridge_nonces')->insert([
            [
                'nonce_hash' => $older,
                'expires_at' => now()->subMinutes(20),
                'created_at' => now()->subMinutes(22),
            ],
            [
                'nonce_hash' => $recent,
                'expires_at' => now()->addSeconds(120),
                'created_at' => now(),
            ],
        ]);

        \Illuminate\Support\Facades\Artisan::call('nanan:mcp-prune-challenges', ['--limit' => 1]);

        $this->assertDatabaseMissing('mcp_bridge_nonces', ['nonce_hash' => $older]);
        $this->assertDatabaseHas('mcp_bridge_nonces', ['nonce_hash' => $recent]);
    }

    public function test_approbation_exige_permission_distincte_et_verification_explicite(): void
    {
        $code = str_repeat('n', 43);
        $employee = $this->staff();
        $other = $this->staff();
        $admin = $this->admin();

        $this->bridge('auth0|double-check', 'create_pairing_challenge',
            ['code' => $code])->assertOk();

        $this->actingAs($employee)->postJson('/nanan/mcp/connections/approve', [
            'code' => $code, 'user_id' => $employee->id, 'identity_verified' => '1',
        ])->assertForbidden();

        $this->actingAs($admin)->postJson('/nanan/mcp/connections/approve', [
            'code' => $code, 'user_id' => $employee->id, 'identity_verified' => false,
        ])->assertStatus(422);

        $this->approve($admin, $employee, $code)->assertOk();

        // Un autre employé qui possède le code ne peut pas voler le lien.
        $this->actingAs($other)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);

        // Même l'admin ne peut pas le réclamer pour lui-même.
        $this->actingAs($admin)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);

        $this->actingAs($employee)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertOk()->assertJson(['linked' => true]);

        $this->approve($admin, $employee, $code)->assertStatus(422);
        $this->assertDatabaseHas('mcp_link_challenges', [
            'code_hash' => hash('sha256', $code),
            'approved_user_id' => $employee->id,
            'approved_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_ne_peut_pas_approuver_pour_soi_et_code_reste_non_transmissible(): void
    {
        $code = str_repeat('h', 43);
        $admin = $this->admin();
        $this->bridge('auth0|selfapproval', 'create_pairing_challenge',
            ['code' => $code])->assertOk();

        $this->approve($admin, $admin, $code)->assertForbidden();
        $this->actingAs($admin)->postJson('/nanan/mcp/connections/claim', [
            'code' => $code, 'consent' => '1',
        ])->assertStatus(422);
        $this->assertDatabaseCount('mcp_external_identities', 0);
    }

    public function test_absence_de_preuve_hmac_refuse_le_bootstrap(): void
    {
        $this->postJson('/api/mcp-bridge', [
            'tool' => 'create_pairing_challenge',
            'arguments' => ['code' => str_repeat('t', 43)],
        ])->assertUnauthorized();
        $this->assertDatabaseCount('mcp_link_challenges', 0);
    }
}

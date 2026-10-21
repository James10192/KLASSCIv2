<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Mcp\CatalogueLectureExterne;
use App\Domain\Assistant\Mcp\RequetePontSignee;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/** Contrôle de bout en bout de la résolution d'identité locale, sans IA réelle. */
class McpBridgeTest extends TestCase
{
    use DatabaseTransactions;

    private const ISSUER = 'https://example.auth0.com/';
    private const ORG = 'org_mcpdemotest';
    private const SECRET = 'mcp-feature-secret-only-for-test-xxxxxxxxxxxxxxxxxxxxxxxx';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([CheckInstalled::class, EnsureInstalled::class]);
        config()->set('cache.default', 'array');
        Cache::store('array')->flush();
        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.read_only.enabled', true);
        config()->set('mcp.bridge.secret', self::SECRET);
        config()->set('mcp.bridge.organization', self::ORG);
        config()->set('mcp.bridge.issuer', self::ISSUER);
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'username' => 'mcp_'.Str::lower(Str::random(12)), 'is_active' => true,
        ]));
    }

    private function lier(User $user, string $subject, ?string $revocation = null): void
    {
        DB::table('mcp_external_identities')->insert([
            'identity_hash' => RequetePontSignee::identiteHash(self::ISSUER, $subject, self::ORG),
            'organization' => self::ORG,
            'user_id' => $user->id,
            'revoked_at' => $revocation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function demander(string $subject, array $changes = []): \Illuminate\Testing\TestResponse
    {
        $payload = array_replace([
            'issuer' => self::ISSUER,
            'subject' => $subject,
            'organization' => self::ORG,
            'tool' => 'search_classes',
            'arguments' => ['limit' => 1],
            'iat' => time(),
            'nonce' => (string) Str::uuid(),
        ], $changes);

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        return $this->call('POST', '/api/mcp-bridge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_KLASSCI_MCP_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
        ], $body);
    }

    public function test_liaison_authentifiee_donne_les_permissions_du_compte_local(): void
    {
        $user = $this->utilisateur();
        $this->lier($user, 'auth0|mcp-1');

        $catalogue = Mockery::mock(CatalogueLectureExterne::class);
        $catalogue->shouldReceive('executer')->once()
            ->withArgs(fn ($tool, $args, $resolved) =>
                $tool === 'search_classes' && $args === ['limit' => 1]
                && $resolved instanceof User && $resolved->id === $user->id
            )->andReturn(['results' => [['nom' => 'L1']], 'count' => 1]);
        app()->instance(CatalogueLectureExterne::class, $catalogue);

        $this->demander('auth0|mcp-1')
            ->assertOk()
            ->assertJson(['results' => [['nom' => 'L1']], 'count' => 1]);
    }

    public function test_profil_ne_repond_qu_apres_liaison_du_compte_local(): void
    {
        $user = $this->utilisateur();
        $this->lier($user, 'auth0|staff-profile');

        $premier = $this->demander('auth0|staff-profile', [
            'tool' => 'get_connected_profile',
            'arguments' => [],
        ])->assertOk()->assertJsonStructure(['id', 'name'])
          ->assertJson(['name' => $user->name])
          ;

        $this->assertStringContainsString('no-store', (string) $premier->headers->get('Cache-Control'));

        $this->assertStringStartsWith('kl_', (string) $premier->json('id'));
        $this->assertMatchesRegularExpression('/^kl_[0-9a-f]{24}$/', (string) $premier->json('id'));
        $this->assertNotSame((string) $user->id, (string) $premier->json('id'));

        $this->demander('auth0|not-linked', [
            'tool' => 'get_connected_profile',
            'arguments' => [],
        ])->assertStatus(403);
    }

    public function test_aucune_liaison_ni_revocation_ne_donnent_acces(): void
    {
        $user = $this->utilisateur();
        $this->lier($user, 'auth0|revoked', now()->toDateTimeString());

        $this->demander('auth0|intruder')->assertStatus(403);
        $this->demander('auth0|revoked')->assertStatus(403);
    }

    public function test_un_compte_desactive_et_un_autre_tenant_sont_refuses(): void
    {
        $user = $this->utilisateur();
        $this->lier($user, 'auth0|inactive');
        $user->update(['is_active' => false]);

        $this->demander('auth0|inactive')->assertStatus(403);
        $this->demander('auth0|inactive', ['organization' => 'org_someother'])->assertStatus(401);
    }
}

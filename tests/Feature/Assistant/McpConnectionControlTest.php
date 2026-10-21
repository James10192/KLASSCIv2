<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Mcp\RequetePontSignee;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Seul le propriétaire Laravel authentifié doit pouvoir révoquer son lien.
 * Les tests d'auth et de propriété sont indépendants des agents IA.
 */
class McpConnectionControlTest extends TestCase
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
        config()->set('mcp.read_only.enabled', true);
        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.bridge.organization', self::ORG);
        config()->set('mcp.bridge.issuer', self::ISSUER);
        config()->set('mcp.bridge.secret', self::SECRET);
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'username' => 'mcp_'.Str::lower(Str::random(12)),
            'is_active' => true,
        ]));
    }

    private function lier(User $user, string $subject): int
    {
        return (int) DB::table('mcp_external_identities')->insertGetId([
            'identity_hash' => RequetePontSignee::identiteHash(self::ISSUER, $subject, self::ORG),
            'organization' => self::ORG,
            'user_id' => $user->id,
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function signedProfile(string $subject): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode([
            'issuer' => self::ISSUER,
            'subject' => $subject,
            'organization' => self::ORG,
            'tool' => 'get_connected_profile',
            'arguments' => [],
            'iat' => time(),
            'nonce' => (string) Str::uuid(),
        ], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/mcp-bridge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_KLASSCI_MCP_SIGNATURE' => hash_hmac('sha256', $payload, self::SECRET),
        ], $payload);
    }

    public function test_connexions_inaccessibles_sans_session_laravel(): void
    {
        $this->get('/nanan/mcp/connections')->assertRedirect('/login');
        $this->post('/nanan/mcp/connections/1/revoke')->assertRedirect('/login');
    }

    public function test_un_compte_ne_voira_jamais_la_liaison_d_autrui(): void
    {
        $alice = $this->utilisateur();
        $bob = $this->utilisateur();
        $aliceId = $this->lier($alice, 'auth0|alice');
        $bobId = $this->lier($bob, 'auth0|bob');

        $list = $this->actingAs($alice)->getJson('/nanan/mcp/connections')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->json('connections');

        $this->assertCount(1, $list);
        $this->assertSame($aliceId, $list[0]['id']);
        $this->assertArrayNotHasKey('identity_hash', $list[0]);
        $this->assertArrayNotHasKey('subject', $list[0]);

        $this->postJson('/nanan/mcp/connections/'.$bobId.'/revoke')->assertNotFound();
        $this->assertNull(DB::table('mcp_external_identities')->where('id', $bobId)->value('revoked_at'));
    }

    public function test_revocation_par_le_proprietaire_coupe_immediatement_l_acces_au_pont(): void
    {
        $alice = $this->utilisateur();
        $linkId = $this->lier($alice, 'auth0|alice-revoke');

        $this->signedProfile('auth0|alice-revoke')->assertOk();

        $this->actingAs($alice)
            ->postJson('/nanan/mcp/connections/'.$linkId.'/revoke')
            ->assertOk()
            ->assertJson(['revoked' => true]);

        $this->signedProfile('auth0|alice-revoke')->assertStatus(403);

        // Seconde tentative idempotente : ne restaure jamais le lien.
        $this->postJson('/nanan/mcp/connections/'.$linkId.'/revoke')
            ->assertOk()->assertJson(['revoked' => true]);
        $this->assertNotNull(DB::table('mcp_external_identities')->where('id', $linkId)->value('revoked_at'));
    }

    public function test_desequiper_le_pont_n_empeche_pas_la_revocation_de_ses_autorisations(): void
    {
        $alice = $this->utilisateur();
        $linkId = $this->lier($alice, 'auth0|link-when-disabled');
        config()->set('mcp.bridge.enabled', false);

        $this->actingAs($alice)
            ->postJson('/nanan/mcp/connections/'.$linkId.'/revoke')
            ->assertOk()->assertJson(['revoked' => true]);
    }

    public function test_aucune_divulgation_si_l_organisation_n_est_pas_provisionnee(): void
    {
        config()->set('mcp.bridge.organization', '');
        $this->actingAs($this->utilisateur())
            ->getJson('/nanan/mcp/connections')->assertNotFound();
    }
}

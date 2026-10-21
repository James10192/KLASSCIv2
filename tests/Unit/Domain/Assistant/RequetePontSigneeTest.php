<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Mcp\RequetePontSignee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequetePontSigneeTest extends TestCase
{
    private const SECRET = 'bridge-secret-only-for-unit-tests-xxxxxxxxxxxxxxxx';
    private const ORG = 'org_aaaaaaaa';
    private const ISSUER = 'https://example.auth0.com/';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        Cache::store('array')->flush();
        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.read_only.enabled', true);
        config()->set('mcp.bridge.secret', self::SECRET);
        config()->set('mcp.bridge.organization', self::ORG);
        config()->set('mcp.bridge.issuer', self::ISSUER);
    }

    private function contenu(array $modifications = []): string
    {
        return json_encode(array_replace([
            'issuer' => self::ISSUER,
            'subject' => 'auth0|prof-8',
            'organization' => self::ORG,
            'tool' => 'search_classes',
            'arguments' => ['limit' => 1],
            'iat' => time(),
            'nonce' => (string) Str::uuid(),
        ], $modifications), JSON_THROW_ON_ERROR);
    }

    private function requete(string $body, ?string $secret = null): Request
    {
        return Request::create('/api/mcp-bridge', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_KLASSCI_MCP_SIGNATURE' => hash_hmac('sha256', $body, $secret ?? self::SECRET),
        ], $body);
    }

    public function test_signature_valide_renvoie_identite_et_refuse_rejeu(): void
    {
        $body = $this->contenu();
        $requete = $this->requete($body);
        $garde = new RequetePontSignee();
        $this->assertSame('auth0|prof-8', $garde->extraire($requete)['subject']);
        $this->assertNull($garde->extraire($requete), 'anti-rejeu');
    }

    public function test_refus_cas_d_identite_alteree_tenant_et_horodatage(): void
    {
        $garde = new RequetePontSignee();
        $body = $this->contenu();
        $mauvaiseSignature = $this->requete($body, 'another-different-secret-string-32-chars');
        $this->assertNull($garde->extraire($mauvaiseSignature));
        // Altérer le body mais conserver le HMAC du contenu original.
        $falsifie = Request::create('/api/mcp-bridge', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_KLASSCI_MCP_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
        ], $body.' ');
        $this->assertNull($garde->extraire($falsifie), 'signature du contenu exact');

        foreach ([
            ['issuer' => 'https://evil.example/'],
            ['organization' => 'org_other'],
            ['iat' => time() - 120],
            ['iat' => time() + 120],
            ['tool' => 'proposer_creation_classes'],
            ['subject' => ''],
            ['arguments' => 'not-an-object'],
            ['nonce' => 'fake-nonce'],
        ] as $incorrect) {
            $this->assertNull($garde->extraire($this->requete($this->contenu($incorrect))), json_encode($incorrect));
        }
    }

    public function test_defaut_ferme_avec_secret_absent_ou_interrupteur_desactive(): void
    {
        $body = $this->contenu();
        config()->set('mcp.bridge.enabled', false);
        $this->assertNull((new RequetePontSignee())->extraire($this->requete($body)));

        config()->set('mcp.bridge.enabled', true);
        config()->set('mcp.bridge.secret', '');
        $this->assertNull((new RequetePontSignee())->extraire($this->requete($body)));

        config()->set('mcp.bridge.secret', self::SECRET);
        config()->set('mcp.read_only.enabled', false);
        $this->assertNull((new RequetePontSignee())->extraire($this->requete($body)));
    }

    public function test_hash_lie_ensemble_issuer_sujet_et_organisation(): void
    {
        $hash = RequetePontSignee::identiteHash(self::ISSUER, 'auth0|prof-8', self::ORG);
        $this->assertNotSame($hash, RequetePontSignee::identiteHash(self::ISSUER, 'auth0|prof-8', 'org_other'));
        $this->assertNotSame($hash, RequetePontSignee::identiteHash(self::ISSUER, 'auth0|prof-9', self::ORG));
    }
}

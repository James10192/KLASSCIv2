<?php

namespace Tests\Feature\LMD;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Unit\Domain\OfficialDocuments\OfficialDocumentDatabaseTestCase;

class OfficialDocumentHttpTest extends OfficialDocumentDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $middleware = new \ReflectionProperty($kernel, 'middleware');
        $middleware->setAccessible(true);
        $middleware->setValue($kernel, array_values(array_filter(
            $middleware->getValue($kernel),
            fn (string $class): bool => $class !== \App\Http\Middleware\CheckInstalled::class,
        )));

        $this->createPermissionTables();
    }

    public function test_public_verification_is_non_enumerating_and_leaks_no_sensitive_fields(): void
    {
        $this->seedIssuableJury();
        $code = str_repeat('V', 48);
        $document = $this->knownDocument($code);

        $unknown = $this->postJson('/verifier-document-officiel', ['reference' => 'UNKNOWN', 'code' => str_repeat('X', 48)]);
        $wrong = $this->postJson('/verifier-document-officiel', ['reference' => $document->reference, 'code' => str_repeat('X', 48)]);
        $unknown->assertOk()->assertExactJson(['valid' => false]);
        $wrong->assertOk()->assertExactJson(['valid' => false]);

        $this->postJson('/verifier-document-officiel', ['reference' => $document->reference, 'code' => $code])
            ->assertOk()->assertJsonStructure(['valid', 'reference', 'document_type', 'issued_at'])
            ->assertJsonMissing(['path' => $document->path])
            ->assertJsonMissingPath('snapshot')->assertJsonMissingPath('checksum_sha256');
    }

    public function test_public_verification_page_uses_same_contract_without_leaking_code_or_snapshot(): void
    {
        $this->seedIssuableJury();
        $code = str_repeat('V', 48);
        $document = $this->knownDocument($code);

        $this->get('/verifier-document-officiel')
            ->assertOk()
            ->assertSee('Vérifier un document officiel')
            ->assertSee('Référence officielle')
            ->assertSee('Code de vérification');

        $this->post('/verifier-document-officiel', [
            'reference' => $document->reference,
            'code' => $code,
        ])
            ->assertOk()
            ->assertSee('Document officiel valide')
            ->assertSee($document->reference)
            ->assertDontSee($code)
            ->assertDontSee($document->path)
            ->assertDontSee('snapshot_sha256');
    }

    public function test_reference_throttle_is_global_across_different_ips(): void
    {
        $this->seedIssuableJury();
        RateLimiter::clear('official-document-verify-reference:'.hash('sha256', 'SAME-REFERENCE'));
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$attempt])
                ->postJson('/verifier-document-officiel', ['reference' => 'same-reference', 'code' => str_repeat('X', 48)])->assertOk();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/verifier-document-officiel', ['reference' => 'same-reference', 'code' => str_repeat('X', 48)])->assertStatus(429);
    }

    public function test_stream_requires_authentication_and_framework_signature(): void
    {
        $this->seedIssuableJury();
        $document = $this->knownDocument(str_repeat('V', 48));
        $url = app(OfficialDocumentDownloadService::class)->signedUrl($document);

        auth()->logout();
        $this->get($url)->assertRedirect();

        $this->actingAs($this->authorizedUser());
        $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get($url.'&document_token=invalid')->assertForbidden();
    }

    public function test_stream_rejects_expired_business_token_when_framework_signature_remains_valid(): void
    {
        $this->seedIssuableJury();
        $document = $this->knownDocument(str_repeat('V', 48));
        $query = $this->parseSignedStreamQuery($document);
        $expired = now()->subHour()->getTimestamp();

        $url = $this->buildSignedStreamUrl($document, [
            'inline' => (int) ($query['inline'] ?? 0),
            'document_expires' => $expired,
            'document_token' => $this->documentHmacToken($document, $expired),
        ]);

        $this->actingAs($this->authorizedUser());
        $this->get($url)->assertForbidden();
    }

    public function test_stream_rejects_forged_business_token_with_valid_framework_signature(): void
    {
        $this->seedIssuableJury();
        $document = $this->knownDocument(str_repeat('V', 48));
        $query = $this->parseSignedStreamQuery($document);
        $query['document_expires'] = (int) $query['document_expires'];

        $tampered = $this->tamperToken((string) $query['document_token']);
        $url = $this->buildSignedStreamUrl($document, [
            'inline' => (int) ($query['inline'] ?? 0),
            'document_expires' => (int) $query['document_expires'],
            'document_token' => $tampered,
        ]);

        $this->actingAs($this->authorizedUser());
        $this->get($url)->assertForbidden();
    }

    public function test_pv_rectification_route_supersedes_existing_document_with_required_reason(): void
    {
        $jury = $this->seedIssuableJury();
        $user = $this->authorizedUser();
        $first = app(\App\Domain\OfficialDocuments\Services\OfficialDocumentService::class)
            ->issueJuryPv($jury, $user);
        $jury->fresh()->forceFill(['status' => 'publie'])->save();

        $this->actingAs($user)
            ->postJson(route('esbtp.lmd.jurys.pv-rectify', $jury), ['motif' => 'court'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('esbtp.lmd.jurys.pv-rectify', $jury), [
                'motif' => 'Erreur matérielle validée par le jury.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('document.version', 2)
            ->assertJsonPath('document.status', OfficialDocument::STATUS_VALID)
            ->assertJsonPath('document.supersedes_document_id', $first->id)
            ->assertJsonMissingPath('document.path')
            ->assertJsonMissingPath('document.snapshot');

        $this->assertSame(OfficialDocument::STATUS_SUPERSEDED, $first->fresh()->status);
        $this->assertSame(
            'Erreur matérielle validée par le jury.',
            $first->fresh()->lifecycle_metadata['supersession_reason'],
        );
    }

    public function test_registered_routes_keep_canonical_permissions(): void
    {
        $reconcile = Route::getRoutes()->getByName('esbtp.lmd.jurys.pv-reconcile');
        $rectify = Route::getRoutes()->getByName('esbtp.lmd.jurys.pv-rectify');
        $stream = Route::getRoutes()->getByName('esbtp.lmd.jurys.official-documents.stream');
        $this->assertContains('permission:admin.access', $reconcile->gatherMiddleware());
        $this->assertContains('permission:lmd.jury.documents.reconcile', $reconcile->gatherMiddleware());
        $this->assertContains('permission:lmd.jury.publish', $rectify->gatherMiddleware());
        $this->assertContains('throttle:5,1', $rectify->gatherMiddleware());
        $this->assertContains('auth', $stream->gatherMiddleware());
        $this->assertContains('permission:admin.access', $stream->gatherMiddleware());
        $this->assertContains('permission:module.lmd.access', $stream->gatherMiddleware());
        $this->assertContains('paywall', $stream->gatherMiddleware());
        $this->assertContains('permission:lmd.jury.view', $stream->gatherMiddleware());
        $this->assertContains('signed', $stream->gatherMiddleware());
        $this->assertContains('throttle:30,1', $stream->gatherMiddleware());

        $publicForm = Route::getRoutes()->getByName('official-documents.verify.form');
        $publicVerify = Route::getRoutes()->getByName('official-documents.verify');
        $this->assertContains('throttle:30,1', $publicForm->gatherMiddleware());
        $this->assertNotContains('auth', $publicVerify->gatherMiddleware());
    }

    private function authorizedUser(): \App\Models\User
    {
        $user = \App\Models\User::query()->findOrFail(1);
        foreach (['admin.access', 'module.lmd.access', 'lmd.jury.view', 'lmd.jury.publish'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $user->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function buildSignedStreamUrl(OfficialDocument $document, array $query): string
    {
        return URL::temporarySignedRoute('esbtp.lmd.jurys.official-documents.stream', now()->addMinutes(5), [
            'document' => $document->id,
            'inline' => (int) ($query['inline'] ?? 0),
            'document_expires' => (int) $query['document_expires'],
            'document_token' => (string) $query['document_token'],
        ]);
    }

    private function parseSignedStreamQuery(OfficialDocument $document): array
    {
        $url = app(OfficialDocumentDownloadService::class)->signedUrl($document);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        return $query;
    }

    private function documentHmacToken(OfficialDocument $document, int $expires): string
    {
        $service = app(OfficialDocumentDownloadService::class);
        $method = new \ReflectionMethod($service, 'token');
        $method->setAccessible(true);

        return (string) $method->invoke($service, $document, $expires);
    }

    private function tamperToken(string $token): string
    {
        if ($token === '') {
            return '0';
        }

        $prefix = $token[0] === 'a' ? 'b' : 'a';

        return $prefix.substr($token, 1);
    }

    private function createPermissionTables(): void
    {
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
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function knownDocument(string $code): OfficialDocument
    {
        $jury = \App\Models\ESBTPLMDJury::query()->find(1);

        if (! $jury) {
            $jury = $this->seedIssuableJury();
        }

        return $this->knownVerifiableDocument($code);
    }

    private function knownVerifiableDocument(string $code): OfficialDocument
    {
        $path = 'official-documents/test.pdf';
        $content = '%PDF-known';
        Storage::put($path, $content);

        return OfficialDocument::query()->create($this->knownDocumentAttributes($code, $path, $content));
    }

    private function knownDocumentAttributes(string $code, string $path, string $content): array
    {
        $snapshot = ['document' => ['reference' => 'DOC-PV-KNOWN', 'version' => 1], 'safe' => true];

        return [
            'document_type' => 'lmd_jury_pv',
            'source_type' => 'test',
            'source_id' => 999,
            'series_key' => 'test-series',
            'version' => 1,
            'reference' => 'DOC-PV-KNOWN',
            'status' => 'valid',
            'valid_series_key' => 'test-series',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($content),
            'checksum_sha256' => hash('sha256', $content),
            'snapshot' => $snapshot,
            'snapshot_sha256' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'rules_version' => 'v1',
            'template_version' => 'v1',
            'renderer_version' => 'v1',
            'verification_code_digest' => hash('sha256', $code),
            'issued_by' => 1,
            'issued_at' => now(),
        ];
    }
}





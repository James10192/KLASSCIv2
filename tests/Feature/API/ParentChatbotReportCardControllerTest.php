<?php

namespace Tests\Feature\API;

use App\Http\Middleware\CheckInstalled;
use App\Models\ESBTPBulletin;
use App\Models\ParentChatbotLink;
use App\Services\ParentChatbot\ParentChatbotReportCardPdfRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\TestCase;

class ParentChatbotReportCardControllerTest extends TestCase
{
    private const PDF_BYTES = '%PDF-1.4 fake-report-card';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        config()->set('app.url', 'https://klassci.test');
        $this->withoutMiddleware(CheckInstalled::class);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('signature_directeur')->default(false);
            $table->boolean('signature_responsable')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->string('telephone')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('esbtp_etudiant_parent', function (Blueprint $table): void {
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->boolean('is_tuteur')->default(false);
            $table->string('relation')->nullable();
            $table->timestamps();
        });

        Schema::create('parent_chatbot_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->char('phone_hash', 64);
            $table->unsignedBigInteger('selected_student_id')->nullable();
            $table->string('status', 20);
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_valid_signed_link_streams_the_report_card_pdf_inline(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        $this->fakePdfRenderer();

        $response = $this->get($this->signedUrl($bulletinId, $linkId));

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('inline; filename="bulletin.pdf"', $response->headers->get('Content-Disposition'));
        $this->assertSame(self::PDF_BYTES, $response->getContent());
        $this->assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));

        $cacheControl = (string) $response->headers->get('Cache-Control');
        foreach (['private', 'no-store', 'max-age=0'] as $directive) {
            $this->assertStringContainsString($directive, $cacheControl);
        }
    }

    public function test_tampered_signature_is_rejected_before_any_rendering(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        $this->assertRendererIsNeverCalled();

        $this->get($this->signedUrl($bulletinId, $linkId).'&tampered=1')->assertStatus(403);
    }

    public function test_unpublished_report_card_is_gone_without_leaking_its_existence(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        ESBTPBulletin::query()->whereKey($bulletinId)->update(['is_published' => false]);
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl($bulletinId, $linkId)));
    }

    public function test_report_card_without_official_signatures_is_gone(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        ESBTPBulletin::query()->whereKey($bulletinId)->update(['signature_directeur' => false]);
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl($bulletinId, $linkId)));
    }

    public function test_revoked_parent_link_can_no_longer_open_the_report_card(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        ParentChatbotLink::query()->whereKey($linkId)->update([
            'status' => ParentChatbotLink::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl($bulletinId, $linkId)));
    }

    public function test_stopped_parent_link_can_no_longer_open_the_report_card(): void
    {
        [$bulletinId, $linkId] = $this->publishedReportCardForLinkedParent();
        ParentChatbotLink::query()->whereKey($linkId)->update([
            'status' => ParentChatbotLink::STATUS_STOPPED,
            'stopped_at' => now(),
        ]);
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl($bulletinId, $linkId)));
    }

    public function test_report_card_of_a_student_not_attached_to_the_parent_is_gone(): void
    {
        [, $linkId] = $this->publishedReportCardForLinkedParent();
        $foreignBulletinId = $this->publishedBulletinFor($this->studentId('Kone', 'Awa'));
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl($foreignBulletinId, $linkId)));
    }

    public function test_signed_link_without_a_parent_link_reference_is_gone(): void
    {
        [$bulletinId] = $this->publishedReportCardForLinkedParent();
        $this->assertRendererIsNeverCalled();

        $url = URL::temporarySignedRoute(
            'parent-chatbot.report-card',
            now()->addHours(48),
            ['bulletin' => $bulletinId]
        );

        $this->assertGone($this->get($url));
    }

    public function test_unknown_report_card_answers_the_same_gone_response(): void
    {
        [, $linkId] = $this->publishedReportCardForLinkedParent();
        $this->assertRendererIsNeverCalled();

        $this->assertGone($this->get($this->signedUrl(999999, $linkId)));
    }

    /** @return array{0: int, 1: int} */
    private function publishedReportCardForLinkedParent(): array
    {
        $parentId = DB::table('esbtp_parents')->insertGetId([
            'nom' => 'Traore',
            'prenoms' => 'Fatou',
            'telephone' => '+2250700000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $studentId = $this->studentId('Traore', 'Aminata');

        DB::table('esbtp_etudiant_parent')->insert([
            'parent_id' => $parentId,
            'etudiant_id' => $studentId,
            'is_tuteur' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $linkId = DB::table('parent_chatbot_links')->insertGetId([
            'parent_id' => $parentId,
            'phone_hash' => str_repeat('a', 64),
            'selected_student_id' => $studentId,
            'status' => ParentChatbotLink::STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$this->publishedBulletinFor($studentId), $linkId];
    }

    private function studentId(string $nom, string $prenoms): int
    {
        return DB::table('esbtp_etudiants')->insertGetId([
            'nom' => $nom,
            'prenoms' => $prenoms,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function publishedBulletinFor(int $studentId): int
    {
        return DB::table('esbtp_bulletins')->insertGetId([
            'etudiant_id' => $studentId,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function fakePdfRenderer(): void
    {
        $renderer = Mockery::mock(ParentChatbotReportCardPdfRenderer::class);
        $renderer->shouldReceive('render')->once()->andReturn(self::PDF_BYTES);
        $this->app->instance(ParentChatbotReportCardPdfRenderer::class, $renderer);
    }

    private function assertRendererIsNeverCalled(): void
    {
        $renderer = Mockery::mock(ParentChatbotReportCardPdfRenderer::class);
        $renderer->shouldNotReceive('render');
        $this->app->instance(ParentChatbotReportCardPdfRenderer::class, $renderer);
    }

    private function assertGone(TestResponse $response): void
    {
        $response->assertStatus(410);
        $response->assertSee('Ce lien de bulletin n\'est plus disponible', false);
        $response->assertDontSee('bulletin.pdf', false);
    }

    private function signedUrl(int $bulletinId, int $linkId): string
    {
        return URL::temporarySignedRoute(
            'parent-chatbot.report-card',
            now()->addHours(48),
            ['bulletin' => $bulletinId, 'link' => $linkId]
        );
    }
}

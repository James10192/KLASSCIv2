<?php

namespace Tests\Feature\API;

use App\Http\Middleware\CheckInstalled;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParentChatbotReportCardControllerTest extends TestCase
{
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

    public function test_legacy_signed_published_report_card_link_returns_gone_without_rendering_bulletin_data(): void
    {
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'etudiant_id' => 42,
            'is_published' => true,
            'signature_directeur' => true,
            'signature_responsable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get($this->signedUrl($bulletinId));

        $response->assertStatus(410);
        $response->assertSee('resume publie', false);
        $cacheControl = (string) $response->headers->get('Cache-Control');

        foreach (['private', 'no-store', 'max-age=0'] as $directive) {
            $this->assertStringContainsString($directive, $cacheControl);
        }
    }

    public function test_legacy_signed_unpublished_report_card_link_stays_hidden(): void
    {
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'is_published' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get($this->signedUrl($bulletinId))->assertStatus(404);
    }

    public function test_legacy_signed_report_card_without_official_signatures_stays_hidden(): void
    {
        $bulletinId = DB::table('esbtp_bulletins')->insertGetId([
            'is_published' => true,
            'signature_directeur' => false,
            'signature_responsable' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get($this->signedUrl($bulletinId))->assertStatus(404);
    }

    private function signedUrl(int $bulletinId): string
    {
        return URL::temporarySignedRoute(
            'parent-chatbot.report-card',
            now()->addMinutes(15),
            ['bulletin' => $bulletinId]
        );
    }
}

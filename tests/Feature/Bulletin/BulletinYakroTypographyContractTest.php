<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Services\BulletinService;
use App\Services\BulletinTypography;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BulletinYakroTypographyContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->string('category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_yakro_official_template_uses_scaled_typography_from_settings(): void
    {
        SettingsHelper::setOrCreate('bulletin_style', 'yakro', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_font_size', '14', 'bulletin');

        // Le conteneur construit le service : un argument ajouté au
        // constructeur ne casse plus ce test (il en manquait un, le septième).
        $this->app->instance(ESBTPAbsenceService::class, Mockery::mock(ESBTPAbsenceService::class));
        $service = $this->app->make(BulletinService::class);

        self::assertSame('esbtp.bulletins.pdf-configurable', $service->getBulletinTemplateView());
        self::assertSame('14', $service->getPDFConfig()['bulletin_font_size']);

        $scale = BulletinTypography::scale(14);
        self::assertGreaterThanOrEqual(14, $scale['body']);
        self::assertGreaterThanOrEqual(13, $scale['table']);

        $view = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable.blade.php'))
            // Décision et signature vivent dans un partiel inclus par le gabarit.
            .file_get_contents(resource_path('views/esbtp/bulletins/partials/conseil-signature.blade.php'));
        self::assertStringContainsString('BulletinTypography::scale', $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['body'] }}px", $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['table'] }}px", $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['decision'] }}px", $view);
        self::assertStringContainsString('bulletin_header_left_font_size', $view);
        self::assertStringContainsString('bulletin_header_school_name_font_size', $view);
        self::assertStringContainsString('bulletin_header_logo_height', $view);
        // La hauteur et la largeur de signature réglées par l'école pilotent
        // la bande décision + signature (la colonne prend la largeur réglée).
        self::assertStringContainsString("height: {{ max(40, \$signatureHeight - 20) }}px", $view);
        self::assertStringContainsString("width: {{ \$signatureWidth + 8 }}px", $view);
        self::assertStringContainsString("font-size: {{ \$signatureFontSize }}px", $view);
        self::assertStringContainsString("font-size: {{ \$authenticityFontSize }}px", $view);
        self::assertStringContainsString("opacity: {{ \$authenticityOpacity }}", $view);
    }
}

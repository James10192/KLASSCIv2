<?php

namespace Tests\Feature\Bulletin;

use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinCohortResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
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

        $service = new BulletinService(
            Mockery::mock(ESBTPAbsenceService::class),
            new BtsAnnualClassMapResolver(new BtsPhaseResolver()),
            new BtsBulletinCohortResolver(new BtsAnnualClassMapResolver(new BtsPhaseResolver())),
            new BtsClassCohortCounter(new BtsPhaseResolver()),
            new ClasseOuvertureResolver()
        );

        self::assertSame('esbtp.bulletins.pdf-configurable', $service->getBulletinTemplateView());
        self::assertSame('14', $service->getPDFConfig()['bulletin_font_size']);

        $scale = BulletinTypography::scale(14);
        self::assertGreaterThanOrEqual(14, $scale['body']);
        self::assertGreaterThanOrEqual(13, $scale['table']);

        $view = file_get_contents(resource_path('views/esbtp/bulletins/pdf-configurable.blade.php'));
        self::assertStringContainsString('BulletinTypography::scale', $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['body'] }}px", $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['table'] }}px", $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['decision'] }}px", $view);
        self::assertStringContainsString("font-size: {{ \$typeScale['signature'] }}px", $view);
    }
}

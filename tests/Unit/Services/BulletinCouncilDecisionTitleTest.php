<?php

namespace Tests\Unit\Services;

use App\Domain\BtsTroncCommun\ClasseOuvertureResolver;
use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinCohortResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BulletinCouncilDecisionTitleTest extends TestCase
{
    private BulletinService $service;

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

        $this->service = new BulletinService(
            Mockery::mock(ESBTPAbsenceService::class),
            new BtsAnnualClassMapResolver(new BtsPhaseResolver()),
            new BtsBulletinCohortResolver(new BtsAnnualClassMapResolver(new BtsPhaseResolver())),
            new BtsClassCohortCounter(new BtsPhaseResolver()),
            new ClasseOuvertureResolver()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_bts1_semester1_title_comes_from_setting_even_when_style_is_yakro(): void
    {
        SettingsHelper::setOrCreate('bulletin_style', 'yakro', 'bulletin');
        SettingsHelper::setOrCreate(
            'bulletin_bts1_s1_council_title',
            'Appréciation du Conseil de Classe',
            'bulletin'
        );

        $classe = new ESBTPClasse(['systeme_academique' => 'BTS']);
        $classe->setRelation('niveau', new ESBTPNiveauEtude(['year' => 1]));

        self::assertSame(
            'Appréciation du Conseil de Classe',
            $this->service->councilDecisionTitle($classe, 'semestre1')
        );
        self::assertSame(
            'Décision du conseil de classe',
            $this->service->councilDecisionTitle($classe, 'semestre2')
        );
    }
}

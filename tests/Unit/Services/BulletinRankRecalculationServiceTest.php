<?php

namespace Tests\Unit\Services;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsBulletinCohortResolver;
use App\Domain\BtsTroncCommun\BtsClassCohortCounter;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Services\BulletinService;
use App\Services\ESBTP\BulletinRankRecalculationService;
use App\Services\ESBTP\ESBTPAbsenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class BulletinRankRecalculationServiceTest extends TestCase
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
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('audits', function (Blueprint $table): void {
            $table->id();
            $table->string('event')->nullable();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('esbtp_filieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_inscription_phases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inscription_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->string('systeme_academique')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('prenoms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->unsignedBigInteger('inscription_origine_id')->nullable();
            $table->string('status')->default('active');
            $table->string('workflow_step')->default('etudiant_cree');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('periode')->nullable();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->float('note_assiduite')->nullable();
            $table->unsignedInteger('rang')->nullable();
            $table->unsignedInteger('effectif_classe')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_dry_run_does_not_write_ranks(): void
    {
        [$anneeId, $classeId, $first, $second, $third] = $this->seedClass();
        $service = $this->makeService();

        $payload = $service->recalculate(false, $anneeId, $classeId, 'semestre2');

        $this->assertSame('DRY-RUN (aucune ecriture)', $payload['mode']);
        $this->assertSame(3, $payload['bulletins_lus']);
        $this->assertSame(3, $payload['rang_1_avant']);
        $this->assertSame(1, $payload['rang_1_apres']);
        $this->assertSame(1, (int) $first->fresh()->rang);
        $this->assertSame(1, (int) $second->fresh()->rang);
        $this->assertSame(1, (int) $third->fresh()->rang);
        $this->assertEqualsCanonicalizing([1, 2, 3], array_column($payload['echantillons'], 'rang_propose'));
    }

    public function test_apply_recalculates_class_ranks(): void
    {
        [$anneeId, $classeId, $first, $second, $third] = $this->seedClass();
        $service = $this->makeService();

        $payload = $service->recalculate(true, $anneeId, $classeId, 'semestre2');

        $this->assertSame('APPLIQUE', $payload['mode']);
        $this->assertSame(1, (int) $first->fresh()->rang);
        $this->assertSame(2, (int) $second->fresh()->rang);
        $this->assertSame(3, (int) $third->fresh()->rang);
    }

    private function makeService(): BulletinRankRecalculationService
    {
        $phaseResolver = new BtsPhaseResolver();
        $bulletinService = new BulletinService(
            Mockery::mock(ESBTPAbsenceService::class),
            new BtsAnnualClassMapResolver($phaseResolver),
            new BtsBulletinCohortResolver(new BtsAnnualClassMapResolver($phaseResolver)),
            new BtsClassCohortCounter($phaseResolver)
        );

        return new BulletinRankRecalculationService($bulletinService);
    }

    /**
     * @return array{0:int,1:int,2:ESBTPBulletin,3:ESBTPBulletin,4:ESBTPBulletin}
     */
    private function seedClass(): array
    {
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'is_current' => true,
            'is_active' => true,
        ]);
        $classe = ESBTPClasse::create([
            'name' => 'BTS1 TP B',
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);

        $first = $this->makeBulletin($classe->id, $annee->id, 16.00, 0.13);
        $second = $this->makeBulletin($classe->id, $annee->id, 12.00, 0.00);
        $third = $this->makeBulletin($classe->id, $annee->id, 7.24, 0.00);

        return [(int) $annee->id, (int) $classe->id, $first, $second, $third];
    }

    private function makeBulletin(int $classeId, int $anneeId, float $moyenne, float $assiduite): ESBTPBulletin
    {
        $etudiant = ESBTPEtudiant::create([
            'nom' => 'Test',
            'prenoms' => (string) $moyenne,
        ]);
        ESBTPInscription::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return ESBTPBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'periode' => 'semestre2',
            'moyenne_generale' => $moyenne,
            'note_assiduite' => $assiduite,
            'rang' => 1,
            'effectif_classe' => 1,
        ]);
    }
}

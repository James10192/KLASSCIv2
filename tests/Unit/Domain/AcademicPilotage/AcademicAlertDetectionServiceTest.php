<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicAlertDetectionService;
use App\Domain\AcademicPilotage\Services\AcademicAlertEngineService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Domain\AcademicPilotage\Services\ClassAcademicHealthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;

class AcademicAlertDetectionServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_period_is_normalized_before_alert_fingerprinting(): void
    {
        $service = new AcademicAlertDetectionService(
            app(ClassAcademicHealthService::class),
            new AcademicAlertEngineService,
            new AcademicPeriodNormalizer,
        );
        $method = new ReflectionMethod($service, 'normalizePeriodForAlerts');
        $method->setAccessible(true);

        $this->assertSame('semestre1', $method->invoke($service, 'S1'));
        $this->assertSame('semestre1', $method->invoke($service, '1'));
    }

    public function test_refresh_alert_command_processes_every_chunk(): void
    {
        $this->createCommandSourceSchema();
        DB::table('esbtp_annee_universitaires')->insert([
            'id' => 20,
            'annee_debut' => 2025,
            'is_active' => true,
        ]);
        foreach ([10, 11, 12] as $classId) {
            DB::table('esbtp_classes')->insert([
                'id' => $classId,
                'systeme_academique' => $classId === 10 ? null : 'BTS',
                'annee_universitaire_id' => 20,
                'is_active' => true,
            ]);
        }

        $this->artisan('academic-pilotage:refresh-alerts --chunk-size=1 --period=S1')
            ->expectsOutputToContain('classes=3')
            ->assertExitCode(0);
    }

    private function createCommandSourceSchema(): void
    {
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('annee_debut');
            $table->boolean('is_active')->default(false);
            $table->softDeletes();
        });

        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('systeme_academique')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }
}

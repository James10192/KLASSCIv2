<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDSession;
use App\Services\ExamenSchedulingService;
use App\Services\RattrapageSchedulingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Mockery;
use Tests\TestCase;

class RattrapageSchedulingServiceScopeTest extends TestCase
{
    private RattrapageSchedulingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('audit.enabled', false);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Cache::flush();

        $this->createSchema();
        $this->seedSettings();

        $scheduler = Mockery::mock(ExamenSchedulingService::class);
        $scheduler->shouldReceive('genererNumeroConvocation')
            ->andReturnUsing(fn (ESBTPExamenPlanifie $exam): string => 'CONV-TEST-'.$exam->id);

        $this->service = new RattrapageSchedulingService($scheduler);
    }

    public function test_identifier_eligibles_is_limited_to_session_scope_and_active_enrollments(): void
    {
        $session = $this->normalSession();
        $scoped = $this->bulletin(101, 10, 20, 30, 1);
        $otherSemester = $this->bulletin(102, 10, 20, 30, 2);
        $inactive = $this->bulletin(103, 10, 20, 30, 1);
        $otherParcours = $this->bulletin(104, 10, 20, 31, 1);

        $this->activeEnrollment(101, 10, 20);
        $this->activeEnrollment(102, 10, 20);
        $this->inactiveEnrollment(103, 10, 20);
        $this->activeEnrollment(104, 10, 20);

        $eligible = $this->resultat($scoped, 101, 501, 9.5);
        $this->resultat($otherSemester, 102, 501, 8.0);
        $this->resultat($inactive, 103, 501, 7.0);
        $this->resultat($otherParcours, 104, 501, 6.0);

        $result = $this->service->identifierEtudiantsEligibles($session);

        $this->assertCount(1, $result);
        $this->assertSame($eligible->id, $result->first()->id);
        $this->assertSame('9.50', $eligible->fresh()->note_session_normale);
        $this->assertTrue($eligible->fresh()->rattrapage_eligible);
    }

    public function test_generer_examens_is_scoped_and_idempotent(): void
    {
        $parent = $this->normalSession();
        $rattrapage = $this->rattrapageSession($parent);
        $scoped = $this->bulletin(101, 10, 20, 30, 1);
        $sameEcueSameClass = $this->bulletin(102, 10, 20, 30, 1);
        $otherClass = $this->bulletin(103, 11, 20, 30, 1);

        $this->activeEnrollment(101, 10, 20);
        $this->activeEnrollment(102, 10, 20);
        $this->activeEnrollment(103, 11, 20);
        $this->matiere(501, 'Fiscalite');

        $this->resultat($scoped, 101, 501, 8.0);
        $this->resultat($sameEcueSameClass, 102, 501, 9.0);
        $this->resultat($otherClass, 103, 501, 7.0);

        $first = $this->service->genererExamensRattrapage($rattrapage);
        $second = $this->service->genererExamensRattrapage($rattrapage);

        $this->assertCount(2, $first);
        $this->assertCount(0, $second);
        $this->assertSame([10, 11], ESBTPExamenPlanifie::query()->orderBy('classe_id')->pluck('classe_id')->all());
    }

    public function test_recalculer_preserves_moyenne_and_updates_only_note_finale(): void
    {
        $parent = $this->normalSession();
        $rattrapage = $this->rattrapageSession($parent);
        $bulletin = $this->bulletin(101, 10, 20, 30, 1);
        $this->activeEnrollment(101, 10, 20);

        $resultat = $this->resultat($bulletin, 101, 501, 8.0, [
            'note_session_normale' => 8.0,
            'note_rattrapage' => 12.0,
            'rattrapage_eligible' => true,
            'rattrapage_inscrit' => true,
        ]);

        $updated = $this->service->recalculerMoyennesAvecRattrapage(101, $rattrapage);

        $fresh = $resultat->fresh();
        $this->assertSame(1, $updated);
        $this->assertSame('8.00', $fresh->moyenne);
        $this->assertSame('12.00', $fresh->note_finale);
    }

    public function test_mutations_are_refused_after_publication(): void
    {
        $parent = $this->normalSession(['status' => 'completed']);
        $rattrapage = $this->rattrapageSession($parent);
        $bulletin = $this->bulletin(101, 10, 20, 30, 1, ['is_published' => true]);
        $this->activeEnrollment(101, 10, 20);
        $this->resultat($bulletin, 101, 501, 8.0, ['rattrapage_eligible' => true]);

        $this->expectException(LogicException::class);

        $this->service->inscrireEtudiantsEligibles($rattrapage, [101]);
    }

    public function test_identifier_eligibles_refuses_published_session_before_mutation(): void
    {
        $parent = $this->normalSession(['status' => 'published']);
        $bulletin = $this->bulletin(101, 10, 20, 30, 1);
        $this->activeEnrollment(101, 10, 20);
        $resultat = $this->resultat($bulletin, 101, 501, 8.0);

        $this->expectException(LogicException::class);

        try {
            $this->service->identifierEtudiantsEligibles($parent);
        } finally {
            $fresh = $resultat->fresh();
            $this->assertNull($fresh->note_session_normale);
            $this->assertFalse($fresh->rattrapage_eligible);
        }
    }

    public function test_session_scope_is_required(): void
    {
        $session = $this->normalSession(['parcours_id' => null]);

        $this->expectException(\DomainException::class);

        $this->service->identifierEtudiantsEligibles($session);
    }

    private function createSchema(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('esbtp_lmd_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->string('type', 16)->default('normale');
            $table->unsignedBigInteger('parent_session_id')->nullable();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->string('libelle');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('status', 24)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedTinyInteger('semestre');
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->unsignedInteger('credits_capitalises')->nullable();
            $table->unsignedInteger('credits_totaux')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('resultat_ue_id')->nullable();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->unsignedInteger('credit')->default(0);
            $table->decimal('note_session_normale', 5, 2)->nullable();
            $table->decimal('note_rattrapage', 5, 2)->nullable();
            $table->decimal('note_finale', 5, 2)->nullable();
            $table->boolean('rattrapage_eligible')->default(false);
            $table->boolean('rattrapage_inscrit')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('etudiant_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->string('status');
            $table->string('workflow_step');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_examens_planifies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('type_examen', 32)->default('EXAMEN');
            $table->string('titre');
            $table->string('numero_convocation', 64)->nullable()->unique();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->unsignedSmallInteger('duree_minutes')->nullable();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->decimal('bareme', 5, 2)->default(20);
            $table->string('status', 32)->default('planned');
            $table->boolean('notes_locked')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('esbtp_lmd_jurys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('annee_universitaire_id');
            $table->unsignedBigInteger('parcours_id')->nullable();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->string('libelle');
            $table->string('status', 32)->default('preparation');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function seedSettings(): void
    {
        DB::table('settings')->insert([
            ['key' => 'lmd_seuil_validation_ecue', 'value' => '10', 'type' => 'float', 'group' => 'lmd', 'is_active' => true],
            ['key' => 'lmd_rattrapage_replace', 'value' => '0', 'type' => 'boolean', 'group' => 'lmd', 'is_active' => true],
        ]);
    }

    private function normalSession(array $attributes = []): ESBTPLMDSession
    {
        return ESBTPLMDSession::query()->create(array_merge([
            'annee_universitaire_id' => 20,
            'parcours_id' => 30,
            'type' => 'normale',
            'semestre' => 1,
            'libelle' => 'Session normale',
            'status' => 'completed',
        ], $attributes));
    }

    private function rattrapageSession(ESBTPLMDSession $parent): ESBTPLMDSession
    {
        return ESBTPLMDSession::query()->create([
            'annee_universitaire_id' => $parent->annee_universitaire_id,
            'parcours_id' => $parent->parcours_id,
            'type' => 'rattrapage',
            'parent_session_id' => $parent->id,
            'semestre' => $parent->semestre,
            'libelle' => 'Rattrapage',
            'status' => 'planned',
        ]);
    }

    private function bulletin(
        int $studentId,
        int $classId,
        int $yearId,
        int $parcoursId,
        int $semester,
        array $attributes = []
    ): ESBTPLMDBulletin {
        return ESBTPLMDBulletin::query()->create(array_merge([
            'etudiant_id' => $studentId,
            'classe_id' => $classId,
            'annee_universitaire_id' => $yearId,
            'parcours_id' => $parcoursId,
            'semestre' => $semester,
            'is_published' => false,
        ], $attributes));
    }

    private function resultat(
        ESBTPLMDBulletin $bulletin,
        int $studentId,
        int $matiereId,
        float $moyenne,
        array $attributes = []
    ): ESBTPLMDResultatECUE {
        return ESBTPLMDResultatECUE::query()->create(array_merge([
            'bulletin_id' => $bulletin->id,
            'matiere_id' => $matiereId,
            'etudiant_id' => $studentId,
            'moyenne' => $moyenne,
        ], $attributes));
    }

    private function activeEnrollment(int $studentId, int $classId, int $yearId): void
    {
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => $classId,
            'annee_universitaire_id' => $yearId,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function inactiveEnrollment(int $studentId, int $classId, int $yearId): void
    {
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => $studentId,
            'classe_id' => $classId,
            'annee_universitaire_id' => $yearId,
            'status' => 'inactive',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function matiere(int $id, string $name): void
    {
        DB::table('esbtp_matieres')->insert(['id' => $id, 'name' => $name]);
    }
}

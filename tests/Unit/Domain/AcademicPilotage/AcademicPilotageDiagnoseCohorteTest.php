<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le bloc `cohorte` du diagnostic doit compter comme le lecteur d'instantanés :
 * soft-deletes respectés, ligne propre prime sur ligne sale, année courante explicite.
 */
class AcademicPilotageDiagnoseCohorteTest extends AcademicPilotageDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('esbtp_annee_universitaires')) {
            Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
                $table->id();
                $table->string('nom')->nullable();
                $table->unsignedSmallInteger('annee_debut')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('esbtp_classes')) {
            Schema::create('esbtp_classes', function (Blueprint $table): void {
                $table->id();
                $table->string('systeme_academique')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('annee_universitaire_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        DB::table('esbtp_annee_universitaires')->insert([
            'id' => 1, 'nom' => '2026-2027', 'annee_debut' => 2026, 'is_active' => true, 'is_current' => true,
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'systeme_academique' => 'BTS', 'is_active' => true],
            ['id' => 11, 'systeme_academique' => 'BTS', 'is_active' => true],
            ['id' => 12, 'systeme_academique' => 'BTS', 'is_active' => true, 'deleted_at' => now()],
        ]);
    }

    public function test_cohorte_exclut_inscriptions_supprimees_et_classes_supprimees(): void
    {
        $this->inscription(1, 10, 'active');
        $this->inscription(2, 10, 'active', deletedAt: now());
        $this->inscription(3, 12, 'active');

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(1, $cohorte['couples_cohorte']);
        $this->assertSame(1, $cohorte['sans_ligne']);
    }

    public function test_une_ligne_propre_prime_sur_une_ligne_sale_pour_le_meme_couple(): void
    {
        $this->inscription(1, 10, 'active');
        $this->snapshot(1, 10, isDirty: true, hash: 'sale');
        $this->snapshot(1, 10, isDirty: false, hash: 'propre');

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(1, $cohorte['exploitable']);
        $this->assertSame(0, $cohorte['ligne_sale']);
        $this->assertSame(0, $cohorte['sans_ligne']);
    }

    public function test_seule_une_ligne_sale_est_comptee_sale(): void
    {
        $this->inscription(1, 10, 'active');
        $this->snapshot(1, 10, isDirty: true, hash: 'sale');

        $cohorte = $this->diagnoseCohorte();

        $this->assertSame(0, $cohorte['exploitable']);
        $this->assertSame(1, $cohorte['ligne_sale']);
        $this->assertSame(1, $cohorte['sans_instantane_exploitable']);
    }

    public function test_sans_annee_courante_le_diagnostic_le_dit_au_lieu_d_afficher_zero(): void
    {
        DB::table('esbtp_annee_universitaires')->update(['is_active' => false, 'is_current' => false]);

        $cohorte = $this->diagnoseCohorte();

        $this->assertNull($cohorte['annee_universitaire_id']);
        $this->assertNull($cohorte['sans_instantane_exploitable']);
        $this->assertStringContainsString('aucune année', $cohorte['motif']);
    }

    private function inscription(int $etudiantId, int $classeId, string $workflow, $deletedAt = null): void
    {
        DB::table('esbtp_inscriptions')->insert([
            'etudiant_id' => $etudiantId,
            'classe_id' => $classeId,
            'annee_universitaire_id' => 1,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deletedAt,
        ]);
    }

    private function snapshot(int $etudiantId, int $classeId, bool $isDirty, string $hash): void
    {
        DB::table('esbtp_academic_metric_snapshots')->insert([
            'context_hash' => $hash.'-'.$etudiantId.'-'.$classeId,
            'scope_type' => 'student',
            'scope_id' => $etudiantId,
            'academic_system' => 'BTS',
            'annee_universitaire_id' => 1,
            'semester' => 'semestre1',
            'classe_id' => $classeId,
            'etudiant_id' => $etudiantId,
            'coverage_pct' => 0,
            'confidence_pct' => 0,
            'level' => 'insufficient_data',
            'metrics' => '{}',
            'evidence_hash' => $hash,
            'engine_version' => '1.0.0',
            'is_dirty' => $isDirty,
            'calculated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function diagnoseCohorte(): array
    {
        Artisan::call('academic-pilotage:diagnose', ['--json' => true]);
        $diagnosis = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        return $diagnosis['cohorte'];
    }
}
